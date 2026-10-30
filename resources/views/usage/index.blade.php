<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06); }
        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }
        .u-bar { min-height: 2px; border-radius: 3px 3px 0 0; }
        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } }
    </style>

    @php
        $cards = [
            ['VISITORS TODAY', $summary['visitors_today'], 'text-sky-300', $summary['visitors_30'].' in the last 30 days'],
            ['VISITORS, LAST 7 DAYS', $summary['visitors_7'], 'text-sky-300', $summary['views_7'].' page views'],
            ['SCANS, LAST 7 DAYS', $summary['scans_7'], 'text-violet-300', $summary['scans_30'].' in the last 30 days'],
            ['NEW SIGN-UPS, LAST 7 DAYS', $summary['signups_7'], 'text-emerald-300', $summary['signups_30'].' in the last 30 days'],
            ['REGISTERED USERS', $summary['users_total'], 'text-white', 'all accounts'],
            ['ACTIVE MEMBERS, 7 DAYS', $summary['active_7'], 'text-white', $summary['active_30'].' in the last 30 days'],
            ['GUEST SCANS, 30 DAYS', $summary['guest_scans_30'], 'text-amber-300', $summary['scans_30'] > 0 ? round($summary['guest_scans_30'] / $summary['scans_30'] * 100).'% of all scans' : 'no scans yet'],
        ];
    @endphp

    <div class="h-in mb-5 sm:mb-6">
        <h1 class="text-2xl font-bold text-white mb-1">Website Usage</h1>
        <p class="text-slate-300 text-sm">How many people use PhishCore. These are counts only: visitors are anonymous daily codes, never names or addresses.</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
        @foreach ($cards as $i => [$label, $value, $tone, $note])
            <div class="h-in h-card p-3.5 sm:p-5" style="--d: {{ .04 + $i * .03 }}s">
                <p class="text-[11px] sm:text-xs font-medium tracking-wide text-slate-300 mb-1">{{ $label }}</p>
                <p class="text-2xl sm:text-3xl font-bold tabular-nums {{ $tone }}">{{ number_format($value) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $note }}</p>
            </div>
        @endforeach
    </div>

    <div class="h-in h-card p-4 sm:p-5 mb-5" style="--d:.28s">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h2 class="text-sm font-semibold text-white">Last 30 days</h2>
            <div class="flex items-center gap-4 text-xs text-slate-300">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-sky-400"></span>Visitors</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-violet-400"></span>Scans</span>
            </div>
        </div>
        <div class="flex items-end gap-[3px] h-40" role="img" aria-label="Daily visitors and scans for the last 30 days">
            @foreach ($days as $day)
                <div class="flex-1 min-w-0 h-full flex items-end gap-px" title="{{ $day['label'] }}: {{ $day['visitors'] }} visitors, {{ $day['scans'] }} scans, {{ $day['signups'] }} sign-ups">
                    <div class="u-bar flex-1 bg-sky-400/80" style="height: {{ $day['visitors'] > 0 ? max(2, round($day['visitors'] / $maxVisitors * 100)) : 0 }}%"></div>
                    <div class="u-bar flex-1 bg-violet-400/80" style="height: {{ $day['scans'] > 0 ? max(2, round($day['scans'] / $maxScans * 100)) : 0 }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-[11px] text-slate-400">
            <span>{{ $days[0]['label'] }}</span>
            <span>{{ $days[count($days) - 1]['label'] }}</span>
        </div>
    </div>

    <div class="h-in h-card overflow-hidden" style="--d:.32s">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[480px]">
                <thead>
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-4 sm:px-5 py-3">DAY</th>
                        <th class="px-4 sm:px-5 py-3 text-right">VISITORS</th>
                        <th class="px-4 sm:px-5 py-3 text-right">PAGE VIEWS</th>
                        <th class="px-4 sm:px-5 py-3 text-right">SCANS</th>
                        <th class="px-4 sm:px-5 py-3 text-right">SIGN-UPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-500/20">
                    @foreach (array_reverse(array_slice($days, -14)) as $day)
                        <tr class="h-row">
                            <td class="px-4 sm:px-5 py-3 text-slate-100">{{ $day['label'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-sky-300">{{ $day['visitors'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-slate-300">{{ $day['views'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-violet-300">{{ $day['scans'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-emerald-300">{{ $day['signups'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 text-xs leading-relaxed text-slate-500">
        A visitor is counted once per day, so the same person on two days counts twice. Bots and search-engine crawlers are left out.
        @if ($summary['tracking_since'])
            Visits are counted since {{ \Illuminate\Support\Carbon::parse($summary['tracking_since'])->format('j F Y') }}.
        @else
            No visits have been counted yet.
        @endif
        Scans, sign-ups and logins come from the accounts and scans already stored.
    </p>
</x-layouts.dashboard>