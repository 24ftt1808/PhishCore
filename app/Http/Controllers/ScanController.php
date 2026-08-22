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
        return view('scan.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $engine = new AnalysisEngine();
        $result = $engine->analyze($request->url);

        $report = Report::create([
            'user_id' => auth()->id(), // null for guests
            'url' => $request->url,
            'status' => 'completed',
        ]);

               Analysis::create([
            'report_id' => $report->id,
            'domain_age_days' => $result['domain_age_days'] !== null ? round($result['domain_age_days']) : null,
            'url_syntax_score' => $result['url_syntax_score'],
            'verdict' => $result['verdict'],
            'flags' => $result['reasons'],
            'risk_score' => $result['risk_score'],
        ]);

        return redirect()->route('scan.show', $report);
    }

    public function show(Report $report): View
    {
        $report->load('analyses');
        return view('scan.show', ['report' => $report, 'analysis' => $report->analyses->first()]);
    }
}