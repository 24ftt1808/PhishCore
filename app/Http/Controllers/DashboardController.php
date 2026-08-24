<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Report;
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

        return view('dashboard', [
            'stats' => $stats,
            'weekLabels' => $days,
            'weekSafe' => $safeByDay,
            'weekSuspicious' => $suspiciousByDay,
            'weekPhishing' => $phishingByDay,
            'weekTotals' => $weekTotals,
            'weekRangeLabel' => $weekStart->format('M j') . ' – ' . $weekEnd->format('M j, Y'),
            'recentScans' => $recentScans,
            'totalScansCount' => $totalScansCount,
        ]);
    }
}