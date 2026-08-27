@php
    $roleBadge = [
        'admin' => ['bg' => 'bg-sky-500/10', 'text' => 'text-sky-400', 'label' => 'ADMINISTRATOR'],
        'user' => ['bg' => 'bg-slate-500/10', 'text' => 'text-slate-400', 'label' => 'USER'],
    ];
@endphp

<x-layouts.dashboard>

    <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">User Management</h1>
            <p class="text-slate-400 text-sm">Manage platform users, roles, and account access.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 text-sm text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-sky-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">TOTAL USERS</p>
            <p class="relative text-3xl font-bold text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-emerald-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">ACTIVE USERS</p>
            <p class="relative text-3xl font-bold text-emerald-400">{{ $stats['active'] }}</p>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-sky-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">ADMINISTRATORS</p>
            <p class="relative text-3xl font-bold text-sky-400">{{ $stats['admins'] }}</p>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-red-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">SUSPENDED USERS</p>
            <p class="relative text-3xl font-bold text-red-400">{{ $stats['suspended'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('user-management.index') }}" class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5 mb-6">
        <div class="flex flex-wrap gap-3 mb-4">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Search by name, email, or user ID"
                   class="flex-1 min-w-[240px] bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">

            <select name="role" onchange="this.form.submit()"
                    class="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                @foreach (['all' => 'All Roles', 'admin' => 'Administrator', 'user' => 'User'] as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['role'] ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()"
                    class="bg-slate-950 border border-slate-800 rounded-lg px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                @foreach (['all' => 'All Statuses', 'active' => 'Active', 'suspended' => 'Suspended'] as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['status'] ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>


        </div>

        <div class="flex gap-2">
            <button type="submit" class="py-2 px-5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition">
                Apply Filters
            </button>
            <a href="{{ route('user-management.index') }}" class="px-3 py-2 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition">
                Reset
            </a>
        </div>
    </form>

    <div class="flex items-center justify-between mb-3 text-sm text-slate-500">
        <span>Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} total users</span>
        <form method="GET" action="{{ route('user-management.index') }}" class="flex items-center gap-2">
            @foreach ($filters as $key => $value)
                @if ($key !== 'rows' && $value !== null)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <span>ROWS</span>
            <div class="relative">
                <select name="rows" onchange="this.form.submit()"
                        class="appearance-none bg-none bg-slate-900 border border-slate-800 rounded-lg pl-3 pr-7 py-1.5 text-slate-200 text-sm focus:outline-none focus:border-sky-500 transition cursor-pointer">
                    @foreach ([8, 16, 32] as $n)
                        <option value="{{ $n }}" {{ ($filters['rows'] ?? 8) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <svg class="w-3.5 h-3.5 text-slate-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead>
                <tr class="border-b border-slate-800 text-left text-xs tracking-wide text-slate-500">
                    <th class="px-5 py-3">USER</th>
                    <th class="px-5 py-3">ROLE</th>
                                     <th class="px-5 py-3">TEAM MEMBER</th>
                    <th class="px-5 py-3">ACCOUNT STATUS</th>
                    <th class="px-5 py-3">ACTIONS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($users as $user)
                    @php $badge = $roleBadge[$user->role] ?? $roleBadge['user']; @endphp
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                                             @if ($user->photoUrl())
                                    <img src="{{ $user->photoUrl() }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                                @else
                                    <span class="w-8 h-8 rounded-full bg-sky-500 text-white text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ collect(explode(' ', $user->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-slate-200 truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            @if ($user->is_team_member)
                                <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full bg-violet-500/10 text-violet-400">YES</span>
                            @else
                                <span class="text-xs text-slate-600">No</span>
                            @endif
                        </td>
                                              <td class="px-5 py-4">
                            @if ($user->isSuspended())
                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-red-500/10 text-red-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> SUSPENDED
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> ACTIVE
                                </span>
                            @endif
                        </td>
                                           <td class="px-5 py-4">
                            <div class="flex items-center gap-3 flex-wrap">
                                <form method="POST" action="{{ route('user-management.update', $user) }}" class="flex items-center gap-3">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" onchange="if (confirm('Change {{ $user->name }}\'s role to ' + this.options[this.selectedIndex].text + '?')) { this.form.submit(); } else { this.value = '{{ $user->role }}'; }" {{ $user->id === auth()->id() ? 'disabled' : '' }}
                                            class="appearance-none bg-slate-950 border border-slate-800 rounded-lg pl-3 pr-7 py-1.5 text-xs font-medium text-slate-200 focus:outline-none focus:border-sky-500/60 hover:border-slate-700 transition disabled:opacity-40 cursor-pointer bg-no-repeat bg-[right_0.5rem_center]"
                                            style="background-image: url('data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22%3E%3Cpath d=%22M19 9l-7 7-7-7%22/%3E%3C/svg%3E');">
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                        <option value="user" @selected($user->role === 'user')>User</option>
                                    </select>

                                    <label class="relative inline-flex items-center gap-2 cursor-pointer group {{ $user->id === auth()->id() ? 'opacity-40 pointer-events-none' : '' }}">
                                        <input type="checkbox" name="is_team_member" value="1"
                                               onchange="if (confirm((this.checked ? 'Add ' : 'Remove ') + '{{ $user->name }}' + (this.checked ? ' to' : ' from') + ' the investigation team?')) { this.form.submit(); } else { this.checked = !this.checked; }"
                                               {{ $user->is_team_member ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <span class="relative inline-block w-9 h-5 shrink-0 rounded-full bg-slate-700 peer-checked:bg-violet-500 transition-colors" style="min-width: 2.25rem;">
                                            <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                                        </span>
                                        <span class="text-xs font-medium text-slate-400 peer-checked:text-violet-400 transition-colors">Team</span>
                                    </label>
                                </form>

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('user-management.toggle-suspend', $user) }}"
                                          onsubmit="return confirm('{{ $user->isSuspended() ? 'Reactivate' : 'Suspend' }} {{ $user->name }}\'s account?');">
                                        @csrf
                                        <button type="submit"
                                                class="appearance-none outline-none ring-0 focus:outline-none focus:ring-0 focus-visible:outline-none flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ $user->isSuspended() ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 hover:border-emerald-500/60 hover:shadow-[0_0_12px_-2px_rgba(52,211,153,0.5)]' : 'border-red-500/40 bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:border-red-500/60 hover:shadow-[0_0_12px_-2px_rgba(248,113,113,0.5)]' }}">
                                            @if ($user->isSuspended())
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                Reactivate
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                Suspend
                                            @endif
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                            No users found. Try adjusting your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

</x-layouts.dashboard>