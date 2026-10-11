<?php

namespace App\Support;

use App\Models\Analysis;
use App\Models\Report;

/**
 * Hands back a recent result for a link that was already scanned, instead of scanning it again.
 *
 * Scam links are scanned over and over, and every fresh scan uses the free quotas of outside
 * services. A finished, complete scan from the last few hours (config scan.reuse_hours) is copied
 * into a new report for the person asking, so their history and access stay their own.
 */
class ScanReuse
{
    /**
     * The recent finished scan of exactly this link, or null when a fresh scan is needed.
     * A scan where some check could not run is not reused, because its result is incomplete.
     */
    public static function find(string $url): ?Report
    {
        $hours = (int) config('scan.reuse_hours');
        $url = trim($url);

        if ($hours <= 0 || $url === '') {
            return null;
        }

        $candidates = Report::query()
            ->where('type', 'url')
            ->where('url', $url)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subHours($hours))
            ->whereHas('analyses')
            ->with(['analyses', 'ctiLookups'])
            ->latest()
            ->limit(5)
            ->get();

        return $candidates->first(function (Report $report): bool {
            $flags = $report->analyses->first()?->flags;

            return is_array($flags)
                && $flags !== []
                && ! collect($flags)->contains(fn ($check) => is_array($check) && ($check['status'] ?? null) === 'UNKNOWN');
        });
    }

    /** Copy the earlier result into a new finished report owned by this person. */
    public static function copyFor(Report $previous, ?int $userId): Report
    {
        $report = Report::create([
            'user_id' => $userId,
            'type' => 'url',
            'url' => $previous->url,
            'status' => 'completed',
        ]);

        $analysis = $previous->analyses->first();

        if ($analysis) {
            $copy = $analysis->replicate();
            $copy->report_id = $report->id;
            // No new team alert: the earlier scan of this link already raised one.
            Analysis::withoutEvents(fn () => $copy->save());
        }

        foreach ($previous->ctiLookups as $lookup) {
            $copy = $lookup->replicate();
            $copy->report_id = $report->id;
            $copy->save();
        }

        return $report;
    }
}
