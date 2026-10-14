<?php

namespace App\Http\Controllers;

use App\Models\NumberReport;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * "Report this number as a scam" on a phone scan result. The number always comes from a scan the
 * person can open, never from free text, so nobody can report numbers they did not look up.
 */
class NumberReportController extends Controller
{
    /** Most number reports (new or changed) one person can send in 24 hours. */
    public const DAILY_LIMIT = 20;

    public function store(Request $request, Report $report): RedirectResponse
    {
        abort_unless($report->canBeViewedBy($request->user()), 404);
        abort_unless($report->type === 'phone' && filled($report->phone_number), 422, 'Only phone number scans can be reported.');

        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(NumberReport::CATEGORIES))],
        ]);

        $limiterKey = 'number-report-daily:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($limiterKey, self::DAILY_LIMIT)) {
            return redirect()->route('scan.show', $report)
                ->with('number_report_limited', 'You have reached today\'s report limit. Please come back tomorrow.');
        }

        RateLimiter::hit($limiterKey, 86400);

        NumberReport::updateOrCreate(
            ['user_id' => $request->user()->id, 'phone' => NumberReport::key((string) $report->phone_number)],
            ['category' => $data['category']],
        );

        return redirect()->route('scan.show', $report)
            ->with('number_reported', 'Thank you. Your report was saved and helps warn other people.');
    }
}
