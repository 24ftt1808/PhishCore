<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\PagePreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The result page calls this every few seconds after "Show page preview" is clicked.
 * Each call either starts the urlscan.io scan, or checks whether it has finished.
 * Expected outcomes are plain JSON with a "status" (pending, ready, busy, limit, invalid, unavailable).
 */
class PagePreviewController extends Controller
{
    /** Give up waiting for urlscan.io after this many seconds. */
    private const MAX_WAIT_SECONDS = 90;

    public function __invoke(Request $request, Report $report, PagePreview $preview): JsonResponse
    {
        abort_unless($preview->enabled(), 404);
        abort_unless($report->canBeViewedBy($request->user()), 404);
        abort_unless($report->status === 'completed' && $report->previewLink() !== null, 404);

        if ($preview->exists($report->previewPicture())) {
            return response()->json(['status' => 'ready', 'url' => $report->previewPicture()]);
        }

        $lock = Cache::lock('page-preview:report:'.$report->id, 40);

        if (! $lock->get()) {
            return response()->json(['status' => 'pending']);
        }

        try {
            return $this->advance($report, $preview);
        } finally {
            $lock->release();
        }
    }

    private function advance(Report $report, PagePreview $preview): JsonResponse
    {
        $jobKey = 'page-preview:job:'.$report->id;
        $job = Cache::get($jobKey);

        if (! is_array($job) || ! isset($job['uuid'], $job['started'])) {
            $started = $preview->start((string) $report->previewLink());

            if ($started['status'] !== 'started') {
                return $this->failure($started['status']);
            }

            Cache::put($jobKey, ['uuid' => $started['uuid'], 'started' => now()->getTimestamp()], 600);

            return response()->json(['status' => 'pending']);
        }

        $checked = $preview->check((string) $job['uuid']);

        if ($checked['status'] === 'ready') {
            Cache::forget($jobKey);
            $address = $preview->publicUrl($checked['path']);
            $report->forceFill([$report->previewColumn() => $address])->save();

            return response()->json(['status' => 'ready', 'url' => $address]);
        }

        if ($checked['status'] === 'pending' && now()->getTimestamp() - (int) $job['started'] < self::MAX_WAIT_SECONDS) {
            return response()->json(['status' => 'pending']);
        }

        Cache::forget($jobKey);

        return $this->failure('unavailable');
    }

    private function failure(string $status): JsonResponse
    {
        $message = match ($status) {
            'limit' => 'Page previews are paused for today. Please try again tomorrow.',
            'invalid' => 'A preview cannot be taken for this link.',
            default => 'A preview could not be taken for this page. It may be offline, or it may block automated visits.',
        };

        return response()->json(['status' => $status, 'message' => $message]);
    }
}
