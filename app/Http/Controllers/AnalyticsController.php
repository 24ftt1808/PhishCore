<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();
        $period = (int) $request->input('period', 30);
        if (!in_array($period, [7, 30, 90, 180])) {
            $period = 30;
        }

        $start = now()->subDays($period)->startOfDay();
        $prevStart = now()->subDays($period * 2)->startOfDay();
        $prevEnd = $start;

        $base = fn () => Analysis::whereHas('report', fn ($q) => $q->where('user_id', $userId));

        $current = $base()->with('report')->where('created_at', '>=', $start)->get();
        $previous = $base()->where('created_at', '>=', $prevStart)->where('created_at', '<', $prevEnd)->get();

        $total = $current->count();
        $prevTotal = $previous->count();

        $phishingCount = $current->where('verdict', 'phishing')->count();
        $prevPhishingCount = $previous->where('verdict', 'phishing')->count();

        $suspiciousCount = $current->where('verdict', 'suspicious')->count();
        $prevSuspiciousCount = $previous->where('verdict', 'suspicious')->count();

        $safeCount = $current->where('verdict', 'clean')->count();
        $prevSafeCount = $previous->where('verdict', 'clean')->count();

        $avgRisk = $total > 0 ? round($current->avg('risk_score')) : 0;
        $prevAvgRisk = $prevTotal > 0 ? round($previous->avg('risk_score')) : 0;

        $phishingRate = $total > 0 ? round(($phishingCount / $total) * 100, 1) : 0.0;
        $prevPhishingRate = $prevTotal > 0 ? round(($prevPhishingCount / $prevTotal) * 100, 1) : 0.0;

        $suspiciousRate = $total > 0 ? round(($suspiciousCount / $total) * 100, 1) : 0.0;
        $prevSuspiciousRate = $prevTotal > 0 ? round(($prevSuspiciousCount / $prevTotal) * 100, 1) : 0.0;

        $pctChange = function ($curr, $prev) {
            if ($prev == 0) {
                return $curr > 0 ? 100.0 : 0.0;
            }
            return round((($curr - $prev) / $prev) * 100, 1);
        };

        $stats = [
            'total' => $total,
            'total_change' => $pctChange($total, $prevTotal),
            'phishing_rate' => $phishingRate,
            'phishing_rate_change' => round($phishingRate - $prevPhishingRate, 1),
            'avg_risk' => $avgRisk,
            'avg_risk_change' => $pctChange($avgRisk, $prevAvgRisk),
            'suspicious_rate' => $suspiciousRate,
            'suspicious_rate_change' => round($suspiciousRate - $prevSuspiciousRate, 1),
        ];

        // Daily activity chart data
        $activityByDate = $current->groupBy(fn ($a) => $a->created_at->format('Y-m-d'))->map->count();
        $labels = [];
        $counts = [];
        $cursor = $start->copy();
        while ($cursor->lte(now())) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('M j');
            $counts[] = $activityByDate->get($key, 0);
            $cursor->addDay();
        }

        $breakdown = [
            'safe' => $safeCount,
            'suspicious' => $suspiciousCount,
            'phishing' => $phishingCount,
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
        $maxIndicatorCount = !empty($indicatorCounts) ? max($indicatorCounts) : 1;

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

        return view('analytics', [
            'stats' => $stats,
            'period' => $period,
            'chartLabels' => $labels,
            'chartCounts' => $counts,
            'breakdown' => $breakdown,
            'periodComparison' => $periodComparison,
            'riskBuckets' => $riskBuckets,
            'indicatorCounts' => $indicatorCounts,
            'maxIndicatorCount' => $maxIndicatorCount,
            'topDomains' => $topDomains,
            'maxDetections' => $maxDetections,
            'performance' => $performance,
        ]);
    }

    /**
     * Type-aware display label for a report — used anywhere a "domain" or
     * "URL" would previously have been shown, so email/phone/screenshot
     * reports display meaningfully instead of a blank string.
     */
    private function reportLabel(?Report $report): string
    {
        if (!$report) {
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