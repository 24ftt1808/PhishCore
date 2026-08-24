@php
    $totalScore = max($analysis->risk_score, 1); // avoid divide-by-zero
    $breakdownRows = collect($analysis->flags ?? [])
        ->map(function ($check) use ($totalScore) {
            $pct = round((($check['points'] ?? 0) / $totalScore) * 100);
            return array_merge($check, ['pct' => $pct]);
        })
        ->sortByDesc('points')
        ->values();

    $refId = 'PG-' . $report->created_at->format('Y-md') . '-' . strtoupper(substr(md5($report->id), 0, 5));
@endphp

<div class="{{ $style['bg'] }} {{ $style['border'] }} border rounded-2xl p-8 mb-10">
    <div class="flex items-start justify-between gap-6 flex-wrap">
        <div>
            <p class="{{ $style['text'] }} font-semibold flex items-center gap-2 mb-1">
                <span>⚠</span> {{ $style['label'] }}
            </p>
            <p class="text-xs text-slate-500">Scanned {{ $report->created_at->format('j F Y \a\t g:i A') }}</p>
        </div>
        <span class="text-xs font-medium px-3 py-1 rounded-full {{ $style['threatBg'] }}">{{ $style['threat'] }}</span>
    </div>

    <div class="grid md:grid-cols-3 gap-8 items-center mt-5">
        <div class="md:col-span-2 space-y-3">
            <p class="text-xs text-slate-500">SCANNED URL</p>
            <div class="bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-300 break-all">
                {{ $report->url }}
            </div>
        </div>
        <div class="flex flex-col items-center">
            <div class="relative w-32 h-32 {{ $style['ring'] }}">
                <svg class="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="{{ 54 }}" stroke="#1e293b" stroke-width="10" fill="none" />
                    <circle cx="60" cy="60" r="{{ 54 }}" stroke="currentColor" stroke-width="10" fill="none"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $offset }}" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold text-white">{{ $analysis->risk_score }}</span>
                    <span class="text-[10px] text-slate-500">/ 100</span>
                </div>
            </div>
            <p class="text-[10px] tracking-wide text-slate-500 mt-2">RISK SCORE</p>
        </div>
    </div>
</div>

<div class="flex gap-4 mb-8">
    <a href="{{ route('scan.index') }}"
       class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition">
        🔍 Scan Another URL
    </a>
    <span class="px-5 py-2.5 rounded-lg border border-slate-800 text-slate-600 text-sm cursor-not-allowed" title="Coming soon">
        📄 Download PDF Report
    </span>
    @guest
        <a href="{{ route('register') }}"
           class="px-5 py-2.5 rounded-lg border border-slate-700 text-slate-200 text-sm hover:bg-slate-900 transition">
            Create Account to Save History
        </a>
    @endguest
</div>

<h2 class="text-lg font-bold text-white mb-1">Detection Details</h2>
<p class="text-sm text-slate-500 mb-5">Results from each of PhishCore's four detection layers.</p>

<div class="grid md:grid-cols-2 gap-4 mb-10">
    @foreach ($analysis->flags ?? [] as $check)
        @php
            $colorClass = $statusColors[$check['status']] ?? $statusColors['SAFE'];
            $icon = $checkIcons[$check['name']] ?? '•';
        @endphp
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-white font-medium flex items-center gap-2">
                    <span>{{ $icon }}</span> {{ $check['name'] }}
                </p>
                <span class="text-[10px] font-medium px-2 py-1 rounded-full border {{ $colorClass }}">{{ $check['status'] }}</span>
            </div>
            <p class="text-sm text-slate-400">{{ $check['message'] }}</p>
        </div>
    @endforeach
</div>

{{-- RISK BREAKDOWN --}}
<div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8">
    <h2 class="text-lg font-bold text-white mb-1">Risk Breakdown</h2>
    <p class="text-sm text-slate-500 mb-5">Contribution of each detection layer to the overall risk score.</p>

    @if ($analysis->risk_score > 0 && $breakdownRows->where('points', '>', 0)->count() > 0)
        <div class="space-y-4">
            @foreach ($breakdownRows->where('points', '>', 0) as $row)
                @php
                    $barColor = $row['pct'] >= 80 ? 'bg-red-400' : ($row['pct'] >= 40 ? 'bg-orange-400' : 'bg-sky-400');
                    $textColor = $row['pct'] >= 80 ? 'text-red-400' : ($row['pct'] >= 40 ? 'text-orange-400' : 'text-sky-400');
                @endphp
                <div>
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span class="text-slate-300">{{ $row['name'] }}</span>
                        <span class="{{ $textColor }} font-medium">{{ $row['pct'] }}%</span>
                    </div>
                    <span class="block h-2 rounded-full bg-slate-800 overflow-hidden">
                        <span class="block h-full {{ $barColor }}" style="width: {{ $row['pct'] }}%"></span>
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500">No risk contributors — this site passed every detection layer cleanly.</p>
    @endif
</div>

{{-- RECOMMENDED ACTIONS (only for suspicious/phishing) --}}
@if ($analysis->verdict !== 'clean')
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8">
        <div class="flex items-start gap-4 mb-5">
            <span class="w-10 h-10 rounded-lg bg-orange-500/10 border border-orange-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-white">Recommended Actions</h2>
                <p class="text-sm text-slate-500">Steps to take if you visited or interacted with this site.</p>
            </div>
        </div>
        <ol class="space-y-3">
            @foreach ([
                'Do not enter usernames, passwords, or payment information on this site.',
                'Close this website immediately and clear your browser cache.',
                'Do not download any files originating from this domain.',
                'Report the link to the organisation being impersonated, if applicable.',
                'If you already entered login details, change your password immediately.',
            ] as $i => $action)
                <li class="flex items-start gap-3 text-sm text-slate-300">
                    <span class="w-5 h-5 rounded-full bg-orange-500/20 text-orange-400 text-xs font-medium flex items-center justify-center shrink-0 mt-0.5">{{ $i + 1 }}</span>
                    {{ $action }}
                </li>
            @endforeach
        </ol>
    </div>
@endif

{{-- TECHNICAL INFORMATION --}}
<div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden" x-data="{ open: false }">
    <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left">
        <span class="flex items-center gap-2 text-white font-medium">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
            Technical Information
        </span>
        <span class="flex items-center gap-2">
            <span class="text-xs text-slate-500">Click to expand</span>
            <svg class="w-4 h-4 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </span>
    </button>
    <div x-show="open" x-collapse class="px-6 pb-6 border-t border-slate-800 pt-4">
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-xs text-slate-500 mb-1">SCAN REFERENCE ID</dt>
                <dd class="text-slate-300">{{ $refId }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">VERDICT</dt>
                <dd class="text-slate-300 uppercase">{{ $analysis->verdict }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">RISK SCORE</dt>
                <dd class="text-slate-300">{{ $analysis->risk_score }} / 100</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">DOMAIN AGE</dt>
                <dd class="text-slate-300">{{ $analysis->domain_age_days !== null ? $analysis->domain_age_days . ' days' : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">SCANNED BY</dt>
                <dd class="text-slate-300">{{ $report->user?->name ?? 'Guest (unregistered)' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">SCAN DURATION</dt>
                <dd class="text-slate-300">{{ $analysis->duration_ms !== null ? round($analysis->duration_ms / 1000, 2) . 's' : 'Not recorded' }}</dd>
            </div>
        </dl>

        <p class="text-xs text-slate-500 mt-5 mb-2">RAW CHECK RESULTS</p>
        <div class="bg-slate-950/60 border border-slate-800 rounded-lg p-4 space-y-2">
            @foreach ($analysis->flags ?? [] as $check)
                <p class="text-xs text-slate-400 font-mono">
                    <span class="text-slate-200">{{ $check['name'] }}:</span>
                    {{ $check['status'] }} ({{ $check['points'] ?? 0 }} pts) — {{ $check['message'] }}
                </p>
            @endforeach
        </div>
    </div>
</div>