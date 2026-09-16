<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicReportsController extends Controller
{
    /**
     * Public, platform-wide transparency feed of confirmed suspicious/
     * phishing reports. Unlike ReportsController (team-only, full detail),
     * this is intentionally stripped down: no risk score, no investigation
     * status, no full submitter identity. No auth required to view.
     */
    public function index(Request $request): View
    {
        $statsBase = fn () => Report::query()->where('status', 'completed');
        $stats = [
            'suspicious' => $statsBase()->whereHas('analyses', fn ($q) => $q->where('verdict', 'suspicious'))->count(),
            'phishing' => $statsBase()->whereHas('analyses', fn ($q) => $q->where('verdict', 'phishing'))->count(),
            'latest' => $statsBase()->whereHas('analyses', fn ($q) => $q->whereIn('verdict', ['suspicious', 'phishing']))->latest()->first()?->created_at,
        ];

        $query = Report::with(['analyses', 'user'])
            ->where('status', 'completed')
            ->whereHas('analyses', fn ($q) => $q->whereIn('verdict', ['suspicious', 'phishing']));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('sender_email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $verdictMap = ['suspicious' => 'suspicious', 'phishing' => 'phishing'];
        $status = $request->input('status', 'all');
        if (isset($verdictMap[$status])) {
            $query->whereHas('analyses', fn ($q) => $q->where('verdict', $verdictMap[$status]));
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $rows = (int) $request->input('rows', 8);
        $reports = $query->latest()->paginate($rows)->withQueryString();

        return view('reports.public', [
            'stats' => $stats,
            'reports' => $reports,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'rows']),
        ]);
    }
}