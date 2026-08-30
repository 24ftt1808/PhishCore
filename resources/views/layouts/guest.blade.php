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
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div class="min-h-screen flex">

               {{-- LEFT: form panel --}}
        <div class="w-full lg:w-2/5 flex flex-col px-10 py-10 lg:px-16">
            <a href="{{ route('welcome') }}" class="flex items-center gap-2">
<span class="w-9 h-9 flex items-center justify-center">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-9 h-9 object-contain">
</span>
                                <span class="leading-tight">
                    <span class="block font-bold text-white">PhishCore</span>
                    <span class="block text-[11px] tracking-wide text-sky-400 mt-0.5">DETECTION PLATFORM</span>
                </span>
            </a>

            <div class="flex-1 flex items-center">
                <div class="max-w-md w-full">
                    {{ $slot }}
                </div>
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
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-32 h-32 object-contain">
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