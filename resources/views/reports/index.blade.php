@php
    $verdictBadge = [
        'clean' => ['text' => 'text-emerald-400', 'label' => 'SAFE'],
        'suspicious' => ['text' => 'text-orange-400', 'label' => 'SUSPICIOUS'],
        'phishing' => ['text' => 'text-red-400', 'label' => 'PHISHING'],
        'review' => ['text' => 'text-sky-300', 'label' => 'REVIEW'],
    ];
    $scoreBarColor = [
        'clean' => 'bg-emerald-400',
        'suspicious' => 'bg-orange-400',
        'phishing' => 'bg-red-400',
        'review' => 'bg-sky-300',
    ];
    $typeIcons = [
        'url' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];
    $invStatusStyles = [
        'active' => ['text' => 'text-sky-300', 'label' => 'ACTIVE'],
        'completed' => ['text' => 'text-emerald-400', 'label' => 'COMPLETED'],
        'takedown_requested' => ['text' => 'text-orange-400', 'label' => 'REQUESTED'],
        'takedown_confirmed' => ['text' => 'text-emerald-400', 'label' => 'CONFIRMED'],
    ];
    $currentStatus = $filters['status'] ?? 'all';

    $statCards = [
        ['label' => 'TOTAL REPORTS', 'short' => 'Total', 'value' => $stats['total'], 'status' => 'all', 'rgb' => '56,189,248', 'text' => 'text-white', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['label' => 'SAFE REPORTS', 'short' => 'Safe', 'value' => $stats['safe'], 'status' => 'safe', 'rgb' => '52,211,153', 'text' => 'text-emerald-400', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
        ['label' => 'SUSPICIOUS REPORTS', 'short' => 'Suspicious', 'value' => $stats['suspicious'], 'status' => 'suspicious', 'rgb' => '251,146,60', 'text' => 'text-orange-400', 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        ['label' => 'PHISHING REPORTS', 'short' => 'Phishing', 'value' => $stats['phishing'], 'status' => 'phishing', 'rgb' => '248,113,113', 'text' => 'text-red-400', 'icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z'],
    ];

    $activeAdvanced = collect(['date_from', 'date_to'])->filter(fn ($key) => filled($filters[$key] ?? null))->count();
    $statusChips = ['all' => 'All Results', 'safe' => 'Safe', 'suspicious' => 'Suspicious', 'phishing' => 'Phishing'];
    $chipCounts = ['all' => $stats['total'], 'safe' => $stats['safe'], 'suspicious' => $stats['suspicious'], 'phishing' => $stats['phishing']];
    $chipDots = ['all' => 'bg-sky-300', 'safe' => 'bg-emerald-400', 'suspicious' => 'bg-orange-400', 'phishing' => 'bg-red-400'];
@endphp

<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-field { background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .2); border-radius: .75rem; color: #e2e8f0; transition: border-color .15s; color-scheme: dark; }
        .h-field::placeholder { color: #94a3b8; }
        .h-field:focus { outline: none; border-color: #38bdf8; }

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
        .h-chip { flex-shrink: 0; white-space: nowrap; }
        .um-scroll { scrollbar-width: none; }
        .um-scroll::-webkit-scrollbar { display: none; }
        @media (max-width: 639px) {
            .h-tile { width: 2.1rem; height: 2.1rem; border-radius: .65rem; }
            .h-tile svg { width: 1rem; height: 1rem; }
            .h-chip { padding: .38rem .7rem; font-size: .75rem; }
        }
        .h-chip:hover { color: #fff; border-color: rgba(148, 163, 184, .45); }
        .h-chip-on { color: #fff; background: rgba(56, 189, 248, .16); border-color: rgba(56, 189, 248, .5); }
        .h-chip-n { font-size: .7rem; font-weight: 700; color: #94a3b8; }
        .h-chip-on .h-chip-n { color: #bae6fd; }

        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }
        .h-url { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size: .8125rem; letter-spacing: -.01em; }

        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem 1rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, opacity .15s; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .h-btn-main:hover { opacity: .92; }
        .h-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); }
        .h-btn-ghost:hover { background: rgba(255, 255, 255, .06); border-color: rgba(148, 163, 184, .5); }

        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } .h-stat:hover { transform: none; } }
    </style>

    <div class="h-in mb-6 md:max-xl:landscape:mb-4">
        <h1 class="text-2xl font-bold text-white mb-1">Reports</h1>
        <p class="text-slate-300 text-sm">Browse every report submitted across the platform.</p>
    </div>

    {{-- STATS (click to filter) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 md:max-lg:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6 md:max-xl:landscape:mb-4">
        @foreach ($statCards as $card)
            <a href="{{ route('reports.index', array_filter(['status' => $card['status'] === 'all' ? null : $card['status']])) }}"
               class="h-card h-stat h-in p-3.5 sm:p-5 {{ $currentStatus === $card['status'] ? 'h-stat-on' : '' }}"
               style="--c: {{ $card['rgb'] }}; --d: {{ $loop->index * 0.06 }}s">
                <div class="flex items-center sm:items-start justify-between gap-2 sm:gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] sm:text-xs font-medium tracking-wide text-slate-300 mb-0.5 sm:mb-2"><span class="sm:hidden">{{ strtoupper($card['short']) }}</span><span class="hidden sm:inline">{{ $card['label'] }}</span></p>
                        <p class="text-2xl sm:text-3xl font-bold leading-none sm:leading-normal {{ $card['text'] }}">{{ $card['value'] }}</p>
                    </div>
                    <span class="h-tile shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- SEARCH + FILTERS --}}
    <form method="GET" action="{{ route('reports.index') }}" class="h-card h-in p-4 sm:p-5 mb-6 md:max-xl:landscape:p-3.5 md:max-xl:landscape:mb-4" style="--d:.12s"
          x-data="{ adv: {{ $activeAdvanced > 0 ? 'true' : 'false' }} }"
          @keydown.window="if ($event.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.q.focus() }">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        @if (filled($filters['rows'] ?? null))
            <input type="hidden" name="rows" value="{{ $filters['rows'] }}">
        @endif

        <div class="flex gap-2 sm:gap-3">
            <label class="h-search flex-1 min-w-0">
                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                <input x-ref="q" type="text" name="search" value="{{ $filters['search'] ?? '' }}" autocomplete="off"
                       placeholder="Search reports">
                @if (filled($filters['search'] ?? null))
                    <a href="{{ route('reports.index', array_filter(['status' => $currentStatus === 'all' ? null : $currentStatus])) }}" class="p-1.5 text-slate-400 hover:text-white transition-colors" title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </a>
                @else
                    <span class="h-kbd hidden sm:inline">/</span>
                @endif
                <button type="submit" class="h-btn h-btn-main !py-2">Search</button>
            </label>

            <button type="button" @click="adv = !adv" class="h-btn h-btn-ghost !h-[3.25rem] !rounded-2xl shrink-0 max-sm:!px-0 max-sm:!w-[3.25rem]" aria-label="Date range" :class="adv ? 'bg-white/5' : ''">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                <span class="hidden sm:inline">Date range</span>
                @if ($activeAdvanced > 0)
                    <span class="min-w-[1.25rem] h-5 px-1 rounded-full bg-sky-400 text-slate-900 text-[11px] font-bold grid place-items-center">{{ $activeAdvanced }}</span>
                @endif
            </button>
        </div>

        <div class="um-scroll flex items-center gap-2 mt-3.5 sm:mt-4 overflow-x-auto sm:overflow-visible sm:flex-wrap -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach ($statusChips as $key => $label)
                <button type="submit" name="status" value="{{ $key }}" class="h-chip {{ $currentStatus === $key ? 'h-chip-on' : '' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $chipDots[$key] }}"></span>
                    {{ $label }}
                    <span class="h-chip-n">{{ $chipCounts[$key] }}</span>
                </button>
            @endforeach
            @if (filled($filters['search'] ?? null) || $currentStatus !== 'all' || $activeAdvanced > 0)
                <a href="{{ route('reports.index') }}" class="ml-1 shrink-0 text-xs font-semibold text-slate-300 hover:text-white underline-offset-2 hover:underline">Reset</a>
            @endif
        </div>

        <div x-show="adv" x-collapse x-cloak>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end pt-4 mt-4 border-t border-slate-500/25">
                <div>
                    <label class="block text-[11px] font-medium tracking-wide text-slate-300 mb-1">DATE FROM</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="h-field w-full px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-medium tracking-wide text-slate-300 mb-1">DATE TO</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="h-field w-full px-3 py-2 text-sm">
                </div>
                <div class="col-span-2 md:col-span-1">
                    <button type="submit" class="h-btn h-btn-main w-full">Apply</button>
                </div>
            </div>
        </div>
    </form>

    {{-- COUNT + ROWS --}}
    <div class="flex items-center justify-between gap-2 mb-3 text-sm text-slate-300">
        <span><span class="hidden sm:inline">Showing </span>{{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }}<span class="hidden sm:inline"> total</span> reports</span>
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
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
    <div class="h-card h-in overflow-hidden hidden md:block" style="--d:.18s">
        <div class="overflow-x-auto">
            <table class="w-full text-sm xl:min-w-[900px]">
                <thead>
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-3 xl:px-5 py-3">REPORTED ITEM</th>
                        <th class="px-3 xl:px-5 py-3">SCAN RESULT</th>
                        <th class="px-3 xl:px-5 py-3">RISK SCORE</th>
                        <th class="px-3 xl:px-5 py-3 md:max-xl:hidden">SUBMITTED BY</th>
                        <th class="px-3 xl:px-5 py-3 md:max-xl:hidden">INVESTIGATION</th>
                        <th class="px-3 xl:px-5 py-3">DATE &amp; TIME</th>
                        <th class="px-3 xl:px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-500/20">
                    @forelse ($reports as $report)
                        @php
                            $verdict = $report->analyses->first()->verdict ?? 'clean';
                            $score = $report->analyses->first()->risk_score ?? 0;
                            $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                            $barColor = $scoreBarColor[$verdict] ?? $scoreBarColor['clean'];
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
                            $submitter = $report->user?->name;
                            $submitterPhoto = $report->user?->photoUrl();
                            $submitterInitials = $submitter ? collect(explode(' ', $submitter))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') : null;
                        @endphp
                        <tr class="h-row">
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5 w-full max-w-0 md:max-xl:min-w-[10rem]">
                                <p class="flex items-center gap-2.5 min-w-0" title="{{ $itemLabel }}">
                                    <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                                    <span class="truncate {{ $isMono ? 'h-url' : '' }} text-slate-100">@if ($scheme)<span class="text-slate-400">{{ $scheme }}</span>@endif{{ $rest }}</span>
                                </p>
                                <p class="hidden md:max-xl:flex items-center gap-1.5 mt-1 pl-[1.65rem] text-xs text-slate-400 min-w-0">
                                    @if ($submitterPhoto)
                                        <img src="{{ $submitterPhoto }}" alt="" class="w-4 h-4 rounded-full object-cover border border-sky-400/40 shrink-0">
                                    @else
                                        <span class="w-4 h-4 rounded-full border border-dashed border-slate-500 shrink-0"></span>
                                    @endif
                                    <span class="truncate">{{ $submitter ?? 'Guest' }}</span>
                                </p>
                            </td>
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $badge['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                                </span>
                                @if ($report->investigation)
                                    @php $invTab = $invStatusStyles[$report->investigation->status] ?? $invStatusStyles['active']; @endphp
                                    <span class="hidden md:max-xl:block mt-1 text-[11px] font-semibold {{ $invTab['text'] }}">Case · {{ $invTab['label'] }}</span>
                                @endif
                            </td>
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="font-semibold w-7 {{ $badge['text'] }}">{{ $score }}</span>
                                    <span class="w-12 xl:w-20 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <span class="block h-full rounded-full {{ $barColor }}" style="width: {{ $score }}%"></span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5 whitespace-nowrap md:max-xl:hidden">
                                @if ($submitter)
                                    <span class="flex items-center gap-2 text-slate-100">
                                        @if ($submitterPhoto)
                                            <img src="{{ $submitterPhoto }}" alt="{{ $submitter }}" class="w-6 h-6 rounded-full object-cover border border-sky-400/40">
                                        @else
                                            <span class="w-6 h-6 rounded-full bg-sky-500/25 border border-sky-400/40 text-sky-100 text-[10px] font-bold grid place-items-center">{{ $submitterInitials }}</span>
                                        @endif
                                        {{ $submitter }}
                                    </span>
                                @else
                                    <span class="flex items-center gap-2 text-slate-400">
                                        <span class="w-6 h-6 rounded-full border border-dashed border-slate-500"></span>
                                        Guest
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5 whitespace-nowrap md:max-xl:hidden">
                                @if ($report->investigation)
                                    @php $invBadge = $invStatusStyles[$report->investigation->status] ?? $invStatusStyles['active']; @endphp
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $invBadge['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $invBadge['label'] }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5 text-slate-300 whitespace-nowrap">
                                <span class="block xl:inline">{{ $report->created_at->format('Y-m-d') }}</span>
                                <span class="block xl:inline text-xs xl:text-sm text-slate-400 xl:text-slate-300">{{ $report->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-3 xl:px-5 py-4 md:max-xl:landscape:py-2.5 text-right">
                                <a href="{{ route('scan.show', $report) }}" class="h-btn h-btn-ghost !py-1.5 !px-3 !text-xs whitespace-nowrap">
                                    View<span class="hidden xl:inline"> Details</span> <span aria-hidden="true">→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-300">
                                No reports found. Try adjusting your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CARDS (mobile) --}}
    <div class="md:hidden space-y-3">
        @forelse ($reports as $report)
            @php
                $verdict = $report->analyses->first()->verdict ?? 'clean';
                $score = $report->analyses->first()->risk_score ?? 0;
                $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                $barColor = $scoreBarColor[$verdict] ?? $scoreBarColor['clean'];
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
                <div class="mb-3">
                    <p class="text-slate-100 text-sm flex items-start gap-2 min-w-0">
                        <svg class="w-4 h-4 text-slate-300 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                        <span class="break-all {{ $isMono ? 'h-url' : '' }}">{{ $itemLabel }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="font-semibold text-sm {{ $badge['text'] }}">{{ $score }}</span>
                    <span class="flex-1 h-1.5 rounded-full bg-white/10 overflow-hidden">
                        <span class="block h-full rounded-full {{ $barColor }}" style="width: {{ $score }}%"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold shrink-0 {{ $badge['text'] }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-3 text-xs text-slate-300">
                    <span class="flex items-center gap-2 min-w-0">
                        @if ($report->user?->photoUrl())
                            <img src="{{ $report->user->photoUrl() }}" alt="" class="w-5 h-5 rounded-full object-cover">
                        @endif
                        <span class="truncate">{{ $report->user?->name ?? 'Guest' }}</span>
                    </span>
                    <span class="shrink-0">{{ $report->created_at->format('Y-m-d H:i') }}</span>
                </div>
            </a>
        @empty
            <div class="h-card p-8 text-center text-sm text-slate-300">
                No reports found. Try adjusting your filters.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $reports->links() }}
    </div>
</x-layouts.dashboard>