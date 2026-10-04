<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PhishCore') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div class="hero-aurora" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>

    @include('partials.navbar')

    <main class="relative z-10 max-w-3xl mx-auto px-6 py-14">
        {{ $slot }}
    </main>

    <div class="relative z-10">
        @include('partials.footer')
    </div>
</body>
</html>