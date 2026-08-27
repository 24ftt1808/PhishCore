@php
    $investigation = $report->investigation;
    $isTeamMember = auth()->user()->is_team_member;

    $invStatusStyles = [
        'active' => ['badge' => 'bg-sky-500/10 text-sky-400 border-sky-500/20', 'label' => 'ACTIVE'],
        'completed' => ['badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'label' => 'COMPLETED'],
        'takedown_requested' => ['badge' => 'bg-orange-500/10 text-orange-400 border-orange-500/20', 'label' => 'TAKEDOWN REQUESTED'],
        'takedown_confirmed' => ['badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'label' => 'TAKEDOWN CONFIRMED'],
    ];
@endphp

@if (!$isTeamMember)
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8 mt-6">
        <div class="flex items-center justify-between flex-wrap gap-3 {{ !$investigation ? 'mb-5' : '' }}">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-white">Investigation Status</h2>
                    <p class="text-sm text-slate-500">
                        {{ $investigation ? 'This report is being tracked by our team.' : 'Think this needs closer attention? Let our team know.' }}
                    </p>
                </div>
            </div>
            @if ($investigation)
                <span class="text-xs font-medium px-3 py-1.5 rounded-full border {{ $invStatusStyles[$investigation->status]['badge'] }}">
                    {{ $invStatusStyles[$investigation->status]['label'] }}
                </span>
            @endif
        </div>

        @if (!$investigation)
            @if (session('success'))
                <div class="mb-4 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 text-sm text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
                    {{ session('error') }}
                </div>
            @endif
            <form method="POST" action="{{ route('investigations.request', $report) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-500 mb-1.5">NOTES (OPTIONAL)</label>
                    <textarea name="notes" rows="2" placeholder="Anything you'd like our team to know about this report..."
                        class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-300 placeholder:text-slate-600 focus:outline-none focus:border-violet-500/50"></textarea>
                </div>
                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-violet-500 to-purple-600 text-white text-sm font-semibold hover:opacity-90 transition">
                    Request Investigation
                </button>
            </form>
        @endif
    </div>
@else
    {{-- FULL MANAGEMENT VIEW for team members --}}
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8 mt-6">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-white">Investigation</h2>
                    <p class="text-sm text-slate-500">Track takedown progress for this report.</p>
                </div>
            </div>
            @if ($investigation)
                <span class="text-xs font-medium px-3 py-1.5 rounded-full border {{ $invStatusStyles[$investigation->status]['badge'] }}">
                    {{ $invStatusStyles[$investigation->status]['label'] }}
                </span>
            @endif
        </div>

        @if (session('success'))
            <div class="mb-4 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 text-sm text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
                {{ session('error') }}
            </div>
        @endif

        @if (!$investigation)
            <form method="POST" action="{{ route('investigations.store', $report) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-500 mb-1.5">ASSIGN TO (OPTIONAL)</label>
                    <select name="assigned_to"
                        class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-violet-500/50">
                        <option value="">Unassigned</option>
                        @foreach (\App\Models\User::where('is_team_member', true)->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1.5">NOTES (OPTIONAL)</label>
                    <textarea name="notes" rows="2" placeholder="Add any initial notes about this case..."
                        class="w-full bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-300 placeholder:text-slate-600 focus:outline-none focus:border-violet-500/50"></textarea>
                </div>
                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-violet-500 to-purple-600 text-white text-sm font-semibold hover:opacity-90 transition">
                    Open Investigation
                </button>
            </form>
        @else
            <dl class="grid sm:grid-cols-2 gap-4 text-sm mb-5">
                <div>
                    <dt class="text-xs text-slate-500 mb-1">ASSIGNED TO</dt>
                    <dd class="text-slate-300">{{ $investigation->assignedUser?->name ?? 'Unassigned' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 mb-1">OPENED</dt>
                    <dd class="text-slate-300">{{ $investigation->created_at->format('j F Y \a\t g:i A') }}</dd>
                </div>
                @if ($investigation->resolved_at)
                    <div>
                        <dt class="text-xs text-slate-500 mb-1">RESOLVED</dt>
                        <dd class="text-slate-300">{{ $investigation->resolved_at->format('j F Y \a\t g:i A') }}</dd>
                    </div>
                @endif
                @if ($investigation->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500 mb-1">NOTES</dt>
                        <dd class="text-slate-300">{{ $investigation->notes }}</dd>
                    </div>
                @endif
            </dl>

            <form method="POST" action="{{ route('investigations.update', $investigation) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs text-slate-500 mb-1.5">UPDATE STATUS</label>
                    <select name="status"
                        class="bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-violet-500/50">
                        @foreach (['active', 'completed', 'takedown_requested', 'takedown_confirmed'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($investigation->status === $statusOption)>
                                {{ $invStatusStyles[$statusOption]['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1.5">REASSIGN TO</label>
                    <select name="assigned_to"
                        class="bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-300 focus:outline-none focus:border-violet-500/50">
                        <option value="">Unassigned</option>
                        @foreach (\App\Models\User::where('is_team_member', true)->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" @selected($investigation->assigned_to === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-violet-500 to-purple-600 text-white text-sm font-semibold hover:opacity-90 transition">
                    Update
                </button>
            </form>
        @endif
    </div>
@endif