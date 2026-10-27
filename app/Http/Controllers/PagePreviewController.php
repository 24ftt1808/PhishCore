<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\AnalysisEngine;
use App\Services\PagePreview;
use App\Support\PageNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            return response()->json(['status' => 'ready', 'url' => $report->previewPicture(), 'note' => $report->preview_note]);
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
            $note = $this->readLoadedPage($report, $preview, (string) $job['uuid'], $checked['page_host'] ?? null);
            $report->forceFill([$report->previewColumn() => $address, 'preview_note' => $note])->save();

            return response()->json(['status' => 'ready', 'url' => $address, 'note' => $note]);
        }

        if ($checked['status'] === 'pending' && now()->getTimestamp() - (int) $job['started'] < self::MAX_WAIT_SECONDS) {
            return response()->json(['status' => 'pending']);
        }

        Cache::forget($jobKey);

        return $this->failure('unavailable');
    }

    /**
     * Reads the page as it looked once it had loaded in the sandbox and writes the short note shown under the picture.
     * Display only: the score, the verdict and the saved checks are never touched. Any problem means "no note",
     * so the picture itself is never held back by this.
     */
    private function readLoadedPage(Report $report, PagePreview $preview, string $uuid, ?string $finalHost): ?array
    {
        try {
            $html = $preview->pageHtml($uuid);
            $linkHost = strtolower((string) parse_url((string) $report->previewLink(), PHP_URL_HOST));

            if ($html === null || $linkHost === '') {
                return null;
            }

            // The loaded page may be on another site than the link (a redirect): judge it against where it really is.
            $moved = $finalHost !== null && $finalHost !== $linkHost && $finalHost !== 'www.'.$linkHost && 'www.'.$finalHost !== $linkHost;
            $result = app(AnalysisEngine::class)->inspectPageHtml($html, $finalHost ?? $linkHost, null, true);

            return PageNote::build($result, $this->earlierReading($report), $moved ? $finalHost : null, $this->scanFoundPageConcern($report));
        } catch (Throwable $e) {
            Log::warning('Page preview: the loaded page could not be read', ['reason' => $e::class]);

            return null;
        }
    }

    /** True when the scan's own page checks (page content, AI page review) raised a concern about this page. */
    private function scanFoundPageConcern(Report $report): bool
    {
        $flags = (array) ($report->analyses()->latest('id')->first()?->flags ?? []);

        foreach ($flags as $check) {
            if (is_array($check)
                && in_array($check['name'] ?? null, ['Page Content Analysis', 'AI Page Review'], true)
                && (int) ($check['points'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /** How the scan itself related to this page, so the note can say when it is the first look at it. */
    private function earlierReading(Report $report): ?string
    {
        if ($report->type !== 'url') {
            return 'unopened';
        }

        $flags = (array) ($report->analyses()->latest('id')->first()?->flags ?? []);

        foreach ($flags as $check) {
            if (($check['name'] ?? null) === 'Page Content Analysis') {
                return in_array($check['status'] ?? '', ['INFO', 'UNKNOWN'], true) ? 'unread' : null;
            }
        }

        return null;
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