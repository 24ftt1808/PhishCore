@php
    $advice = \App\Services\ScanAdvice::for((string) $report->type, (string) $analysis->verdict);
@endphp

{{-- WHAT TO DO --}}
@if ($advice)
    <div class="r-card r-in p-6 mb-6" style="--d:.15s">
        <div class="flex items-start gap-4 mb-5">
            <span class="r-tile" style="--c: 251,146,60">
                <svg class="w-5 h-5 text-orange-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-white">What you should do</h2>
                <p class="text-sm text-slate-300">{{ $advice['intro'] }}</p>
            </div>
        </div>
        <ol class="space-y-3">
            @foreach ($advice['steps'] as $i => $action)
                <li class="flex items-start gap-3 text-sm text-slate-100">
                    <span class="w-6 h-6 rounded-full bg-orange-500/15 border border-orange-400/30 text-orange-300 text-xs font-semibold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                    <span class="pt-0.5">{{ $action }}</span>
                </li>
            @endforeach
        </ol>

        <div class="r-well mt-6 p-4" data-advice="already-affected">
            <p class="text-sm font-semibold text-white">{{ $advice['affectedTitle'] }}</p>
            <ol class="mt-2 space-y-1.5 text-sm text-slate-200 list-decimal list-inside">
                @foreach ($advice['affected'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ol>
        </div>
    </div>
@endif