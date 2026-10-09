<?php

namespace App\Http\Controllers;

use App\Models\PlayProgress;
use App\Services\PlayContent;
use App\Services\PlayProgressSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * The "Phish Lab" tab: small games that train people to spot scams.
 * Progress is kept in the browser and also saved per user, so it follows them to another device.
 */
class PlayController extends Controller
{
    public function __invoke(): View
    {
        return view('play.index', [
            'messages' => PlayContent::messages(),
            'stories' => PlayContent::stories(),
            // The browser copy is keyed per user so two people sharing a browser never see each other's.
            'scope' => substr(hash('sha256', 'play|'.auth()->id()), 0, 16),
            // The saved copy, or null for someone who has never saved. An empty list must reach the page as an object.
            'saved' => ($row = PlayProgress::where('user_id', auth()->id())->first()) ? (object) $row->data : null,
        ]);
    }

    /** Saves progress sent by the page, merged with what is already stored so a score can never go down. */
    public function store(Request $request): JsonResponse
    {
        // Answered as JSON here, because the app only renders JSON errors for /api routes and validate() would redirect.
        if (Validator::make($request->all(), ['data' => ['required', 'array', 'max:60']])->fails()) {
            return response()->json(['ok' => false], 422);
        }

        $row = PlayProgress::firstOrNew(['user_id' => $request->user()->id]);
        $merged = PlayProgressSync::merge($row->data ?? [], $request->input('data'));

        $row->xp = (int) ($merged['xp'] ?? 0);
        $row->data = $merged;
        $row->save();

        return response()->json(['ok' => true]);
    }
}