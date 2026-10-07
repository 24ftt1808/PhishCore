<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Creates and revokes the public share link for a scan. A scan is private
 * until its owner (or the team) creates a link, and revoking the link makes
 * the old URL stop working immediately.
 */
class ScanShareController extends Controller
{
    public function store(Request $request, Report $report): RedirectResponse
    {
        abort_unless($report->canBeViewedBy($request->user()), 404);
        abort_unless($report->isShareable(), 422, 'Only finished link scans can be shared.');

        if (! $report->share_token) {
            $report->forceFill(['share_token' => Str::random(40)])->save();
        }

        return redirect()->route('scan.show', $report)->with('shared', 'Share link created.');
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        abort_unless($report->canBeViewedBy($request->user()), 404);

        $report->forceFill(['share_token' => null])->save();

        return redirect()->route('scan.show', $report)->with('shared', 'Share link revoked. The old link no longer works.');
    }
}   