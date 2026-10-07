@php
    $verdictBadge = [
        'suspicious' => ['cls' => 'bg-amber-400/10 text-amber-300 border-amber-300/25', 'label' => 'SUSPICIOUS'],
        'phishing' => ['cls' => 'bg-rose-400/10 text-rose-300 border-rose-300/25', 'label' => 'PHISHING'],
    ];

    $currentStatus = $filters['status'] ?? 'all';
    $hasFilters = !empty($filters['search'] ?? '') || $currentStatus !== 'all' || !empty($filters['date_from'] ?? '') || !empty($filters['date_to'] ?? '');

    $typeIcons = [
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
        'url' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
    ];

    /* build each row once, render it as table row (desktop) and card (mobile) */
    $rows = [];
    foreach ($reports as $report) {
        $verdict = $report->analyses->first()->verdict ?? 'suspicious';
        $badge = $verdictBadge[$verdict] ?? $verdictBadge['suspicious'];

        $itemLabel = match ($report->type) {
            'email' => $report->sender_email,
            'phone' => $report->phone_number,
            'screenshot' => (function () use ($report) {
                $extraction = collect($report->analyses->first()?->flags ?? [])
                    ->firstWhere('name', 'Screenshot Text Extraction');
                if ($extraction && preg_match('/(?:URL(?:\s\(from QR code\))?|Sender|Phone number):\s*([^|]+)/', $extraction['message'], $matches)) {
                    return trim($matches[1]);
                }
                return 'Uploaded screenshot';
            })(),
            default => $report->url,
        };

        $rows[] = [
            'label' => $itemLabel,
            'icon' => $typeIcons[$report->type] ?? $typeIcons['url'],
            'badge' => $badge,
            'by' => $report->user?->name ? Str::before($report->user->name, ' ') : 'Anonymous',
            'date' => $report->created_at->format('Y-m-d H:i'),
        ];
    }
@endphp

<x-layouts.guest-landing>

    <section class="max-w-6xl mx-auto px-6 pt-8 md:pt-10 pb-16 md:pb-20">

        {{-- Header --}}
        <div class="mb-6 md:mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-5">
            <div>
                <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-sky-500/10 text-sky-300 border border-sky-400/20 mb-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> Community Transparency Feed
                </span>
                <h1 class="text-[1.7rem] md:text-3xl font-bold text-white mb-2">Public Threat Reports</h1>
                <p class="text-slate-400 text-sm max-w-2xl leading-relaxed">Confirmed suspicious and phishing submissions across the PhishCore community. Safe results are not shown here.</p>
            </div>

            <div class="shrink-0">
                <p class="text-[11px] tracking-[0.14em] text-slate-400 mb-2">DOWNLOAD PHISHING FEED</p>
                <div class="flex items-center gap-2">
                    @foreach (['csv' => 'CSV', 'json' => 'JSON', 'txt' => 'TXT'] as $ext => $label)
                        <a href="{{ route('reports.feed', ['format' => $ext]) }}"
                           @if ($ext === 'csv') download @endif
                           class="inline-flex items-center gap-1.5 text-xs font-medium px-3.5 py-2 rounded-xl bg-white/5 text-slate-200 border border-white/10 hover:bg-white/10 hover:border-sky-300/30 transition">
                            <svg class="w-3.5 h-3.5 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-500 mt-2 max-w-[17rem] leading-snug">Links only, no personal data. Automated verdicts can contain errors &mdash; verify before blocking.</p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6 md:mb-8">
            <div class="glass-card p-4 md:p-5 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 md:block">
                <div class="contents md:flex md:items-center md:justify-between md:mb-4">
                    <p class="col-start-2 row-start-1 max-md:min-h-[1.55rem] max-md:flex max-md:items-end text-[10px] md:text-[11px] tracking-[0.1em] md:tracking-[0.14em] leading-tight text-slate-400">TOTAL FLAGGED</p>
                    <div class="max-md:col-start-1 max-md:row-start-1 max-md:row-span-2 icon-tile !w-10 !h-10 md:!w-9 md:!h-9"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg></div>
                </div>
                @php $totalFlagged = $stats['suspicious'] + $stats['phishing']; @endphp
                <p class="col-start-2 row-start-2 mt-1 md:mt-0 text-[1.65rem] md:text-3xl whitespace-nowrap leading-none font-bold text-white tabular-nums" data-count="{{ $totalFlagged }}">{{ $totalFlagged }}</p>
            </div>

            <div class="glass-card p-4 md:p-5 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 md:block">
                <div class="contents md:flex md:items-center md:justify-between md:mb-4">
                    <p class="col-start-2 row-start-1 max-md:min-h-[1.55rem] max-md:flex max-md:items-end text-[10px] md:text-[11px] tracking-[0.1em] md:tracking-[0.14em] leading-tight text-slate-400">SUSPICIOUS</p>
                    <div class="max-md:col-start-1 max-md:row-start-1 max-md:row-span-2 icon-tile !w-10 !h-10 md:!w-9 md:!h-9 !bg-amber-400/10 !border-amber-300/20 !text-amber-300"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg></div>
                </div>
                <p class="col-start-2 row-start-2 mt-1 md:mt-0 text-[1.65rem] md:text-3xl whitespace-nowrap leading-none font-bold text-white tabular-nums" data-count="{{ $stats['suspicious'] }}">{{ $stats['suspicious'] }}</p>
            </div>

            <div class="glass-card p-4 md:p-5 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 md:block">
                <div class="contents md:flex md:items-center md:justify-between md:mb-4">
                    <p class="col-start-2 row-start-1 max-md:min-h-[1.55rem] max-md:flex max-md:items-end text-[10px] md:text-[11px] tracking-[0.1em] md:tracking-[0.14em] leading-tight text-slate-400">PHISHING</p>
                    <div class="max-md:col-start-1 max-md:row-start-1 max-md:row-span-2 icon-tile !w-10 !h-10 md:!w-9 md:!h-9 !bg-rose-400/10 !border-rose-300/20 !text-rose-300"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" /></svg></div>
                </div>
                <p class="col-start-2 row-start-2 mt-1 md:mt-0 text-[1.65rem] md:text-3xl whitespace-nowrap leading-none font-bold text-white tabular-nums" data-count="{{ $stats['phishing'] }}">{{ $stats['phishing'] }}</p>
            </div>

            <div class="glass-card p-4 md:p-5 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-3 md:block">
                <div class="contents md:flex md:items-center md:justify-between md:mb-4">
                    <p class="col-start-2 row-start-1 max-md:min-h-[1.55rem] max-md:flex max-md:items-end text-[10px] md:text-[11px] tracking-[0.1em] md:tracking-[0.14em] leading-tight text-slate-400">MOST RECENT</p>
                    <div class="max-md:col-start-1 max-md:row-start-1 max-md:row-span-2 icon-tile !w-10 !h-10 md:!w-9 md:!h-9 !bg-emerald-400/10 !border-emerald-300/20 !text-emerald-300"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                </div>
                <p class="col-start-2 row-start-2 mt-1 md:mt-0 text-[1.3rem] md:text-3xl whitespace-nowrap leading-none font-bold text-white">{{ $stats['latest']?->diffForHumans(short: true) ?? '—' }}</p>
            </div>
        </div>

        {{-- Trends --}}
        @php
            $typeMax = max(1, collect($trends['byType'])->max('count'));
            $domainMax = max(1, collect($trends['topDomains'])->max('count') ?? 1);
        @endphp
        <div class="grid lg:grid-cols-3 gap-3 md:gap-4 mb-6 md:mb-8">
            <div class="glass-panel rounded-2xl p-5 md:p-6 lg:col-span-2 flex flex-col">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-white font-semibold">Flagged reports per day</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Last 14 days &middot; {{ number_format($trends['windowTotal']) }} flagged</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-300">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#fb923c"></span>Suspicious</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background:#f87171"></span>Phishing</span>
                    </div>
                </div>
                <div class="relative flex-1 h-52 md:h-auto md:min-h-[15rem]">
                    <canvas id="trendChart" role="img" aria-label="Flagged reports per day over the last 14 days, split into suspicious and phishing"></canvas>
                </div>
                <table class="sr-only">
                    <caption>Flagged reports per day, last 14 days</caption>
                    <thead><tr><th>Date</th><th>Suspicious</th><th>Phishing</th></tr></thead>
                    <tbody>
                        @foreach ($trends['labels'] as $i => $label)
                            <tr><td>{{ $label }}</td><td>{{ $trends['suspicious'][$i] }}</td><td>{{ $trends['phishing'][$i] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 md:gap-4 md:grid-cols-2 lg:grid-cols-1">
                <div class="glass-panel rounded-2xl p-5 md:p-6">
                    <h2 class="text-white font-semibold mb-4">By scan type</h2>
                    <div class="space-y-3">
                        @foreach ($trends['byType'] as $row)
                            <div title="{{ $row['label'] }}: {{ $row['count'] }}">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-slate-300">{{ $row['label'] }}</span>
                                    <span class="text-slate-400 tabular-nums">{{ number_format($row['count']) }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-white/5 overflow-hidden">
                                    <div class="h-full rounded-full bg-sky-400" style="width: {{ $row['count'] > 0 ? max(4, round($row['count'] / $typeMax * 100)) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="glass-panel rounded-2xl p-5 md:p-6">
                    <h2 class="text-white font-semibold mb-4">Most reported domains</h2>
                    @if (count($trends['topDomains']))
                        <ol class="space-y-3">
                            @foreach ($trends['topDomains'] as $row)
                                <li title="{{ $row['domain'] }}: {{ $row['count'] }} report(s)">
                                    <div class="flex items-center justify-between gap-3 text-xs mb-1.5">
                                        <span class="text-slate-300 font-mono truncate">{{ $row['domain'] }}</span>
                                        <span class="text-slate-400 tabular-nums shrink-0">{{ $row['count'] }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-white/5 overflow-hidden">
                                        <div class="h-full rounded-full bg-rose-400" style="width: {{ max(6, round($row['count'] / $domainMax * 100)) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-xs text-slate-500">No flagged domains yet.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('reports.public') }}" class="glass-panel rounded-2xl p-4 md:p-5 mb-5 md:mb-6">
            <div class="flex flex-wrap gap-3 mb-4">
                <div class="relative w-full md:w-auto md:flex-1 md:min-w-[240px]">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search URL, email or phone"
                           class="field !pl-11 !py-3">
                </div>

                <div class="flex w-full md:w-auto gap-1 p-1 rounded-full bg-black/20 border border-white/5">
                    @foreach (['all' => 'All', 'suspicious' => 'Suspicious', 'phishing' => 'Phishing'] as $key => $label)
                        <button type="submit" name="status" value="{{ $key }}"
                                class="tab-btn flex-1 md:flex-none justify-center {{ $currentStatus === $key ? 'tab-btn--active' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-[10px] tracking-[0.14em] text-slate-500 mb-1.5">DATE FROM</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="field !py-2.5">
                </div>
                <div>
                    <label class="block text-[10px] tracking-[0.14em] text-slate-500 mb-1.5">DATE TO</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="field !py-2.5">
                </div>
                <div class="col-span-2 md:col-span-1 flex gap-2">
                    <button type="submit" class="btn-primary flex-1 !rounded-xl !py-2.5 !text-sm">Apply Filters</button>
                    <a href="{{ route('reports.public') }}" class="btn-ghost !rounded-xl !py-2.5 !px-4 !text-sm">Reset</a>
                </div>
            </div>
        </form>

        <div class="flex items-center justify-between mb-3 text-sm text-slate-500">
            <span>Showing <span class="text-slate-300">{{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }}</span> of <span class="text-slate-300">{{ $reports->total() }}</span> total reports</span>
        </div>

        @if (count($rows))
            {{-- Desktop table --}}
            <div class="hidden md:block glass-panel rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10 text-left text-[11px] tracking-[0.14em] text-slate-500 bg-white/[0.02]">
                            <th class="px-4 xl:px-6 py-4 font-medium">REPORTED ITEM</th>
                            <th class="px-4 xl:px-6 py-4 font-medium">SCAN RESULT</th>
                            <th class="px-4 xl:px-6 py-4 font-medium">SUBMITTED BY</th>
                            <th class="px-4 xl:px-6 py-4 font-medium">DATE &amp; TIME</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06]">
                        @foreach ($rows as $r)
                            <tr class="hover:bg-white/[0.035] transition-colors">
                                <td class="px-4 xl:px-6 py-4 max-w-[16rem] lg:max-w-md">
                                    <p title="{{ $r['label'] }}" class="text-slate-200 truncate flex items-center gap-3">
                                        <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}" /></svg>
                                        <span class="truncate font-mono text-[13px]">{{ $r['label'] }}</span>
                                    </p>
                                </td>
                                <td class="px-4 xl:px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full border {{ $r['badge']['cls'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $r['badge']['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 xl:px-6 py-4 text-slate-400">{{ $r['by'] }}</td>
                                <td class="px-4 xl:px-6 py-4 text-slate-400 tabular-nums"><span class="block xl:inline">{{ substr($r['date'], 0, 10) }}</span> <span class="block xl:inline text-slate-500 xl:text-slate-400 text-xs xl:text-sm">{{ substr($r['date'], 11) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden space-y-3">
                @foreach ($rows as $r)
                    <div class="glass-card p-4">
                        <p title="{{ $r['label'] }}" class="text-slate-200 flex items-center gap-2.5 mb-3">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}" /></svg>
                            <span class="truncate font-mono text-[13px]">{{ $r['label'] }}</span>
                        </p>
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium tracking-wide px-2.5 py-1 rounded-full border {{ $r['badge']['cls'] }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $r['badge']['label'] }}
                            </span>
                            <span class="text-xs text-slate-400 text-right">{{ $r['by'] }} &middot; {{ $r['date'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="glass-panel rounded-2xl px-6 py-16 text-center">
                <div class="icon-tile !w-12 !h-12 mx-auto mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                </div>
                <p class="text-white font-medium">No suspicious or phishing reports found</p>
                <p class="text-sm text-slate-500 mt-1">
                    @if ($hasFilters)
                        Nothing matches your filters. <a href="{{ route('reports.public') }}" class="text-sky-300 hover:text-sky-200">Reset them</a> to see everything.
                    @else
                        Flagged submissions from the community will appear here.
                    @endif
                </p>
            </div>
        @endif

        <div class="mt-5">
            {{ $reports->links() }}
        </div>

    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            document.querySelectorAll('[data-count]').forEach((el) => {
                const target = parseFloat(el.dataset.count) || 0;
                if (reduceMotion || target === 0) return;
                el.textContent = '0';
                const start = performance.now();
                const dur = 1100;
                const tick = (now) => {
                    const p = Math.min((now - start) / dur, 1);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
                    if (p < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
            });

            document.querySelectorAll('.glass-card').forEach((card) => {
                card.addEventListener('mousemove', (e) => {
                    const r = card.getBoundingClientRect();
                    card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                    card.style.setProperty('--my', (e.clientY - r.top) + 'px');
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('trendChart');
            if (!canvas || typeof Chart === 'undefined') return;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            Chart.defaults.font.family = "'Manrope', system-ui, sans-serif";
            Chart.defaults.color = '#cbd5e1';

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: @json($trends['labels']),
                    datasets: [
                        { label: 'Suspicious', data: @json($trends['suspicious']), backgroundColor: '#fb923c', borderRadius: 4, borderSkipped: false, maxBarThickness: 22 },
                        { label: 'Phishing', data: @json($trends['phishing']), backgroundColor: '#f87171', borderRadius: 4, borderSkipped: false, maxBarThickness: 22 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    animation: reduce ? false : { duration: 700 },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(8, 15, 34, .95)', borderColor: 'rgba(148, 163, 184, .25)', borderWidth: 1,
                            titleColor: '#fff', bodyColor: '#e2e8f0', padding: 10, cornerRadius: 10, boxPadding: 4
                        }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, border: { color: 'rgba(148,163,184,.25)' },
                             ticks: { color: '#cbd5e1', maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
                        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(148,163,184,.14)' }, border: { display: false },
                             ticks: { color: '#cbd5e1', precision: 0 } }
                    }
                }
            });
        });
    </script>

</x-layouts.guest-landing>  