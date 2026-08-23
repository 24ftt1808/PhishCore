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

<div class="grid md:grid-cols-2 gap-4">
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