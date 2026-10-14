<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-field { background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .2); border-radius: .75rem; color: #e2e8f0; transition: border-color .15s; color-scheme: dark; }
        .h-field::placeholder { color: #94a3b8; }
        .h-field:focus { outline: none; border-color: #38bdf8; }
        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .9rem; border-radius: .75rem; font-size: .8125rem; font-weight: 600; transition: background-color .15s, border-color .15s; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .h-btn-danger { color: #fca5a5; border: 1px solid rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .08); }
        .h-btn-danger:hover { background: rgba(248, 113, 113, .18); }
        .h-btn-ok { color: #6ee7b7; border: 1px solid rgba(52, 211, 153, .4); background: rgba(52, 211, 153, .08); }
        .h-btn-ok:hover { background: rgba(52, 211, 153, .18); }
        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }
        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } }
    </style>

    <div class="h-in mb-5 sm:mb-6">
        <h1 class="text-2xl font-bold text-white mb-1">Reported Numbers</h1>
        <p class="text-slate-300 text-sm">Phone numbers people have reported as scams. Hide a number whose listing is wrong (for example, one that has been given to a new owner). It leaves the public lists and scans treat it as having no reports.@unless ($canHide) Only administrators can hide or show a number.@endunless</p>
    </div>

    @if (session('status'))
        <p class="h-in mb-4 rounded-xl border border-emerald-300/25 bg-emerald-400/[0.07] px-4 py-3 text-sm text-emerald-200" role="status">{{ session('status') }}</p>
    @endif

    <div class="h-in grid grid-cols-3 gap-3 sm:gap-4 mb-5" style="--d:.06s">
        @foreach ([['REPORTED NUMBERS', $stats['numbers'], 'text-white'], ['ON PUBLIC LIST', $stats['listed'], 'text-emerald-300'], ['HIDDEN', $stats['hidden'], 'text-orange-300']] as [$label, $value, $tone])
            <div class="h-card p-3.5 sm:p-5">
                <p class="text-[11px] sm:text-xs font-medium tracking-wide text-slate-300 mb-1">{{ $label }}</p>
                <p class="text-2xl sm:text-3xl font-bold tabular-nums {{ $tone }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('reported-numbers.index') }}" class="h-in h-card p-4 mb-5 flex gap-2" style="--d:.1s">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search a number" autocomplete="off" class="h-field flex-1 min-w-0 px-4 py-2.5 text-sm">
        <button type="submit" class="h-btn h-btn-main">Search</button>
    </form>

    <div class="h-in h-card overflow-hidden" style="--d:.14s">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]">
                <thead>
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-4 sm:px-5 py-3">NUMBER</th>
                        <th class="px-4 sm:px-5 py-3">PEOPLE</th>
                        <th class="px-4 sm:px-5 py-3">KIND OF SCAM</th>
                        <th class="px-4 sm:px-5 py-3">LAST REPORT</th>
                        <th class="px-4 sm:px-5 py-3 text-right">PUBLIC LIST</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-500/20">
                    @forelse ($numbers as $row)
                        <tr class="h-row">
                            <td class="px-4 sm:px-5 py-3 font-mono text-slate-100">{{ $row['phone'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-slate-200 tabular-nums">{{ $row['people'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-slate-300">{{ $row['category'] }}</td>
                            <td class="px-4 sm:px-5 py-3 text-slate-300" title="{{ $row['last']->format('j F Y \a\t g:i A') }}">{{ $row['last']->diffForHumans() }}</td>
                            <td class="px-4 sm:px-5 py-3 text-right">
                                @if ($row['hidden'])
                                    <div class="inline-flex items-center gap-3">
                                        <span class="text-right">
                                            <span class="block text-xs font-semibold text-orange-300">Hidden</span>
                                            @if ($row['hiddenBy'])
                                                <span class="block text-[11px] text-slate-400">by {{ $row['hiddenBy'] }}</span>
                                            @endif
                                        </span>
                                        @if ($canHide)
                                            <form method="POST" action="{{ route('reported-numbers.show') }}">
                                                @csrf
                                                <input type="hidden" name="phone" value="{{ $row['phone'] }}">
                                                <button type="submit" class="h-btn h-btn-ok">Show again</button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-3">
                                        <span class="text-xs font-semibold {{ $row['people'] >= 2 ? 'text-emerald-300' : 'text-slate-400' }}">{{ $row['people'] >= 2 ? 'Listed' : 'Not listed yet' }}</span>
                                        @if ($canHide)
                                            <form method="POST" action="{{ route('reported-numbers.hide') }}"
                                                  onsubmit="return confirm('Hide {{ $row['phone'] }} from the public lists? Scans will treat it as having no reports.');">
                                                @csrf
                                                <input type="hidden" name="phone" value="{{ $row['phone'] }}">
                                                <button type="submit" class="h-btn h-btn-danger">Hide</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-300">
                                {{ $search !== '' ? 'No reported number matches that search.' : 'Nobody has reported a number yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.dashboard>