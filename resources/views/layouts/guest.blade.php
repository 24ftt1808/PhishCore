<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PhishCore') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .auth-in { animation: auth-in .7s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes auth-in { from { opacity: 0; transform: translateY(16px) scale(.985); } to { opacity: 1; transform: none; } }
        .auth-float { animation: auth-float 6s ease-in-out infinite; }
        @keyframes auth-float { 50% { transform: translateY(-8px); } }
        @media (prefers-reduced-motion: reduce) { .auth-in, .auth-float { animation: none; } }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">

    {{-- Same animated background as the landing page --}}
    <div class="hero-aurora" aria-hidden="true"><span></span><span></span><span></span></div>

    <div class="relative z-10 min-h-screen grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

        {{-- LEFT: form panel --}}
        <div class="flex flex-col px-5 sm:px-10 lg:px-14 py-8">
            <a href="{{ route('welcome') }}" class="auth-in inline-flex items-center gap-2.5 self-start">
                <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-9 h-9 object-contain">
                <span class="leading-tight">
                    <span class="block font-bold text-white">PhishCore</span>
                    <span class="block text-[11px] font-medium tracking-wide text-sky-300/90 mt-0.5">DETECTION PLATFORM</span>
                </span>
            </a>

            <div class="flex-1 flex items-center justify-center py-8">
                <div class="auth-in glass-panel w-full max-w-md rounded-3xl p-7 sm:p-9" style="--d:.08s">
                    {{ $slot }}
                </div>
            </div>

            <p class="text-center lg:text-left text-xs text-slate-500">
                <a href="{{ route('welcome') }}" class="hover:text-slate-300 transition">&larr; Back to home</a>
            </p>
        </div>

        {{-- RIGHT: brand panel --}}
        <div class="hidden lg:flex relative items-center justify-center overflow-hidden border-l border-white/[0.06]">
            <div class="relative text-center px-12 max-w-lg w-full">
                @isset($rightPanel)
                    <div class="auth-in" style="--d:.15s">{{ $rightPanel }}</div>
                @else
                    <div class="auth-in" style="--d:.15s">
                        <div class="auth-float relative w-40 h-40 mx-auto mb-9 grid place-items-center rounded-[2rem] border border-white/10 bg-white/[0.04] shadow-[0_0_60px_-10px_rgba(56,189,248,0.35)]">
                            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-24 h-24 object-contain">
                        </div>

                        <h2 class="text-3xl font-bold text-white mb-4 leading-snug">Centralised Phishing Detection</h2>
                        <p class="text-slate-400 mb-10 leading-relaxed">
                            Protecting Brunei's digital infrastructure through AI-powered threat analysis and real-time phishing detection.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        @foreach ([['489', 'URLS SCANNED'], ['97.4%', 'ACCURACY'], ['2.1s', 'AVG SCAN TIME']] as $i => $stat)
                            <div class="auth-in glass-card py-5 px-2" style="--d:{{ 0.3 + $i * 0.1 }}s">
                                <p class="text-2xl font-bold text-sky-300 tabular-nums">{{ $stat[0] }}</p>
                                <p class="text-[10px] tracking-wider text-slate-400 mt-1.5">{{ $stat[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endisset
            </div>

            <p class="absolute bottom-8 text-xs tracking-wide text-slate-500">
                PHISHCORE · AI-POWERED PHISHING DETECTION
            </p>
        </div>

    </div>
</body>
</html>