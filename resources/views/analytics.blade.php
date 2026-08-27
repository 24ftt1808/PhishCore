@php
    $periods = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 3 Months', 180 => 'Last 6 Months'];

    function trendArrow($value) {
        return $value >= 0 ? '↗' : '↘';
    }
    function trendColor($value, $goodWhenUp = true) {
        $isGood = $goodWhenUp ? $value >= 0 : $value <= 0;
        return $isGood ? 'text-emerald-400' : 'text-red-400';
    }
    function msLabel($ms) {
        return $ms === null ? '—' : round($ms / 1000, 1) . 's';
    }
@endphp

<x-layouts.dashboard>

    <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">Detection Analytics</h1>
            <p class="text-slate-400 text-sm">Monitor phishing trends, report activity and detection performance.</p>
        </div>
        <span class="px-4 py-2.5 rounded-lg border border-slate-800 text-slate-600 text-sm cursor-not-allowed" title="Coming soon">
            ⬇ Export Analytics
        </span>
    </div>

    <div class="flex flex-wrap gap-2 mb-8">
        @foreach ($periods as $days => $label)
            <a href="{{ route('analytics', ['period' => $days]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ $period == $days ? 'bg-sky-500/20 text-sky-400' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-slate-200' }} transition">
                {{ $label }}
            </a>
        @endforeach
        <span class="px-4 py-2 rounded-lg text-sm bg-slate-900 border border-slate-800 text-slate-600 cursor-not-allowed" title="Coming soon">
            Custom Range
        </span>
    </div>

    {{-- TOP STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">TOTAL REPORTS</p>
                <span class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</p>
            <p class="text-xs {{ trendColor($stats['total_change']) }}">{{ trendArrow($stats['total_change']) }} {{ $stats['total_change'] > 0 ? '+' : '' }}{{ $stats['total_change'] }}% vs prev. period</p>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">PHISHING<br>DETECTION RATE</p>
                <span class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ $stats['phishing_rate'] }}%</p>
            <p class="text-xs {{ trendColor($stats['phishing_rate_change'], false) }}">{{ trendArrow($stats['phishing_rate_change']) }} {{ $stats['phishing_rate_change'] > 0 ? '+' : '' }}{{ $stats['phishing_rate_change'] }}% vs prev. period</p>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">AVERAGE RISK<br>SCORE</p>
                <span class="w-8 h-8 rounded-lg bg-orange-500/10 border border-orange-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ $stats['avg_risk'] }}</p>
            <p class="text-xs {{ trendColor($stats['avg_risk_change'], false) }}">{{ trendArrow($stats['avg_risk_change']) }} {{ $stats['avg_risk_change'] > 0 ? '+' : '' }}{{ $stats['avg_risk_change'] }}% vs prev. period</p>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">SUSPICIOUS<br>RATE</p>
                <span class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ $stats['suspicious_rate'] }}%</p>
            <p class="text-xs {{ trendColor($stats['suspicious_rate_change'], false) }}">{{ trendArrow($stats['suspicious_rate_change']) }} {{ $stats['suspicious_rate_change'] > 0 ? '+' : '' }}{{ $stats['suspicious_rate_change'] }}% vs prev. period</p>
        </div>
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-white font-semibold">Report Activity Over Time</h2>
                <span class="text-xs text-slate-500">{{ $periods[$period] ?? '' }}</span>
            </div>
            <p class="text-sm text-slate-500 mb-4">Daily report totals for the selected period</p>
            <canvas id="activityChart" height="90"></canvas>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-white font-semibold mb-1">Detection Results</h2>
            <p class="text-sm text-slate-500 mb-4">Result breakdown for {{ $periods[$period] ?? '' }}</p>
            <div class="relative w-40 h-40 mx-auto mb-4">
                <canvas id="breakdownChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-bold text-white">{{ $breakdown['total'] }}</span>
                    <span class="text-[10px] text-slate-500">TOTAL</span>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-300"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Safe</span>
                    <span class="text-slate-400">{{ $breakdown['safe'] }} <span class="text-slate-600">({{ $breakdown['total'] > 0 ? round($breakdown['safe'] / $breakdown['total'] * 100, 1) : 0 }}%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-300"><span class="w-2 h-2 rounded-full bg-orange-400"></span> Suspicious</span>
                    <span class="text-slate-400">{{ $breakdown['suspicious'] }} <span class="text-slate-600">({{ $breakdown['total'] > 0 ? round($breakdown['suspicious'] / $breakdown['total'] * 100, 1) : 0 }}%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-slate-300"><span class="w-2 h-2 rounded-full bg-red-400"></span> Phishing</span>
                    <span class="text-slate-400">{{ $breakdown['phishing'] }} <span class="text-slate-600">({{ $breakdown['total'] > 0 ? round($breakdown['phishing'] / $breakdown['total'] * 100, 1) : 0 }}%)</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- RISK DISTRIBUTION + INDICATORS --}}
    <div class="grid lg:grid-cols-2 gap-4 mb-4">
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-white font-semibold mb-1">Risk-Level Distribution</h2>
            <p class="text-sm text-slate-500 mb-5">Number of reports in each risk bracket</p>
            <div class="space-y-4">
                @foreach ($riskBuckets as $bucket)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="text-slate-300">{{ $bucket['label'] }}</span>
                            <span class="text-{{ $bucket['color'] }}-400 font-medium">{{ $bucket['count'] }} <span class="text-slate-500 font-normal">reports</span></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex-1 h-2 rounded-full bg-slate-800 overflow-hidden">
                                <span class="block h-full bg-{{ $bucket['color'] }}-400" style="width: {{ $bucket['pct'] }}%"></span>
                            </span>
                            <span class="text-xs text-slate-500 w-9 text-right">{{ $bucket['pct'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-white font-semibold mb-1">Most Common Phishing Indicators</h2>
            <p class="text-sm text-slate-500 mb-5">Top triggers across your flagged reports, across all detection types (URL, email, phone, screenshot)</p>
            @if (count($indicatorCounts) > 0)
                <div class="space-y-4">
                    @foreach ($indicatorCounts as $name => $count)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1.5">
                                <span class="text-slate-300">{{ $loop->iteration }}. {{ $name }}</span>
                                <span class="text-sky-400 font-medium">{{ $count }}</span>
                            </div>
                            <span class="block h-2 rounded-full bg-slate-800 overflow-hidden">
                                <span class="block h-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($count / $maxIndicatorCount * 100) }}%"></span>
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">No flagged indicators in this period yet.</p>
            @endif
        </div>
    </div>

    {{-- PERIOD COMPARISON + SCANNING PERFORMANCE --}}
    <div class="grid lg:grid-cols-2 gap-4 mb-4">
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-white font-semibold mb-1">Period Comparison</h2>
            <p class="text-sm text-slate-500 mb-5">Current vs previous {{ strtolower($periods[$period] ?? '') }}</p>
            <div class="space-y-4">
                @foreach ([['key' => 'safe', 'label' => 'Safe', 'color' => 'emerald'], ['key' => 'suspicious', 'label' => 'Suspicious', 'color' => 'orange'], ['key' => 'phishing', 'label' => 'Phishing', 'color' => 'red']] as $row)
                    @php $pc = $periodComparison[$row['key']]; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="flex items-center gap-2 text-slate-300"><span class="w-2 h-2 rounded-full bg-{{ $row['color'] }}-400"></span> {{ $row['label'] }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-white font-medium">{{ $pc['current'] }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $pc['change'] >= 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">{{ $pc['change'] > 0 ? '+' : '' }}{{ $pc['change'] }}%</span>
                            </span>
                        </div>
                        <div class="flex gap-1.5">
                            <span class="flex-1 h-2 rounded-full bg-slate-800 overflow-hidden">
                                <span class="block h-full bg-slate-600" style="width: {{ $pc['previous'] > 0 ? min(100, round($pc['previous'] / max($pc['current'], $pc['previous'], 1) * 100)) : 0 }}%"></span>
                            </span>
                            <span class="flex-1 h-2 rounded-full bg-slate-800 overflow-hidden">
                                <span class="block h-full bg-{{ $row['color'] }}-400" style="width: {{ $pc['current'] > 0 ? min(100, round($pc['current'] / max($pc['current'], $pc['previous'], 1) * 100)) : 0 }}%"></span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 mt-5 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-slate-600"></span> Previous</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-sky-400"></span> Current</span>
            </div>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-white font-semibold mb-1">Scanning Performance</h2>
            <p class="text-sm text-slate-500 mb-5">Engine response times across your reports</p>
            @if ($performance)
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-300">Average Scan Time</span>
                            <span class="text-sky-400 font-medium">{{ msLabel($performance['avg_ms']) }}</span>
                        </div>
                        <p class="text-xs text-slate-600">Across {{ $performance['count'] }} timed reports</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-300">Fastest Scan</span>
                            <span class="text-emerald-400 font-medium">{{ msLabel($performance['fastest_ms']) }}</span>
                        </div>
                        <p class="text-xs text-slate-600 truncate">{{ $performance['fastest_label'] }}</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-300">Slowest Scan</span>
                            <span class="text-orange-400 font-medium">{{ msLabel($performance['slowest_ms']) }}</span>
                        </div>
                        <p class="text-xs text-slate-600 truncate">{{ $performance['slowest_label'] }}</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-300">Median Scan Time</span>
                            <span class="text-sky-400 font-medium">{{ msLabel($performance['median_ms']) }}</span>
                        </div>
                        <p class="text-xs text-slate-600">P50 percentile</p>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-500">No timing data yet — this started being recorded with your most recent reports. Submit a few more scans to populate this.</p>
            @endif
        </div>
    </div>

    {{-- TOP SOURCE COUNTRIES --}}
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-4">
        <h2 class="text-white font-semibold mb-1">Top Source Countries</h2>
        <p class="text-sm text-slate-500 mb-5">Countries your scanned URLs were hosted in, based on IP geolocation</p>
        @if ($topCountries->count() > 0)
            <div class="space-y-4">
                @foreach ($topCountries as $c)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="text-slate-300">{{ $loop->iteration }}. {{ $c['country'] }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-sky-400 font-medium">{{ $c['count'] }}</span>
                                @if ($c['phishing_count'] > 0)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-red-500/20 text-red-400">{{ $c['phishing_count'] }} phishing</span>
                                @endif
                            </span>
                        </div>
                        <span class="block h-2 rounded-full bg-slate-800 overflow-hidden">
                            <span class="block h-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($c['count'] / $maxCountryCount * 100) }}%"></span>
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-500">No country data available yet — this is captured for URL scans going forward.</p>
        @endif
    </div>
        <p class="text-sm text-slate-500 mb-5">Your most frequently detected phishing sources in this period — URLs, sender domains, phone numbers, or screenshots</p>

                @if ($topDomains->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead>
                    <tr class="text-left text-xs tracking-wide text-slate-500 border-b border-slate-800">
                        <th class="pb-3 pr-4">#</th>
                        <th class="pb-3 pr-4">SOURCE</th>
                        <th class="pb-3 pr-4">DETECTIONS</th>
                        <th class="pb-3 pr-4">AVG RISK SCORE</th>
                        <th class="pb-3">LATEST DETECTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach ($topDomains as $i => $d)
                        <tr>
                            <td class="py-3 pr-4 text-slate-500">{{ $i + 1 }}</td>
                            <td class="py-3 pr-4">
                                <span class="flex items-center gap-2 text-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> {{ $d['domain'] }}
                                </span>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-red-400 font-medium w-5">{{ $d['detections'] }}</span>
                                    <span class="w-20 h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                        <span class="block h-full bg-red-400" style="width: {{ round($d['detections'] / $maxDetections * 100) }}%"></span>
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 pr-4 text-orange-400 font-medium">{{ $d['avg_score'] }} <span class="text-slate-600 font-normal">/ 100</span></td>
                            <td class="py-3 text-slate-400">{{ \Carbon\Carbon::parse($d['latest'])->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                        </table>
        </div>
        @else
            <p class="text-sm text-slate-500">No phishing detections in this period yet.</p>
        @endif
    </div>

    @if ($breakdown['total'] === 0)
        <p class="text-center text-sm text-slate-500 mt-6">No reports found in this period yet — try a wider date range or submit a few scans first.</p>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const activityCtx = document.getElementById('activityChart');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Reports',
                    data: @json($chartCounts),
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56, 189, 248, 0.15)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#38bdf8',
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b', maxTicksLimit: 8 }, grid: { color: 'rgba(100,116,139,0.1)' } },
                    y: { beginAtZero: true, ticks: { color: '#64748b', precision: 0 }, grid: { color: 'rgba(100,116,139,0.1)' } }
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
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    </script>
    @endpush

</x-layouts.dashboard>