<?php

namespace App\Http\Controllers;

use App\Models\Investigation;
use App\Models\InvestigationStatusLog;
use App\Models\Report;
use App\Models\User;
use App\Notifications\InvestigationNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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

               $investigation = Investigation::create([
            'report_id' => $report->id,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        InvestigationStatusLog::create([
            'investigation_id' => $investigation->id,
            'status' => 'active',
            'changed_by' => auth()->id(),
        ]);

        if ($investigation->assigned_to) {
            $this->tellAssignee($investigation, null);
        } else {
            // Nobody owns it yet, so tell the rest of the team and the admins that it needs someone.
            Notification::send($this->teamAndAdmins(), new InvestigationNotice($investigation->load('report'), InvestigationNotice::UNASSIGNED));
        }

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

              $investigation = Investigation::create([
            'report_id' => $report->id,
            'requested_by' => auth()->id(),
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        InvestigationStatusLog::create([
            'investigation_id' => $investigation->id,
            'status' => 'active',
            'changed_by' => auth()->id(),
        ]);

        // Tell the team and the admins (everyone active, except the person who asked) so it shows in their bell.
        Notification::send($this->teamAndAdmins(), new InvestigationNotice($investigation->load('report'), InvestigationNotice::REQUESTED));

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

        $statusChanged = $investigation->status !== $validated['status'];
        $previousAssignee = $investigation->assigned_to;

        $investigation->update([
            'status' => $validated['status'],
            'assigned_to' => $request->filled('assigned_to') ? $validated['assigned_to'] : null,
            'notes' => $validated['notes'] ?? $investigation->notes,
            'resolved_at' => $resolvedAt,
        ]);

        // Only log an actual status TRANSITION, not every save — otherwise
        // re-saving the same status (e.g. just updating notes) would create
        // a misleading duplicate entry in the timeline showing no real change.
        if ($statusChanged) {
            InvestigationStatusLog::create([
                'investigation_id' => $investigation->id,
                'status' => $validated['status'],
                'changed_by' => auth()->id(),
            ]);
        }

        // Tell the person it was just handed to.
        $this->tellAssignee($investigation, $previousAssignee);

        // Tell the person who asked for the investigation when its status really changes.
        if ($statusChanged && $investigation->requested_by && (int) $investigation->requested_by !== (int) auth()->id()) {
            $investigation->requestedBy?->notify(new InvestigationNotice($investigation->load('report'), InvestigationNotice::STATUS_CHANGED));
        }

        return redirect()->route('scan.show', $investigation->report)
            ->with('success', 'Investigation updated.');
    }

    /**
     * Tell whoever the investigation is now assigned to, but only when it is a new person,
     * not the one making the change, and not a suspended account.
     */
    private function tellAssignee(Investigation $investigation, ?int $previousAssignee): void
    {
        $assigneeId = $investigation->assigned_to;

        if (! $assigneeId || (int) $assigneeId === (int) $previousAssignee || (int) $assigneeId === (int) auth()->id()) {
            return;
        }

        $assignee = User::whereNull('suspended_at')->find($assigneeId);

        $assignee?->notify(new InvestigationNotice($investigation->load('report'), InvestigationNotice::ASSIGNED));
    }

    /** Every active team member and admin, except the person making this change. */
    private function teamAndAdmins()
    {
        return User::whereNull('suspended_at')
            ->where(fn ($q) => $q->where('is_team_member', true)->orWhere('role', 'admin'))
            ->where('id', '!=', auth()->id())
            ->get();
    }
}