@php
    $roleBadge = [
        'admin' => ['text' => 'text-sky-300', 'label' => 'ADMINISTRATOR'],
        'user' => ['text' => 'text-slate-300', 'label' => 'USER'],
    ];
    $currentRole = $filters['role'] ?? 'all';
    $currentStatus = $filters['status'] ?? 'all';

    $statCards = [
        ['label' => 'TOTAL USERS', 'value' => $stats['total'], 'href' => [], 'on' => $currentRole === 'all' && $currentStatus === 'all', 'rgb' => '56,189,248', 'text' => 'text-white', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['label' => 'ACTIVE USERS', 'value' => $stats['active'], 'href' => ['status' => 'active'], 'on' => $currentStatus === 'active', 'rgb' => '52,211,153', 'text' => 'text-emerald-400', 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'ADMINISTRATORS', 'value' => $stats['admins'], 'href' => ['role' => 'admin'], 'on' => $currentRole === 'admin', 'rgb' => '167,139,250', 'text' => 'text-violet-300', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
        ['label' => 'SUSPENDED USERS', 'value' => $stats['suspended'], 'href' => ['status' => 'suspended'], 'on' => $currentStatus === 'suspended', 'rgb' => '248,113,113', 'text' => 'text-red-400', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
    ];

    $statusChips = ['all' => 'All Statuses', 'active' => 'Active', 'suspended' => 'Suspended'];
    $statusCounts = ['all' => $stats['total'], 'active' => $stats['active'], 'suspended' => $stats['suspended']];
    $statusDots = ['all' => 'bg-sky-300', 'active' => 'bg-emerald-400', 'suspended' => 'bg-red-400'];
    $roleChips = ['all' => 'All Roles', 'admin' => 'Administrator', 'user' => 'User', 'team' => 'Team Member'];
    $roleCounts = ['all' => $stats['total'], 'admin' => $stats['admins'], 'user' => $stats['total'] - $stats['admins'], 'team' => $stats['team']];
@endphp

<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-field { background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .2); border-radius: .75rem; color: #e2e8f0; transition: border-color .15s; color-scheme: dark; }
        .h-field:focus { outline: none; border-color: #38bdf8; }
        .h-field:disabled { opacity: .45; cursor: not-allowed; }

        .h-stat { position: relative; overflow: hidden; display: block; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
        .h-stat::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(120% 140% at 100% 0%, rgba(var(--c), .26), transparent 62%); opacity: .75; transition: opacity .3s; }
        .h-stat:hover { transform: translateY(-2px); border-color: rgba(var(--c), .5); }
        .h-stat:hover::before { opacity: 1; }
        .h-stat-on { border-color: rgba(var(--c), .55); }
        .h-stat > * { position: relative; }
        .h-tile { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .8rem; background: rgba(var(--c), .14); border: 1px solid rgba(var(--c), .38); color: rgb(var(--c)); box-shadow: 0 0 18px -4px rgba(var(--c), .55); flex-shrink: 0; }

        .h-search { display: flex; align-items: center; gap: .5rem; height: 3.25rem; padding: 0 .5rem 0 1rem; border-radius: 1rem; background: rgba(8, 15, 32, .6); border: 1px solid rgba(148, 163, 184, .22); transition: border-color .2s, box-shadow .2s; }
        .h-search:focus-within { border-color: #38bdf8; box-shadow: 0 0 0 4px rgba(56, 189, 248, .14); }
        .h-search input { flex: 1; min-width: 0; background: transparent; border: 0; outline: 0; box-shadow: none; color: #f1f5f9; font-size: .9rem; }
        .h-search input::placeholder { color: #94a3b8; }
        .h-kbd { font-size: .7rem; color: #94a3b8; border: 1px solid rgba(148, 163, 184, .3); border-radius: .4rem; padding: .05rem .4rem; }

        .h-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 600; color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .22); background: rgba(8, 15, 32, .35); transition: background-color .15s, border-color .15s, color .15s; white-space: nowrap; }
        .h-chip:hover { color: #fff; border-color: rgba(148, 163, 184, .45); }
        .h-chip-on { color: #fff; background: rgba(56, 189, 248, .16); border-color: rgba(56, 189, 248, .5); }
        .h-chip-n { font-size: .7rem; font-weight: 700; color: #94a3b8; }
        .h-chip-on .h-chip-n { color: #bae6fd; }

        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }

        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem 1rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, opacity .15s; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .h-btn-main:hover { opacity: .92; }
        .h-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); }
        .h-btn-ghost:hover { background: rgba(255, 255, 255, .06); border-color: rgba(148, 163, 184, .5); }
        .h-btn-danger { color: #fca5a5; border: 1px solid rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .08); }
        .h-btn-danger:hover { background: rgba(248, 113, 113, .18); border-color: rgba(248, 113, 113, .6); }
        .h-btn-ok { color: #6ee7b7; border: 1px solid rgba(52, 211, 153, .4); background: rgba(52, 211, 153, .08); }
        .h-btn-ok:hover { background: rgba(52, 211, 153, .18); border-color: rgba(52, 211, 153, .6); }

        /* team switch: the knob is a pseudo-element so it actually moves when checked */
        .um-switch { position: relative; display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; }
        .um-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .um-track { position: relative; width: 2.25rem; height: 1.25rem; border-radius: 999px; background: rgba(148, 163, 184, .35); transition: background-color .2s; flex-shrink: 0; }
        .um-track::after { content: ""; position: absolute; top: .125rem; left: .125rem; width: 1rem; height: 1rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .35); transition: transform .2s cubic-bezier(.34, 1.4, .64, 1); }
        .um-switch input:checked + .um-track { background: #8b5cf6; }
        .um-switch input:checked + .um-track::after { transform: translateX(1rem); }
        .um-switch input:focus-visible + .um-track { outline: 2px solid #c4b5fd; outline-offset: 2px; }
        .um-switch-label { font-size: .75rem; font-weight: 600; color: #cbd5e1; transition: color .2s; }
        .um-switch input:checked ~ .um-switch-label { color: #c4b5fd; }

        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } .h-stat:hover { transform: none; } .um-track::after { transition: none; } }
    </style>

    <div class="h-in mb-6">
        <h1 class="text-2xl font-bold text-white mb-1">User Management</h1>
        <p class="text-slate-300 text-sm">Manage platform users, roles, and account access.</p>
    </div>

    @if (session('success'))
        <div class="h-card mb-6 text-sm text-emerald-300 px-4 py-3" style="border-color: rgba(52,211,153,.4)">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="h-card mb-6 text-sm text-red-300 px-4 py-3" style="border-color: rgba(248,113,113,.4)">
            {{ session('error') }}
        </div>
    @endif

    {{-- STATS (click to filter) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ($statCards as $card)
            <a href="{{ route('user-management.index', $card['href']) }}"
               class="h-card h-stat h-in p-4 sm:p-5 {{ $card['on'] ? 'h-stat-on' : '' }}"
               style="--c: {{ $card['rgb'] }}; --d: {{ $loop->index * 0.06 }}s">
                <div class="flex items-start justify-between gap-2 sm:gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] sm:text-xs font-medium sm:tracking-wide text-slate-300 mb-2">{{ $card['label'] }}</p>
                        <p class="text-3xl font-bold {{ $card['text'] }}">{{ $card['value'] }}</p>
                    </div>
                    <span class="h-tile shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- SEARCH + FILTERS --}}
    <form method="GET" action="{{ route('user-management.index') }}" class="h-card h-in p-4 sm:p-5 mb-6" style="--d:.14s"
          x-data
          @keydown.window="if ($event.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.q.focus() }">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        <input type="hidden" name="role" value="{{ $currentRole }}">
        @if (filled($filters['rows'] ?? null))
            <input type="hidden" name="rows" value="{{ $filters['rows'] }}">
        @endif

        <label class="h-search">
            <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
            <input x-ref="q" type="text" name="search" value="{{ $filters['search'] ?? '' }}" autocomplete="off"
                   placeholder="Search by name, email, or user ID">
            @if (filled($filters['search'] ?? null))
                <a href="{{ route('user-management.index', array_filter(['status' => $currentStatus === 'all' ? null : $currentStatus, 'role' => $currentRole === 'all' ? null : $currentRole])) }}" class="p-1.5 text-slate-400 hover:text-white transition-colors" title="Clear search">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </a>
            @else
                <span class="h-kbd hidden sm:inline">/</span>
            @endif
            <button type="submit" class="h-btn h-btn-main !py-2">Search</button>
        </label>

        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 mt-4">
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($roleChips as $key => $label)
                    <button type="submit" name="role" value="{{ $key }}" class="h-chip {{ $currentRole === $key ? 'h-chip-on' : '' }}">
                        {{ $label }}
                        <span class="h-chip-n">{{ $roleCounts[$key] }}</span>
                    </button>
                @endforeach
            </div>
            <span class="hidden sm:block w-px h-5 bg-slate-500/30"></span>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($statusChips as $key => $label)
                    <button type="submit" name="status" value="{{ $key }}" class="h-chip {{ $currentStatus === $key ? 'h-chip-on' : '' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDots[$key] }}"></span>
                        {{ $label }}
                        <span class="h-chip-n">{{ $statusCounts[$key] }}</span>
                    </button>
                @endforeach
            </div>
            @if (filled($filters['search'] ?? null) || $currentRole !== 'all' || $currentStatus !== 'all')
                <a href="{{ route('user-management.index') }}" class="text-xs font-semibold text-slate-300 hover:text-white underline-offset-2 hover:underline">Reset</a>
            @endif
        </div>
    </form>

    {{-- COUNT + ROWS --}}
    <div class="flex items-center justify-between flex-wrap gap-2 mb-3 text-sm text-slate-300">
        <span>Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} total users</span>
        <form method="GET" action="{{ route('user-management.index') }}" class="flex items-center gap-2">
            @foreach ($filters as $key => $value)
                @if ($key !== 'rows' && $value !== null)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <span class="text-xs font-medium tracking-wide">ROWS</span>
            <div class="relative">
                <select name="rows" onchange="this.form.submit()"
                        class="h-field appearance-none bg-none pl-3 pr-7 py-1.5 text-sm cursor-pointer">
                    @foreach ([8, 16, 32] as $n)
                        <option value="{{ $n }}" {{ ($filters['rows'] ?? 8) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <svg class="w-3.5 h-3.5 text-slate-300 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="h-card h-in overflow-hidden" style="--d:.2s">
        <div class="overflow-x-auto">
            <table class="block md:table w-full text-sm md:min-w-[920px]">
                <thead class="hidden md:table-header-group">
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-5 py-3 whitespace-nowrap">USER</th>
                        <th class="px-5 py-3 whitespace-nowrap">ROLE</th>
                        <th class="px-5 py-3 whitespace-nowrap">TEAM MEMBER</th>
                        <th class="px-5 py-3 whitespace-nowrap">ACCOUNT STATUS</th>
                        <th class="px-5 py-3 whitespace-nowrap">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="block md:table-row-group divide-y divide-slate-500/20">
                    @forelse ($users as $user)
                        @php
                            $badge = $roleBadge[$user->role] ?? $roleBadge['user'];
                            $isSelf = $user->id === auth()->id();
                            $userInitials = collect(explode(' ', $user->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                        @endphp
                        <tr class="h-row flex flex-wrap items-center gap-x-6 gap-y-3 p-4 md:p-0 md:table-row">
                            <td class="w-full md:px-5 md:py-3.5 md:max-w-0">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    @if ($user->photoUrl())
                                        <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="w-11 h-11 rounded-full object-cover shrink-0 border border-slate-400/30">
                                    @else
                                        <span class="w-11 h-11 rounded-full bg-sky-500 text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $userInitials }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-slate-100 font-medium truncate">{{ $user->name }}@if ($isSelf) <span class="ml-1.5 text-[10px] font-semibold tracking-wide text-slate-300 border border-slate-500/40 rounded px-1.5 py-0.5 align-middle">YOU</span>@endif</p>
                                        <p class="text-xs text-slate-300 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="md:px-5 md:py-3.5 whitespace-nowrap">
                                <span class="md:hidden mr-1.5 text-[10px] tracking-wider text-slate-400">ROLE</span><span class="text-xs font-semibold {{ $badge['text'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="md:px-5 md:py-3.5 whitespace-nowrap">
                                <span class="md:hidden mr-1.5 text-[10px] tracking-wider text-slate-400">TEAM</span>
                                @if ($user->is_team_member)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-violet-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> YES
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">No</span>
                                @endif
                            </td>
                            <td class="md:px-5 md:py-3.5 whitespace-nowrap">
                                @if ($user->isSuspended())
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> SUSPENDED
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> ACTIVE
                                    </span>
                                @endif
                            </td>
                            <td class="w-full md:w-auto md:px-5 md:py-3.5 pt-3 md:pt-3.5 border-t border-slate-500/20 md:border-0">
                                <div class="flex items-center gap-3 flex-wrap md:flex-nowrap">
                                    <form method="POST" action="{{ route('user-management.update', $user) }}" class="flex items-center gap-3">
                                        @csrf
                                        @method('PATCH')
                                        <div class="relative">
                                            <select name="role"
                                                    onchange="if (confirm({{ Js::from('Change ' . $user->name . '\'s role to ') }} + this.options[this.selectedIndex].text + '?')) { this.form.submit(); } else { this.value = {{ Js::from($user->role) }}; }"
                                                    {{ $isSelf ? 'disabled' : '' }}
                                                    class="h-field appearance-none bg-none pl-3 pr-8 py-1.5 text-xs font-semibold cursor-pointer">
                                                <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                                <option value="user" @selected($user->role === 'user')>User</option>
                                            </select>
                                            <svg class="w-3 h-3 text-slate-300 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                        </div>

                                        <label class="um-switch {{ $isSelf ? 'opacity-40 pointer-events-none' : '' }}">
                                            <input type="checkbox" name="is_team_member" value="1"
                                                   onchange="if (confirm((this.checked ? 'Add ' : 'Remove ') + {{ Js::from($user->name) }} + (this.checked ? ' to' : ' from') + ' the investigation team?')) { this.form.submit(); } else { this.checked = !this.checked; }"
                                                   {{ $user->is_team_member ? 'checked' : '' }}>
                                            <span class="um-track"></span>
                                            <span class="um-switch-label">Team</span>
                                        </label>
                                    </form>

                                    @if (! $isSelf)
                                        <form method="POST" action="{{ route('user-management.toggle-suspend', $user) }}"
                                              onsubmit="return confirm({{ Js::from(($user->isSuspended() ? 'Reactivate ' : 'Suspend ') . $user->name . '\'s account?') }});">
                                            @csrf
                                            <button type="submit" class="h-btn !py-1.5 !px-3 !text-xs whitespace-nowrap {{ $user->isSuspended() ? 'h-btn-ok' : 'h-btn-danger' }}">
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
                            <td colspan="5" class="px-5 py-10 text-center text-slate-300">
                                No users found. Try adjusting your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</x-layouts.dashboard>