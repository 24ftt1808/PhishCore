<?php

namespace App\Http\Controllers;

use App\Models\Investigation;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestigationController extends Controller
{
    /**
     * List all investigations with search, status filtering, and pagination.
     * Restricted to team members only — this is an internal ops view.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->is_team_member, 403, 'Only team members can view investigations.');

        $statsBase = fn () => Investigation::query();
        $stats = [
            'total' => $statsBase()->count(),
            'active' => $statsBase()->where('status', 'active')->count(),
            'completed' => $statsBase()->where('status', 'completed')->count(),
            'takedown_requested' => $statsBase()->where('status', 'takedown_requested')->count(),
            'takedown_confirmed' => $statsBase()->where('status', 'takedown_confirmed')->count(),
        ];

        $query = Investigation::with(['report', 'assignedUser']);

        if ($search = $request->input('search')) {
            $query->whereHas('report', function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('sender_email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $rows = (int) $request->input('rows', 8);
        $investigations = $query->latest()->paginate($rows)->withQueryString();

        return view('investigations.index', [
            'stats' => $stats,
            'investigations' => $investigations,
            'filters' => $request->only(['search', 'status', 'rows']),
        ]);
    }

    /**
     * Create a new investigation for a report.
     * Restricted to team members only. A report can only ever have one
     * investigation (enforced by a unique constraint on report_id).
     */
    public function store(Request $request, Report $report): RedirectResponse
    {
        abort_unless(auth()->user()->is_team_member, 403, 'Only team members can open investigations.');

        if ($report->investigation) {
            return redirect()->route('scan.show', $report)
                ->with('error', 'This report already has an investigation.');
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Investigation::create([
            'report_id' => $report->id,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('scan.show', $report)
            ->with('success', 'Investigation opened for this report.');
    }

        /**
     * Allow a regular (non-team) user to request an investigation on their
     * own report. Creates the investigation immediately so it surfaces on
     * the team's /investigations queue, but the requester gets no
     * management controls afterward — only team members can act on it.
     */
    public function request(Request $request, Report $report): RedirectResponse
    {
        if ($report->investigation) {
            return redirect()->route('scan.show', $report)
                ->with('error', 'This report is already being tracked.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Investigation::create([
            'report_id' => $report->id,
            'requested_by' => auth()->id(),
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('scan.show', $report)
            ->with('success', 'Investigation requested. Our team will review this report.');
    }

    /**
     * Update an existing investigation's status, assignee, or notes.
     * Restricted to team members only.
     */
    public function update(Request $request, Investigation $investigation): RedirectResponse
    {
        abort_unless(auth()->user()->is_team_member, 403, 'Only team members can update investigations.');

        $validated = $request->validate([
            'status' => ['required', 'in:active,completed,takedown_requested,takedown_confirmed'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $resolvedAt = in_array($validated['status'], ['completed', 'takedown_confirmed'], true)
            ? ($investigation->resolved_at ?? now())
            : null;

        $investigation->update([
            'status' => $validated['status'],
            'assigned_to' => $request->filled('assigned_to') ? $validated['assigned_to'] : null,
            'notes' => $validated['notes'] ?? $investigation->notes,
            'resolved_at' => $resolvedAt,
        ]);

        return redirect()->route('scan.show', $investigation->report)
            ->with('success', 'Investigation updated.');
    }
}