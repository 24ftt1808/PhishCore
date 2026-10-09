<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ChatAdvisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * Receives a chat turn from the floating widget (signed-in users only).
 */
class ChatController extends Controller
{
    /** Messages one user may send per day, so the chat cannot use up the whole free allowance. */
    private const DAILY_LIMIT = 100;

    public function __invoke(Request $request, ChatAdvisor $advisor): JsonResponse
    {
        // Validated by hand so a bad request always gets a JSON answer. The app only renders
        // JSON errors for /api routes, so $request->validate() would redirect this one instead.
        $validator = Validator::make($request->all(), [
            'messages' => ['required', 'array', 'min:1', 'max:'.ChatAdvisor::MAX_MESSAGES],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:'.ChatAdvisor::MAX_MESSAGE_CHARS],
            'report_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'reply' => 'That message could not be sent. Please shorten it and try again.'], 422);
        }

        $data = $validator->validated();

        if (! $advisor->enabled()) {
            return response()->json(['ok' => false, 'reply' => 'The adviser is switched off right now.'], 503);
        }

        $report = null;
        if (! empty($data['report_id'])) {
            $report = Report::find($data['report_id']);

            if (! $report || ! $report->canBeViewedBy($request->user())) {
                return response()->json(['ok' => false, 'reply' => 'That scan could not be found.'], 404);
            }
        }

        $key = 'chat-daily:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::DAILY_LIMIT)) {
            return response()->json(['ok' => false, 'reply' => 'You have reached today\'s chat limit. Please come back tomorrow.'], 429);
        }

        RateLimiter::hit($key, 86400);

        $result = $advisor->reply($data['messages'], $report, $data['page'] ?? null);

        return response()->json($result, $result['ok'] ? 200 : 503);
    }
}