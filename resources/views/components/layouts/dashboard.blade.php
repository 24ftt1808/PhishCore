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
<body class="bg-slate-950 text-slate-100 antialiased">

    {{-- Same animated background as the public pages, dimmed so data stays readable --}}
    <div class="hero-aurora dash-aurora" aria-hidden="true" style="opacity:.55"><span></span><span></span><span></span></div>

    <div class="relative z-10 flex lg:h-screen dash-scope" x-data="{ sidebarOpen: false, logoutOpen: false }" @keydown.escape.window="logoutOpen = false">

        {{-- MOBILE TOP BAR --}}
        <div class="lg:hidden fixed top-0 left-0 right-0 z-30 flex items-center justify-between px-4 py-3 side-bg border-b">
            <a href="{{ route('welcome') }}" class="flex items-center gap-2">
              <span class="w-8 h-8 flex items-center justify-center">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-8 h-8 object-contain">
</span>
                <span class="font-bold text-white">PhishCore</span>
            </a>
            <button @click="sidebarOpen = true" class="p-2 text-slate-300">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>
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
                ['route' => 'investigations.index', 'match' => ['investigations.index'],  'label' => 'Investigations',  'tone' => 'violet', 'show' => (bool) $u->is_team_member, 'anim' => 'open', 'icon' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'],
                ['route' => 'reports.index',    'match' => ['reports.index'],             'label' => 'Reports',         'tone' => 'violet', 'show' => (bool) $u->is_team_member, 'anim' => 'nudge', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
                ['route' => 'user-management.index', 'match' => ['user-management.index'], 'label' => 'User Management', 'tone' => 'violet', 'show' => $u->role === 'admin',     'anim' => 'pulse', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
            ];
            $navPrefs = [
                ['route' => 'profile.edit',     'match' => ['profile.edit'],              'label' => 'Settings',        'tone' => 'sky',    'show' => true,                      'anim' => 'gear', 'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.1250 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z'],
            ];
            $roleLabel = match (true) {
                $u->role === 'admin' => 'Admin',
                (bool) $u->is_team_member => 'Team Member',
                default => 'Member',
            };
        @endphp

        <style>
            /* navy with a soft blue glow from the top */
            .side-bg {
                background:
                    radial-gradient(120% 40% at 0% 0%, rgba(56, 189, 248, .14), transparent 70%),
                    linear-gradient(180deg, #0c1a38 0%, #09142c 55%, #070f24 100%);
                border-color: rgba(125, 211, 252, .14);
                box-shadow: inset -1px 0 0 rgba(255, 255, 255, .03), 8px 0 30px -12px rgba(2, 8, 23, .8);
            }
            .side-scroll { scrollbar-width: none; overscroll-behavior: contain; }
            .side-scroll::-webkit-scrollbar { display: none; }

            /* the background blobs stay as a still glow on signed-in pages: less repaint, smoother hover */
            .dash-aurora span { animation: none !important; }

            .side-link { position: relative; z-index: 1; display: flex; align-items: center; gap: .75rem; padding: .5rem .75rem; border-radius: .8rem; font-size: .875rem; color: #b9c5d6; transition: color .2s; }
            .side-label { transform-origin: left center; transition: transform .28s cubic-bezier(.34, 1.4, .64, 1); }

            /* icon tile: the glow is a pseudo-element that only fades, so it never repaints a shadow */
            .side-ic { position: relative; width: 2rem; height: 2rem; display: grid; place-items: center; flex-shrink: 0; border-radius: .6rem; transition: transform .3s cubic-bezier(.34, 1.56, .64, 1), background-color .2s, color .2s; }
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
            .side-link[data-anim="pulse"]:hover .side-ic svg  { animation-name: ia-pulse; }
            .side-link[data-anim="gear"]:hover .side-ic svg   { animation-name: ia-gear; animation-timing-function: cubic-bezier(.4, 0, .2, 1); animation-duration: .9s; }
            .side-link[data-anim="out"]:hover .side-ic svg    { animation-name: ia-out; }

            @keyframes ia-spin   { to { transform: rotate(90deg); } }
            @keyframes ia-orbit  { 0%, 100% { transform: translate(0, 0); } 20% { transform: translate(2px, 0); } 40% { transform: translate(0, 2px); } 60% { transform: translate(-2px, 0); } 80% { transform: translate(0, -2px); } }
            @keyframes ia-rewind { to { transform: rotate(-360deg); } }
            @keyframes ia-rise   { 0% { transform: translateY(0); } 35% { transform: translateY(-4px) scaleY(1.12); } 65% { transform: translateY(1px); } 100% { transform: translateY(0); } }
            @keyframes ia-open   { 0% { transform: rotate(0); } 30% { transform: rotate(-12deg) scale(1.12); } 60% { transform: rotate(5deg); } 100% { transform: rotate(0); } }
            @keyframes ia-nudge  { 0% { transform: translateY(0) rotate(0); } 30% { transform: translateY(-4px) rotate(5deg); } 60% { transform: translateY(1px) rotate(-3deg); } 100% { transform: translateY(0) rotate(0); } }
            @keyframes ia-pulse  { 0%, 100% { transform: scale(1); } 25% { transform: scale(1.28); } 50% { transform: scale(.95); } 75% { transform: scale(1.14); } }
            @keyframes ia-gear   { to { transform: rotate(180deg); } }
            @keyframes ia-out    { 0% { transform: translateX(0); } 40% { transform: translateX(5px); } 70% { transform: translateX(-1px); } 100% { transform: translateX(0); } }

            .side-link[data-active="true"] { color: #fff; font-weight: 500; }
            .side-link[data-active="true"] .side-ic { background-color: rgba(56, 189, 248, .18); color: #7dd3fc; }
            .side-link[data-active="true"] .side-ic::before { opacity: 1; }
            .side-link[data-active="true"][data-tone="violet"] .side-ic { background-color: rgba(167, 139, 250, .18); color: #c4b5fd; }
            .side-link[data-active="true"]::after { content: ""; position: absolute; left: -.75rem; top: 24%; bottom: 24%; width: 3px; border-radius: 0 4px 4px 0; background: #38bdf8; box-shadow: 0 0 12px 1px rgba(56, 189, 248, .8); }
            .side-link[data-active="true"][data-tone="violet"]::after { background: #a78bfa; box-shadow: 0 0 12px 1px rgba(167, 139, 250, .8); }

            /* moving pill: only transform and opacity change while it travels, so it runs on the GPU */
            .side-hl { position: absolute; top: 0; left: .75rem; right: .75rem; pointer-events: none; will-change: transform; }
            .side-hl-in { width: 100%; height: 100%; border-radius: .8rem; will-change: transform;
                          background: linear-gradient(90deg, rgba(56, 189, 248, .2), rgba(56, 189, 248, .05));
                          border: 1px solid rgba(125, 211, 252, .22);
                          box-shadow: 0 0 12px -2px rgba(56, 189, 248, .3), inset 0 1px 0 rgba(255, 255, 255, .07); }
            .side-hl[data-tone="violet"] .side-hl-in { background: linear-gradient(90deg, rgba(167, 139, 250, .2), rgba(167, 139, 250, .05)); border-color: rgba(196, 181, 253, .24); box-shadow: 0 0 12px -2px rgba(167, 139, 250, .3), inset 0 1px 0 rgba(255, 255, 255, .07); }

            .side-dot { width: .375rem; height: .375rem; border-radius: 9999px; margin-left: auto; background: #38bdf8; box-shadow: 0 0 10px 2px rgba(56, 189, 248, .7); animation: side-pulse 2.4s ease-in-out infinite; }
            .side-link[data-tone="violet"] .side-dot { background: #a78bfa; box-shadow: 0 0 10px 2px rgba(167, 139, 250, .7); }
            @keyframes side-pulse { 50% { opacity: .45; } }

            .side-group { padding: 0 1.5rem; margin: 1.25rem 0 .4rem; font-size: 10px; letter-spacing: .14em; color: #8191a8; }
            .side-brand img { transition: transform .4s cubic-bezier(.34, 1.56, .64, 1), filter .3s; }
            .side-brand:hover img { transform: scale(1.1) rotate(-5deg); filter: drop-shadow(0 0 10px rgba(56, 189, 248, .7)); }

            .side-out { transition: color .2s, background-color .2s; }
            .side-out:hover { color: #fecaca; background-color: rgba(248, 113, 113, .1); }
            .side-out .side-ic::before { box-shadow: 0 0 16px rgba(248, 113, 113, .5); }
            .side-out:hover .side-ic { transform: scale(1.1); background-color: rgba(248, 113, 113, .15); color: #fca5a5; }

            @media (prefers-reduced-motion: reduce) { .side-dot, .side-link:hover .side-ic svg { animation: none !important; } .side-link:hover .side-ic, .side-link:hover .side-label { transform: none; } }
                    /* signed-in user card: opens Settings, and the log out row is tinted so it reads as its own action */
            .side-user { display: flex; align-items: center; gap: .75rem; padding: .6rem .75rem; border-radius: .9rem; border: 1px solid rgba(125, 211, 252, .12); background: linear-gradient(135deg, rgba(56, 189, 248, .09), rgba(56, 189, 248, .03)); transition: border-color .2s, background-color .2s; }
            .side-user:hover { border-color: rgba(125, 211, 252, .3); background: linear-gradient(135deg, rgba(56, 189, 248, .14), rgba(56, 189, 248, .05)); }
            .side-user-go { color: #7dd3fc; opacity: 0; transform: translateX(-4px); transition: opacity .2s, transform .2s; }
            .side-user:hover .side-user-go { opacity: .9; transform: none; }
            .side-out { margin-top: .1rem; }
            .side-out .side-ic { background-color: rgba(248, 113, 113, .1); color: #fca5a5; }

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

        <aside
            class="w-64 shrink-0 side-bg border-r flex flex-col justify-between
                   fixed inset-y-0 left-0 z-50 transform transition-transform duration-200 ease-in-out
                   lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="side-scroll overflow-y-auto overflow-x-hidden">
                <div class="flex items-center justify-between px-6 py-6">
                    <a href="{{ route('welcome') }}" class="side-brand flex items-center gap-2.5">
                        <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-9 h-9 object-contain">
                        <span class="leading-tight">
                            <span class="block font-bold text-white">PhishCore</span>
                            <span class="block text-[10px] tracking-wide text-sky-300">DETECTION PLATFORM</span>
                        </span>
                    </a>
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
                                <span class="side-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg></span>
                                <span class="side-label">{{ $item['label'] }}</span>
                                @if ($on) <span class="side-dot"></span> @endif
                            </a>
                        @endif
                    @endforeach

                    <p class="side-group">PREFERENCES</p>
                    @foreach ($navPrefs as $item)
                        @php $on = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}" data-tone="{{ $item['tone'] }}" data-active="{{ $on ? 'true' : 'false' }}"
                           data-key="{{ $item['route'] }}" data-anim="{{ $item['anim'] }}" @mouseenter="moveTo($el)" @focus="moveTo($el)" @blur="reset()" @click="go($el)" class="side-link">
                            <span class="side-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg></span>
                            <span class="side-label">{{ $item['label'] }}</span>
                            @if ($on) <span class="side-dot"></span> @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="p-3 border-t" style="border-color: rgba(125, 211, 252, .12)">
                <a href="{{ route('profile.edit') }}" class="side-user" title="Open settings">
                    <span class="relative w-10 h-10 shrink-0">
                        @if ($u->photoUrl())
                            <img src="{{ $u->photoUrl() }}" alt="" class="w-10 h-10 rounded-full object-cover ring-2 ring-sky-400/40">
                        @else
                            <span class="w-10 h-10 rounded-full bg-sky-500 text-white text-xs font-bold flex items-center justify-center ring-2 ring-sky-300/40">
                                {{ collect(explode(' ', $u->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') }}
                            </span>
                        @endif
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-slate-950"></span>
                    </span>
                    <span class="leading-tight min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-white truncate">{{ $u->name }}</span>
                        <span class="block text-xs text-sky-300/80 truncate">{{ $roleLabel }}</span>
                    </span>
                    <svg class="side-user-go w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2 relative">
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
                    <button type="button" @click.stop="logoutOpen = ! logoutOpen" :aria-expanded="logoutOpen" data-anim="out" class="side-link side-out w-full text-slate-300">
                        <span class="side-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg></span>
                        <span class="side-label">Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
                <main class="flex-1 p-4 pt-20 lg:p-8 lg:pt-8 lg:ml-64 overflow-y-auto min-w-0">
            {{ $slot }}
        </main>

    </div>

    @stack('scripts')
</body>
</html>