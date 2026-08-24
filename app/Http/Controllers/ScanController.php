<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Report;
use App\Services\AnalysisEngine;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ScanController extends Controller
{
    public function index(): View
    {
        $recentScans = [];

        if (auth()->check()) {
            $recentScans = Report::where('user_id', auth()->id())
                ->latest()
                ->take(3)
                ->with('analyses')
                ->get();
        }

        return view('scan.index', ['recentScans' => $recentScans]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

       $engine = new AnalysisEngine();
$startTime = microtime(true);
$result = $engine->analyze($request->url);
$durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $report = Report::create([
            'user_id' => auth()->id(), // null for guests
            'url' => $request->url,
            'status' => 'completed',
        ]);

      Analysis::create([
    'report_id' => $report->id,
    'domain_age_days' => $result['domain_age_days'],
    'url_syntax_score' => $result['url_syntax_score'],
    'verdict' => $result['verdict'],
    'flags' => $result['checks'],
    'risk_score' => $result['risk_score'],
    'duration_ms' => $durationMs,
]);

        return redirect()->route('scan.show', $report);
    }

    public function show(Report $report): View
    {
        $report->load('analyses');
        return view('scan.show', ['report' => $report, 'analysis' => $report->analyses->first()]);
    }
}