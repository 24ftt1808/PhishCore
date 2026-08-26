<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\CtiLookup;
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

        $screenshotPath = null;
        $screenshotStoragePath = null;

        if ($type === 'screenshot') {
            $uploadedFile = $request->file('screenshot');
            $storedPath = $uploadedFile->store('screenshots', 'public');
            $screenshotStoragePath = storage_path('app/public/' . $storedPath);
            $screenshotPath = asset('storage/' . $storedPath);
        }

        // Create the report first so we have a report_id to attach CTI lookups to.
        $report = Report::create([
            'user_id' => auth()->id(), // null for guests
            'type' => $type,
            'url' => $request->input('url'),
            'sender_email' => $request->input('email'),
            'phone_number' => $request->input('phone'),
            'screenshot_path' => $screenshotPath,
            'status' => 'processing',
        ]);

        // A single scan can chain several sequential external API calls
        // (WHOIS, SSL, Google Safe Browsing, VirusTotal submit+poll, IP
        // reputation, redirect-chain following, and — for screenshots —
        // OCR before any of that even starts). This can legitimately take
        // longer than PHP's default 30s execution limit, so we raise it
        // specifically for this request rather than for the whole app.
        set_time_limit(120);

        try {
            $engine = new AnalysisEngine();
            $startTime = microtime(true);

            $result = $engine->analyze(
                type: $type,
                url: $request->input('url'),
                email: $request->input('email'),
                phone: $request->input('phone'),
                screenshotPath: $screenshotStoragePath,
                reportId: $report->id,
            );

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!empty($result['cti'])) {
                CtiLookup::create([
                    'report_id' => $report->id,
                    'source' => $result['cti']['source'],
                    'raw_response' => $result['cti']['raw_response'],
                    'threat_score' => $result['cti']['threat_score'],
                ]);
            }

            Analysis::create([
                'report_id' => $report->id,
                'domain_age_days' => $result['domain_age_days'],
                'url_syntax_score' => $result['url_syntax_score'],
                'ip_address' => $result['ip_address'] ?? null,
                'ip_reputation' => $result['ip_reputation'] ?? null,
                'redirect_chain' => $result['redirect_chain'] ?? null,
                'verdict' => $result['verdict'],
                'flags' => $result['checks'],
                'risk_score' => $result['risk_score'],
                'duration_ms' => $durationMs,
            ]);

            $report->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            // Don't leave a "processing" report with no Analysis row behind —
            // that's what was causing scan.show to crash on a null verdict.
            // Mark it as failed so the results page can show a clear message
            // instead of a fatal error.
            report($e);
            $report->update(['status' => 'failed']);

            return redirect()->route('scan.show', $report)
                ->with('error', 'The scan took too long or ran into a problem partway through. You can try scanning again.');
        }

        return redirect()->route('scan.show', $report);
    }

    public function show(Report $report): View
    {
        $report->load(['analyses', 'ctiLookups']);
        return view('scan.show', [
            'report' => $report,
            'analysis' => $report->analyses->first(),
            'ctiLookup' => $report->ctiLookups->first(),
        ]);
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