<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** How long a notification is kept before it is removed. */
    public const KEEP_DAYS = 30;

    /** The bell's data: the unread count and the latest notifications of the signed-in user. */
    public function index(Request $request): JsonResponse
    {
        // Notifications are kept for 30 days, then removed here (no scheduled job needed).
        $request->user()->notifications()->where('created_at', '<', now()->subDays(self::KEEP_DAYS))->delete();

        return response()->json(self::summary($request->user()));
    }

    /** Open one notification: mark it read, then go to the scan it is about. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $reportId = $notification->data['report_id'] ?? null;

        if ($reportId && Report::whereKey($reportId)->exists()) {
            return redirect()->route('scan.show', $reportId);
        }

        return redirect()->route('dashboard');
    }

    /** Delete one of your own notifications. */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->delete();

        return response()->json(self::summary($request->user()));
    }

    /** Delete all of your own notifications. */
    public function clear(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(self::summary($request->user()));
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(self::summary($request->user()))
            : back();
    }

    /**
     * @return array{unread: int, items: array<int, array<string, mixed>>}
     */
    public static function summary($user): array
    {
        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(15)->get()->map(fn ($n) => [
                'id' => $n->id,
                'message' => (string) ($n->data['message'] ?? 'Investigation update'),
                'kind' => (string) ($n->data['kind'] ?? ''),
                'unread' => $n->read_at === null,
                'when' => $n->created_at?->diffForHumans(),
                'url' => route('notifications.open', $n->id),
            ])->all(),
        ];
    }
}