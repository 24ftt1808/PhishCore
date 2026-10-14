<?php

namespace App\Http\Controllers;

use App\Models\HiddenNumber;
use App\Models\NumberReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportedNumbersController extends Controller
{
    /**
     * Admin page for the public "Most reported phone numbers" list: see every number people have
     * reported, and hide one whose listing is wrong (for example a number that was reassigned).
     * Team members and administrators can see it, but only administrators can hide or show a number.
     */
    public function index(Request $request): View
    {
        $this->authorizeManage();

        $hidden = HiddenNumber::query()->with('hiddenByUser:id,name')->get()->keyBy('phone');

        $numbers = NumberReport::query()
            ->get()
            ->groupBy('phone')
            ->map(function ($rows, $phone) use ($hidden) {
                $counts = array_count_values($rows->pluck('category')->all());
                arsort($counts);

                return [
                    'phone' => (string) $phone,
                    'people' => $rows->pluck('user_id')->unique()->count(),
                    'category' => NumberReport::CATEGORIES[array_key_first($counts)] ?? NumberReport::CATEGORIES['other'],
                    'last' => $rows->max('created_at'),
                    'hidden' => $hidden->has($phone),
                    'hiddenBy' => $hidden->get($phone)?->hiddenByUser?->name,
                ];
            })
            ->sortByDesc(fn ($row) => $row['people'] * 10_000_000_000 + $row['last']->timestamp)
            ->values();

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $needle = NumberReport::key($search);
            $numbers = $numbers->filter(fn ($row) => str_contains($row['phone'], $needle))->values();
        }

        return view('reported-numbers.index', [
            'numbers' => $numbers,
            'stats' => [
                'numbers' => $numbers->count(),
                'listed' => $numbers->where('hidden', false)->where('people', '>=', 2)->count(),
                'hidden' => $numbers->where('hidden', true)->count(),
            ],
            'search' => $search,
            'canHide' => auth()->user()->role === 'admin',
        ]);
    }

    /** Take a number off the public lists. Administrators only, because it changes what scans warn about. */
    public function hide(Request $request): RedirectResponse
    {
        $this->authorizeHide();

        $phone = NumberReport::key((string) $request->validate(['phone' => ['required', 'string', 'max:40']])['phone']);

        HiddenNumber::firstOrCreate(['phone' => $phone], ['hidden_by' => $request->user()->id]);

        return back()->with('status', "{$phone} is hidden from the public lists.");
    }

    /** Put a hidden number back. */
    public function show(Request $request): RedirectResponse
    {
        $this->authorizeHide();

        $phone = NumberReport::key((string) $request->validate(['phone' => ['required', 'string', 'max:40']])['phone']);

        HiddenNumber::where('phone', $phone)->get()->each->delete();

        return back()->with('status', "{$phone} is listed again.");
    }

    private function authorizeHide(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can hide or show a reported number.');
    }

    private function authorizeManage(): void
    {
        $user = auth()->user();

        abort_unless($user->is_team_member || $user->role === 'admin', 403, 'Only team members and administrators can manage reported numbers.');
    }
}
