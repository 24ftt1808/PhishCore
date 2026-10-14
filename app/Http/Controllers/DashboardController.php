<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\HiddenNumber;
use App\Models\NumberReport;
use App\Models\Report;
use App\Models\ScamNews;
use App\Services\CommunityStats;
use App\Services\ScamNewsFetcher;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $statsBase = fn () => Analysis::whereHas('report', fn ($q) => $q->where('user_id', $userId));

        $stats = [
            'total' => (clone $statsBase())->count(),
            'safe' => (clone $statsBase())->where('verdict', 'clean')->count(),
            'suspicious' => (clone $statsBase())->where('verdict', 'suspicious')->count(),
            'phishing' => (clone $statsBase())->where('verdict', 'phishing')->count(),
        ];

        // Weekly detection overview (last 7 days, grouped by day + verdict)
        $weekStart = now()->subDays(6)->startOfDay();
        $weekEnd = now()->endOfDay();

        $weekAnalyses = $statsBase()->whereBetween('created_at', [$weekStart, $weekEnd])->get();

        $days = [];
        $safeByDay = [];
        $suspiciousByDay = [];
        $phishingByDay = [];

        $cursor = $weekStart->copy();
        while ($cursor->lte($weekEnd)) {
            $key = $cursor->format('Y-m-d');
            $dayAnalyses = $weekAnalyses->filter(fn ($a) => $a->created_at->format('Y-m-d') === $key);

            $days[] = $cursor->format('D');
            $safeByDay[] = $dayAnalyses->where('verdict', 'clean')->count();
            $suspiciousByDay[] = $dayAnalyses->where('verdict', 'suspicious')->count();
            $phishingByDay[] = $dayAnalyses->where('verdict', 'phishing')->count();

            $cursor->addDay();
        }

        $weekTotals = [
            'safe' => array_sum($safeByDay),
            'suspicious' => array_sum($suspiciousByDay),
            'phishing' => array_sum($phishingByDay),
        ];

        // Recent scans
        $recentScans = Report::where('user_id', $userId)
            ->with('analyses')
            ->latest()
            ->take(5)
            ->get();

        $totalScansCount = Report::where('user_id', $userId)->count();

        // "Latest Scam News" box: the same articles for everyone, newest first.
        $scamNews = ScamNews::visible()
            ->latest('published_at')->latest('id')
            ->limit((int) config('scamnews.display'))
            ->get();

        return view('dashboard', [
            'community' => $this->communityThisWeek(),
            'scamNews' => $scamNews,
            'scamNewsFetchedAt' => ScamNewsFetcher::lastRun()['at'] ?? null,
            'stats' => $stats,
            'weekLabels' => $days,
            'weekSafe' => $safeByDay,
            'weekSuspicious' => $suspiciousByDay,
            'weekPhishing' => $phishingByDay,
            'weekTotals' => $weekTotals,
            'weekRangeLabel' => $weekStart->format('M j').' – '.$weekEnd->format('M j, Y'),
            'recentScans' => $recentScans,
            'totalScansCount' => $totalScansCount,
        ]);
    }

    /**
     * Platform-wide figures for the last 7 days, the same for every signed-in user: how many scans
     * everyone ran, how many were risky, and the phone number that most different people reported.
     * Only counts and a number two or more people reported are shown, never who scanned or reported.
     *
     * @return array{scans: int, flagged: int, topNumber: ?array{phone: string, people: int}}
     */
    private function communityThisWeek(): array
    {
        $days = collect(CommunityStats::get()['days']);

        $top = NumberReport::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotIn('phone', HiddenNumber::keys())
            ->get()
            ->groupBy('phone')
            ->map(fn ($rows, $phone) => ['phone' => (string) $phone, 'people' => $rows->pluck('user_id')->unique()->count()])
            ->filter(fn ($row) => $row['people'] >= CommunityStats::MIN_PEOPLE)
            ->sortByDesc('people')
            ->first();

        return [
            'scans' => (int) ($days->sum('clean') + $days->sum('suspicious') + $days->sum('phishing')),
            'flagged' => (int) ($days->sum('suspicious') + $days->sum('phishing')),
            'topNumber' => $top,
        ];
    }
}
