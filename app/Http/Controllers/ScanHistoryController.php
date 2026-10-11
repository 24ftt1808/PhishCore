<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScanHistoryController extends Controller
{
    private const EXPORT_MAX_ROWS = 5000;

    public function index(Request $request): View
    {
        $userId = auth()->id();

        $statsBase = fn () => Analysis::whereHas('report', fn ($q) => $q->where('user_id', $userId));

        $stats = [
            'total' => $statsBase()->count(),
            'safe' => $statsBase()->where('verdict', 'clean')->count(),
            'suspicious' => $statsBase()->where('verdict', 'suspicious')->count(),
            'phishing' => $statsBase()->where('verdict', 'phishing')->count(),
        ];

        $rows = (int) $request->input('rows', 8);
        $reports = $this->filteredReports($request)->with('analyses')->latest()->paginate($rows)->withQueryString();

        return view('scan.history', [
            'stats' => $stats,
            'reports' => $reports,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'min_score', 'max_score', 'rows']),
        ]);
    }

    /**
     * Download the signed-in user's scan history as a CSV file. It uses the
     * same filters as the page, but covers every matching scan, not just the
     * current page (capped at EXPORT_MAX_ROWS, newest first).
     */
    public function export(Request $request): StreamedResponse
    {
        $reports = $this->filteredReports($request)
            ->with('analyses:id,report_id,verdict,risk_score')
            ->latest()
            ->orderByDesc('id')
            ->limit(self::EXPORT_MAX_ROWS)
            ->get();

        $verdictLabels = ['clean' => 'Safe', 'suspicious' => 'Suspicious', 'phishing' => 'Phishing'];
        $filename = 'phishcore-scan-history-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($reports, $verdictLabels): void {
            $out = fopen('php://output', 'w');

            // Byte order mark, so Excel reads the file as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reference', 'Type', 'Reported item', 'Scan result', 'Risk score', 'Date', 'Time'], ',', '"', '');

            foreach ($reports as $report) {
                $analysis = $report->analyses->first();
                $item = match ($report->type) {
                    'email' => $report->sender_email,
                    'phone' => $report->phone_number,
                    'screenshot' => 'Uploaded screenshot',
                    default => $report->url,
                };

                fputcsv($out, [
                    'PG-'.$report->created_at->format('Y-md').'-'.strtoupper(substr(md5((string) $report->id), 0, 5)),
                    $report->type ?? 'url',
                    $this->csvSafe((string) $item),
                    $analysis?->verdict ? ($verdictLabels[$analysis->verdict] ?? ucfirst($analysis->verdict)) : 'Pending',
                    $analysis?->risk_score ?? '',
                    $report->created_at->format('Y-m-d'),
                    $report->created_at->format('H:i'),
                ], ',', '"', '');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * The signed-in user's scans, narrowed by the filters on the history page.
     *
     * @return Builder<Report>
     */
    private function filteredReports(Request $request): Builder
    {
        $query = Report::where('user_id', auth()->id());

        if ($search = $request->input('search')) {
            $query->where('url', 'like', "%{$search}%");
        }

        $status = $request->input('status', 'all');
        $verdictMap = ['safe' => 'clean', 'suspicious' => 'suspicious', 'phishing' => 'phishing'];
        if (isset($verdictMap[$status])) {
            $query->whereHas('analyses', fn ($q) => $q->where('verdict', $verdictMap[$status]));
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($request->filled('min_score')) {
            $min = (int) $request->input('min_score');
            $query->whereHas('analyses', fn ($q) => $q->where('risk_score', '>=', $min));
        }

        if ($request->filled('max_score')) {
            $max = (int) $request->input('max_score');
            $query->whereHas('analyses', fn ($q) => $q->where('risk_score', '<=', $max));
        }

        return $query;
    }

    /**
     * Scanned items come from outside, so a value that starts like a
     * spreadsheet formula gets a leading apostrophe and is shown as text.
     * A plain phone number such as +6737654321 is left as it is.
     */
    private function csvSafe(string $value): string
    {
        if (preg_match('/^\+[\d\s().\-]+$/', $value) === 1) {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
