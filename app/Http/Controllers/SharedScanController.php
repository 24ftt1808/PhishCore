<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Response;

/**
 * Public, read-only result page reached through a share link. It reuses the
 * normal result view in "shared" mode, which leaves out who submitted the scan
 * and the PDF and investigation tools. Search engines are told not to index it.
 */
class SharedScanController extends Controller
{
    public function show(string $token): Response
    {
        $report = Report::query()
            ->where('share_token', $token)
            ->where('type', 'url')
            ->where('status', 'completed')
            ->first();

        $analysis = null;
        if ($report) {
            $report->load(['analyses', 'ctiLookups']);
            $analysis = $report->analyses->first();
        }

        if (! $report || ! $analysis || ! $analysis->verdict) {
            return $this->unavailable();
        }

        return response(view('scan.show', [
            'report' => $report,
            'analysis' => $analysis,
            'ctiLookup' => $report->ctiLookups->first(),
            'shared' => true,
        ]))->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** A friendly 404 for unknown, revoked or unfinished share links. */
    private function unavailable(): Response
    {
        return response(view('scan.shared-unavailable'), 404)->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}