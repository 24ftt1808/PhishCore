<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanHistoryController extends Controller
{
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

        $query = Report::where('user_id', $userId)->with('analyses');

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

        $rows = (int) $request->input('rows', 8);
        $reports = $query->latest()->paginate($rows)->withQueryString();

        return view('scan.history', [
            'stats' => $stats,
            'reports' => $reports,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'min_score', 'max_score', 'rows']),
        ]);
    }
}