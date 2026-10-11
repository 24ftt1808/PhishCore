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
     * Checks that depend on whether our server can reach the site itself. If the site blocks us or is
     * already taken down, they come back UNKNOWN every time, so a retry would not change anything and
     * the result is still worth reusing. Any other UNKNOWN check (VirusTotal, Safe Browsing, WHOIS, the
     * AI review and so on) means an outside service failed, so the result is incomplete and is not reused.
     */
    private const SITE_REACH_CHECKS = ['SSL Certificate', 'Page Content Analysis', 'Site Availability', 'Redirect Chain'];

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
            ->whereIn('url', self::variants($url))
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
                && ! collect($flags)->contains(
                    fn ($check) => is_array($check)
                        && ($check['status'] ?? null) === 'UNKNOWN'
                        && ! in_array($check['name'] ?? '', self::SITE_REACH_CHECKS, true)
                );
        });
    }

    /**
     * The ways the same link might have been typed: with or without a trailing slash, and with the
     * scheme and host in capitals. Anything after the host (the path) is kept exactly as typed.
     *
     * @return array<int, string>
     */
    private static function variants(string $url): array
    {
        $url = trim($url);
        $lowerHost = fn (string $u): string => (string) preg_replace_callback(
            '~^([a-z][a-z0-9+.-]*://)([^/?#]+)~i',
            fn (array $m): string => strtolower($m[1].$m[2]),
            $u
        );

        $variants = [];

        foreach ([$url, $lowerHost($url)] as $candidate) {
            $trimmed = rtrim($candidate, '/');
            array_push($variants, $candidate, $trimmed, $trimmed.'/');
        }

        return array_values(array_unique($variants));
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
