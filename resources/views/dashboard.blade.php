@php
    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-400/10 border-emerald-400/25', 'text' => 'text-emerald-300', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-400/10 border-orange-400/25', 'text' => 'text-orange-300', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-400/10 border-red-400/25', 'text' => 'text-red-300', 'label' => 'PHISHING'],
        'review' => ['bg' => 'bg-sky-400/10 border-sky-400/25', 'text' => 'text-sky-300', 'label' => 'REVIEW'],
    ];
    $scoreColor = [
        'clean' => ['text' => 'text-emerald-300', 'bar' => 'bg-emerald-400'],
        'suspicious' => ['text' => 'text-orange-300', 'bar' => 'bg-orange-400'],
        'phishing' => ['text' => 'text-red-300', 'bar' => 'bg-red-400'],
        'review' => ['text' => 'text-sky-300', 'bar' => 'bg-sky-400'],
    ];
    $typeIcons = [
        'url' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5zM10.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];

    $total = max((int) $stats['total'], 0);
    $pct = fn ($n) => $total > 0 ? (int) round(((int) $n) / $total * 100) : 0;
    $weekSum = (int) $weekTotals['safe'] + (int) $weekTotals['suspicious'] + (int) $weekTotals['phishing'];

    $cards = [
        ['key' => 'total',      'title' => 'TOTAL REPORTS',      'tag' => 'SUBMITTED',        'sub' => 'URLs, emails, phone numbers & screenshots', 'rgb' => '56,189,248',  'num' => 'text-white',        'tile' => 'text-sky-300',     'bar' => null,             'icon' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18'],
        ['key' => 'safe',       'title' => 'SAFE REPORTS',       'tag' => 'VERIFIED',         'sub' => 'No threats detected',                       'rgb' => '52,211,153',  'num' => 'text-emerald-300',  'tile' => 'text-emerald-300', 'bar' => 'bg-emerald-400', 'icon' => 'M4.5 12.75l6 6 9-13.5'],
        ['key' => 'suspicious', 'title' => 'SUSPICIOUS REPORTS', 'tag' => 'NEEDS REVIEW',     'sub' => 'Potential threats',                         'rgb' => '251,146,60',  'num' => 'text-orange-300',   'tile' => 'text-orange-300',  'bar' => 'bg-orange-400',  'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
        ['key' => 'phishing',   'title' => 'PHISHING REPORTS',   'tag' => 'THREATS DETECTED', 'sub' => 'Confirmed malicious',                      'rgb' => '248,113,113', 'num' => 'text-red-300',      'tile' => 'text-red-300',     'bar' => 'bg-red-400',     'icon' => 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    $actions = [
        ['href' => route('scan.index'),   'title' => 'Run New Scan', 'sub' => 'URL, email, phone, or screenshot', 'icon' => 'M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z'],
        ['href' => route('scan.history'), 'title' => 'Scan History', 'sub' => $totalScansCount . ' recent records',  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['href' => route('analytics'),    'title' => 'Analytics',    'sub' => 'Charts & insights',                   'icon' => 'M3 13.5l3.75-3.75 3 3 4.5-4.5M3 19.5h18'],
        auth()->user()->is_team_member
            ? ['href' => route('reports.index'), 'title' => 'Reports',  'sub' => 'Platform-wide reports', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z']
            : ['href' => route('profile.edit'),  'title' => 'Settings', 'sub' => 'Manage your account',   'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z'],
    ];
@endphp

<x-layouts.dashboard>

    <style>
        /* light panels: gradient + hairline border, no blur filters, so the page stays smooth */
        .d-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .d-lift { position: relative; transition: transform .3s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
        .d-lift:hover { transform: translateY(-2px); border-color: rgba(var(--c, 56, 189, 248), .4); }

        .d-tile { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: .8rem; background: rgba(var(--c), .12); border: 1px solid rgba(var(--c), .3); }

        .d-in { animation: d-in .6s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes d-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .d-grow { transform-origin: left; animation: d-grow .9s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, .3s); }
        @keyframes d-grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }

        .d-row { transition: background-color .2s; }
        .d-url { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size: .8125rem; letter-spacing: -.01em; }
        .d-view { display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .8rem; border-radius: .7rem; font-size: .75rem; font-weight: 600; color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); transition: background-color .15s, border-color .15s; }
        .d-scroll { scrollbar-width: none; }
        .d-scroll::-webkit-scrollbar { display: none; }
        @media (max-width: 639px) { .d-tile { width: 2.2rem; height: 2.2rem; border-radius: .7rem; } }
        @media (min-width: 768px) and (max-width: 1279px) and (pointer: coarse) {
            .d-view { display: inline-flex; align-items: center; min-height: 2.5rem; }
        }
        .d-view:hover { background: rgba(255, 255, 255, .06); border-color: rgba(148, 163, 184, .5); }
        .d-row:hover { background-color: rgba(125, 211, 252, .06); }
        .d-arrow { transition: transform .25s cubic-bezier(.34, 1.4, .64, 1); }
        a:hover > .d-arrow, .d-link:hover .d-arrow { transform: translateX(4px); }

        @media (prefers-reduced-motion: reduce) { .d-in, .d-grow { animation: none; } .d-lift:hover { transform: none; } }
    </style>

    {{-- HEADER --}}
    <div class="d-in flex items-start justify-between mb-5 sm:mb-7 flex-wrap gap-3 sm:gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1.5">
                @php $hour = now()->hour; @endphp
                Good {{ $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }} {{ $hour < 12 ? '☀️' : ($hour < 18 ? '🌤️' : '🌙') }}
            </h1>
            <p class="text-slate-300 text-sm">
                Here is your security overview for <span class="text-white font-medium">{{ now()->format('l, j F Y') }}</span>.
            </p>
        </div>
        <span class="flex items-center gap-2 text-xs text-emerald-300 bg-emerald-400/10 border border-emerald-300/25 px-3.5 py-2 rounded-full">
            <span class="relative flex w-2 h-2"><span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-60"></span><span class="relative w-2 h-2 rounded-full bg-emerald-400"></span></span>
            System operational
        </span>
    </div>

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 md:max-xl:portrait:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6 md:max-xl:landscape:mb-4">
        @foreach ($cards as $i => $card)
            <div class="d-in d-card d-lift p-4 sm:p-5 md:max-xl:p-4" style="--c: {{ $card['rgb'] }}; --d: {{ $i * 0.07 }}s">
                <div class="max-sm:relative">
                <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center mb-3 sm:mb-5 md:max-xl:portrait:flex-col md:max-xl:portrait:items-start md:max-xl:landscape:gap-2.5 md:max-xl:landscape:mb-3 md:max-xl:landscape:min-h-[3rem]">
                    <span class="d-tile shrink-0 md:max-xl:landscape:!w-9 md:max-xl:landscape:!h-9 max-sm:absolute max-sm:right-0 max-sm:bottom-0 {{ $card['tile'] }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-100 sm:truncate md:max-xl:overflow-visible md:max-xl:whitespace-normal md:max-xl:leading-tight">{{ $card['title'] }}</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider sm:truncate md:max-xl:overflow-visible md:max-xl:whitespace-nowrap md:max-xl:tracking-normal md:max-xl:leading-tight md:max-xl:mt-0.5">{{ $card['tag'] }}</p>
                    </div>
                </div>

                <div class="flex items-end justify-between gap-2">
                    <p class="text-3xl sm:text-4xl font-bold leading-none tabular-nums {{ $card['num'] }}" data-count="{{ $stats[$card['key']] }}">{{ $stats[$card['key']] }}</p>
                </div>
                </div>

                <p class="hidden sm:block text-xs text-slate-300 mt-3 md:max-xl:landscape:mt-2 leading-snug">{{ $card['sub'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- QUICK CHECK + QUICK ACTIONS --}}
    <div class="mb-6">

        <div class="d-in d-card overflow-hidden" style="--d:.2s"
             x-data="{ busy: false }"
             x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) busy = false; })">
            <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3.5 sm:py-4 border-b border-white/10">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="d-tile shrink-0 text-sky-300" style="--c: 56,189,248">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-white font-semibold text-sm">Quick URL Check</p>
                        <p class="text-xs text-slate-300 mt-0.5">Paste a link for an instant security check</p>
                    </div>
                </div>
                <span class="hidden sm:flex items-center gap-2 text-[10px] tracking-wide text-slate-200 bg-white/5 border border-white/10 px-3 py-1.5 rounded-full shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> PHISHCORE ENGINE
                </span>
            </div>

            <div class="p-4 sm:p-6" x-data="{ url: '' }">
                <form method="POST" action="{{ route('scan.store') }}" class="flex flex-col sm:flex-row gap-3" @submit="busy = true">
                    @csrf
                    <x-scan-guard />
                    <div class="relative flex-1">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18" /></svg>
                        <input type="text" name="url" required x-model="url" :readonly="busy" placeholder="https://example.com"
                               class="w-full rounded-xl pl-11 pr-20 py-3.5 text-sm text-white placeholder-slate-400 bg-[#0a1630]/80 border border-sky-200/15 focus:outline-none focus:border-sky-400/70 focus:ring-2 focus:ring-sky-400/20 transition">
                        <button type="button" :disabled="busy"
                                @click="navigator.clipboard.readText().then(t => url = t)"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10 hover:text-white transition disabled:opacity-50">Paste</button>
                    </div>
                    <button type="submit" :disabled="busy"
                            class="flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold whitespace-nowrap shadow-[0_0_20px_-2px_rgba(56,189,248,0.45)] hover:brightness-110 active:scale-[0.98] transition disabled:opacity-70">
                        <svg x-show="!busy" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="busy ? 'Scanning...' : 'Scan URL'"></span>
                    </button>
                </form>

                @error('url')
                    <p class="mt-3 text-xs text-red-300">{{ $message }}</p>
                @enderror

                <div class="d-scroll flex gap-2 mt-4 overflow-x-auto sm:overflow-visible sm:flex-wrap -mx-4 px-4 sm:mx-0 sm:px-0">
                    @foreach (['URL structure', 'Domain age analysis', 'HTTPS validation', 'Blacklist verification'] as $chip)
                        <span class="inline-flex items-center gap-1.5 shrink-0 whitespace-nowrap text-xs text-slate-200 px-2.5 py-1 rounded-full border border-white/10 bg-white/[0.04]">
                            <svg class="w-3 h-3 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            {{ $chip }}
                        </span>
                    @endforeach
                </div>

                <p class="text-xs text-slate-300 mt-4">
                    Need to report a sender email, phone number, or screenshot instead?
                    <a href="{{ route('scan.index') }}" class="d-link text-sky-300 hover:text-sky-200 font-medium">Go to the full Scan page <span class="d-arrow inline-block">→</span></a>
                </p>
            </div>
        </div>
    </div>

    {{-- DETECTION OVERVIEW + BREAKDOWN --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 md:max-xl:grid-cols-3 gap-4 mb-6 md:max-xl:landscape:mb-4">
        <div class="d-in d-card min-w-0 p-4 sm:p-6 xl:col-span-2 md:max-xl:col-span-2" style="--d:.3s">
            <div class="flex items-start justify-between gap-3 mb-1">
                <h2 class="text-white font-semibold">Detection Overview</h2>
                <span class="text-xs text-slate-300 bg-white/5 border border-white/10 px-2.5 py-1 rounded-full whitespace-nowrap">{{ $weekRangeLabel }}</span>
            </div>
            <p class="text-sm text-slate-300 mb-5">Safe, Suspicious and Phishing results by day</p>

            <div class="relative h-56 sm:h-64 md:max-xl:landscape:h-52 min-w-0"><canvas id="weekChart"></canvas></div>

            <div class="flex items-center justify-center gap-5 mt-4 text-xs text-slate-200">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Safe</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span> Suspicious</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span> Phishing</span>
            </div>
        </div>

        <div class="d-in d-card min-w-0 p-4 sm:p-6 md:max-xl:p-4 flex flex-col" style="--d:.36s">
            <h2 class="text-white font-semibold">This Week</h2>
            <p class="text-sm text-slate-300 mb-4">Share of each verdict</p>

            <div class="relative mx-auto w-44 h-44 sm:w-48 sm:h-48 md:max-xl:w-36 md:max-xl:h-36 my-auto">
                <canvas id="weekDonut"></canvas>
                <div class="absolute inset-0 grid place-items-center pointer-events-none">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-white tabular-nums leading-none">{{ $weekSum }}</p>
                        <p class="text-[11px] text-slate-300 mt-1.5 tracking-wide">SCANS</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 mt-5 md:max-xl:gap-1.5 text-center">
                @foreach ([['safe', 'Safe', 'text-emerald-300', 'bg-emerald-400'], ['suspicious', 'Suspicious', 'text-orange-300', 'bg-orange-400'], ['phishing', 'Phishing', 'text-red-300', 'bg-red-400']] as $t)
                    <div class="rounded-xl border border-white/10 bg-white/[0.04] py-2.5">
                        <p class="text-xl font-bold tabular-nums {{ $t[2] }}">{{ $weekTotals[$t[0]] }}</p>
                        <p class="text-[11px] md:max-xl:text-[10px] text-slate-300 flex items-center justify-center gap-1"><span class="w-1.5 h-1.5 rounded-full {{ $t[3] }}"></span>{{ $t[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- RECENT REPORTS --}}
    <div class="d-in d-card p-4 sm:p-6" style="--d:.4s">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-white font-semibold">Recent Reports</h2>
                <p class="text-sm text-slate-300">Latest {{ $recentScans->count() }} scan results</p>
            </div>
            <a href="{{ route('scan.history') }}" class="d-link text-sky-300 text-sm font-medium hover:text-sky-200 flex items-center gap-1 whitespace-nowrap">
                View All Scans <span class="d-arrow inline-block">→</span>
            </a>
        </div>

        @if ($recentScans->count() > 0)
            <div class="overflow-x-auto -mx-1 px-1">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="text-left text-[11px] tracking-[0.12em] text-slate-300 border-b border-white/10">
                            <th class="pb-3 pr-4 font-medium">REPORTED ITEM</th>
                            <th class="pb-3 pr-4 font-medium">SCAN RESULT</th>
                            <th class="pb-3 pr-4 font-medium">RISK SCORE</th>
                            <th class="pb-3 pr-4 font-medium">DATE &amp; TIME</th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.07]">
                        @foreach ($recentScans as $scan)
                            @php
                                $verdict = $scan->analyses->first()->verdict ?? 'clean';
                                $score = $scan->analyses->first()->risk_score ?? 0;
                                $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                                $sc = $scoreColor[$verdict] ?? $scoreColor['clean'];
                                $itemLabel = match ($scan->type) {
                                    'email' => $scan->sender_email,
                                    'phone' => $scan->phone_number,
                                    'screenshot' => 'Uploaded screenshot',
                                    default => $scan->url,
                                };
                                $isMono = $scan->type !== 'screenshot';
                                $scheme = '';
                                $rest = (string) $itemLabel;
                                if ($scan->type === 'url' && preg_match('#^(https?://)(.*)$#i', $rest, $m)) {
                                    [$scheme, $rest] = [$m[1], $m[2]];
                                }
                                $iconPath = $typeIcons[$scan->type] ?? $typeIcons['url'];
                            @endphp
                            <tr class="d-row">
                                <td class="py-3.5 md:max-xl:landscape:py-2.5 md:max-xl:portrait:py-4 pr-4 w-full max-w-0">
                                    <p class="flex items-center gap-2.5 min-w-0" title="{{ $itemLabel }}">
                                        <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                                        <span class="truncate {{ $isMono ? 'd-url' : '' }} text-slate-100">@if ($scheme)<span class="text-slate-400">{{ $scheme }}</span>@endif{{ $rest }}</span>
                                    </p>
                                </td>
                                <td class="py-3.5 md:max-xl:landscape:py-2.5 md:max-xl:portrait:py-4 pr-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $badge['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 md:max-xl:landscape:py-2.5 md:max-xl:portrait:py-4 pr-4">
                                    <span class="flex items-center gap-2.5">
                                        <span class="w-7 font-semibold tabular-nums {{ $sc['text'] }}">{{ $score }}</span>
                                        <span class="hidden md:block w-20 h-1.5 rounded-full bg-white/10 overflow-hidden"><span class="block h-full rounded-full {{ $sc['bar'] }}" style="width: {{ min(max((int) $score, 0), 100) }}%"></span></span>
                                    </span>
                                </td>
                                <td class="py-3.5 md:max-xl:landscape:py-2.5 md:max-xl:portrait:py-4 pr-4 text-slate-300 tabular-nums whitespace-nowrap">{{ $scan->created_at->format('Y-m-d H:i') }}</td>
                                <td class="py-3.5 md:max-xl:landscape:py-2.5 md:max-xl:portrait:py-4 text-right">
                                    <a href="{{ route('scan.show', $scan) }}" class="d-view whitespace-nowrap">
                                        View Details <span aria-hidden="true">→</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-white/10">
                <p class="text-xs text-slate-300">Showing {{ $recentScans->count() }} of {{ $totalScansCount }} total records</p>
                <a href="{{ route('scan.history') }}" class="d-link text-sky-300 text-xs font-medium hover:text-sky-200 flex items-center gap-1">
                    View All Scans <span class="d-arrow inline-block">→</span>
                </a>
            </div>
        @else
            <div class="text-center py-10">
                <span class="w-12 h-12 mx-auto grid place-items-center rounded-2xl bg-sky-400/10 border border-sky-300/25 text-sky-300 mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                </span>
                <p class="text-sm text-slate-200">No reports yet.</p>
                <p class="text-sm text-slate-300"><a href="{{ route('scan.index') }}" class="text-sky-300 hover:text-sky-200 font-medium">Submit your first scan</a> to see it here.</p>
            </div>
        @endif
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 md:max-xl:grid-cols-4 gap-3 sm:gap-4 mt-6 md:max-xl:landscape:mt-4">
        @foreach ($actions as $i => $a)
            <a href="{{ $a['href'] }}" class="d-in d-card d-lift block p-4 sm:p-5" style="--c: 56,189,248; --d: {{ 0.45 + $i * 0.06 }}s">
                <span class="d-tile text-sky-300 mb-3" style="--c: 56,189,248">
                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $a['icon'] }}" /></svg>
                </span>
                <p class="text-white font-medium text-sm mb-0.5">{{ $a['title'] }}</p>
                <p class="text-xs text-slate-300">{{ $a['sub'] }}</p>
            </a>
        @endforeach
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* count-up for the stat numbers */
            document.querySelectorAll('[data-count]').forEach((el) => {
                const target = parseFloat(el.dataset.count) || 0;
                if (reduce || target === 0) return;
                const start = performance.now(), dur = 1000;
                el.textContent = '0';
                const tick = (now) => {
                    const p = Math.min((now - start) / dur, 1);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
                    if (p < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
            });

            Chart.defaults.font.family = "'Manrope', system-ui, sans-serif";
            Chart.defaults.color = '#cbd5e1';

            const tooltip = {
                backgroundColor: 'rgba(8, 15, 34, .95)', borderColor: 'rgba(148, 163, 184, .25)', borderWidth: 1,
                titleColor: '#fff', bodyColor: '#e2e8f0', padding: 10, cornerRadius: 10, boxPadding: 4
            };

            new Chart(document.getElementById('weekChart'), {
                type: 'bar',
                data: {
                    labels: @json($weekLabels),
                    datasets: [
                        { label: 'Safe', data: @json($weekSafe), backgroundColor: '#34d399', borderRadius: 6, maxBarThickness: 26 },
                        { label: 'Suspicious', data: @json($weekSuspicious), backgroundColor: '#fb923c', borderRadius: 6, maxBarThickness: 26 },
                        { label: 'Phishing', data: @json($weekPhishing), backgroundColor: '#f87171', borderRadius: 6, maxBarThickness: 26 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    animation: reduce ? false : { duration: 700 },
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip },
                    scales: {
                        x: { ticks: { color: '#cbd5e1' }, grid: { display: false }, border: { color: 'rgba(148,163,184,.25)' } },
                        y: { beginAtZero: true, ticks: { color: '#cbd5e1', precision: 0 }, grid: { color: 'rgba(148,163,184,.14)' }, border: { display: false } }
                    }
                }
            });

            const wk = [{{ (int) $weekTotals['safe'] }}, {{ (int) $weekTotals['suspicious'] }}, {{ (int) $weekTotals['phishing'] }}];
            const empty = wk.every((n) => n === 0);
            new Chart(document.getElementById('weekDonut'), {
                type: 'doughnut',
                data: {
                    labels: ['Safe', 'Suspicious', 'Phishing'],
                    datasets: [{
                        data: empty ? [1] : wk,
                        backgroundColor: empty ? ['rgba(148,163,184,.2)'] : ['#34d399', '#fb923c', '#f87171'],
                        borderColor: 'rgba(10, 20, 44, 1)', borderWidth: 3, hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: true, cutout: '74%',
                    animation: reduce ? false : { duration: 800 },
                    plugins: { legend: { display: false }, tooltip: empty ? { enabled: false } : tooltip }
                }
            });
        })();
    </script>
    @endpush

</x-layouts.dashboard>