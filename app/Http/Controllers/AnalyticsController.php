<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Report;
use App\Services\CommunityStats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    private const PRESET_DAYS = [7, 30, 90, 180];

    private const MAX_CUSTOM_DAYS = 366;

    public function index(Request $request): View
    {
        return view('analytics', $this->analytics($request) + ['community' => CommunityStats::get()]);
    }

    /**
     * Download the numbers behind the page, for the range that is selected,
     * as one CSV file (Section, Item, Value, Previous period, Change).
     */
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->exportRows($this->analytics($request));
        $filename = 'phishcore-analytics-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');

            // Byte order mark, so Excel reads the file as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Section', 'Item', 'Value', 'Previous period', 'Change (%)'], ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Everything the Analytics page shows for the signed-in user, for the
     * range picked with ?period=... or ?from=...&to=...
     *
     * @return array<string, mixed>
     */
    private function analytics(Request $request): array
    {
        $userId = auth()->id();
        $range = $this->resolveRange($request, $userId);

        $start = $range['start'];
        $end = $range['end'];
        $hasComparison = $range['hasComparison'];

        $base = fn () => Analysis::whereHas('report', fn ($q) => $q->where('user_id', $userId));

        $current = $base()->with('report')->whereBetween('created_at', [$start, $end])->get();
        $previous = $hasComparison
            ? $base()->where('created_at', '>=', $range['prevStart'])->where('created_at', '<', $range['prevEnd'])->get()
            : collect();

        $total = $current->count();
        $prevTotal = $previous->count();

        $phishingCount = $current->where('verdict', 'phishing')->count();
        $prevPhishingCount = $previous->where('verdict', 'phishing')->count();

        $suspiciousCount = $current->where('verdict', 'suspicious')->count();
        $prevSuspiciousCount = $previous->where('verdict', 'suspicious')->count();

        $safeCount = $current->where('verdict', 'clean')->count();
        $prevSafeCount = $previous->where('verdict', 'clean')->count();

        $reviewCount = $current->where('verdict', 'review')->count();

        $avgRisk = $total > 0 ? round($current->avg('risk_score')) : 0;
        $prevAvgRisk = $prevTotal > 0 ? round($previous->avg('risk_score')) : 0;

        $phishingRate = $total > 0 ? round(($phishingCount / $total) * 100, 1) : 0.0;
        $prevPhishingRate = $prevTotal > 0 ? round(($prevPhishingCount / $prevTotal) * 100, 1) : 0.0;

        $suspiciousRate = $total > 0 ? round(($suspiciousCount / $total) * 100, 1) : 0.0;
        $prevSuspiciousRate = $prevTotal > 0 ? round(($prevSuspiciousCount / $prevTotal) * 100, 1) : 0.0;

        // With nothing to compare against (All Time), every change is 0 instead of a misleading +100%.
        $pctChange = function ($curr, $prev) use ($hasComparison) {
            if (! $hasComparison) {
                return 0.0;
            }
            if ($prev == 0) {
                return $curr > 0 ? 100.0 : 0.0;
            }

            return round((($curr - $prev) / $prev) * 100, 1);
        };
        $pointChange = fn ($curr, $prev) => $hasComparison ? round($curr - $prev, 1) : 0.0;

        $stats = [
            'total' => $total,
            'total_change' => $pctChange($total, $prevTotal),
            'phishing_rate' => $phishingRate,
            'phishing_rate_change' => $pointChange($phishingRate, $prevPhishingRate),
            'avg_risk' => $avgRisk,
            'avg_risk_change' => $pctChange($avgRisk, $prevAvgRisk),
            'suspicious_rate' => $suspiciousRate,
            'suspicious_rate_change' => $pointChange($suspiciousRate, $prevSuspiciousRate),
        ];

        $previousStats = [
            'total' => $prevTotal,
            'phishing_rate' => $prevPhishingRate,
            'avg_risk' => $prevAvgRisk,
            'suspicious_rate' => $prevSuspiciousRate,
        ];

        // Daily activity chart data
        $activityByDate = $current->groupBy(fn ($a) => $a->created_at->format('Y-m-d'))->map->count();
        $labels = [];
        $dates = [];
        $counts = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('M j');
            $dates[] = $key;
            $counts[] = $activityByDate->get($key, 0);
            $cursor->addDay();
        }

        $breakdown = [
            'safe' => $safeCount,
            'suspicious' => $suspiciousCount,
            'phishing' => $phishingCount,
            'review' => $reviewCount,
            'total' => $total,
        ];

        // Period comparison (current vs previous, for the bar chart)
        $periodComparison = [
            'safe' => ['current' => $safeCount, 'previous' => $prevSafeCount, 'change' => $pctChange($safeCount, $prevSafeCount)],
            'suspicious' => ['current' => $suspiciousCount, 'previous' => $prevSuspiciousCount, 'change' => $pctChange($suspiciousCount, $prevSuspiciousCount)],
            'phishing' => ['current' => $phishingCount, 'previous' => $prevPhishingCount, 'change' => $pctChange($phishingCount, $prevPhishingCount)],
        ];

        // Risk-Level Distribution
        $riskBuckets = [
            'low' => ['label' => 'Low Risk (0–25)', 'min' => 0, 'max' => 25, 'count' => 0, 'color' => 'emerald'],
            'medium' => ['label' => 'Medium Risk (26–50)', 'min' => 26, 'max' => 50, 'count' => 0, 'color' => 'sky'],
            'high' => ['label' => 'High Risk (51–75)', 'min' => 51, 'max' => 75, 'count' => 0, 'color' => 'orange'],
            'critical' => ['label' => 'Critical (76–100)', 'min' => 76, 'max' => 100, 'count' => 0, 'color' => 'red'],
        ];
        foreach ($current as $a) {
            foreach ($riskBuckets as $key => $bucket) {
                if ($a->risk_score >= $bucket['min'] && $a->risk_score <= $bucket['max']) {
                    $riskBuckets[$key]['count']++;
                    break;
                }
            }
        }
        foreach ($riskBuckets as $key => $bucket) {
            $riskBuckets[$key]['pct'] = $total > 0 ? round(($bucket['count'] / $total) * 100) : 0;
        }

        // Most Common Phishing Indicators — count how often each real check flagged something
        $indicatorCounts = [];
        foreach ($current as $a) {
            foreach (($a->flags ?? []) as $check) {
                if (($check['status'] ?? 'SAFE') !== 'SAFE') {
                    $name = $check['name'] ?? 'Unknown';
                    $indicatorCounts[$name] = ($indicatorCounts[$name] ?? 0) + 1;
                }
            }
        }
        arsort($indicatorCounts);
        $maxIndicatorCount = ! empty($indicatorCounts) ? max($indicatorCounts) : 1;

        // Top Threat Sources — group phishing-flagged scans by a type-aware label
        // (URL host, email domain, phone number, or "Uploaded screenshot")
        $phishingScans = $current->where('verdict', 'phishing');
        $sourceGroups = $phishingScans->groupBy(function ($a) {
            return $this->reportLabel($a->report);
        });
        $topDomains = $sourceGroups->map(function ($group, $source) {
            return [
                'domain' => $source,
                'detections' => $group->count(),
                'avg_score' => round($group->avg('risk_score')),
                'latest' => $group->max('created_at'),
            ];
        })->sortByDesc('detections')->take(5)->values();
        $maxDetections = $topDomains->max('detections') ?: 1;

        // Top Source Countries — group URL scans by the country their IP
        // resolved to, from ip-api.com geolocation captured on each Analysis.
        $countryGroups = $current->whereNotNull('country')->groupBy('country');
        $topCountries = $countryGroups->map(function ($group, $country) {
            return [
                'country' => $country,
                'count' => $group->count(),
                'phishing_count' => $group->where('verdict', 'phishing')->count(),
            ];
        })->sortByDesc('count')->take(5)->values();
        $maxCountryCount = $topCountries->max('count') ?: 1;

        // Scanning Performance — only scans that have duration_ms recorded
        $timedScans = $current->whereNotNull('duration_ms');
        $performance = null;
        if ($timedScans->count() > 0) {
            $durations = $timedScans->pluck('duration_ms')->sort()->values();
            $fastest = $timedScans->sortBy('duration_ms')->first();
            $slowest = $timedScans->sortByDesc('duration_ms')->first();
            $median = $durations[intdiv($durations->count(), 2)];

            $performance = [
                'avg_ms' => round($durations->avg()),
                'fastest_ms' => $fastest->duration_ms,
                'fastest_label' => $this->reportLabel($fastest->report),
                'slowest_ms' => $slowest->duration_ms,
                'slowest_label' => $this->reportLabel($slowest->report),
                'median_ms' => $median,
                'count' => $timedScans->count(),
            ];
        }

        $shownEnd = $end->copy()->min(now());

        return [
            'stats' => $stats,
            'previousStats' => $previousStats,
            'period' => $range['key'],
            'rangeLabel' => $range['label'],
            'rangeNotice' => $range['notice'],
            'hasComparison' => $hasComparison,
            'rangeFrom' => $start->format('Y-m-d'),
            'rangeTo' => $shownEnd->format('Y-m-d'),
            'exportParams' => $range['key'] === 'custom'
                ? ['from' => $start->format('Y-m-d'), 'to' => $shownEnd->format('Y-m-d')]
                : ['period' => $range['key']],
            'chartLabels' => $labels,
            'chartDates' => $dates,
            'chartCounts' => $counts,
            'breakdown' => $breakdown,
            'periodComparison' => $periodComparison,
            'riskBuckets' => $riskBuckets,
            'indicatorCounts' => $indicatorCounts,
            'maxIndicatorCount' => $maxIndicatorCount,
            'topDomains' => $topDomains,
            'maxDetections' => $maxDetections,
            'topCountries' => $topCountries,
            'maxCountryCount' => $maxCountryCount,
            'performance' => $performance,
        ];
    }

    /**
     * Works out which dates to show and which equal-length stretch just before
     * them to compare against. Anything unusable falls back to the last 30 days
     * and says why in 'notice'.
     *
     * @return array{key: string, label: string, start: Carbon, end: Carbon, prevStart: ?Carbon, prevEnd: ?Carbon, hasComparison: bool, notice: ?string}
     */
    private function resolveRange(Request $request, int|string|null $userId): array
    {
        $key = (string) $request->input('period', '30');
        $notice = null;

        if ($request->filled('from') || $request->filled('to')) {
            $from = $this->parseDate($request->input('from'));
            $to = $this->parseDate($request->input('to'));

            if ($from !== null && $to !== null) {
                return $this->customRange($from, $to);
            }

            $notice = 'Pick both a start date and an end date for a custom range. Showing the last 30 days instead.';
            $key = '30';
        }

        $now = now();

        if ($key === 'this_month') {
            return $this->equalLengthRange($key, 'This Month', $now->copy()->startOfMonth(), $now->copy(), $notice);
        }

        if ($key === 'last_month') {
            $start = $now->copy()->subMonthNoOverflow()->startOfMonth();

            return $this->equalLengthRange($key, 'Last Month', $start, $start->copy()->endOfMonth(), $notice);
        }

        if ($key === 'all') {
            $first = Analysis::whereHas('report', fn ($q) => $q->where('user_id', $userId))->min('created_at');
            $start = $first ? Carbon::parse($first)->startOfDay() : $now->copy()->startOfDay();

            return $this->buildRange('all', 'All Time', $start, $now->copy(), null, null, $notice);
        }

        $days = ctype_digit($key) && in_array((int) $key, self::PRESET_DAYS, true) ? (int) $key : 30;

        return $this->presetRange($days, $notice);
    }

    /**
     * @return array{key: string, label: string, start: Carbon, end: Carbon, prevStart: ?Carbon, prevEnd: ?Carbon, hasComparison: bool, notice: ?string}
     */
    private function presetRange(int $days, ?string $notice): array
    {
        $labels = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 3 Months', 180 => 'Last 6 Months'];
        $start = now()->subDays($days)->startOfDay();

        return $this->buildRange((string) $days, $labels[$days], $start, now(), $start->copy()->subDays($days), $start->copy(), $notice);
    }

    /**
     * @return array{key: string, label: string, start: Carbon, end: Carbon, prevStart: ?Carbon, prevEnd: ?Carbon, hasComparison: bool, notice: ?string}
     */
    private function customRange(Carbon $from, Carbon $to): array
    {
        $notice = null;
        $today = now()->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
            $notice = 'The start date was after the end date, so they were swapped.';
        }

        if ($from->gt($today)) {
            return $this->presetRange(30, 'That range is in the future, so the last 30 days are shown instead.');
        }

        if ($to->gt($today)) {
            $to = $today->copy();
        }

        if ((int) abs($from->diffInDays($to)) >= self::MAX_CUSTOM_DAYS) {
            $from = $to->copy()->subDays(self::MAX_CUSTOM_DAYS - 1);
            $notice = 'Custom ranges are limited to 12 months, so the start date was moved to '.$from->format('j M Y').'.';
        }

        $start = $from->copy()->startOfDay();
        $end = $to->isSameDay(now()) ? now() : $to->copy()->endOfDay();
        $label = $start->year === $end->year
            ? $start->format('j M').' – '.$end->format('j M Y')
            : $start->format('j M Y').' – '.$end->format('j M Y');

        return $this->equalLengthRange('custom', $label, $start, $end, $notice);
    }

    /**
     * The comparison stretch is as many days long as the range itself and ends where the range starts.
     *
     * @return array{key: string, label: string, start: Carbon, end: Carbon, prevStart: ?Carbon, prevEnd: ?Carbon, hasComparison: bool, notice: ?string}
     */
    private function equalLengthRange(string $key, string $label, Carbon $start, Carbon $end, ?string $notice): array
    {
        $length = (int) abs($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay())) + 1;

        return $this->buildRange($key, $label, $start, $end, $start->copy()->subDays($length), $start->copy(), $notice);
    }

    /**
     * @return array{key: string, label: string, start: Carbon, end: Carbon, prevStart: ?Carbon, prevEnd: ?Carbon, hasComparison: bool, notice: ?string}
     */
    private function buildRange(string $key, string $label, Carbon $start, Carbon $end, ?Carbon $prevStart, ?Carbon $prevEnd, ?string $notice): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'prevStart' => $prevStart,
            'prevEnd' => $prevEnd,
            'hasComparison' => $prevStart !== null,
            'notice' => $notice,
        ];
    }

    /** A real calendar date written as YYYY-MM-DD, or null. */
    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date !== null && $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * One row per number on the page: Section, Item, Value, Previous period, Change (%).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string|int|float>>
     */
    private function exportRows(array $data): array
    {
        $compare = $data['hasComparison'];
        $stats = $data['stats'];
        $previous = $data['previousStats'];
        $before = fn ($value) => $compare ? $value : '';
        $change = fn ($value) => $compare ? $value : '';

        $rows = [
            ['Range', 'Period', $data['rangeLabel'], '', ''],
            ['Range', 'From', $data['rangeFrom'], '', ''],
            ['Range', 'To', $data['rangeTo'], '', ''],
            ['Summary', 'Total reports', $stats['total'], $before($previous['total']), $change($stats['total_change'])],
            ['Summary', 'Phishing detection rate (%)', $stats['phishing_rate'], $before($previous['phishing_rate']), $change($stats['phishing_rate_change'])],
            ['Summary', 'Average risk score', $stats['avg_risk'], $before($previous['avg_risk']), $change($stats['avg_risk_change'])],
            ['Summary', 'Suspicious rate (%)', $stats['suspicious_rate'], $before($previous['suspicious_rate']), $change($stats['suspicious_rate_change'])],
        ];

        foreach (['safe' => 'Safe', 'suspicious' => 'Suspicious', 'phishing' => 'Phishing'] as $key => $label) {
            $row = $data['periodComparison'][$key];
            $rows[] = ['Results', $label, $row['current'], $before($row['previous']), $change($row['change'])];
        }
        $rows[] = ['Results', 'Needs review', $data['breakdown']['review'], '', ''];

        foreach ($data['riskBuckets'] as $bucket) {
            $rows[] = ['Risk levels', $bucket['label'], $bucket['count'], '', ''];
        }

        foreach ($data['topDomains'] as $source) {
            $rows[] = ['Top threat sources', $this->csvSafe((string) $source['domain']), $source['detections'], '', ''];
        }

        foreach ($data['indicatorCounts'] as $name => $count) {
            $rows[] = ['Common indicators', $this->csvSafe((string) $name), $count, '', ''];
        }

        foreach ($data['topCountries'] as $country) {
            $rows[] = ['Top source countries', $this->csvSafe((string) $country['country']), $country['count'], '', ''];
        }

        if ($data['performance'] !== null) {
            $performance = $data['performance'];
            $rows[] = ['Scan speed', 'Average (ms)', $performance['avg_ms'], '', ''];
            $rows[] = ['Scan speed', 'Median (ms)', $performance['median_ms'], '', ''];
            $rows[] = ['Scan speed', 'Fastest (ms)', $performance['fastest_ms'], '', ''];
            $rows[] = ['Scan speed', 'Slowest (ms)', $performance['slowest_ms'], '', ''];
        }

        foreach ($data['chartDates'] as $index => $date) {
            $rows[] = ['Daily activity', $date, $data['chartCounts'][$index], '', ''];
        }

        return $rows;
    }

    /**
     * Scanned items come from outside, so a value that starts like a
     * spreadsheet formula gets a leading apostrophe and is shown as text.
     * A plain phone number such as +6737654321 is left as it is.
     */
    private function csvSafe(string $value): string
    {
        if (preg_match('/^\+[\d\s().\-]+$/', $value) === 1) {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * Type-aware display label for a report — used anywhere a "domain" or
     * "URL" would previously have been shown, so email/phone/screenshot
     * reports display meaningfully instead of a blank string.
     */
    private function reportLabel(?Report $report): string
    {
        if (! $report) {
            return 'Unknown';
        }

        return match ($report->type) {
            'email' => $report->sender_email
                ? (strtolower(substr(strrchr($report->sender_email, '@'), 1)) ?: $report->sender_email)
                : 'Unknown sender',
            'phone' => $report->phone_number ?? 'Unknown number',
            'screenshot' => 'Uploaded screenshot',
            default => parse_url($report->url ?? '', PHP_URL_HOST) ?: ($report->url ?: 'Unknown URL'),
        };
    }
}
