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
        $type = $this->resolveType($request);

        $request->validate($this->rulesFor($type));

        $engine = new AnalysisEngine();
        $startTime = microtime(true);

        $screenshotPath = null;
        $screenshotStoragePath = null;

        if ($type === 'screenshot') {
            $uploadedFile = $request->file('screenshot');
            $storedPath = $uploadedFile->store('screenshots', 'public');
            $screenshotStoragePath = storage_path('app/public/' . $storedPath);
            $screenshotPath = asset('storage/' . $storedPath);
        }

        $result = $engine->analyze(
            type: $type,
            url: $request->input('url'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            screenshotPath: $screenshotStoragePath,
        );

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $report = Report::create([
            'user_id' => auth()->id(), // null for guests
            'type' => $type,
            'url' => $request->input('url'),
            'sender_email' => $request->input('email'),
            'phone_number' => $request->input('phone'),
            'screenshot_path' => $screenshotPath,
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

    /**
     * Determine which scan type was submitted based on which field is filled.
     */
    private function resolveType(Request $request): string
    {
        if ($request->hasFile('screenshot')) {
            return 'screenshot';
        }

        if ($request->filled('email')) {
            return 'email';
        }

        if ($request->filled('phone')) {
            return 'phone';
        }

        return 'url';
    }

    private function rulesFor(string $type): array
    {
        return match ($type) {
            'email' => ['email' => ['required', 'email', 'max:255']],
            'phone' => ['phone' => ['required', 'string', 'max:30']],
            'screenshot' => ['screenshot' => ['required', 'image', 'max:5120']], // 5MB max
            default => ['url' => ['required', 'url', 'max:2048']],
        };
    }
}