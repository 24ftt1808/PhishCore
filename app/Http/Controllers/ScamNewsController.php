<?php

namespace App\Http\Controllers;

use App\Models\ScamNews;
use App\Services\ScamNewsFetcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScamNewsController extends Controller
{
    /**
     * Admin page for the dashboard's "Latest Scam News" box: see what was fetched, add an article by
     * hand, hide or delete one, and fetch now. Restricted to team members and administrators.
     */
    public function index(Request $request): View
    {
        $this->authorizeManage();

        $stats = [
            'total' => ScamNews::count(),
            'visible' => ScamNews::visible()->count(),
            'hidden' => ScamNews::where('is_visible', false)->count(),
            'manual' => ScamNews::where('is_manual', true)->count(),
        ];

        $query = ScamNews::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $filter = $request->input('filter', 'all');
        if ($filter === 'visible') {
            $query->where('is_visible', true);
        } elseif ($filter === 'hidden') {
            $query->where('is_visible', false);
        } elseif ($filter === 'manual') {
            $query->where('is_manual', true);
        }

        $rows = in_array((int) $request->input('rows', 10), [10, 20, 40], true) ? (int) $request->input('rows', 10) : 10;
        $articles = $query->latest('published_at')->latest('id')->paginate($rows)->withQueryString();

        // The articles the dashboard box really shows right now: the newest visible ones.
        $onDashboard = ScamNews::visible()
            ->latest('published_at')->latest('id')
            ->limit((int) config('scamnews.display'))
            ->pluck('id')
            ->all();

        return view('scam-news.index', [
            'stats' => $stats,
            'articles' => $articles,
            'onDashboard' => $onDashboard,
            'filters' => $request->only(['search', 'filter', 'rows']),
            'feeds' => (array) config('scamnews.feeds', []),
            'lastRun' => ScamNewsFetcher::lastRun(),
            'display' => (int) config('scamnews.display'),
        ]);
    }

    /** Add an article by hand. These are never deleted by the daily clean-up. */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'source' => ['required', 'string', 'max:120'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'published_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $hash = ScamNews::hashFor($validated['url']);

        if (ScamNews::where('url_hash', $hash)->exists()) {
            return back()->withInput()->withErrors(['url' => 'This article is already in the list.']);
        }

        ScamNews::create([
            'title' => trim($validated['title']),
            'url' => trim($validated['url']),
            'url_hash' => $hash,
            'source' => trim($validated['source']),
            'image_url' => $validated['image_url'] ?? null,
            'published_at' => $validated['published_at'] ?? now(),
            'is_visible' => true,
            'is_manual' => true,
        ]);

        return redirect()->route('scam-news.index')->with('success', 'Article added. It shows on the dashboard right away.');
    }

    /** Hide an article from the dashboard, or show it again. */
    public function toggle(ScamNews $scamNews): RedirectResponse
    {
        $this->authorizeManage();

        $scamNews->update(['is_visible' => ! $scamNews->is_visible]);

        return back()->with('success', $scamNews->is_visible
            ? 'Article is visible on the dashboard again.'
            : 'Article hidden from the dashboard.');
    }

    /**
     * Delete an article. An article that came from a feed can come back on the next fetch while it is
     * still in that feed; hiding it keeps it off the dashboard for good.
     */
    public function destroy(ScamNews $scamNews): RedirectResponse
    {
        $this->authorizeManage();

        $scamNews->delete();

        return back()->with('success', 'Article deleted.');
    }

    /** Run the same fetch as the daily schedule, right now. */
    public function fetch(ScamNewsFetcher $fetcher): RedirectResponse
    {
        $this->authorizeManage();

        $summary = $fetcher->run();
        $failed = count(array_filter($summary['feeds'], fn (array $feed) => ! $feed['ok']));

        if ($summary['feeds'] === []) {
            return back()->with('error', 'No feeds are set up. Add some in config/scamnews.php.');
        }

        if ($failed === count($summary['feeds'])) {
            return back()->with('error', 'Could not read any of the feeds. Check the feed list below for the reason.');
        }

        $message = "Fetched {$summary['added']} new article(s).";
        if ($failed > 0) {
            $message .= " {$failed} feed(s) could not be read; see the feed list below.";
        }

        return back()->with('success', $message);
    }

    private function authorizeManage(): void
    {
        $user = auth()->user();

        abort_unless($user->is_team_member || $user->role === 'admin', 403, 'Only team members and administrators can manage scam news.');
    }
}
