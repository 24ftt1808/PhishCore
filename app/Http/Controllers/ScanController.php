<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\CtiLookup;
use App\Models\Report;
use App\Services\AnalysisEngine;
use App\Support\PhoneCountries;
use App\Support\ScanGuard;
use App\Support\ScanRateLimit;
use App\Support\ScanReuse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        // A robot filled in the hidden trap field: do nothing, and say nothing.
        if (ScanGuard::honeypotTripped($request)) {
            return redirect()->back();
        }

        // Guests prove they are human once (when the Turnstile keys are set), then are trusted for a while.
        if (ScanGuard::needsHumanCheck($request) && ! ScanGuard::passesHumanCheck($request)) {
            return ScanRateLimit::back($request, 'Please complete the human check and try again.');
        }

        // A link scanned recently is not scanned again: the earlier result is copied for this person,
        // which is instant, uses none of the outside quotas and does not count against the scan limit.
        if ($type === 'url' && is_string($request->input('url')) && ($earlier = ScanReuse::find($request->input('url')))) {
            $report = ScanReuse::copyFor($earlier, auth()->id());

            if (! auth()->check()) {
                session()->push('guest_report_ids', $report->id);
            }

            return redirect()->route('scan.show', $report)
                ->with('info', 'This link was checked '.$earlier->created_at->diffForHumans().', so you are seeing that result. Scan it again later for a fresh check.');
        }

        // A real scan is about to run, so count it against the person's allowance.
        if ($limited = ScanRateLimit::attempt($request)) {
            return $limited;
        }

        $request->validate($this->rulesFor($type));

        // A local-style number is read as belonging to the country picked on
        // the form; one that already starts with "+" keeps its own country.
        // Storing the normalised form also keeps repeat-number matching
        // consistent between scans.
        $phone = $type === 'phone'
            ? PhoneCountries::normalise((string) $request->input('phone'), $request->input('phone_country'))
            : $request->input('phone');

        $screenshotPath = null;
        $screenshotStoragePath = null;

        if ($type === 'screenshot') {
            $uploadedFile = $request->file('screenshot');
            $storedPath = $uploadedFile->store('screenshots', 'public');
            $screenshotStoragePath = storage_path('app/public/'.$storedPath);
            $screenshotPath = asset('storage/'.$storedPath);
        }

        // Create the report first so we have a report_id to attach CTI lookups to.
        $report = Report::create([
            'user_id' => auth()->id(), // null for guests
            'type' => $type,
            'url' => $request->input('url'),
            'sender_email' => $request->input('email'),
            'phone_number' => $phone,
            'screenshot_path' => $screenshotPath,
            'status' => 'processing',
        ]);

        // Guests have no account to own the scan, so remember it in their
        // session — that is what lets them open their own result page.
        if (! auth()->check()) {
            session()->push('guest_report_ids', $report->id);
        }

        // A single scan can chain several sequential external API calls
        // (WHOIS, SSL, Google Safe Browsing, VirusTotal submit+poll, IP
        // reputation, redirect-chain following, and — for screenshots —
        // OCR before any of that even starts). This can legitimately take
        // longer than PHP's default 30s execution limit, so we raise it
        // specifically for this request rather than for the whole app.
        set_time_limit(120);

        try {
            $engine = new AnalysisEngine;
            $startTime = microtime(true);

            $result = $engine->analyze(
                type: $type,
                url: $request->input('url'),
                email: $request->input('email'),
                phone: $phone,
                screenshotPath: $screenshotStoragePath,
                reportId: $report->id,
                emailSubject: $request->input('subject'),
                emailBody: $request->input('body'),
            );

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if (! empty($result['cti'])) {
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
                'country' => $result['country'] ?? null,
                'redirect_chain' => $result['redirect_chain'] ?? null,
                'verdict' => $result['verdict'],
                'flags' => $result['checks'],
                'risk_score' => $result['risk_score'],
                'duration_ms' => $durationMs,
            ]);

            // For screenshot scans, persist whatever URL/email OCR extracted
            // back onto the report row — otherwise this evidence only ever
            // lived in-memory during this one scan, and future scans of the
            // same phishing domain (via a different screenshot, or a direct
            // URL/email submission) would have nothing to correlate against.
            if ($type === 'screenshot') {
                $report->update(array_filter([
                    'url' => $result['extracted_url'] ?? null,
                    'sender_email' => $result['extracted_email'] ?? null,
                ]));
            }

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
        abort_unless($report->canBeViewedBy(auth()->user()), 404);

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
        if ($request->filled('email') || $request->filled('subject') || $request->filled('body')) {
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
            'email' => [
                'email' => ['required', 'email', 'max:255'],
                'subject' => ['nullable', 'string', 'max:255'],
                'body' => ['nullable', 'string', 'max:5000'],
            ],
            'phone' => [
                'phone' => ['required', 'string', 'max:30'],
                'phone_country' => ['nullable', 'string', Rule::in(PhoneCountries::codes())],
            ],
            'screenshot' => ['screenshot' => ['required', 'image', 'max:5120']], // 5MB max
            default => ['url' => ['required', 'url', 'max:2048']],
        };
    }
}
