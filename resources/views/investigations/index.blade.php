@php
    $statusBadge = [
        'active' => ['text' => 'text-sky-300', 'label' => 'ACTIVE'],
        'completed' => ['text' => 'text-emerald-400', 'label' => 'COMPLETED'],
        'takedown_requested' => ['text' => 'text-orange-400', 'label' => 'TAKEDOWN REQUESTED'],
        'takedown_confirmed' => ['text' => 'text-emerald-400', 'label' => 'TAKEDOWN CONFIRMED'],
    ];
    $typeIcons = [
        'url' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];
    $currentStatus = $filters['status'] ?? 'all';

    $statCards = [
        ['label' => 'TOTAL CASES', 'value' => $stats['total'], 'status' => 'all', 'rgb' => '167,139,250', 'text' => 'text-white', 'icon' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'],
        ['label' => 'ACTIVE', 'value' => $stats['active'], 'status' => 'active', 'rgb' => '56,189,248', 'text' => 'text-sky-300', 'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'],
        ['label' => 'TAKEDOWN REQUESTED', 'value' => $stats['takedown_requested'], 'status' => 'takedown_requested', 'rgb' => '251,146,60', 'text' => 'text-orange-400', 'icon' => 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5'],
        ['label' => 'TAKEDOWN CONFIRMED', 'value' => $stats['takedown_confirmed'], 'status' => 'takedown_confirmed', 'rgb' => '52,211,153', 'text' => 'text-emerald-400', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
        ['label' => 'COMPLETED', 'value' => $stats['completed'], 'status' => 'completed', 'rgb' => '148,163,184', 'text' => 'text-slate-200', 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    $statusChips = ['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'takedown_requested' => 'Requested', 'takedown_confirmed' => 'Confirmed'];
    $chipCounts = ['all' => $stats['total'], 'active' => $stats['active'], 'completed' => $stats['completed'], 'takedown_requested' => $stats['takedown_requested'], 'takedown_confirmed' => $stats['takedown_confirmed']];
    $chipDots = ['all' => 'bg-violet-300', 'active' => 'bg-sky-300', 'completed' => 'bg-emerald-400', 'takedown_requested' => 'bg-orange-400', 'takedown_confirmed' => 'bg-emerald-400'];
@endphp

<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-field { background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .2); border-radius: .75rem; color: #e2e8f0; transition: border-color .15s; color-scheme: dark; }
        .h-field:focus { outline: none; border-color: #a78bfa; }

        .h-stat { position: relative; overflow: hidden; display: block; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
        .h-stat::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(120% 140% at 100% 0%, rgba(var(--c), .26), transparent 62%); opacity: .75; transition: opacity .3s; }
        .h-stat:hover { transform: translateY(-2px); border-color: rgba(var(--c), .5); }
        .h-stat:hover::before { opacity: 1; }
        .h-stat-on { border-color: rgba(var(--c), .55); }
        .h-stat > * { position: relative; }
        .h-tile { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .8rem; background: rgba(var(--c), .14); border: 1px solid rgba(var(--c), .38); color: rgb(var(--c)); box-shadow: 0 0 18px -4px rgba(var(--c), .55); flex-shrink: 0; }

        .h-search { display: flex; align-items: center; gap: .5rem; height: 3.25rem; padding: 0 .5rem 0 1rem; border-radius: 1rem; background: rgba(8, 15, 32, .6); border: 1px solid rgba(148, 163, 184, .22); transition: border-color .2s, box-shadow .2s; }
        .h-search:focus-within { border-color: #a78bfa; box-shadow: 0 0 0 4px rgba(167, 139, 250, .16); }
        .h-search input { flex: 1; min-width: 0; background: transparent; border: 0; outline: 0; box-shadow: none; color: #f1f5f9; font-size: .9rem; }
        .h-search input::placeholder { color: #94a3b8; }
        .h-kbd { font-size: .7rem; color: #94a3b8; border: 1px solid rgba(148, 163, 184, .3); border-radius: .4rem; padding: .05rem .4rem; }

        .h-chip { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 600; color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .22); background: rgba(8, 15, 32, .35); transition: background-color .15s, border-color .15s, color .15s; white-space: nowrap; }
        .h-chip:hover { color: #fff; border-color: rgba(148, 163, 184, .45); }
        .h-chip-on { color: #fff; background: rgba(167, 139, 250, .18); border-color: rgba(167, 139, 250, .55); }
        .h-chip-n { font-size: .7rem; font-weight: 700; color: #94a3b8; }
        .h-chip-on .h-chip-n { color: #ddd6fe; }

        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(167, 139, 250, .07); }
        .h-url { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size: .8125rem; letter-spacing: -.01em; }

        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem 1rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, opacity .15s; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
        .h-btn-main:hover { opacity: .92; }
        .h-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); }
        .h-btn-ghost:hover { background: rgba(255, 255, 255, .06); border-color: rgba(148, 163, 184, .5); }

        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } .h-stat:hover { transform: none; } }
    </style>

    <div class="h-in mb-6">
        <h1 class="text-2xl font-bold text-white mb-1">Investigations</h1>
        <p class="text-slate-300 text-sm">Track and manage takedown progress across all reported threats.</p>
    </div>

    {{-- STATS (click to filter) --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        @foreach ($statCards as $card)
            <a href="{{ route('investigations.index', array_filter(['status' => $card['status'] === 'all' ? null : $card['status']])) }}"
               class="h-card h-stat h-in p-5 {{ $loop->first ? 'col-span-2 lg:col-span-1' : '' }} {{ $currentStatus === $card['status'] ? 'h-stat-on' : '' }}"
               style="--c: {{ $card['rgb'] }}; --d: {{ $loop->index * 0.06 }}s">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-2">{{ $card['label'] }}</p>
                        <p class="text-3xl font-bold {{ $card['text'] }}">{{ $card['value'] }}</p>
                    </div>
                    <span class="h-tile">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- SEARCH + STATUS --}}
    <form method="GET" action="{{ route('investigations.index') }}" class="h-card h-in p-4 sm:p-5 mb-6" style="--d:.14s"
          x-data
          @keydown.window="if ($event.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.q.focus() }">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        @if (filled($filters['rows'] ?? null))
            <input type="hidden" name="rows" value="{{ $filters['rows'] }}">
        @endif

        <label class="h-search">
            <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
            <input x-ref="q" type="text" name="search" value="{{ $filters['search'] ?? '' }}" autocomplete="off"
                   placeholder="Search by URL, email, or phone number">
            @if (filled($filters['search'] ?? null))
                <a href="{{ route('investigations.index', array_filter(['status' => $currentStatus === 'all' ? null : $currentStatus])) }}" class="p-1.5 text-slate-400 hover:text-white transition-colors" title="Clear search">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </a>
            @else
                <span class="h-kbd hidden sm:inline">/</span>
            @endif
            <button type="submit" class="h-btn h-btn-main !py-2">Search</button>
        </label>

        <div class="flex flex-wrap items-center gap-2 mt-4">
            @foreach ($statusChips as $key => $label)
                <button type="submit" name="status" value="{{ $key }}" class="h-chip {{ $currentStatus === $key ? 'h-chip-on' : '' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $chipDots[$key] }}"></span>
                    {{ $label }}
                    <span class="h-chip-n">{{ $chipCounts[$key] }}</span>
                </button>
            @endforeach
            @if (filled($filters['search'] ?? null) || $currentStatus !== 'all')
                <a href="{{ route('investigations.index') }}" class="ml-1 text-xs font-semibold text-slate-300 hover:text-white underline-offset-2 hover:underline">Reset</a>
            @endif
        </div>
    </form>

    {{-- COUNT + ROWS --}}
    <div class="flex items-center justify-between flex-wrap gap-2 mb-3 text-sm text-slate-300">
        <span>Showing {{ $investigations->firstItem() ?? 0 }}–{{ $investigations->lastItem() ?? 0 }} of {{ $investigations->total() }} total cases</span>
        <form method="GET" action="{{ route('investigations.index') }}" class="flex items-center gap-2">
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

    {{-- TABLE (desktop) --}}
    <div class="h-card h-in overflow-hidden hidden md:block" style="--d:.2s">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[820px]">
                <thead>
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-5 py-3">REPORTED ITEM</th>
                        <th class="px-5 py-3">STATUS</th>
                        <th class="px-5 py-3">ASSIGNED TO</th>
                        <th class="px-5 py-3">OPENED</th>
                        <th class="px-5 py-3">RESOLVED</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-500/20">
                    @forelse ($investigations as $investigation)
                        @php
                            $report = $investigation->report;
                            $badge = $statusBadge[$investigation->status] ?? $statusBadge['active'];
                            $itemLabel = match ($report->type) {
                                'email' => $report->sender_email,
                                'phone' => $report->phone_number,
                                'screenshot' => 'Uploaded screenshot',
                                default => $report->url,
                            };
                            $isMono = in_array($report->type, ['url', 'email', 'phone'], true);
                            $scheme = '';
                            $rest = (string) $itemLabel;
                            if ($report->type === 'url' && preg_match('#^(https?://)(.*)$#i', $rest, $m)) {
                                [$scheme, $rest] = [$m[1], $m[2]];
                            }
                            $iconPath = $typeIcons[$report->type] ?? $typeIcons['url'];
                            $assignee = $investigation->assignedUser?->name;
                            $assigneePhoto = $investigation->assignedUser?->photoUrl();
                            $assigneeInitials = $assignee ? collect(explode(' ', $assignee))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') : null;
                        @endphp
                        <tr class="h-row">
                            <td class="px-5 py-4 w-full max-w-0">
                                <p class="flex items-center gap-2.5 min-w-0" title="{{ $itemLabel }}">
                                    <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                                    <span class="truncate {{ $isMono ? 'h-url' : '' }} text-slate-100">@if ($scheme)<span class="text-slate-400">{{ $scheme }}</span>@endif{{ $rest }}</span>
                                </p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $badge['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($assignee)
                                    <span class="flex items-center gap-2 text-slate-100">
                                        @if ($assigneePhoto)
                                            <img src="{{ $assigneePhoto }}" alt="{{ $assignee }}" class="w-6 h-6 rounded-full object-cover border border-violet-400/40">
                                        @else
                                            <span class="w-6 h-6 rounded-full bg-violet-500/25 border border-violet-400/40 text-violet-100 text-[10px] font-bold grid place-items-center">{{ $assigneeInitials }}</span>
                                        @endif
                                        {{ $assignee }}
                                    </span>
                                @else
                                    <span class="flex items-center gap-2 text-slate-400">
                                        <span class="w-6 h-6 rounded-full border border-dashed border-slate-500"></span>
                                        Unassigned
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-300 whitespace-nowrap">{{ $investigation->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-5 py-4 text-slate-300 whitespace-nowrap">{{ $investigation->resolved_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('scan.show', $report) }}" class="h-btn h-btn-ghost !py-1.5 !px-3 !text-xs whitespace-nowrap">
                                    View Case <span aria-hidden="true">→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-300">
                                No investigations found. Try adjusting your filters, or open one from a scan result page.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CARDS (mobile) --}}
    <div class="md:hidden space-y-3">
        @forelse ($investigations as $investigation)
            @php
                $report = $investigation->report;
                $badge = $statusBadge[$investigation->status] ?? $statusBadge['active'];
                $itemLabel = match ($report->type) {
                    'email' => $report->sender_email,
                    'phone' => $report->phone_number,
                    'screenshot' => 'Uploaded screenshot',
                    default => $report->url,
                };
                $isMono = in_array($report->type, ['url', 'email', 'phone'], true);
                $iconPath = $typeIcons[$report->type] ?? $typeIcons['url'];
            @endphp
            <a href="{{ route('scan.show', $report) }}" class="h-card h-in block p-4" style="--d: {{ min($loop->index, 6) * 0.04 }}s">
                <p class="text-slate-100 text-sm flex items-start gap-2 min-w-0 mb-3">
                    <svg class="w-4 h-4 text-slate-300 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                    <span class="break-all {{ $isMono ? 'h-url' : '' }}">{{ $itemLabel }}</span>
                </p>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $badge['text'] }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                    </span>
                    <span class="flex items-center gap-2 text-xs text-slate-300">
                        @if ($investigation->assignedUser?->photoUrl())
                            <img src="{{ $investigation->assignedUser->photoUrl() }}" alt="" class="w-5 h-5 rounded-full object-cover">
                        @endif
                        {{ $investigation->assignedUser?->name ?? 'Unassigned' }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-300">
                    <span>Opened {{ $investigation->created_at->format('Y-m-d H:i') }}</span>
                    <span>{{ $investigation->resolved_at ? 'Resolved ' . $investigation->resolved_at->format('Y-m-d') : 'Unresolved' }}</span>
                </div>
            </a>
        @empty
            <div class="h-card p-8 text-center text-sm text-slate-300">
                No investigations found. Try adjusting your filters, or open one from a scan result page.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $investigations->links() }}
    </div>
</x-layouts.dashboard>