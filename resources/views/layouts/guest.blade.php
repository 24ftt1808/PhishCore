<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PhishCore') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div class="min-h-screen flex">

        {{-- LEFT: form panel --}}
        <div class="w-full lg:w-2/5 flex flex-col px-10 py-10 lg:px-16 lg:pt-14">
            <a href="{{ route('welcome') }}" class="flex items-center gap-2 mb-14">
                <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
                </span>
                                <span class="leading-tight">
                    <span class="block font-bold text-white">PhishCore</span>
                    <span class="block text-[11px] tracking-wide text-sky-400 mt-0.5">DETECTION PLATFORM</span>
                </span>
            </a>

            <div class="max-w-md">
                {{ $slot }}
            </div>
        </div>

               {{-- RIGHT: brand panel --}}
        <div class="hidden lg:flex lg:w-3/5 relative bg-gradient-to-br from-slate-900 to-slate-950 items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,theme(colors.sky.500/15%),transparent_70%)]"></div>

            <div class="relative text-center px-12 max-w-md">
                @isset($rightPanel)
                    {{ $rightPanel }}
                @else
                    <div class="relative w-56 h-56 mx-auto mb-8 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-[2.5rem] border border-slate-800/60"></div>
                        <div class="w-20 h-20 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center">
                            <svg class="w-10 h-10 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                            </svg>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-white mb-4">Centralised Phishing Detection</h2>
                    <p class="text-slate-400 mb-10">
                        Protecting Brunei's digital infrastructure through AI-powered threat analysis and real-time phishing detection.
                    </p>

                    <div class="flex justify-center gap-10">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-sky-400">489</p>
                            <p class="text-[11px] tracking-wide text-slate-500">URLS SCANNED</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-sky-400">97.4%</p>
                            <p class="text-[11px] tracking-wide text-slate-500">ACCURACY</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-sky-400">2.1s</p>
                            <p class="text-[11px] tracking-wide text-slate-500">AVG SCAN TIME</p>
                        </div>
                    </div>
                @endisset
            </div>

            <p class="absolute bottom-8 text-xs tracking-wide text-slate-600">
                POLITEKNIK BRUNEI · FINAL YEAR PROJECT 2026
            </p>
        </div>

    </div>
</body>
</html>