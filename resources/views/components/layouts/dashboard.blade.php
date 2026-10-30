{{-- true only for the first page after signing in or registering: it plays the entrance animation once --}}
@php $intro = (bool) session()->pull('intro', false); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PhishCore') }} — Dashboard</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased"@if ($intro) data-intro="1" @endif>
    {{-- Weak phones and data-saver mode get the plain version of the glass effects: no blur, no bouncy animation --}}
    <script>
        try {
            var c = navigator.connection || {};
            if ((navigator.deviceMemory && navigator.deviceMemory <= 2) || (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2) || c.saveData) {
                document.documentElement.classList.add('fx-lite');
            }
        } catch (e) {}
    </script>

    {{-- Same animated background as the public pages, dimmed so data stays readable --}}
    <div class="hero-aurora dash-aurora" aria-hidden="true" style="opacity:.55"><span></span><span></span><span></span></div>

    <div class="relative z-10 flex lg:h-screen dash-scope" x-data="{ sidebarOpen: false, logoutOpen: false, scrolled: false, init() { this.scrolled = window.scrollY > 110; } }" @keydown.escape.window="logoutOpen = false" @scroll.window.passive="scrolled = window.scrollY > 110">

        {{-- Soft fade under the floating top bar so content doesn't look cut off as it scrolls up (phones only) --}}
        <div class="pointer-events-none fixed top-0 inset-x-0 z-[34] h-24 bg-gradient-to-b from-slate-950/90 via-slate-950/50 to-transparent lg:hidden" aria-hidden="true"></div>

        {{-- MOBILE TOP BAR --}}
        @php
            $tbTitle = match (true) {
                request()->routeIs('dashboard') => 'Dashboard',
                request()->routeIs('scan.index', 'scan.show') => 'Scan',
                request()->routeIs('scan.history') => 'Scan History',
                request()->routeIs('analytics') => 'Analytics',
                request()->routeIs('chat.index') => 'AI Chat',
                request()->routeIs('play.index') => 'Phish Lab',
                request()->routeIs('investigations.index') => 'Investigations',
                request()->routeIs('reports.index') => 'Reports',
                request()->routeIs('scam-news.index') => 'Scam News',
                request()->routeIs('reported-numbers.index') => 'Reported Numbers',
                request()->routeIs('safe-files.index') => 'Safe Files',
                request()->routeIs('user-management.index') => 'User Management',
                request()->routeIs('usage.index') => 'Website Usage',
                request()->routeIs('profile.edit') => 'Settings',
                default => 'PhishCore',
            };
        @endphp
        <div class="dash-topbar lg:hidden fixed z-[35] flex items-center justify-between" :class="{ 'is-scrolled': scrolled }">
            {{-- left island: logo + the page you are on --}}
            <a href="{{ route('welcome') }}" class="tb-isl tb-brand" aria-label="PhishCore home">
                <span class="tb-chip"><img src="{{ asset('phishcore-logo-icon.png') }}" alt="" class="w-6 h-6 object-contain"></span>
                {{-- the page already shows its own heading, so the bar says "PhishCore" until you scroll past it, then shows the page name --}}
                <span class="tb-ttl">
                    <span class="tb-name">PhishCore</span>
                    <span class="tb-page"><small>PhishCore</small><strong>{{ $tbTitle }}</strong></span>
                </span>
            </a>
            {{-- right island: notifications + menu --}}
            <div class="tb-isl tb-actions">
                <button type="button" @click.stop="$store.bell.toggle()" :aria-expanded="$store.bell.open" aria-label="Notifications" class="tb-btn"
                        :class="{ 'is-open': $store.bell.open, 'has-unread': $store.bell.unread > 0 }">
                    <svg class="tb-bell" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    <span class="nb-count tb-count" x-show="$store.bell.unread > 0" x-cloak x-text="$store.bell.unread > 99 ? '99+' : $store.bell.unread"></span>
                </button>
                <button type="button" @click="sidebarOpen = true" aria-label="Open the menu" class="tb-btn tb-burger">
                    <span aria-hidden="true"><i></i><i></i><i></i></span>
                </button>
            </div>
        </div>

        {{-- MOBILE BACKDROP --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="lg:hidden fixed inset-0 bg-black/60 z-40"
             x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        {{-- SIDEBAR --}}
        @php
            $u = auth()->user();
            $navMain = [
                ['route' => 'dashboard',        'match' => ['dashboard'],                 'label' => 'Dashboard',       'tone' => 'sky',    'show' => true,                      'anim' => 'spin', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
                ['route' => 'scan.index',       'match' => ['scan.index', 'scan.show'],   'label' => 'Scan',            'tone' => 'sky',    'show' => true,                      'anim' => 'orbit', 'icon' => 'M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z'],
                ['route' => 'scan.history',     'match' => ['scan.history'],              'label' => 'Scan History',    'tone' => 'sky',    'show' => true,                      'anim' => 'rewind', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['route' => 'analytics',        'match' => ['analytics'],                 'label' => 'Analytics',       'tone' => 'sky',    'show' => true,                      'anim' => 'rise', 'icon' => 'M3 13.5l3.75-3.75 3 3 4.5-4.5M3 19.5h18'],
                ['route' => 'chat.index',       'match' => ['chat.index'],                'label' => 'AI Chat',         'tone' => 'sky',    'show' => app(\App\Services\ChatAdvisor::class)->enabled(), 'anim' => 'bot', 'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
                ['route' => 'play.index',       'match' => ['play.index'],                'label' => 'Phish Lab',       'tone' => 'sky',    'show' => true,                      'anim' => 'play', 'icon' => 'M6.5 8h11a4.5 4.5 0 014.4 5.4l-.8 3.8a2.5 2.5 0 01-4.2 1.2L15 16H9l-1.9 2.4a2.5 2.5 0 01-4.2-1.2l-.8-3.8A4.5 4.5 0 016.5 8zM7.5 11v3M6 12.5h3M16 12h.01M18 13.5h.01'],
                ['route' => 'investigations.index', 'match' => ['investigations.index'],  'label' => 'Investigations',  'tone' => 'violet', 'show' => (bool) $u->is_team_member, 'anim' => 'open', 'icon' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'],
                ['route' => 'reports.index',    'match' => ['reports.index'],             'label' => 'Reports',         'tone' => 'violet', 'show' => (bool) $u->is_team_member, 'anim' => 'nudge', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
                ['route' => 'scam-news.index',   'match' => ['scam-news.index'],           'label' => 'Scam News',       'tone' => 'violet', 'show' => (bool) ($u->is_team_member || $u->role === 'admin'), 'anim' => 'nudge', 'icon' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z'],
                ['route' => 'reported-numbers.index', 'match' => ['reported-numbers.index'], 'label' => 'Reported Numbers', 'tone' => 'violet', 'show' => (bool) ($u->is_team_member || $u->role === 'admin'), 'anim' => 'nudge', 'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                ['route' => 'safe-files.index', 'match' => ['safe-files.index'], 'label' => 'Safe Files', 'tone' => 'violet', 'show' => (bool) ($u->is_team_member || $u->role === 'admin'), 'anim' => 'pulse', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                ['route' => 'usage.index',      'match' => ['usage.index'],               'label' => 'Website Usage',   'tone' => 'violet', 'show' => $u->role === 'admin',     'anim' => 'rise', 'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
                ['route' => 'user-management.index', 'match' => ['user-management.index'], 'label' => 'User Management', 'tone' => 'violet', 'show' => $u->role === 'admin',     'anim' => 'pulse', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
            ];
            $navPrefs = [
                ['route' => 'profile.edit',     'match' => ['profile.edit'],              'label' => 'Settings',        'tone' => 'sky',    'show' => true,                      'anim' => 'tune', 'icon' => ''],
            ];
            $roleLabel = match (true) {
                $u->role === 'admin' => 'Admin',
                (bool) $u->is_team_member => 'Team Member',
                default => 'Member',
            };
        @endphp

        <style>
            /* navy with a soft blue glow from the top */
            /* floating island: deep brand navy with a soft sky glow from the top, solid so it never looks grey */
            .side-bg {
                background:
                    radial-gradient(130% 38% at 0% 0%, rgba(56, 189, 248, .17), transparent 72%),
                    linear-gradient(180deg, #0e1e44 0%, #0a1634 50%, #070f26 100%);
                border: 1px solid rgba(125, 211, 252, .18);
                box-shadow: 0 30px 60px -24px rgba(0, 0, 0, .85), 0 0 0 1px rgba(2, 8, 23, .4), inset 0 1px 0 rgba(255, 255, 255, .07);
            }
            .side-island { top: .75rem; bottom: .75rem; left: .75rem; border-radius: 2rem;
                           /* row height follows the screen height and the number of links, so a long list (admins) fits without scrolling */
                           --side-row: clamp(2.375rem, calc((100vh - 17rem) / var(--side-rows, 8)), 3rem); }
            @media (min-width: 1024px) { .side-island { top: 1rem; bottom: 1rem; left: 1rem; } }
            /* phones: the top bar floats as a frosted-glass pill, like the public navbar, and the page blurs behind it */
            .dash-topbar {
                top: max(.75rem, env(safe-area-inset-top)); left: .75rem; right: .75rem; gap: .5rem;
                pointer-events: none; /* only the two islands catch taps, so the gap between them stays clickable-through */
            }
            /* two floating glass islands, like the public navbar: logo + page name on the left, bell + menu on the right */
            .tb-isl {
                pointer-events: auto; display: flex; align-items: center; height: 3.25rem; border-radius: 9999px;
                background: linear-gradient(180deg, rgba(255, 255, 255, .11), rgba(255, 255, 255, .03)), rgba(9, 18, 40, .4);
                border: 1px solid rgba(255, 255, 255, .17);
                box-shadow: 0 10px 28px -12px rgba(0, 0, 0, .6), inset 0 1px 0 rgba(255, 255, 255, .18), inset 0 -1px 0 rgba(0, 0, 0, .18);
                -webkit-backdrop-filter: blur(12px) saturate(160%); backdrop-filter: blur(12px) saturate(160%);
            }
            .tb-brand { gap: .55rem; padding: 0 1rem 0 .3rem; min-width: 0; text-decoration: none; -webkit-tap-highlight-color: transparent; }
            .tb-chip { flex: none; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 9999px;
                background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .16); }
            .tb-ttl { display: grid; min-width: 0; }
            .tb-ttl > * { grid-area: 1 / 1; align-self: center; min-width: 0; transition: opacity .22s ease, transform .22s ease; }
            .tb-name { font-weight: 800; font-size: 1rem; color: #fff; white-space: nowrap; }
            .tb-page { display: flex; flex-direction: column; line-height: 1.15; opacity: 0; transform: translate3d(0, 6px, 0); }
            .is-scrolled .tb-name { opacity: 0; transform: translate3d(0, -6px, 0); }
            .is-scrolled .tb-page { opacity: 1; transform: none; }
            .tb-page small { font: 600 .56rem/1 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: .16em; text-transform: uppercase; color: #7dd3fc; margin-bottom: .2rem; }
            .tb-ttl strong { font-weight: 800; font-size: 1rem; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .tb-actions { flex: none; gap: .25rem; padding: 0 .3rem; }
            .tb-actions .tb-btn { background: none; border-color: transparent; box-shadow: none; }
            .tb-actions .tb-btn.is-open { color: #bae6fd; border-color: rgba(125, 211, 252, .55); background: linear-gradient(180deg, rgba(56, 189, 248, .32), rgba(56, 189, 248, .1)); }
            @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) { .tb-isl { background: #0b1830; } }

            /* the two round glass buttons in the top bar: only transform and opacity move, so they stay smooth on weak phones */
            .tb-btn { position: relative; display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 9999px; color: #e2e8f0;
                background: linear-gradient(180deg, rgba(255, 255, 255, .16), rgba(255, 255, 255, .05));
                border: 1px solid rgba(255, 255, 255, .2);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28), 0 4px 10px -4px rgba(0, 0, 0, .5);
                transition: transform .35s cubic-bezier(.3, 1.5, .5, 1), background-color .2s, color .2s, border-color .2s; -webkit-tap-highlight-color: transparent; }
            .tb-btn:active { transform: scale(.88); }
            .tb-btn.is-open { color: #bae6fd; border-color: rgba(125, 211, 252, .55); background: linear-gradient(180deg, rgba(56, 189, 248, .32), rgba(56, 189, 248, .1)); }
            .tb-btn:focus-visible { outline: 2px solid #7dd3fc; outline-offset: 2px; }
            .tb-count { top: -.2rem; right: -.2rem; box-shadow: 0 0 0 2px rgba(9, 18, 40, .9); }
            .tb-bell { transform-origin: 50% 12%; }
            .tb-btn.is-open .tb-bell { animation: tb-ring .7s ease-out; }
            .tb-btn.has-unread:not(.is-open) .tb-bell { animation: tb-ring 1.1s ease-out .6s 2; }
            @keyframes tb-ring { 0%, 100% { transform: rotate(0); } 15% { transform: rotate(16deg); } 35% { transform: rotate(-13deg); } 55% { transform: rotate(8deg); } 75% { transform: rotate(-4deg); } }
            .tb-burger span { display: grid; gap: 4px; width: 1.1rem; }
            .tb-burger i { display: block; height: 2px; border-radius: 2px; background: currentColor; transition: transform .3s cubic-bezier(.3, 1.5, .5, 1), width .3s; transform-origin: left center; }
            .tb-burger i:nth-child(2) { width: 70%; }
            .tb-burger:active i:nth-child(1) { transform: translateY(-1px) scaleX(.8); }
            .tb-burger:active i:nth-child(2) { width: 100%; }
            .tb-burger:active i:nth-child(3) { transform: translateY(1px) scaleX(.8); }
            .side-scroll { scrollbar-width: none; overscroll-behavior: contain; }
            .side-scroll::-webkit-scrollbar { display: none; }

            /* the background blobs stay as a still glow on signed-in pages: less repaint, smoother hover */
            .dash-aurora span { animation: none !important; }

            .side-link { position: relative; z-index: 1; display: flex; align-items: center; gap: .75rem; min-height: var(--side-row, 3rem); padding: 0 .75rem 0 .375rem; border-radius: 9999px; font-size: .875rem; font-weight: 600; color: #a9b6cb; transition: color .2s; }
            .side-label { transform-origin: left center; transition: transform .28s cubic-bezier(.34, 1.4, .64, 1); }

            /* icon tile: the glow is a pseudo-element that only fades, so it never repaints a shadow */
            .side-ic { position: relative; width: min(2.25rem, calc(var(--side-row, 3rem) - .375rem)); height: min(2.25rem, calc(var(--side-row, 3rem) - .375rem)); display: grid; place-items: center; flex-shrink: 0; border-radius: 9999px; color: #7dd3fc; background-color: rgba(56, 189, 248, .10); border: 1px solid rgba(125, 211, 252, .18); transition: transform .3s cubic-bezier(.34, 1.56, .64, 1), background-color .2s, color .2s; }
            .side-link[data-tone="violet"] .side-ic { color: #c4b5fd; background-color: rgba(167, 139, 250, .12); border-color: rgba(196, 181, 253, .22); }
            .side-ic::before { content: ""; position: absolute; inset: 0; border-radius: inherit; box-shadow: 0 0 16px rgba(56, 189, 248, .55); opacity: 0; transition: opacity .25s; pointer-events: none; }
            .side-link[data-tone="violet"] .side-ic::before { box-shadow: 0 0 16px rgba(167, 139, 250, .55); }


            .side-link:hover { color: #fff; }
            .side-link:hover .side-ic { transform: scale(1.1); background-color: rgba(56, 189, 248, .16); color: #7dd3fc; }
            .side-link:hover .side-ic::before { opacity: 1; }
            .side-link:hover .side-label { transform: translateX(3px) scale(1.07); font-weight: 700; }
            .side-link[data-tone="violet"]:hover .side-ic { background-color: rgba(167, 139, 250, .16); color: #c4b5fd; }


            /* every icon plays its own short animation on hover (transform only, so it stays smooth) */
            .side-ic svg { transform-origin: 50% 50%; }
            .side-link:hover .side-ic svg { animation-duration: .75s; animation-timing-function: cubic-bezier(.34, 1.3, .64, 1); animation-fill-mode: none; }
            .side-link[data-anim="spin"]:hover .side-ic svg   { animation-name: ia-spin; }
            .side-link[data-anim="orbit"]:hover .side-ic svg  { animation-name: ia-orbit; animation-timing-function: linear; animation-duration: .8s; }
            .side-link[data-anim="rewind"]:hover .side-ic svg { animation-name: ia-rewind; animation-timing-function: cubic-bezier(.4, 0, .2, 1); animation-duration: .9s; }
            .side-link[data-anim="rise"]:hover .side-ic svg   { animation-name: ia-rise; }
            .side-link[data-anim="open"]:hover .side-ic svg   { animation-name: ia-open; }
            .side-link[data-anim="nudge"]:hover .side-ic svg  { animation-name: ia-nudge; }
            .side-link[data-anim="play"]:hover .side-ic svg   { animation-name: ia-play; }
            .side-link[data-anim="bot"]:hover .side-ic svg    { animation-name: ia-bot; animation-iteration-count: infinite; animation-timing-function: ease-in-out; animation-duration: .8s; }
            .side-link[data-anim="pulse"]:hover .side-ic svg  { animation-name: ia-pulse; }
            .side-link[data-anim="gear"]:hover .side-ic svg   { animation-name: ia-gear; animation-timing-function: cubic-bezier(.4, 0, .2, 1); animation-duration: .9s; }
            .side-link[data-anim="out"]:hover .side-ic svg    { animation-name: ia-out; }

            @keyframes ia-spin   { to { transform: rotate(90deg); } }
            @keyframes ia-orbit  { 0%, 100% { transform: translate(0, 0); } 20% { transform: translate(2px, 0); } 40% { transform: translate(0, 2px); } 60% { transform: translate(-2px, 0); } 80% { transform: translate(0, -2px); } }
            @keyframes ia-rewind { to { transform: rotate(-360deg); } }
            @keyframes ia-rise   { 0% { transform: translateY(0); } 35% { transform: translateY(-4px) scaleY(1.12); } 65% { transform: translateY(1px); } 100% { transform: translateY(0); } }
            @keyframes ia-open   { 0% { transform: rotate(0); } 30% { transform: rotate(-12deg) scale(1.12); } 60% { transform: rotate(5deg); } 100% { transform: rotate(0); } }
            @keyframes ia-play   { 0% { transform: rotate(0); } 25% { transform: rotate(-16deg) scale(1.12); } 55% { transform: rotate(12deg) scale(1.08); } 80% { transform: rotate(-5deg); } 100% { transform: rotate(0); } }
            @keyframes ia-bot    { 0%, 100% { transform: translateY(0) scale(1.06, .94); } 35% { transform: translateY(-14%) scale(.95, 1.07); } 65% { transform: translateY(0) scale(1.05, .95); } 82% { transform: translateY(-5%) scale(1); } }
            @keyframes ia-nudge  { 0% { transform: translateY(0) rotate(0); } 30% { transform: translateY(-4px) rotate(5deg); } 60% { transform: translateY(1px) rotate(-3deg); } 100% { transform: translateY(0) rotate(0); } }
            @keyframes ia-pulse  { 0%, 100% { transform: scale(1); } 25% { transform: scale(1.28); } 50% { transform: scale(.95); } 75% { transform: scale(1.14); } }
            @keyframes ia-gear   { to { transform: rotate(180deg); } }
            @keyframes ia-out    { 0% { transform: translateX(0); } 40% { transform: translateX(5px); } 70% { transform: translateX(-1px); } 100% { transform: translateX(0); } }
            /* settings icon: the three slider knobs travel along their tracks and settle back (transform only) */
            .ic-tune circle { transform-box: fill-box; transform-origin: center; }
            .side-link[data-anim="tune"]:hover .ic-tune .k1 { animation: ia-k1 .9s cubic-bezier(.4, 0, .2, 1); }
            .side-link[data-anim="tune"]:hover .ic-tune .k2 { animation: ia-k2 .9s cubic-bezier(.4, 0, .2, 1) .06s; }
            .side-link[data-anim="tune"]:hover .ic-tune .k3 { animation: ia-k3 .9s cubic-bezier(.4, 0, .2, 1) .12s; }
            @keyframes ia-k1 { 0%, 100% { transform: translateX(0); } 45% { transform: translateX(8px); } }
            @keyframes ia-k2 { 0%, 100% { transform: translateX(0); } 45% { transform: translateX(-8px); } }
            @keyframes ia-k3 { 0%, 100% { transform: translateX(0); } 45% { transform: translateX(7px); } }
            @media (prefers-reduced-motion: reduce) { .ic-tune circle { animation: none !important; } }

            .side-link[data-active="true"] { color: #fff; }
            .side-link[data-active="true"] .side-ic { background-color: rgba(56, 189, 248, .34); border-color: rgba(186, 230, 253, .5); color: #fff; }
            .side-link[data-active="true"][data-tone="violet"] .side-ic { background-color: rgba(167, 139, 250, .34); border-color: rgba(221, 214, 254, .5); }
            .side-link[data-active="true"] .side-ic::before { opacity: 1; }

            /* moving pill: only transform and opacity change while it travels, so it runs on the GPU */
            .side-hl { position: absolute; top: 0; left: .75rem; right: .75rem; pointer-events: none; will-change: transform; }
            .side-hl-in { width: 100%; height: 100%; border-radius: 9999px; will-change: transform;
                          background: linear-gradient(90deg, rgba(56, 189, 248, .30), rgba(56, 189, 248, .12));
                          border: 1px solid rgba(125, 211, 252, .38);
                          box-shadow: 0 6px 16px -8px rgba(56, 189, 248, .55); }
            .side-hl[data-tone="violet"] .side-hl-in { background: linear-gradient(90deg, rgba(167, 139, 250, .30), rgba(167, 139, 250, .12)); border-color: rgba(196, 181, 253, .38); box-shadow: 0 6px 16px -8px rgba(167, 139, 250, .55); }


            .side-group { padding: 0 1rem; margin: 1rem 0 .5rem; font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 10px; font-weight: 500; letter-spacing: .16em; color: #7f8ea6; }
            .side-brand img { transition: transform .4s cubic-bezier(.34, 1.56, .64, 1), filter .3s; }
            .side-brand:hover img { transform: scale(1.1) rotate(-5deg); filter: drop-shadow(0 0 10px rgba(56, 189, 248, .7)); }

            .side-out { transition: color .2s, background-color .2s; }
            .side-out:hover { color: #fecaca; background-color: rgba(248, 113, 113, .1); }
            .side-out .side-ic::before { box-shadow: 0 0 16px rgba(248, 113, 113, .5); }
            .side-out:hover .side-ic { transform: scale(1.1); background-color: rgba(248, 113, 113, .15); color: #fca5a5; }

            @media (prefers-reduced-motion: reduce) { .side-link:hover .side-ic svg { animation: none !important; } .side-link:hover .side-ic, .side-link:hover .side-label { transform: none; } }
                    /* signed-in user card: opens Settings, and the log out row is tinted so it reads as its own action */
            .side-user { display: flex; align-items: center; gap: .625rem; min-height: 3.25rem; padding: .4rem .75rem .4rem .45rem; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .1); background: rgba(2, 8, 23, .5); transition: border-color .2s, background-color .2s; }
            .side-user:hover { border-color: rgba(125, 211, 252, .35); background: rgba(56, 189, 248, .1); }
            .side-out--round { flex: none; width: 3.25rem; height: 3.25rem; min-height: 0; padding: 0; justify-content: center; border-radius: 9999px; color: #fda4af; background: rgba(244, 63, 94, .1); border: 1px solid rgba(251, 113, 133, .28); }
            .side-out--round:hover { background: rgba(244, 63, 94, .2); }
            .side-out--round .side-ic { background: transparent; border-color: transparent; color: #fda4af; }
            .side-out .side-ic { background-color: rgba(248, 113, 113, .1); color: #fca5a5; }
            /* calmer rows: bare icons, only the active row gets a shape */
            .side-ic, .side-link[data-tone="violet"] .side-ic { background-color: transparent; border-color: transparent; color: #8fa6c9; }
            .side-link[data-tone="violet"] .side-ic { color: #a99bf0; }
            .side-ic::before { display: none; }
            .side-link:hover .side-ic { background-color: transparent; color: #e0f2fe; }
            .side-link[data-tone="violet"]:hover .side-ic { background-color: transparent; color: #ede9fe; }
            .side-link[data-active="true"] .side-ic, .side-link[data-active="true"][data-tone="violet"] .side-ic { background-color: transparent; border-color: transparent; color: #fff; }
            /* glow behind and around the icon: on hover and on the current page */
            .side-ic svg { position: relative; z-index: 1; transition: filter .25s; }
            .side-ic::after { content: ""; position: absolute; inset: -5px; border-radius: 9999px; background: radial-gradient(circle, rgba(56, 189, 248, .42), rgba(56, 189, 248, 0) 68%); opacity: 0; transition: opacity .25s; pointer-events: none; }
            .side-link[data-tone="violet"] .side-ic::after { background: radial-gradient(circle, rgba(167, 139, 250, .44), rgba(167, 139, 250, 0) 68%); }
            .side-out .side-ic::after { background: radial-gradient(circle, rgba(248, 113, 113, .42), rgba(248, 113, 113, 0) 68%); }
            .side-link:hover .side-ic::after, .side-link[data-active="true"] .side-ic::after { opacity: 1; }
            .side-link:hover .side-ic svg, .side-link[data-active="true"] .side-ic svg { filter: drop-shadow(0 0 5px rgba(125, 211, 252, .9)); }
            .side-link[data-tone="violet"]:hover .side-ic svg, .side-link[data-active="true"][data-tone="violet"] .side-ic svg { filter: drop-shadow(0 0 5px rgba(196, 181, 253, .9)); }
            .side-out:hover .side-ic svg { filter: drop-shadow(0 0 5px rgba(252, 165, 165, .9)); }

            /* log out confirmation: a small frosted-glass bubble that grows out of the button */
            .lo-pop { position: absolute; left: 0; right: 0; bottom: calc(100% + .5rem); z-index: 20; padding: .9rem .9rem .8rem; border-radius: 1.1rem; overflow: hidden; transform-origin: 50% 100%; will-change: transform, opacity;
                background: linear-gradient(160deg, rgba(255, 255, 255, .15), rgba(255, 255, 255, .06) 50%, rgba(56, 189, 248, .08)), rgba(8, 17, 40, .6);
                border: 1px solid rgba(255, 255, 255, .22);
                box-shadow: 0 18px 36px -14px rgba(0, 0, 0, .75), inset 0 1px 0 rgba(255, 255, 255, .35);
                -webkit-backdrop-filter: blur(18px) saturate(1.4); backdrop-filter: blur(18px) saturate(1.4);
                animation: lo-pop .55s cubic-bezier(.3, 1.3, .5, 1); }
            @keyframes lo-pop {
                0% { opacity: 0; transform: translateY(18px) scale(.55, .2); border-radius: 2rem; }
                55% { opacity: 1; transform: translateY(-3px) scale(1.04, 1.05); }
                78% { transform: translateY(1px) scale(.99, .98); }
                100% { opacity: 1; transform: none; }
            }
            @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) { .lo-pop { background: #10234a; } }
            .lo-gloss { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(120% 70% at 20% 0%, rgba(255, 255, 255, .16), transparent 55%); }
            .lo-pop > *:not(.lo-gloss) { position: relative; }
            .lo-actions { display: flex; gap: .5rem; margin-top: .75rem; }
            .lo-btn { flex: 1; padding: .45rem .5rem; border-radius: .7rem; font-size: .8rem; font-weight: 600; transition: background-color .15s, border-color .15s; }
            .lo-btn-ghost { color: #e2e8f0; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .2); }
            .lo-btn-ghost:hover { background: rgba(255, 255, 255, .12); }
            .lo-btn-out { color: #fecaca; background: rgba(248, 113, 113, .2); border: 1px solid rgba(248, 113, 113, .5); }
            .lo-btn-out:hover { background: rgba(248, 113, 113, .32); }
            @media (prefers-reduced-motion: reduce) { .lo-pop { animation: none; } .lo-btn { transition: none; } }
        </style>

        {{-- First-visit entrance (only when body has data-intro, set once after sign-in / register) --}}
        <style>
            @keyframes di-slide { from { opacity: 0; transform: translateX(-28px); } to { opacity: 1; transform: none; } }
            @keyframes di-rise  { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
            @keyframes di-fade  { from { opacity: 0; } to { opacity: 1; } }
            body[data-intro] .side-brand { animation: di-rise .6s cubic-bezier(.2, .9, .3, 1) .05s backwards; }
            body[data-intro] .side-group { animation: di-fade .5s ease .25s backwards; }
            body[data-intro] .side-link { animation: di-slide .55s cubic-bezier(.2, .9, .3, 1) backwards; animation-delay: calc(.3s + var(--i, 0) * .06s); }
            body[data-intro] .side-link:nth-of-type(1) { --i: 0; }
            body[data-intro] .side-link:nth-of-type(2) { --i: 1; }
            body[data-intro] .side-link:nth-of-type(3) { --i: 2; }
            body[data-intro] .side-link:nth-of-type(4) { --i: 3; }
            body[data-intro] .side-link:nth-of-type(5) { --i: 4; }
            body[data-intro] .side-link:nth-of-type(6) { --i: 5; }
            body[data-intro] .side-link:nth-of-type(7) { --i: 6; }
            body[data-intro] .side-link:nth-of-type(8) { --i: 7; }
            body[data-intro] .side-link:nth-of-type(9) { --i: 8; }
            body[data-intro] .side-link:nth-of-type(10) { --i: 9; }
            body[data-intro] .side-link:nth-of-type(11) { --i: 10; }
            body[data-intro] .side-link:nth-of-type(12) { --i: 11; }
            body[data-intro] .side-link:nth-of-type(13) { --i: 12; }
            body[data-intro] .side-link:nth-of-type(14) { --i: 13; }
            body[data-intro] .side-link:nth-of-type(15) { --i: 14; }
            body[data-intro] .side-link:nth-of-type(16) { --i: 15; }
            body[data-intro] .side-link:nth-of-type(17) { --i: 16; }
            body[data-intro] .side-link:nth-of-type(18) { --i: 17; }
            body[data-intro] .side-hl { animation: di-fade .5s ease .95s backwards; }
            body[data-intro] .side-user { animation: di-rise .6s cubic-bezier(.2, .9, .3, 1) 1.15s backwards; }
            body[data-intro] .side-out { animation: di-slide .55s cubic-bezier(.2, .9, .3, 1) 1.25s backwards; }
            body[data-intro] .tb-btn { animation: di-rise .5s cubic-bezier(.2, .9, .3, 1) .1s backwards; }
            @media (prefers-reduced-motion: reduce) {
                body[data-intro] .side-brand, body[data-intro] .side-group, body[data-intro] .side-link, body[data-intro] .side-hl,
                body[data-intro] .side-user, body[data-intro] .side-out, body[data-intro] .tb-btn { animation: none; }
            }
        </style>
        <aside
            class="w-64 lg:w-60 xl:w-64 shrink-0 side-bg side-island flex flex-col justify-between
                   fixed z-50 transform transition-transform duration-200 ease-in-out
                   lg:translate-x-0"
            style="--side-rows: {{ collect($navMain)->where('show', true)->count() + count($navPrefs) }}"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-[calc(100%+1.5rem)]'">
            <div class="side-scroll overflow-y-auto overflow-x-hidden">
                <div class="flex items-center justify-between px-5 pt-5 pb-2">
                    <a href="{{ route('welcome') }}" class="side-brand flex items-center gap-2.5">
                        <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-10 h-10 object-contain">
                        <span class="leading-none">
                            <span class="block text-[17px] font-extrabold tracking-tight text-white">PhishCore</span>
                            <span class="block mt-1 text-[11px] font-semibold text-sky-300">Detection Platform</span>
                        </span>
                    </a>
                    <button type="button" @click.stop="$store.bell.toggle()" :aria-expanded="$store.bell.open" aria-label="Notifications" class="nb-btn nb-desktop">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                <span class="nb-count" x-show="$store.bell.unread > 0" x-cloak x-text="$store.bell.unread > 99 ? '99+' : $store.bell.unread"></span>
            </button>
                    <button @click="sidebarOpen = false" class="lg:hidden p-1 text-slate-400 hover:text-white">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav x-ref="nav" @mouseleave="reset()" class="relative px-3 pb-5"
                     x-data="{
                        hl: { y: 0, h: 0, dur: 0, tone: 'sky', ready: false },
                        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                        init() {
                            this.$nextTick(() => this.reset(true));
                            document.fonts?.ready.then(() => this.reset(true));
                            window.addEventListener('resize', () => this.reset(true));
                        },
                        active() { return this.$refs.nav.querySelector('[data-active=true]'); },
                        moveTo(el, snap = false) {
                            if (!el) return;
                            const y = el.offsetTop, h = el.offsetHeight;
                            const d = Math.abs(y - this.hl.y);
                            const still = snap || !this.hl.ready || this.reduced;
                            this.hl = { y, h, tone: el.dataset.tone, ready: true, dur: still ? 0 : Math.min(.26 + d / 900, .48) };
                            if (!still && d > 4) this.stretch(d);
                        },
                        stretch(d) {
                            /* quick squash and settle on the inner pill: compositor-only */
                            const s = 1 + Math.min(d / 500, .3);
                            this.$refs.blob?.animate(
                                [{ transform: `scale(.98, ${s})` }, { transform: 'scale(1, 1)' }],
                                { duration: 420, easing: 'cubic-bezier(.3,1.4,.5,1)' }
                            );
                        },
                        reset(snap = false) {
                            const el = this.active();
                            if (el) this.moveTo(el, snap); else this.hl.ready = false;
                        },
                        go(el) { this.moveTo(el); }
                     }">

                    <span class="side-hl" :data-tone="hl.tone"
                          :style="`height:${hl.h}px;opacity:${hl.ready ? 1 : 0};transform:translate3d(0,${hl.y}px,0);transition:transform ${hl.dur}s cubic-bezier(.3,1.15,.5,1),height ${hl.dur}s ease,opacity .2s ease`">
                        <span x-ref="blob" class="side-hl-in block"></span>
                    </span>

                    <p class="side-group" style="margin-top:.25rem">MAIN MENU</p>
                    @foreach ($navMain as $item)
                        @if ($item['show'])
                            @php $on = request()->routeIs($item['match']); @endphp
                            <a href="{{ route($item['route']) }}" data-tone="{{ $item['tone'] }}" data-active="{{ $on ? 'true' : 'false' }}"
                               data-key="{{ $item['route'] }}" data-anim="{{ $item['anim'] }}" @mouseenter="moveTo($el)" @focus="moveTo($el)" @blur="reset()" @click="go($el)" class="side-link">
                                <span class="side-ic">
                                    @if ($item['anim'] === 'bot')
                                        <x-robot-head class="rb w-[20px] h-[20px]" />
                                    @else
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                                    @endif
                                </span>
                                <span class="side-label">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach

                    <p class="side-group">PREFERENCES</p>
                    @foreach ($navPrefs as $item)
                        @php $on = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}" data-tone="{{ $item['tone'] }}" data-active="{{ $on ? 'true' : 'false' }}"
                           data-key="{{ $item['route'] }}" data-anim="{{ $item['anim'] }}" @mouseenter="moveTo($el)" @focus="moveTo($el)" @blur="reset()" @click="go($el)" class="side-link">
                            <span class="side-ic">
                                @if ($item['anim'] === 'tune')
                                    {{-- sliders: three tracks, each knob slides on hover --}}
                                    <svg class="ic-tune w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                        <path d="M4 6h16M4 12h16M4 18h16" />
                                        <circle class="k1" cx="8" cy="6" r="2.3" fill="currentColor" stroke="none" />
                                        <circle class="k2" cx="16" cy="12" r="2.3" fill="currentColor" stroke="none" />
                                        <circle class="k3" cx="10" cy="18" r="2.3" fill="currentColor" stroke="none" />
                                    </svg>
                                @else
                                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                                @endif
                            </span>
                            <span class="side-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="p-3">
              <div class="relative flex items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="side-user flex-1 min-w-0" title="Open settings">
                    <span class="relative w-10 h-10 shrink-0">
                        @if ($u->photoUrl())
                            <img src="{{ $u->photoUrl() }}" alt="" class="w-10 h-10 rounded-full object-cover ring-2 ring-sky-400/40">
                        @else
                            <span class="w-10 h-10 rounded-full bg-sky-500 text-white text-xs font-bold flex items-center justify-center ring-2 ring-sky-300/40">
                                {{ collect(explode(' ', $u->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') }}
                            </span>
                        @endif
                    </span>
                    <span class="leading-tight min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-white truncate">{{ $u->name }}</span>
                        <span class="block text-xs text-sky-300/80 truncate">{{ $roleLabel }}</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <div x-show="logoutOpen" x-cloak @click.outside="logoutOpen = false" class="lo-pop" role="dialog" aria-labelledby="lo-title"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
                        <span class="lo-gloss" aria-hidden="true"></span>
                        <p id="lo-title" class="text-sm font-semibold text-white">Log out of PhishCore?</p>
                        <p class="text-xs text-slate-300 mt-0.5">You will need to sign in again.</p>
                        <div class="lo-actions">
                            <button type="button" @click="logoutOpen = false" class="lo-btn lo-btn-ghost">Stay</button>
                            <button type="submit" class="lo-btn lo-btn-out">Log out</button>
                        </div>
                    </div>
                    <button type="button" @click.stop="logoutOpen = ! logoutOpen" :aria-expanded="logoutOpen" aria-label="Log out" title="Log out" data-anim="out" class="side-link side-out side-out--round">
                        <span class="side-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg></span>
                        </button>
                </form>
              </div>
            </div>
        </aside>

        {{-- NOTIFICATION BELL: the list opens from either bell button (top bar on phones, sidebar on desktop) --}}
        <style>
            .nb-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; padding: .5rem; border-radius: .75rem; color: #cbd5e1; transition: background-color .15s, color .15s; }
            .nb-btn:hover { background: rgba(125, 211, 252, .12); color: #fff; }
            .nb-desktop { display: none; width: 2.5rem; height: 2.5rem; padding: 0; border-radius: 9999px; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .12); }
            @media (min-width: 1024px) { .nb-desktop { display: inline-flex; } }
            .nb-count { position: absolute; top: .1rem; right: .05rem; min-width: 1.05rem; height: 1.05rem; padding: 0 .28rem; border-radius: 999px; background: #ef4444; color: #fff; font-size: .65rem; font-weight: 700; line-height: 1.05rem; text-align: center; box-shadow: 0 0 0 2px #020617; }
            /* the list: a frosted-glass card that scales out of the bell. Blur and a soft static edge light, but no moving shine and no bounce */
            .nb-panel { position: fixed; z-index: 60; top: 4.9rem; left: .75rem; right: .75rem; max-height: calc(100vh - 6.5rem); display: flex; flex-direction: column; overflow: hidden; border-radius: 1.4rem;
                transform-origin: calc(100% - 4.3rem) 0;
                background: linear-gradient(160deg, rgba(255, 255, 255, .15), rgba(255, 255, 255, .04) 55%, rgba(56, 189, 248, .08)), rgba(9, 18, 42, .55);
                border: 1px solid rgba(255, 255, 255, .2);
                box-shadow: 0 22px 44px -16px rgba(0, 0, 0, .8), inset 0 1px 0 rgba(255, 255, 255, .3), inset 0 -1px 0 rgba(255, 255, 255, .05);
                -webkit-backdrop-filter: blur(16px) saturate(1.5); backdrop-filter: blur(16px) saturate(1.5);
                animation: nb-in .34s cubic-bezier(.2, .9, .3, 1.04); }
            @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) { .nb-panel { background: #0c1a38; } }
            @media (min-width: 1024px) { .nb-panel { top: 1rem; left: 16.75rem; right: auto; width: 23rem; max-height: calc(100vh - 2rem); transform-origin: 0 1.6rem; } }
            @media (min-width: 1280px) { .nb-panel { left: 17.75rem; } }
            @keyframes nb-in { from { opacity: 0; transform: translate3d(0, -10px, 0) scale(.86, .6); } to { opacity: 1; transform: none; } }
            .nb-leaving { transition: opacity .14s ease, transform .14s ease; }
            .nb-leave-from { opacity: 1; transform: none; }
            .nb-leave-to { opacity: 0; transform: translate3d(0, -8px, 0) scale(.96); }
            @keyframes nb-row-in { from { opacity: 0; transform: translate3d(0, 6px, 0); } to { opacity: 1; transform: none; } }
            .nb-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem .7rem; border-bottom: 1px solid rgba(255, 255, 255, .12); }
            .nb-new { font-size: .65rem; font-weight: 700; padding: .12rem .5rem; border-radius: 9999px; color: #bae6fd; background: rgba(56, 189, 248, .18); border: 1px solid rgba(125, 211, 252, .3); }
            .nb-list { overflow-y: auto; overscroll-behavior: contain; }
            .nb-group { font: 600 .58rem/1 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: .16em; text-transform: uppercase; color: #94a3b8; padding: .7rem 1rem .15rem; }
            .nb-row { display: flex; align-items: flex-start; transition: background-color .15s; animation: nb-row-in .26s cubic-bezier(.2, .9, .3, 1) both; animation-delay: calc(min(var(--i, 0), 6) * 45ms + 60ms); }
            .nb-row + .nb-row { border-top: 1px solid rgba(255, 255, 255, .09); }
            .nb-row.is-unread { background: rgba(56, 189, 248, .1); }
            .nb-row:hover { background: rgba(125, 211, 252, .09); }
            .nb-item { flex: 1; min-width: 0; display: flex; gap: .7rem; padding: .75rem .25rem .75rem 1rem; color: #cbd5e1; }
            .nb-ic { flex: none; width: 2.2rem; height: 2.2rem; display: grid; place-items: center; border-radius: .75rem; border: 1px solid; color: #7dd3fc; background: rgba(56, 189, 248, .12); border-color: rgba(56, 189, 248, .28); }
            .nb-ic svg { width: 1.05rem; height: 1.05rem; }
            .nb-ic.tone-violet { color: #c4b5fd; background: rgba(139, 92, 246, .14); border-color: rgba(139, 92, 246, .32); }
            .nb-ic.tone-amber { color: #fbbf24; background: rgba(251, 191, 36, .12); border-color: rgba(251, 191, 36, .28); }
            .nb-ic.tone-green { color: #6ee7b7; background: rgba(16, 185, 129, .12); border-color: rgba(16, 185, 129, .28); }
            .nb-dot { flex: none; align-self: center; width: .5rem; height: .5rem; margin-left: auto; border-radius: 999px; background: transparent; }
            .nb-row.is-unread .nb-dot { background: #38bdf8; }
            .nb-row.is-unread .nb-msg { color: #fff; font-weight: 700; }
            .nb-del { flex: none; margin: .45rem .5rem 0 .1rem; padding: .5rem; border-radius: .5rem; color: #94a3b8; transition: background-color .15s, color .15s; }
            .nb-del:hover { background: rgba(248, 113, 113, .18); color: #fecaca; }
            .nb-foot { display: flex; align-items: center; justify-content: space-between; padding: .65rem 1rem; border-top: 1px solid rgba(255, 255, 255, .1); }
            .nb-empty { padding: 1.6rem 1.4rem 1.8rem; text-align: center; }
            .nb-empty-ic { display: grid; place-items: center; width: 3rem; height: 3rem; margin: 0 auto .7rem; border-radius: 1rem; color: #7dd3fc; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .12); }

            /* weak phones, data saver and "reduce motion": no blur and no bounce, just a quick fade */
            .fx-lite .tb-ttl > * { transition: none; }
            .fx-lite .tb-isl { background: rgba(9, 20, 44, .9); -webkit-backdrop-filter: none; backdrop-filter: none; }
            .fx-lite .nb-panel { background: #0c1a38; -webkit-backdrop-filter: none; backdrop-filter: none; animation: nb-fade .18s ease-out; }
            .fx-lite .nb-row, .fx-lite .tb-bell { animation: none !important; }
            @keyframes nb-fade { from { opacity: 0; } to { opacity: 1; } }
            @media (prefers-reduced-motion: reduce) {
                .nb-panel { animation: nb-fade .18s ease-out; }
                .nb-row, .tb-bell { animation: none !important; }
                .tb-btn, .tb-burger i { transition: none; }
            }
        </style>
        <div class="nb-panel" x-show="$store.bell.open" x-cloak @click.outside="$store.bell.open = false" @keydown.escape.window="$store.bell.open = false" role="dialog" aria-label="Notifications"
             x-transition:leave="nb-leaving" x-transition:leave-start="nb-leave-from" x-transition:leave-end="nb-leave-to">
            <div class="nb-head">
                <p class="flex items-center gap-2 text-sm font-semibold text-white">Notifications <span class="nb-new" x-show="$store.bell.unread > 0" x-cloak x-text="$store.bell.unread + ' new'"></span></p>
                <button type="button" x-show="$store.bell.unread > 0" @click="$store.bell.readAll()" class="text-xs font-semibold text-sky-300 hover:text-white">Mark all as read</button>
            </div>
            <div class="nb-list">
                <p class="nb-group" x-show="$store.bell.newItems.length > 0 && $store.bell.earlierItems.length > 0" x-cloak>New</p>
                <template x-for="(n, idx) in $store.bell.newItems" :key="n.id">
                    <div class="nb-row" :class="n.unread ? 'is-unread' : ''" :style="'--i:' + (idx)">
                        <a :href="n.url" class="nb-item">
                            <span class="nb-ic" :class="{ 'tone-violet': n.kind === 'assigned', 'tone-amber': n.kind === 'unassigned', 'tone-green': n.kind === 'status_changed' }" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" :d="$store.bell.icon(n.kind)" /></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="nb-msg block text-sm leading-snug break-words" x-text="n.message"></span>
                                <span class="block mt-0.5 text-xs text-slate-500" x-text="n.when"></span>
                            </span>
                            <span class="nb-dot" aria-hidden="true"></span>
                        </a>
                        <button type="button" class="nb-del" @click.stop="$store.bell.remove(n.id)" aria-label="Delete notification" title="Delete">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
                <p class="nb-group" x-show="$store.bell.newItems.length > 0 && $store.bell.earlierItems.length > 0" x-cloak>Earlier</p>
                <template x-for="(n, idx) in $store.bell.earlierItems" :key="n.id">
                    <div class="nb-row" :class="n.unread ? 'is-unread' : ''" :style="'--i:' + ($store.bell.newItems.length + idx)">
                        <a :href="n.url" class="nb-item">
                            <span class="nb-ic" :class="{ 'tone-violet': n.kind === 'assigned', 'tone-amber': n.kind === 'unassigned', 'tone-green': n.kind === 'status_changed' }" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" :d="$store.bell.icon(n.kind)" /></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="nb-msg block text-sm leading-snug break-words" x-text="n.message"></span>
                                <span class="block mt-0.5 text-xs text-slate-500" x-text="n.when"></span>
                            </span>
                            <span class="nb-dot" aria-hidden="true"></span>
                        </a>
                        <button type="button" class="nb-del" @click.stop="$store.bell.remove(n.id)" aria-label="Delete notification" title="Delete">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
                <div class="nb-empty" x-show="$store.bell.items.length === 0" x-cloak>
                    <span class="nb-empty-ic"><svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg></span>
                    <p class="text-sm font-semibold text-white">You are all caught up</p>
                    <p class="mt-1 text-xs leading-snug text-slate-400">Investigation updates will show up here.</p>
                </div>
            </div>
            <div class="nb-foot" x-show="$store.bell.items.length > 0" x-cloak>
                <button type="button" @click="$store.bell.clearAll()" class="text-xs font-semibold text-slate-400 hover:text-white">Clear all</button>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
                <main class="relative flex-1 p-4 pt-20 lg:p-8 lg:pt-8 lg:ml-4 lg:pl-[18rem] xl:pl-[19rem] overflow-y-auto min-w-0">
            {{ $slot }}
        </main>

    </div>

    @include('components.chat-widget')

    <script>
        document.addEventListener('alpine:init', () => {
            const initial = @json(\App\Http\Controllers\NotificationController::summary(auth()->user()));
            Alpine.store('bell', {
                open: false,
                unread: initial.unread,
                items: initial.items,
                get newItems() { return this.items.filter((n) => n.unread); },
                get earlierItems() { return this.items.filter((n) => ! n.unread); },
                icon(kind) { return ({
                    assigned: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                    unassigned: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
                    status_changed: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                })[kind] || 'M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M2.25 13.5V6.75A2.25 2.25 0 014.5 4.5h15a2.25 2.25 0 012.25 2.25v6.75M2.25 13.5v4.5A2.25 2.25 0 004.5 20.25h15a2.25 2.25 0 002.25-2.25v-4.5'; },
                toggle() { this.open = ! this.open; if (this.open) { this.load(); } },
                apply(data) { this.unread = data.unread; this.items = data.items; },
                async load() {
                    try {
                        const res = await fetch(@json(route('notifications.index')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        if (res.ok) { this.apply(await res.json()); }
                    } catch (e) { /* offline or signed out: keep what is shown */ }
                },
                async send(url, method) {
                    try {
                        const res = await fetch(url, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) } });
                        if (res.ok) { this.apply(await res.json()); }
                    } catch (e) { /* ignore */ }
                },
                remove(id) { return this.send(@json(url('/notifications')) + '/' + id, 'DELETE'); },
                clearAll() { return this.send(@json(route('notifications.clear')), 'DELETE'); },
                async readAll() {
                    try {
                        const res = await fetch(@json(route('notifications.read-all')), {
                            method: 'POST', credentials: 'same-origin',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                        });
                        if (res.ok) { this.apply(await res.json()); }
                    } catch (e) { /* ignore */ }
                },
            });
            // Check for new notifications once a minute while the page is visible.
            setInterval(() => { if (! document.hidden) { Alpine.store('bell').load(); } }, 60000);
        });
    </script>

    @stack('scripts')
</body>
</html>