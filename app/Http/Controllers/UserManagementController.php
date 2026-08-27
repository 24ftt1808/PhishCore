<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * List all users with search, role/status filtering, and pagination.
     * Restricted to admins only — this manages platform-wide access control,
     * a level above Investigations/Reports which are team-member accessible.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can manage users.');

        $statsBase = fn () => User::query();
        $stats = [
            'total' => $statsBase()->count(),
            'active' => $statsBase()->whereNull('suspended_at')->count(),
            'admins' => $statsBase()->where('role', 'admin')->count(),
            'suspended' => $statsBase()->whereNotNull('suspended_at')->count(),
        ];

        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $role = $request->input('role', 'all');
        if ($role !== 'all') {
            $query->where('role', $role);
        }

        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->whereNull('suspended_at');
        } elseif ($status === 'suspended') {
            $query->whereNotNull('suspended_at');
        }

        

        $rows = (int) $request->input('rows', 8);
        $users = $query->orderBy('name')->paginate($rows)->withQueryString();

        return view('user-management.index', [
            'stats' => $stats,
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status', 'rows']),
        ]);
    }

    /**
     * Update a user's role, team-member flag, and department.
     * Prevents an admin from demoting themselves out of admin, which would
     * otherwise be an easy way to accidentally lock yourself out.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can manage users.');

        $validated = $request->validate([
            'role' => ['required', 'in:admin,user'],
            'is_team_member' => ['nullable', 'boolean'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return redirect()->route('user-management.index')
                ->with('error', 'You cannot remove your own administrator access.');
        }

        $user->update([
            'role' => $validated['role'],
            'is_team_member' => $request->boolean('is_team_member'),
            'department' => $validated['department'] ?? $user->department,
        ]);

        return redirect()->route('user-management.index')
            ->with('success', "Updated {$user->name}'s access.");
    }

    /**
     * Toggle a user's suspended status. Suspending immediately blocks future
     * logins (enforced in LoginRequest); it does not terminate an already
     * active session.
     */
    public function toggleSuspend(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can manage users.');

        if ($user->id === auth()->id()) {
            return redirect()->route('user-management.index')
                ->with('error', 'You cannot suspend your own account.');
        }

        $user->update([
            'suspended_at' => $user->isSuspended() ? null : now(),
        ]);

        $action = $user->fresh()->isSuspended() ? 'suspended' : 'reactivated';

        return redirect()->route('user-management.index')
            ->with('success', "{$user->name} has been {$action}.");
    }
}