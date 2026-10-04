@php
    $periods = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 3 Months', 180 => 'Last 6 Months'];

    if (! function_exists('trendArrow')) {
        function trendArrow($value) {
            return $value >= 0 ? '↗' : '↘';
        }
    }
    if (! function_exists('trendColor')) {
        function trendColor($value, $goodWhenUp = true) {
            $isGood = $goodWhenUp ? $value >= 0 : $value <= 0;
            return $isGood ? 'text-emerald-400' : 'text-red-400';
        }
    }
    if (! function_exists('msLabel')) {
        function msLabel($ms) {
            return $ms === null ? '—' : round($ms / 1000, 1) . 's';
        }
    }

    // explicit class map so Tailwind always generates these colours
    $tones = [
        'emerald' => ['bg' => 'bg-emerald-400', 'text' => 'text-emerald-400', 'rgb' => '52,211,153'],
        'sky' => ['bg' => 'bg-sky-400', 'text' => 'text-sky-300', 'rgb' => '56,189,248'],
        'orange' => ['bg' => 'bg-orange-400', 'text' => 'text-orange-400', 'rgb' => '251,146,60'],
        'red' => ['bg' => 'bg-red-400', 'text' => 'text-red-400', 'rgb' => '248,113,113'],
    ];

    $statCards = [
        ['label' => 'TOTAL REPORTS', 'value' => $stats['total'], 'suffix' => '', 'change' => $stats['total_change'], 'goodUp' => true, 'rgb' => '56,189,248', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['label' => 'PHISHING DETECTION RATE', 'value' => $stats['phishing_rate'], 'suffix' => '%', 'change' => $stats['phishing_rate_change'], 'goodUp' => false, 'rgb' => '248,113,113', 'icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z'],
        ['label' => 'AVERAGE RISK SCORE', 'value' => $stats['avg_risk'], 'suffix' => '', 'change' => $stats['avg_risk_change'], 'goodUp' => false, 'rgb' => '251,191,36', 'icon' => 'M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18'],
        ['label' => 'SUSPICIOUS RATE', 'value' => $stats['suspicious_rate'], 'suffix' => '%', 'change' => $stats['suspicious_rate_change'], 'goodUp' => false, 'rgb' => '251,146,60', 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
    ];

    $breakdownRows = [
        ['key' => 'safe', 'label' => 'Safe', 'tone' => 'emerald'],
        ['key' => 'suspicious', 'label' => 'Suspicious', 'tone' => 'orange'],
        ['key' => 'phishing', 'label' => 'Phishing', 'tone' => 'red'],
    ];
@endphp

<x-layouts.dashboard>
    <style>
        .a-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .a-well { background: rgba(8, 15, 32, .5); border: 1px solid rgba(148, 163, 184, .16); border-radius: .8rem; }

        .a-stat { position: relative; overflow: hidden; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
        .a-stat::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(120% 140% at 100% 0%, rgba(var(--c), .24), transparent 62%); opacity: .75; transition: opacity .3s; }
        .a-stat:hover { transform: translateY(-2px); border-color: rgba(var(--c), .5); }
        .a-stat:hover::before { opacity: 1; }
        .a-stat > * { position: relative; }
        .a-tile { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .8rem; background: rgba(var(--c), .14); border: 1px solid rgba(var(--c), .38); color: rgb(var(--c)); box-shadow: 0 0 18px -4px rgba(var(--c), .55); flex-shrink: 0; }

        .a-chip { display: inline-flex; align-items: center; padding: .5rem 1rem; border-radius: 999px; font-size: .8rem; font-weight: 600; color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .22); background: rgba(8, 15, 32, .35); transition: background-color .15s, border-color .15s, color .15s; white-space: nowrap; }
        .a-chip:hover { color: #fff; border-color: rgba(148, 163, 184, .45); }
        .a-chip-on { color: #fff; background: rgba(56, 189, 248, .16); border-color: rgba(56, 189, 248, .5); }

        .a-chip { flex-shrink: 0; white-space: nowrap; }
        .a-scroll { scrollbar-width: none; }
        .a-scroll::-webkit-scrollbar { display: none; }
        @media (max-width: 639px) {
            .a-tile { width: 2.1rem; height: 2.1rem; border-radius: .65rem; }
            .a-tile svg { width: 1rem; height: 1rem; }
            .a-chip { padding: .42rem .85rem; font-size: .75rem; }
        }

        .a-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; letter-spacing: -.01em; }
        .a-row { transition: background-color .15s; }
        .a-row:hover { background-color: rgba(125, 211, 252, .06); }

        .a-grow { transform-origin: left; animation: a-grow .8s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, .2s); }
        @keyframes a-grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        .a-in { animation: a-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes a-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .a-in, .a-grow { animation: none; } .a-stat:hover { transform: none; } }
    </style>

    <div class="a-in flex items-start justify-between mb-5 sm:mb-6 md:max-xl:landscape:mb-4 sm:flex-wrap gap-3 sm:gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-white mb-1">Detection Analytics</h1>
            <p class="text-slate-300 text-sm">Monitor phishing trends, report activity and detection performance.</p>
        </div>
        <span class="inline-flex items-center justify-center shrink-0 gap-2 max-sm:w-10 max-sm:h-10 sm:px-4 sm:py-2.5 rounded-xl border border-slate-500/30 text-slate-200 text-sm font-semibold opacity-60 cursor-not-allowed" title="Coming soon">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            <span class="hidden sm:inline">Export Analytics</span>
        </span>
    </div>

    {{-- PERIOD --}}
    <div class="a-scroll a-in flex gap-2 mb-5 sm:mb-6 md:max-xl:landscape:mb-4 overflow-x-auto sm:overflow-visible sm:flex-wrap -mx-4 px-4 sm:mx-0 sm:px-0" style="--d:.04s">
        @foreach ($periods as $days => $label)
            <a href="{{ route('analytics', ['period' => $days]) }}" class="a-chip {{ $period == $days ? 'a-chip-on' : '' }}">{{ $label }}</a>
        @endforeach
        <span class="a-chip opacity-50 cursor-not-allowed" title="Coming soon">Custom Range</span>
    </div>

    {{-- TOP STAT CARDS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6 md:max-xl:landscape:mb-4">
        @foreach ($statCards as $card)
            <div class="a-card a-stat a-in p-3.5 sm:p-5 md:max-xl:p-4" style="--c: {{ $card['rgb'] }}; --d: {{ 0.06 + $loop->index * 0.06 }}s">
                <div class="flex items-start justify-between gap-2 sm:gap-3 md:max-xl:gap-2 mb-3 sm:mb-4 md:max-xl:min-h-[2.5rem]">
                    <p class="text-[11px] sm:text-xs md:max-xl:text-[11px] font-medium tracking-wide md:max-xl:tracking-normal text-slate-300 leading-snug min-w-0">{{ $card['label'] }}</p>
                    <span class="a-tile shrink-0 md:max-xl:!w-9 md:max-xl:!h-9">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-white mb-1">{{ $card['value'] }}{{ $card['suffix'] }}</p>
                <p class="text-xs font-medium {{ trendColor($card['change'], $card['goodUp']) }}">
                    {{ trendArrow($card['change']) }} {{ $card['change'] > 0 ? '+' : '' }}{{ $card['change'] }}% <span class="hidden sm:inline md:max-xl:hidden text-slate-400 font-normal">vs prev. period</span><span class="hidden md:max-xl:inline text-slate-400 font-normal">vs prev.</span>
                </p>
            </div>
        @endforeach
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 a-card a-in p-4 sm:p-6" style="--d:.1s">
            <div class="flex items-center justify-between gap-3 mb-1">
                <h2 class="text-white font-semibold">Report Activity Over Time</h2>
                <span class="text-xs font-medium text-slate-300">{{ $periods[$period] ?? '' }}</span>
            </div>
            <p class="text-sm text-slate-300 mb-4">Daily report totals for the selected period</p>
            <div class="relative h-48 sm:h-56 md:max-xl:h-64 xl:h-auto"><canvas id="activityChart" height="90"></canvas></div>
        </div>

        <div class="a-card a-in p-4 sm:p-6 md:max-xl:p-4 md:max-lg:portrait:grid md:max-lg:portrait:grid-cols-[auto_1fr] md:max-lg:portrait:items-center md:max-lg:portrait:gap-x-10" style="--d:.16s">
            <h2 class="text-white font-semibold mb-1 md:max-lg:portrait:col-span-2">Detection Results</h2>
            <p class="text-sm text-slate-300 mb-4 md:max-lg:portrait:col-span-2">Result breakdown for {{ $periods[$period] ?? '' }}</p>
            <div class="relative w-40 h-40 mx-auto mb-5 md:max-lg:portrait:mb-0 md:max-lg:portrait:ml-4">
                <canvas id="breakdownChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-bold text-white">{{ $breakdown['total'] }}</span>
                    <span class="text-[10px] font-medium tracking-wide text-slate-300">TOTAL</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                @foreach ($breakdownRows as $row)
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-100"><span class="w-2 h-2 rounded-full {{ $tones[$row['tone']]['bg'] }}"></span> {{ $row['label'] }}</span>
                        <span class="text-slate-100 font-medium">{{ $breakdown[$row['key']] }} <span class="text-slate-400 font-normal">({{ $breakdown['total'] > 0 ? round($breakdown[$row['key']] / $breakdown['total'] * 100, 1) : 0 }}%)</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- RISK DISTRIBUTION + INDICATORS --}}
    <div class="grid md:grid-cols-2 gap-4 mb-4 md:max-xl:landscape:mb-3">
        <div class="a-card a-in p-4 sm:p-6 flex flex-col" style="--d:.1s">
            <h2 class="text-white font-semibold mb-1">Risk-Level Distribution</h2>
            <p class="text-sm text-slate-300 mb-5">Number of reports in each risk bracket</p>

            {{-- one stacked bar = the whole picture at a glance --}}
            <div class="flex h-3 rounded-full overflow-hidden bg-white/10 mb-6 gap-0.5">
                @foreach ($riskBuckets as $bucket)
                    @php $tone = $tones[$bucket['color']] ?? $tones['sky']; @endphp
                    @if ($bucket['pct'] > 0)
                        <span class="a-grow block h-full {{ $tone['bg'] }}" style="width: {{ $bucket['pct'] }}%; --d: {{ 0.2 + $loop->index * 0.08 }}s" title="{{ $bucket['label'] }}: {{ $bucket['count'] }}"></span>
                    @endif
                @endforeach
            </div>

            {{-- tiles stretch to fill the card, so it matches the height of the card beside it --}}
            <div class="grid grid-cols-2 gap-3 flex-1 auto-rows-fr">
                @foreach ($riskBuckets as $bucket)
                    @php
                        $tone = $tones[$bucket['color']] ?? $tones['sky'];
                        $bucketName = trim(preg_replace('/\s*\(.*\)$/', '', $bucket['label']));
                        $bucketRange = preg_match('/\((.*)\)/', $bucket['label'], $mm) ? $mm[1] : '';
                    @endphp
                    <div class="a-well p-3.5 sm:p-4 flex flex-col justify-between min-h-[7.25rem] sm:min-h-[8.5rem]">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-100">{{ $bucketName }}</p>
                                <p class="text-xs text-slate-400 a-mono mt-0.5">{{ $bucketRange }}</p>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full mt-1 {{ $tone['bg'] }}"></span>
                        </div>
                        <div>
                            <p class="flex items-baseline gap-2">
                                <span class="text-3xl sm:text-4xl font-bold {{ $tone['text'] }}">{{ $bucket['count'] }}</span>
                                <span class="hidden sm:inline text-xs text-slate-300">{{ $bucket['count'] == 1 ? 'report' : 'reports' }}</span>
                                <span class="ml-auto text-sm font-semibold text-slate-200">{{ $bucket['pct'] }}%</span>
                            </p>
                            <span class="block h-1.5 rounded-full bg-white/10 overflow-hidden mt-3">
                                <span class="a-grow block h-full rounded-full {{ $tone['bg'] }}" style="width: {{ $bucket['pct'] }}%; --d: {{ 0.3 + $loop->index * 0.08 }}s"></span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="a-card a-in p-4 sm:p-6" style="--d:.16s">
            <h2 class="text-white font-semibold mb-1">Most Common Phishing Indicators</h2>
            <p class="text-sm text-slate-300 mb-5">Top triggers across your flagged reports, across all detection types (URL, email, phone, screenshot)</p>
            @if (count($indicatorCounts) > 0)
                <div class="space-y-4">
                    @foreach ($indicatorCounts as $name => $count)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm mb-1.5">
                                <span class="text-slate-100 min-w-0"><span class="text-slate-400">{{ $loop->iteration }}.</span> {{ $name }}</span>
                                <span class="text-sky-300 font-semibold">{{ $count }}</span>
                            </div>
                            <span class="block h-2 rounded-full bg-white/10 overflow-hidden">
                                <span class="a-grow block h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($count / $maxIndicatorCount * 100) }}%; --d: {{ 0.2 + $loop->index * 0.07 }}s"></span>
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-300">No flagged indicators in this period yet.</p>
            @endif
        </div>
    </div>

    {{-- PERIOD COMPARISON + SCANNING PERFORMANCE --}}
    <div class="grid md:grid-cols-2 gap-4 mb-4 md:max-xl:landscape:mb-3">
        <div class="a-card a-in p-4 sm:p-6" style="--d:.1s">
            <h2 class="text-white font-semibold mb-1">Period Comparison</h2>
            <p class="text-sm text-slate-300 mb-5">Current vs previous {{ strtolower($periods[$period] ?? '') }}</p>
            <div class="space-y-4">
                @foreach ($breakdownRows as $row)
                    @php
                        $pc = $periodComparison[$row['key']];
                        $tone = $tones[$row['tone']];
                        $peak = max($pc['current'], $pc['previous'], 1);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="flex items-center gap-2 text-slate-100"><span class="w-2 h-2 rounded-full {{ $tone['bg'] }}"></span> {{ $row['label'] }}</span>
                            <span class="flex items-center gap-2.5">
                                <span class="text-white font-semibold">{{ $pc['current'] }}</span>
                                <span class="text-xs font-semibold {{ $pc['change'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $pc['change'] > 0 ? '+' : '' }}{{ $pc['change'] }}%</span>
                            </span>
                        </div>
                        <div class="flex gap-1.5">
                            <span class="flex-1 h-2 rounded-full bg-white/10 overflow-hidden">
                                <span class="a-grow block h-full rounded-full bg-slate-400/60" style="width: {{ $pc['previous'] > 0 ? min(100, round($pc['previous'] / $peak * 100)) : 0 }}%"></span>
                            </span>
                            <span class="flex-1 h-2 rounded-full bg-white/10 overflow-hidden">
                                <span class="a-grow block h-full rounded-full {{ $tone['bg'] }}" style="width: {{ $pc['current'] > 0 ? min(100, round($pc['current'] / $peak * 100)) : 0 }}%; --d:.3s"></span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 mt-5 text-xs text-slate-300">
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-slate-400/60"></span> Previous</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-sky-400"></span> Current</span>
            </div>
        </div>

        <div class="a-card a-in p-4 sm:p-6" style="--d:.16s">
            <h2 class="text-white font-semibold mb-1">Scanning Performance</h2>
            <p class="text-sm text-slate-300 mb-5">Engine response times across your reports</p>
            @if ($performance)
                <div class="grid grid-cols-2 gap-3">
                    <div class="a-well p-4">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">AVERAGE</p>
                        <p class="text-2xl font-bold text-white">{{ msLabel($performance['avg_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1">Across {{ $performance['count'] }} timed reports</p>
                    </div>
                    <div class="a-well p-4">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">MEDIAN</p>
                        <p class="text-2xl font-bold text-white">{{ msLabel($performance['median_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1">P50 percentile</p>
                    </div>
                    <div class="a-well p-4 min-w-0">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">FASTEST</p>
                        <p class="text-2xl font-bold text-emerald-400">{{ msLabel($performance['fastest_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1 truncate a-mono" title="{{ $performance['fastest_label'] }}">{{ $performance['fastest_label'] }}</p>
                    </div>
                    <div class="a-well p-4 min-w-0">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">SLOWEST</p>
                        <p class="text-2xl font-bold text-orange-400">{{ msLabel($performance['slowest_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1 truncate a-mono" title="{{ $performance['slowest_label'] }}">{{ $performance['slowest_label'] }}</p>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-300">No timing data yet — this started being recorded with your most recent reports. Submit a few more scans to populate this.</p>
            @endif
        </div>
    </div>

    {{-- TOP SOURCE COUNTRIES --}}
    <div class="a-card a-in p-4 sm:p-6 mb-4" style="--d:.1s">
        <h2 class="text-white font-semibold mb-1">Top Source Countries</h2>
        <p class="text-sm text-slate-300 mb-5">Countries your scanned URLs were hosted in, based on IP geolocation</p>
        @if ($topCountries->count() > 0)
            <div class="space-y-4">
                @foreach ($topCountries as $c)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="text-slate-100"><span class="text-slate-400">{{ $loop->iteration }}.</span> {{ $c['country'] }}</span>
                            <span class="flex items-center gap-3">
                                @if ($c['phishing_count'] > 0)
                                    <span class="text-xs font-semibold text-red-400">{{ $c['phishing_count'] }} phishing</span>
                                @endif
                                <span class="text-sky-300 font-semibold">{{ $c['count'] }}</span>
                            </span>
                        </div>
                        <span class="block h-2 rounded-full bg-white/10 overflow-hidden">
                            <span class="a-grow block h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($c['count'] / $maxCountryCount * 100) }}%; --d: {{ 0.2 + $loop->index * 0.07 }}s"></span>
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-300">No country data available yet — this is captured for URL scans going forward.</p>
        @endif
    </div>

    {{-- TOP PHISHING SOURCES --}}
    <div class="a-card a-in p-4 sm:p-6 mb-4" style="--d:.14s">
        <h2 class="text-white font-semibold mb-1">Top Phishing Sources</h2>
        <p class="text-sm text-slate-300 mb-5">Your most frequently detected phishing sources in this period — URLs, sender domains, phone numbers, or screenshots</p>

        @if ($topDomains->count() > 0)
            <div class="md:overflow-x-auto md:-mx-2">
                <table class="block md:table w-full text-sm md:min-w-[600px]">
                    <thead class="hidden md:table-header-group">
                        <tr class="text-left text-xs font-medium tracking-wide text-slate-300 border-b border-slate-500/25">
                            <th class="pb-3 px-2 w-10">#</th>
                            <th class="pb-3 px-2">SOURCE</th>
                            <th class="pb-3 px-2">DETECTIONS</th>
                            <th class="pb-3 px-2">AVG RISK SCORE</th>
                            <th class="pb-3 px-2">LATEST DETECTION</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group divide-y divide-slate-500/20">
                        @foreach ($topDomains as $i => $d)
                            <tr class="a-row flex flex-wrap items-center gap-x-3 gap-y-1.5 py-3 md:py-0 md:table-row">
                                <td class="w-6 md:w-auto md:py-3 md:px-2 text-slate-400">{{ $i + 1 }}</td>
                                <td class="flex-1 min-w-0 md:py-3 md:px-2 md:w-full md:max-w-0">
                                    <span class="flex items-center gap-2 text-slate-100 min-w-0" title="{{ $d['domain'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                        <span class="truncate a-mono text-[13px]">{{ $d['domain'] }}</span>
                                    </span>
                                </td>
                                <td class="pl-9 md:pl-2 md:py-3 md:px-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-red-400 font-semibold w-5">{{ $d['detections'] }}</span>
                                        <span class="w-20 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                            <span class="block h-full rounded-full bg-red-400" style="width: {{ round($d['detections'] / $maxDetections * 100) }}%"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-xs md:text-sm md:py-3 md:px-2 whitespace-nowrap text-orange-400 font-semibold">{{ $d['avg_score'] }} <span class="text-slate-400 font-normal">/ 100</span></td>
                                <td class="ml-auto md:ml-0 text-xs md:text-sm md:py-3 md:px-2 whitespace-nowrap text-slate-300">{{ \Carbon\Carbon::parse($d['latest'])->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-300">No phishing detections in this period yet.</p>
        @endif
    </div>

    @if ($breakdown['total'] === 0)
        <p class="text-center text-sm text-slate-300 mt-6">No reports found in this period yet — try a wider date range or submit a few scans first.</p>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = "'Manrope', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = '#94a3b8';

        const tooltip = {
            backgroundColor: 'rgba(8, 15, 32, .95)',
            borderColor: 'rgba(148, 163, 184, .3)',
            borderWidth: 1,
            titleColor: '#f1f5f9',
            bodyColor: '#cbd5e1',
            padding: 10,
            cornerRadius: 10,
            displayColors: false,
        };

        const activityCtx = document.getElementById('activityChart');
        const fill = activityCtx.getContext('2d').createLinearGradient(0, 0, 0, 260);
        fill.addColorStop(0, 'rgba(56, 189, 248, 0.30)');
        fill.addColorStop(1, 'rgba(56, 189, 248, 0)');

        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Reports',
                    data: @json($chartCounts),
                    borderColor: '#38bdf8',
                    backgroundColor: fill,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#38bdf8',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: window.matchMedia('(min-width: 1280px)').matches,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip },
                scales: {
                    x: { ticks: { color: '#94a3b8', maxTicksLimit: 8 }, grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, ticks: { color: '#94a3b8', precision: 0 }, grid: { color: 'rgba(148,163,184,0.12)' }, border: { display: false } }
                }
            }
        });

        const breakdownCtx = document.getElementById('breakdownChart');
        new Chart(breakdownCtx, {
            type: 'doughnut',
            data: {
                labels: ['Safe', 'Suspicious', 'Phishing'],
                datasets: [{
                    data: [{{ $breakdown['safe'] }}, {{ $breakdown['suspicious'] }}, {{ $breakdown['phishing'] }}],
                    backgroundColor: ['#34d399', '#fb923c', '#f87171'],
                    borderWidth: 0,
                    spacing: 3,
                    borderRadius: 6,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                cutout: '72%',
                plugins: { legend: { display: false }, tooltip: { ...tooltip, displayColors: true } }
            }
        });
    </script>
    @endpush

</x-layouts.dashboard>