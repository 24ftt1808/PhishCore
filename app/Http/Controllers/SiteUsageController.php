<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Administrator-only numbers on how many people use the site. Everything here is a count: visitors are
 * the anonymous daily codes from CountSiteVisit, never a name or an address.
 */
class SiteUsageController extends Controller
{
    /** How many days the chart and the table look back. */
    private const DAYS = 30;

    public function index(): View
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can see site usage.');

        $from = now()->subDays(self::DAYS - 1)->startOfDay();

        $visits = DB::table('site_visits')
            ->where('day', '>=', $from->toDateString())
            ->selectRaw('day, COUNT(*) as visitors, SUM(views) as views, SUM(CASE WHEN is_member THEN 1 ELSE 0 END) as members')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => substr((string) $row->day, 0, 10));

        $scans = Report::query()->where('status', 'completed')->where('created_at', '>=', $from)->get(['created_at', 'user_id'])
            ->groupBy(fn ($r) => $r->created_at->format('Y-m-d'));
        $signups = User::query()->where('created_at', '>=', $from)->get(['created_at'])
            ->groupBy(fn ($u) => $u->created_at->format('Y-m-d'));

        $days = [];
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $row = $visits->get($key);

            $days[] = [
                'date' => $key,
                'label' => $date->format('D j M'),
                'visitors' => (int) ($row->visitors ?? 0),
                'views' => (int) ($row->views ?? 0),
                'scans' => $scans->get($key, collect())->count(),
                'signups' => $signups->get($key, collect())->count(),
            ];
        }

        $last = fn (int $n, string $field) => collect($days)->take(-$n)->sum($field);

        $summary = [
            'visitors_today' => $last(1, 'visitors'),
            'visitors_7' => $last(7, 'visitors'),
            'visitors_30' => $last(30, 'visitors'),
            'views_7' => $last(7, 'views'),
            'scans_7' => $last(7, 'scans'),
            'scans_30' => $last(30, 'scans'),
            'guest_scans_30' => $scans->flatten(1)->whereNull('user_id')->count(),
            'signups_7' => $last(7, 'signups'),
            'signups_30' => $last(30, 'signups'),
            'users_total' => User::count(),
            'active_7' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            'active_30' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'tracking_since' => DB::table('site_visits')->min('day'),
        ];

        return view('usage.index', [
            'summary' => $summary,
            'days' => $days,
            'maxVisitors' => max(1, collect($days)->max('visitors')),
            'maxScans' => max(1, collect($days)->max('scans')),
        ]);
    }
}
