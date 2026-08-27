<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    /**
     * Platform-wide list of every report submitted by every user, for the
     * team to browse and discover cases worth investigating. Restricted to
     * team members — this is an internal ops view, distinct from a user's
     * own personal Scan History.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->is_team_member, 403, 'Only team members can view all reports.');

        $statsBase = fn () => Report::query();
        $stats = [
            'total' => $statsBase()->count(),
            'safe' => $statsBase()->whereHas('analyses', fn ($q) => $q->where('verdict', 'clean'))->count(),
            'suspicious' => $statsBase()->whereHas('analyses', fn ($q) => $q->where('verdict', 'suspicious'))->count(),
            'phishing' => $statsBase()->whereHas('analyses', fn ($q) => $q->where('verdict', 'phishing'))->count(),
        ];

        $query = Report::with(['analyses', 'user', 'investigation'])->where('status', 'completed');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('sender_email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
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

        $rows = (int) $request->input('rows', 8);
        $reports = $query->latest()->paginate($rows)->withQueryString();

        return view('reports.index', [
            'stats' => $stats,
            'reports' => $reports,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'rows']),
        ]);
    }
}