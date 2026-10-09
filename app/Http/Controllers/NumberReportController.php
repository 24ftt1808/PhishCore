<?php

namespace App\Http\Controllers;

use App\Models\NumberReport;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Report this number as a scam" on a phone scan result. The number always comes from a scan the
 * person can open, never from free text, so nobody can report numbers they did not look up.
 */
class NumberReportController extends Controller
{
    public function store(Request $request, Report $report): RedirectResponse
    {
        abort_unless($report->canBeViewedBy($request->user()), 404);
        abort_unless($report->type === 'phone' && filled($report->phone_number), 422, 'Only phone number scans can be reported.');

        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(NumberReport::CATEGORIES))],
        ]);

        NumberReport::updateOrCreate(
            ['user_id' => $request->user()->id, 'phone' => NumberReport::key((string) $report->phone_number)],
            ['category' => $data['category']],
        );

        return redirect()->route('scan.show', $report)
            ->with('number_reported', 'Thank you. Your report was saved and helps warn other people.');
    }
}