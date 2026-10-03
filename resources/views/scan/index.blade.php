@php
    $inner = View::make('scan._scanner-card', ['recentScans' => $recentScans, 'wide' => auth()->check()])->render();
@endphp

@auth
    <x-layouts.dashboard>
        <div class="sc-in mb-7" style="animation: sc-in .6s cubic-bezier(.2,.9,.3,1) both">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1.5">Scan</h1>
            <p class="text-slate-300 text-sm">Check a website URL, sender email, phone number, or screenshot for phishing threats.</p>
        </div>
        {!! $inner !!}
    </x-layouts.dashboard>
@else
    <x-layouts.public>
        <div class="text-center mb-10">
            <div class="w-14 h-14 mx-auto rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Report a Threat</h1>
            <p class="text-slate-300">Analyse a website URL, sender email, phone number, or screenshot for phishing threats.</p>
        </div>
        {!! $inner !!}
    </x-layouts.public>
@endauth