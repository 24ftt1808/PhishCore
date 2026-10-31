<?php

namespace App\Http\Controllers;

use App\Models\LookalikeDomain;
use App\Models\LookalikeWatchRun;
use App\Services\LookalikeWatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Team page for the Look-alike Watch: new websites whose names copy a Brunei brand. Team members and administrators
 * can see the list, start a check, scan a site (through the normal scan) and dismiss a site that is genuine.
 */
class LookalikeWatchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTeam();

        $show = $request->input('show') === 'dismissed' ? 'dismissed' : 'new';
        $domains = LookalikeDomain::query()
            ->with('dismissedByUser:id,name')
            ->where('status', $show)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('lookalike-watch.index', [
            'domains' => $domains,
            'scans' => $domains->getCollection()->mapWithKeys(fn (LookalikeDomain $d) => [$d->id => $d->latestScan()]),
            'show' => $show,
            'stats' => [
                'new' => LookalikeDomain::where('status', 'new')->count(),
                'week' => LookalikeDomain::where('status', 'new')->where('created_at', '>=', now()->subDays(7))->count(),
                'dismissed' => LookalikeDomain::where('status', 'dismissed')->count(),
            ],
            'lastRun' => LookalikeWatchRun::latest('id')->first(),
            'lastGoodRun' => LookalikeWatchRun::where('ok', true)->latest('id')->first(),
        ]);
    }

    /**
     * Start a check now. crt.sh can take a minute or two, longer than a page may wait, so the check runs after the
     * page has been sent and the person refreshes to see the results.
     */
    public function check(): RedirectResponse
    {
        $this->authorizeTeam();

        dispatch(function () {
            set_time_limit(600);
            app(LookalikeWatch::class)->run();
        })->afterResponse();

        return redirect()->route('lookalike-watch.index')
            ->with('status', 'Check started. The certificate logs can take a minute or two to answer: refresh this page shortly.');
    }

    public function dismiss(LookalikeDomain $lookalikeDomain): RedirectResponse
    {
        $this->authorizeTeam();
        $lookalikeDomain->update(['status' => 'dismissed', 'dismissed_by' => auth()->id()]);

        return back()->with('status', "{$lookalikeDomain->domain} was dismissed. It will not be listed again.");
    }

    public function restore(LookalikeDomain $lookalikeDomain): RedirectResponse
    {
        $this->authorizeTeam();
        $lookalikeDomain->update(['status' => 'new', 'dismissed_by' => null]);

        return back()->with('status', "{$lookalikeDomain->domain} is back on the list.");
    }

    private function authorizeTeam(): void
    {
        $user = auth()->user();

        abort_unless($user->is_team_member || $user->role === 'admin', 403, 'Only team members and administrators can use the Look-alike Watch.');
    }
}