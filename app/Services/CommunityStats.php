<?php

namespace App\Services;

use App\Models\NumberReport;
use App\Models\Report;
use Illuminate\Support\Facades\Cache;

/**
 * Platform-wide numbers for the "Community" section of the Analytics page, shown to every
 * signed-in user. It only ever produces counts and the Top 10 list: never who scanned or
 * reported anything, and never an individual scan. Cached for a few minutes so a busy page
 * does not run these queries for every visitor.
 */
class CommunityStats
{
    public const CACHE_KEY = 'community-stats';
    public const CACHE_MINUTES = 5;

    /** A link or number needs at least this many different people before it is listed. */
    public const MIN_PEOPLE = 2;

    /** @return array{totals: array<string, int>, days: array<int, array<string, mixed>>, top: array<int, array<string, mixed>>} */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), fn () => self::build());
    }

    /**
     * Throw the saved numbers away so the next page load recalculates them. Called whenever a scan,
     * its result or a number report is saved or removed, so the section is never stale for long.
     */
    public static function forget(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{totals: array<string, int>, days: array<int, array<string, mixed>>, top: array<int, array<string, mixed>>} */
    public static function build(): array
    {
        $reports = Report::query()
            ->where('status', 'completed')
            ->whereHas('analyses', fn ($q) => $q->whereIn('verdict', ['clean', 'suspicious', 'phishing']))
            ->with('analyses:id,report_id,verdict,flags')
            ->get(['id', 'user_id', 'type', 'url', 'created_at']);

        $verdictOf = fn (Report $r): string => (string) $r->analyses->first()?->verdict;

        $totals = [
            'total' => $reports->count(),
            'clean' => $reports->filter(fn ($r) => $verdictOf($r) === 'clean')->count(),
            'suspicious' => $reports->filter(fn ($r) => $verdictOf($r) === 'suspicious')->count(),
            'phishing' => $reports->filter(fn ($r) => $verdictOf($r) === 'phishing')->count(),
        ];

        $byDate = $reports->groupBy(fn ($r) => $r->created_at->format('Y-m-d'));
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $rows = $byDate->get($date->format('Y-m-d'), collect());
            $days[] = [
                'label' => $date->format('D j'),
                'clean' => $rows->filter(fn ($r) => $verdictOf($r) === 'clean')->count(),
                'suspicious' => $rows->filter(fn ($r) => $verdictOf($r) === 'suspicious')->count(),
                'phishing' => $rows->filter(fn ($r) => $verdictOf($r) === 'phishing')->count(),
            ];
        }

        $top = collect(self::topLinks($reports, $verdictOf))
            ->merge(self::topNumbers())
            ->sortByDesc(fn ($row) => $row['count'] * 10_000_000_000 + $row['last'])
            ->take(10)
            ->values()
            ->map(function ($row) {
                unset($row['last']);

                return $row;
            })
            ->all();

        return ['totals' => $totals, 'days' => $days, 'top' => $top];
    }

    /**
     * Websites that two or more different people scanned and that were rated phishing, in the last
     * 90 days. Signed-in users count once each; every guest scan counts as one person.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function topLinks($reports, callable $verdictOf): array
    {
        $since = now()->subDays(90);

        return $reports
            ->filter(fn (Report $r) => $r->type === 'url' && $r->url && $verdictOf($r) === 'phishing' && $r->created_at >= $since)
            ->groupBy(fn (Report $r) => BruneiScamAlert::hostOf($r->url))
            ->reject(fn ($group, $host) => $host === '')
            ->map(function ($group, $host) {
                $people = $group->pluck('user_id')->filter()->unique()->count() + $group->whereNull('user_id')->count();
                $brand = null;

                foreach ($group as $report) {
                    $brand = BruneiScamAlert::brandFor('phishing', $report->analyses->first()?->flags);
                    if ($brand !== null) {
                        break;
                    }
                }

                return [
                    'kind' => 'link',
                    'item' => (string) $host,
                    'type' => $brand !== null ? 'Imitates '.$brand : 'Phishing link',
                    'count' => $people,
                    'verb' => 'scanned',
                    'last' => $group->max('created_at')->timestamp,
                ];
            })
            ->filter(fn ($row) => $row['count'] >= self::MIN_PEOPLE)
            ->values()
            ->all();
    }

    /**
     * Phone numbers that two or more different people reported as scams in the last year.
     * Same rule as the public reports page.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function topNumbers(): array
    {
        return NumberReport::query()
            ->where('created_at', '>=', now()->subDays(365))
            ->get()
            ->groupBy('phone')
            ->map(function ($rows, $phone) {
                $counts = array_count_values($rows->pluck('category')->all());
                $order = array_keys(NumberReport::CATEGORIES);
                uksort($counts, fn ($a, $b) => [$counts[$b], array_search($a, $order, true)] <=> [$counts[$a], array_search($b, $order, true)]);
                $main = array_key_first($counts);

                return [
                    'kind' => 'phone',
                    'item' => (string) $phone,
                    'type' => NumberReport::CATEGORIES[$main] ?? NumberReport::CATEGORIES['other'],
                    'count' => $rows->pluck('user_id')->unique()->count(),
                    'verb' => 'reported',
                    'last' => $rows->max('created_at')->timestamp,
                ];
            })
            ->filter(fn ($row) => $row['count'] >= self::MIN_PEOPLE)
            ->values()
            ->all();
    }
}