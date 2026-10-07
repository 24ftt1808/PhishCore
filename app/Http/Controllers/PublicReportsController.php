<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $trends = $this->buildTrends();

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
            'trends' => $trends,
            'reports' => $reports,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'rows']),
        ]);
    }
    /**
     * Aggregates for the public "Trends" section: flagged reports per day
     * (last 14 days, split by verdict), flagged reports by scan type, and
     * the most frequently reported domains. Only suspicious/phishing
     * reports are counted — the same population the public feed shows —
     * and only the domain is exposed, never who submitted it.
     */
    private function buildTrends(): array
    {
        $flagged = Report::with('analyses:id,report_id,verdict')
            ->where('status', 'completed')
            ->whereHas('analyses', fn ($q) => $q->whereIn('verdict', ['suspicious', 'phishing']))
            ->get(['id', 'type', 'url', 'sender_email', 'created_at']);

        $days = 14;
        $labels = [];
        $suspicious = [];
        $phishing = [];
        $byDate = $flagged->groupBy(fn ($r) => $r->created_at->format('Y-m-d'));

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('j M');
            $rows = $byDate->get($key, collect());
            $suspicious[] = $rows->filter(fn ($r) => $r->analyses->first()?->verdict === 'suspicious')->count();
            $phishing[] = $rows->filter(fn ($r) => $r->analyses->first()?->verdict === 'phishing')->count();
        }

        $typeLabels = ['url' => 'Links', 'email' => 'Emails', 'phone' => 'Phone numbers', 'screenshot' => 'Screenshots'];
        $byType = [];
        foreach ($typeLabels as $key => $label) {
            $byType[] = ['label' => $label, 'count' => $flagged->where('type', $key)->count()];
        }

        $topDomains = $flagged
            ->map(function ($r) {
                if ($r->url) {
                    $host = parse_url($r->url, PHP_URL_HOST);

                    return $host ? Str::of($host)->lower()->replaceMatches('/^www\\./', '')->toString() : null;
                }
                if ($r->sender_email && str_contains($r->sender_email, '@')) {
                    return strtolower(substr(strrchr($r->sender_email, '@'), 1));
                }

                return null;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $domain) => ['domain' => $domain, 'count' => $count])
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'suspicious' => $suspicious,
            'phishing' => $phishing,
            'windowTotal' => array_sum($suspicious) + array_sum($phishing),
            'byType' => $byType,
            'topDomains' => $topDomains,
        ];
    }
}