@php
    $currentFilter = $filters['filter'] ?? 'all';

    $statCards = [
        ['label' => 'TOTAL ARTICLES', 'value' => $stats['total'], 'href' => [], 'on' => $currentFilter === 'all', 'rgb' => '56,189,248', 'text' => 'text-white', 'icon' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z'],
        ['label' => 'VISIBLE', 'value' => $stats['visible'], 'href' => ['filter' => 'visible'], 'on' => $currentFilter === 'visible', 'rgb' => '52,211,153', 'text' => 'text-emerald-400', 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'HIDDEN', 'value' => $stats['hidden'], 'href' => ['filter' => 'hidden'], 'on' => $currentFilter === 'hidden', 'rgb' => '251,146,60', 'text' => 'text-orange-300', 'icon' => 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88'],
        ['label' => 'ADDED BY HAND', 'value' => $stats['manual'], 'href' => ['filter' => 'manual'], 'on' => $currentFilter === 'manual', 'rgb' => '167,139,250', 'text' => 'text-violet-300', 'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'],
    ];

    $chips = ['all' => 'All', 'visible' => 'Visible', 'hidden' => 'Hidden', 'manual' => 'Added by hand'];
    $chipCounts = ['all' => $stats['total'], 'visible' => $stats['visible'], 'hidden' => $stats['hidden'], 'manual' => $stats['manual']];

    $feedResults = collect($lastRun['feeds'] ?? [])->keyBy('name');
    $lastRunAt = isset($lastRun['at']) ? \Illuminate\Support\Carbon::parse($lastRun['at']) : null;
@endphp

<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-field { width: 100%; background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .2); border-radius: .75rem; color: #e2e8f0; transition: border-color .15s; color-scheme: dark; }
        .h-field:focus { outline: none; border-color: #38bdf8; box-shadow: none; }
        .h-field::placeholder { color: #94a3b8; }

        .h-stat { position: relative; overflow: hidden; display: block; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
        .h-stat::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(120% 140% at 100% 0%, rgba(var(--c), .26), transparent 62%); opacity: .75; transition: opacity .3s; }
        .h-stat:hover { transform: translateY(-2px); border-color: rgba(var(--c), .5); }
        .h-stat:hover::before { opacity: 1; }
        .h-stat-on { border-color: rgba(var(--c), .55); }
        .h-stat > * { position: relative; }
        .h-tile { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .8rem; background: rgba(var(--c), .14); border: 1px solid rgba(var(--c), .38); color: rgb(var(--c)); box-shadow: 0 0 18px -4px rgba(var(--c), .55); flex-shrink: 0; }

        .h-search { display: flex; align-items: center; gap: .5rem; height: 3.25rem; padding: 0 .5rem 0 1rem; border-radius: 1rem; background: rgba(8, 15, 32, .6); border: 1px solid rgba(148, 163, 184, .22); transition: border-color .2s, box-shadow .2s; }
        .h-search:focus-within { border-color: #38bdf8; box-shadow: 0 0 0 4px rgba(56, 189, 248, .14); }
        .h-search input { flex: 1; min-width: 0; background: transparent; border: 0; outline: 0; box-shadow: none; color: #f1f5f9; font-size: .9rem; }
        .h-search input::placeholder { color: #94a3b8; }

        .h-chip { display: inline-flex; align-items: center; gap: .5rem; flex-shrink: 0; padding: .45rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 600; color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .22); background: rgba(8, 15, 32, .35); transition: background-color .15s, border-color .15s, color .15s; white-space: nowrap; }
        .h-chip:hover { color: #fff; border-color: rgba(148, 163, 184, .45); }
        .h-chip-on { color: #fff; background: rgba(56, 189, 248, .16); border-color: rgba(56, 189, 248, .5); }
        .h-chip-n { font-size: .7rem; font-weight: 700; color: #94a3b8; }
        .h-chip-on .h-chip-n { color: #bae6fd; }
        .sn-scroll { scrollbar-width: none; }
        .sn-scroll::-webkit-scrollbar { display: none; }

        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }

        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem 1rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, opacity .15s; }
        .h-btn:disabled { opacity: .6; cursor: not-allowed; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .h-btn-main:hover { opacity: .92; }
        .h-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); }
        .h-btn-ghost:hover { background: rgba(255, 255, 255, .06); border-color: rgba(148, 163, 184, .5); }
        .h-btn-danger { color: #fca5a5; border: 1px solid rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .08); }
        .h-btn-danger:hover { background: rgba(248, 113, 113, .18); border-color: rgba(248, 113, 113, .6); }
        .h-btn-ok { color: #6ee7b7; border: 1px solid rgba(52, 211, 153, .4); background: rgba(52, 211, 153, .08); }
        .h-btn-ok:hover { background: rgba(52, 211, 153, .18); border-color: rgba(52, 211, 153, .6); }

        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } .h-stat:hover { transform: none; } }
    </style>

    <div x-data="{ adding: {{ $errors->any() ? 'true' : 'false' }} }">

        <div class="h-in flex items-start justify-between gap-3 flex-wrap mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white mb-1">Scam News</h1>
                <p class="text-slate-300 text-sm">Manage the news box on everyone's dashboard. It shows the newest {{ $display }} visible articles.</p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <form method="POST" action="{{ route('scam-news.fetch') }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <button type="submit" :disabled="busy" class="h-btn h-btn-ghost">
                        <svg x-show="! busy" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="busy ? 'Fetching...' : 'Fetch now'"></span>
                    </button>
                </form>
                <button type="button" @click="adding = ! adding" class="h-btn h-btn-main">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add article
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="h-card mb-6 text-sm text-emerald-300 px-4 py-3" style="border-color: rgba(52,211,153,.4)">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="h-card mb-6 text-sm text-red-300 px-4 py-3" style="border-color: rgba(248,113,113,.4)">{{ session('error') }}</div>
        @endif

        {{-- ADD BY HAND --}}
        <form method="POST" action="{{ route('scam-news.store') }}" x-show="adding" x-cloak class="h-card p-4 sm:p-6 mb-6">
            @csrf
            <h2 class="text-white font-semibold">Add an article by hand</h2>
            <p class="text-sm text-slate-300 mt-0.5 mb-5">It shows on the dashboard right away and is never deleted by the daily clean-up.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="sn-title" class="block text-xs font-medium text-slate-300 mb-1.5">Headline</label>
                    <input id="sn-title" type="text" name="title" value="{{ old('title') }}" maxlength="300" required class="h-field px-3.5 py-2.5 text-sm" placeholder="Police warn of fake parcel texts">
                    @error('title') <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="sn-url" class="block text-xs font-medium text-slate-300 mb-1.5">Article link</label>
                    <input id="sn-url" type="url" name="url" value="{{ old('url') }}" maxlength="2048" required class="h-field px-3.5 py-2.5 text-sm" placeholder="https://...">
                    @error('url') <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sn-source" class="block text-xs font-medium text-slate-300 mb-1.5">Source name</label>
                    <input id="sn-source" type="text" name="source" value="{{ old('source') }}" maxlength="120" required class="h-field px-3.5 py-2.5 text-sm" placeholder="Example Daily">
                    @error('source') <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sn-date" class="block text-xs font-medium text-slate-300 mb-1.5">Published on <span class="text-slate-400">(optional, today if empty)</span></label>
                    <input id="sn-date" type="date" name="published_at" value="{{ old('published_at') }}" max="{{ now()->toDateString() }}" class="h-field px-3.5 py-2.5 text-sm">
                    @error('published_at') <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="sn-image" class="block text-xs font-medium text-slate-300 mb-1.5">Picture link <span class="text-slate-400">(optional, a themed picture is used if empty)</span></label>
                    <input id="sn-image" type="url" name="image_url" value="{{ old('image_url') }}" maxlength="2048" class="h-field px-3.5 py-2.5 text-sm" placeholder="https://...">
                    @error('image_url') <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-2.5 mt-5">
                <button type="submit" class="h-btn h-btn-main">Add article</button>
                <button type="button" @click="adding = false" class="h-btn h-btn-ghost">Cancel</button>
            </div>
        </form>

        {{-- SOURCES --}}
        <div class="h-card h-in p-4 sm:p-5 mb-6" style="--d:.06s">
            <div class="flex items-start justify-between gap-3 flex-wrap mb-2">
                <div>
                    <h2 class="text-white font-semibold text-sm">News sources</h2>
                    <p class="text-xs text-slate-300 mt-0.5">Fetched automatically once a day. To add or swap a source, edit the list in <span class="font-mono">config/scamnews.php</span>.</p>
                </div>
                <p class="text-xs text-slate-400 whitespace-nowrap">{{ $lastRunAt ? 'Last fetch: '.$lastRunAt->format('j M Y, H:i') : 'Not fetched yet' }}</p>
            </div>
            <ul class="divide-y divide-white/[0.07]">
                @forelse ($feeds as $feed)
                    @php
                        $feedName = $feed['name'] ?? ($feed['url'] ?? 'Feed');
                        $result = $feedResults->get($feedName);
                    @endphp
                    <li class="py-2.5 flex items-center justify-between gap-x-4 gap-y-1 flex-wrap">
                        <span class="min-w-0 text-sm text-slate-100">
                            {{ $feedName }}
                            @if (! empty($feed['filter']))
                                <span class="ml-2 align-middle text-[10px] font-semibold tracking-wide text-slate-300 border border-slate-500/40 rounded px-1.5 py-0.5">SCAM KEYWORDS ONLY</span>
                            @endif
                        </span>
                        @if ($result === null)
                            <span class="text-xs font-semibold text-slate-400">Not fetched yet</span>
                        @elseif ($result['ok'])
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span> OK &middot; {{ $result['added'] }} new</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-400 min-w-0"><span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span> <span class="truncate">Failed &middot; {{ $result['error'] }}</span></span>
                        @endif
                    </li>
                @empty
                    <li class="py-2.5 text-sm text-slate-300">No sources are set up. Add some in <span class="font-mono">config/scamnews.php</span>.</li>
                @endforelse
            </ul>
        </div>

        {{-- STATS (click to filter) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6">
            @foreach ($statCards as $card)
                <a href="{{ route('scam-news.index', $card['href']) }}"
                   class="h-card h-stat h-in p-3.5 sm:p-5 {{ $card['on'] ? 'h-stat-on' : '' }}"
                   style="--c: {{ $card['rgb'] }}; --d: {{ 0.1 + $loop->index * 0.06 }}s">
                    <div class="flex items-center sm:items-start justify-between gap-2 sm:gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] sm:text-xs font-medium tracking-wide text-slate-300 mb-0.5 sm:mb-2">{{ $card['label'] }}</p>
                            <p class="text-2xl sm:text-3xl font-bold leading-none sm:leading-normal {{ $card['text'] }}">{{ $card['value'] }}</p>
                        </div>
                        <span class="h-tile shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- SEARCH + FILTERS --}}
        <form method="GET" action="{{ route('scam-news.index') }}" class="h-card h-in p-4 sm:p-5 mb-6" style="--d:.2s">
            <input type="hidden" name="filter" value="{{ $currentFilter }}">
            @if (filled($filters['rows'] ?? null))
                <input type="hidden" name="rows" value="{{ $filters['rows'] }}">
            @endif

            <label class="h-search">
                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" autocomplete="off" placeholder="Search headline, source or link">
                @if (filled($filters['search'] ?? null))
                    <a href="{{ route('scam-news.index', array_filter(['filter' => $currentFilter === 'all' ? null : $currentFilter])) }}" class="p-1.5 text-slate-400 hover:text-white transition-colors" title="Clear search">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </a>
                @endif
                <button type="submit" class="h-btn h-btn-main !py-2">Search</button>
            </label>

            <div class="sn-scroll flex items-center gap-2 overflow-x-auto sm:overflow-visible sm:flex-wrap -mx-4 px-4 sm:mx-0 sm:px-0 mt-3.5 sm:mt-4">
                @foreach ($chips as $key => $label)
                    <button type="submit" name="filter" value="{{ $key }}" class="h-chip {{ $currentFilter === $key ? 'h-chip-on' : '' }}">
                        {{ $label }}
                        <span class="h-chip-n">{{ $chipCounts[$key] }}</span>
                    </button>
                @endforeach
                @if (filled($filters['search'] ?? null) || $currentFilter !== 'all')
                    <a href="{{ route('scam-news.index') }}" class="shrink-0 ml-1 text-xs font-semibold text-slate-300 hover:text-white underline-offset-2 hover:underline">Reset</a>
                @endif
            </div>
        </form>

        {{-- COUNT + ROWS --}}
        <div class="flex items-center justify-between gap-2 mb-3 text-sm text-slate-300">
            <span><span class="hidden sm:inline">Showing </span>{{ $articles->firstItem() ?? 0 }}–{{ $articles->lastItem() ?? 0 }} of {{ $articles->total() }}<span class="hidden sm:inline"> total</span> articles</span>
            <form method="GET" action="{{ route('scam-news.index') }}" class="flex items-center gap-2">
                @foreach ($filters as $key => $value)
                    @if ($key !== 'rows' && $value !== null)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <span class="text-xs font-medium tracking-wide">ROWS</span>
                <div class="relative">
                    <select name="rows" onchange="this.form.submit()" class="h-field !w-auto appearance-none bg-none pl-3 pr-7 py-1.5 text-sm cursor-pointer">
                        @foreach ([10, 20, 40] as $n)
                            <option value="{{ $n }}" {{ ($filters['rows'] ?? 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <svg class="w-3.5 h-3.5 text-slate-300 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="h-card h-in overflow-hidden" style="--d:.26s">
            <div class="overflow-x-auto">
                <table class="block md:table w-full text-sm xl:min-w-[900px]">
                    <thead class="hidden md:table-header-group">
                        <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                            <th class="px-3 xl:px-5 py-3 whitespace-nowrap">ARTICLE</th>
                            <th class="px-3 xl:px-5 py-3 whitespace-nowrap">PUBLISHED</th>
                            <th class="px-3 xl:px-5 py-3 whitespace-nowrap">STATUS</th>
                            <th class="px-3 xl:px-5 py-3 whitespace-nowrap">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group divide-y divide-slate-500/20">
                        @forelse ($articles as $article)
                            @php
                                $isHidden = ! $article->is_visible;
                                $isOnDashboard = in_array($article->id, $onDashboard, true);
                                $deleteWarning = $article->is_manual
                                    ? 'Delete this article?'
                                    : 'Delete this article? It can come back on the next fetch while it is still in the feed. Hide it instead to keep it off the dashboard.';
                            @endphp
                            <tr class="h-row flex flex-wrap items-center gap-x-3 gap-y-3 p-4 md:p-0 md:table-row">
                                <td class="w-full md:px-3 xl:px-5 md:py-3.5 md:max-w-0">
                                    <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" title="{{ $article->title }}"
                                       class="block text-slate-100 font-medium truncate hover:text-sky-200 transition-colors">{{ $article->title }}</a>
                                    <p class="mt-0.5 text-xs text-slate-300 truncate">
                                        {{ $article->source }}
                                        <span class="text-slate-500" aria-hidden="true">&middot;</span>
                                        @if ($article->is_manual)
                                            <span class="font-semibold text-violet-300">ADDED BY HAND</span>
                                        @else
                                            <span class="text-slate-400">From a feed</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="md:px-3 xl:px-5 md:py-3.5 whitespace-nowrap text-slate-300 tabular-nums">{{ $article->published_at->format('j M Y') }}</td>
                                <td class="ml-auto md:ml-0 md:px-3 xl:px-5 md:py-3.5 whitespace-nowrap">
                                    @if ($isHidden)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-300"><span class="w-1.5 h-1.5 rounded-full bg-current"></span> HIDDEN</span>
                                    @elseif ($isOnDashboard)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span> ON DASHBOARD</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span> OLDER, NOT SHOWN</span>
                                    @endif
                                </td>
                                <td class="w-full md:w-auto md:px-3 xl:px-5 md:py-3.5 pt-3 md:pt-3.5 border-t border-slate-500/20 md:border-0">
                                    <div class="flex items-center gap-2.5">
                                        <form method="POST" action="{{ route('scam-news.toggle', $article) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="h-btn !py-1.5 !px-3 !text-xs whitespace-nowrap {{ $isHidden ? 'h-btn-ok' : 'h-btn-ghost' }}">{{ $isHidden ? 'Show' : 'Hide' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('scam-news.destroy', $article) }}" onsubmit="return confirm({{ Js::from($deleteWarning) }});">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-btn h-btn-danger !py-1.5 !px-3 !text-xs whitespace-nowrap">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-slate-300">
                                    @if (filled($filters['search'] ?? null) || $currentFilter !== 'all')
                                        No articles match. Try adjusting your search or filter.
                                    @else
                                        No articles yet. Click Fetch now to load the latest news, or add one by hand.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $articles->links() }}
        </div>
    </div>
</x-layouts.dashboard>