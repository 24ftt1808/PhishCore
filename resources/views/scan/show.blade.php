<x-guest-layout>

    @php
        $verdictStyles = [
            'phishing' => ['bg' => 'bg-red-500/10', 'border' => 'border-red-500/20', 'text' => 'text-red-400', 'ring' => 'text-red-500', 'label' => 'PHISHING WEBSITE DETECTED'],
            'suspicious' => ['bg' => 'bg-orange-500/10', 'border' => 'border-orange-500/20', 'text' => 'text-orange-400', 'ring' => 'text-orange-500', 'label' => 'SUSPICIOUS WEBSITE'],
            'clean' => ['bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/20', 'text' => 'text-emerald-400', 'ring' => 'text-emerald-500', 'label' => 'WEBSITE APPEARS SAFE'],
        ];
        $style = $verdictStyles[$analysis->verdict] ?? $verdictStyles['clean'];
    @endphp

    <div class="max-w-3xl mx-auto py-16 px-6">

        <div class="{{ $style['bg'] }} {{ $style['border'] }} border rounded-2xl p-6 mb-8">
            <div class="flex items-start justify-between gap-6 flex-wrap">
                <div>
                    <p class="{{ $style['text'] }} font-semibold flex items-center gap-2 mb-1">
                        <span>⚠</span> {{ $style['label'] }}
                    </p>
                    <p class="text-xs text-slate-500">Scanned {{ $report->created_at->format('j F Y \a\t g:i A') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold {{ $style['ring'] }}">{{ $analysis->risk_score }}<span class="text-lg text-slate-500">/100</span></p>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-3 mt-4 text-sm text-slate-300 break-all">
                {{ $report->url }}
            </div>
        </div>

        @if (count($analysis->flags ?? []) > 0)
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-8">
                <h2 class="text-white font-semibold mb-4">Detection Details</h2>
                <ul class="space-y-3">
                    @foreach ($analysis->flags as $flag)
                        <li class="flex items-start gap-3 text-sm text-slate-300">
                            <span class="text-orange-400 mt-0.5">●</span>
                            {{ $flag }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-8 text-sm text-slate-400">
                No suspicious indicators were detected for this URL.
            </div>
        @endif

        @if ($analysis->domain_age_days !== null)
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 mb-8">
                <p class="text-xs text-slate-500 mb-1">DOMAIN AGE</p>
                <p class="text-white font-medium">{{ $analysis->domain_age_days }} days old</p>
            </div>
        @endif

        <div class="flex gap-4">
            <a href="{{ route('scan.index') }}"
               class="px-6 py-3 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white font-medium hover:opacity-90 transition">
                Scan Another URL
            </a>
            @guest
                <a href="{{ route('register') }}"
                   class="px-6 py-3 rounded-lg border border-slate-700 text-slate-200 hover:bg-slate-900 transition">
                    Create Account to Save History
                </a>
            @endguest
        </div>
    </div>

</x-guest-layout>