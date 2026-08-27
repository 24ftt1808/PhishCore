<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PhishCore') }} — Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">

        {{-- MOBILE TOP BAR --}}
        <div class="lg:hidden fixed top-0 left-0 right-0 z-30 flex items-center justify-between px-4 py-3 bg-slate-950 border-b border-slate-800/60">
            <a href="{{ route('welcome') }}" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center">
                    <svg width="16" height="16" class="text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
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
        <aside
            class="w-64 shrink-0 bg-slate-950 border-r border-slate-800/60 flex flex-col justify-between
                   fixed inset-y-0 left-0 z-50 transform transition-transform duration-200 ease-in-out
                   lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div>
                <div class="flex items-center justify-between px-6 py-6">
                    <a href="{{ route('welcome') }}" class="flex items-center gap-2">
                        <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center">
                            <svg width="20" height="20" class="text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                            </svg>
                        </span>
                        <span class="leading-tight">
                            <span class="block font-bold text-white">PhishCore</span>
                            <span class="block text-[10px] tracking-wide text-sky-400">DETECTION PLATFORM</span>
                        </span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden p-1 text-slate-500 hover:text-slate-300">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="px-6 text-[10px] tracking-wider text-slate-600 mt-2 mb-2">MAIN MENU</p>
                <nav class="px-3 space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('dashboard') ? 'bg-sky-500/10 text-sky-400' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }} transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        Dashboard
                        @if (request()->routeIs('dashboard'))
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 ml-auto"></span>
                        @endif
                    </a>
                    <a href="{{ route('scan.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs(['scan.index', 'scan.show']) ? 'bg-sky-500/10 text-sky-400' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }} transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        Scan
                        @if (request()->routeIs(['scan.index', 'scan.show']))
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 ml-auto"></span>
                        @endif
                    </a>
                    <a href="{{ route('scan.history') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('scan.history') ? 'bg-sky-500/10 text-sky-400' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }} transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Scan History
                        @if (request()->routeIs('scan.history'))
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 ml-auto"></span>
                        @endif
                    </a>
                    <a href="{{ route('analytics') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('analytics') ? 'bg-sky-500/10 text-sky-400' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }} transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5M3 19.5h18" /></svg>
                        Analytics
                        @if (request()->routeIs('analytics'))
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 ml-auto"></span>
                        @endif
                    </a>
                                      @if (auth()->user()->is_team_member)
                        <a href="{{ route('investigations.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('investigations.index') ? 'bg-violet-500/10 text-violet-400' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }} transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                            Investigations
                            @if (request()->routeIs('investigations.index'))
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-400 ml-auto"></span>
                            @endif
                        </a>
                        <a href="{{ route('reports.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('reports.index') ? 'bg-violet-500/10 text-violet-400' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }} transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25" /></svg>
                            Reports
                            @if (request()->routeIs('reports.index'))
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-400 ml-auto"></span>
                            @endif
                        </a>
                    @endif
                    @if (auth()->user()->role === 'admin')
                        <span class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-600 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                            User Management <span class="text-[9px] ml-auto bg-slate-800 px-1.5 py-0.5 rounded">SOON</span>
                        </span>
                    @endif
                </nav>

                <p class="px-6 text-[10px] tracking-wider text-slate-600 mt-6 mb-2">PREFERENCES</p>
                <nav class="px-3">
                    <span class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-600 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /></svg>
                        Settings <span class="text-[9px] ml-auto bg-slate-800 px-1.5 py-0.5 rounded">SOON</span>
                    </span>
                </nav>
            </div>

            <div class="p-3 border-t border-slate-800/60">
                <div class="flex items-center gap-3 px-3 py-2">
                    <span class="relative w-9 h-9 shrink-0">
                        <span class="w-9 h-9 rounded-full bg-sky-500 text-white text-xs font-bold flex items-center justify-center">
                            {{ collect(explode(' ', auth()->user()->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') }}
                        </span>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-slate-950"></span>
                    </span>
                    <span class="leading-tight overflow-hidden">
                        <span class="block text-sm text-white truncate">{{ auth()->user()->name }}</span>
     @php
    $roleLabel = match (true) {
        auth()->user()->role === 'admin' => 'Admin',
        (bool) auth()->user()->is_team_member => 'Team Member',
        default => 'Member',
    };
@endphp
<span class="block text-xs text-slate-500">{{ $roleLabel }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-400 hover:bg-slate-900 hover:text-slate-200 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <main class="flex-1 p-4 pt-20 lg:p-8 lg:pt-8 overflow-y-auto min-w-0">
            {{ $slot }}
        </main>

    </div>

    @stack('scripts')
</body>
</html>