<x-layouts.dashboard>
    <style>
        .h-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .h-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .9rem; border-radius: .75rem; font-size: .8125rem; font-weight: 600; transition: background-color .15s, border-color .15s; white-space: nowrap; }
        .h-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .h-btn-soft { color: #7dd3fc; border: 1px solid rgba(56, 189, 248, .4); background: rgba(56, 189, 248, .08); }
        .h-btn-soft:hover { background: rgba(56, 189, 248, .18); }
        .h-btn-quiet { color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .3); background: rgba(148, 163, 184, .06); }
        .h-btn-quiet:hover { background: rgba(148, 163, 184, .16); }
        .h-row { transition: background-color .15s; }
        .h-row:hover { background-color: rgba(125, 211, 252, .06); }
        .h-tab { padding: .4rem .9rem; border-radius: 9999px; font-size: .8125rem; font-weight: 600; color: #cbd5e1; }
        .h-tab--on { color: #fff; background: rgba(56, 189, 248, .18); box-shadow: inset 0 0 0 1px rgba(56, 189, 248, .4); }
        .h-in { animation: h-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes h-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .h-in { animation: none; } }
    </style>

    @php
        $brandLabel = fn (string $word) => strlen($word) <= 4 ? strtoupper($word) : ucfirst($word);
        $verdictTone = ['phishing' => 'text-rose-300', 'suspicious' => 'text-orange-300', 'clean' => 'text-emerald-300'];
        $verdictLabel = ['phishing' => 'Phishing', 'suspicious' => 'Suspicious', 'clean' => 'No threats found', 'review' => 'Needs review'];
    @endphp

    <div class="h-in mb-5 sm:mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">Look-alike Watch</h1>
            <p class="text-slate-300 text-sm max-w-3xl leading-relaxed">New websites whose names copy a Brunei brand, such as BIBD, Baiduri, Progresif, DST or the police. PhishCore reads the daily lists of newly registered domains and the public logs of new website certificates, so a fake site often shows up here before anyone receives the scam message. A listing is a lead, not proof: scan it to check, or dismiss it if it is genuine.</p>
        </div>
        <form method="POST" action="{{ route('lookalike-watch.check') }}" class="shrink-0">
            @csrf
            <button type="submit" class="h-btn h-btn-main">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Check now
            </button>
        </form>
    </div>

    @if (session('status'))
        <p class="h-in mb-4 rounded-xl border border-emerald-300/25 bg-emerald-400/[0.07] px-4 py-3 text-sm text-emerald-200" role="status">{{ session('status') }}</p>
    @endif

    <div class="h-in mb-4 text-xs sm:text-sm text-slate-300" style="--d:.04s" data-last-check>
        @if ($lastRun)
            Last check {{ $lastRun->created_at->diffForHumans() }}:
            @if ($lastRun->ok)
                @if (str_contains((string) $lastRun->note, 'did not answer'))
                    <span class="text-amber-300 font-semibold">partly worked</span>,
                @else
                    <span class="text-emerald-300 font-semibold">worked</span>,
                @endif
                {{ number_format($lastRun->names_checked) }} {{ $lastRun->names_checked === 1 ? 'name contained' : 'names contained' }} a brand word, {{ $lastRun->new_found }} new {{ $lastRun->new_found === 1 ? 'look-alike' : 'look-alikes' }}.
            @else
                <span class="text-orange-300 font-semibold">the certificate logs did not answer</span>@if ($lastGoodRun) (last successful check {{ $lastGoodRun->created_at->diffForHumans() }})@endif. It tries again automatically every morning.
            @endif
            @if ($lastRun->note)<span class="block text-slate-400 mt-0.5">{{ $lastRun->note }}</span>@endif
        @else
            No check has run yet. It runs every morning at 7:15, or press Check now.
        @endif
    </div>

    <div class="h-in grid grid-cols-3 gap-3 sm:gap-4 mb-5" style="--d:.06s">
        @foreach ([['ON THE LIST', $stats['new'], 'text-white'], ['FOUND THIS WEEK', $stats['week'], 'text-orange-300'], ['DISMISSED', $stats['dismissed'], 'text-slate-300']] as [$label, $value, $tone])
            <div class="h-card p-3.5 sm:p-5">
                <p class="text-[11px] sm:text-xs font-medium tracking-wide text-slate-300 mb-1">{{ $label }}</p>
                <p class="text-2xl sm:text-3xl font-bold tabular-nums {{ $tone }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="h-in flex gap-1 mb-4" style="--d:.08s">
        <a href="{{ route('lookalike-watch.index') }}" class="h-tab {{ $show === 'new' ? 'h-tab--on' : '' }}">On the list</a>
        <a href="{{ route('lookalike-watch.index', ['show' => 'dismissed']) }}" class="h-tab {{ $show === 'dismissed' ? 'h-tab--on' : '' }}">Dismissed</a>
    </div>

    <div class="h-in h-card overflow-hidden hidden md:block" style="--d:.12s">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="border-b border-slate-500/25 text-left text-xs font-medium tracking-wide text-slate-300">
                        <th class="px-4 sm:px-5 py-3">WEBSITE</th>
                        <th class="px-4 sm:px-5 py-3">COPIES</th>
                        <th class="px-4 sm:px-5 py-3">APPEARED</th>
                        <th class="px-4 sm:px-5 py-3">PHISHCORE SCAN</th>
                        <th class="px-4 sm:px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-500/20">
                    @forelse ($domains as $row)
                        @php
                            $scan = $scans[$row->id] ?? null;
                            $verdict = $scan?->analyses->first()?->verdict;
                        @endphp
                        <tr class="h-row">
                            {{-- Shown as text, never as a link: these may be live scam sites. --}}
                            <td class="px-4 sm:px-5 py-3 font-mono text-[13px] text-slate-100"><span class="break-all">{{ $row->domain }}</span>
                                @if ($row->address_points >= 25)
                                    <span class="block text-[11px] font-sans text-orange-300 mt-0.5">The address itself looks risky</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-3 text-slate-200">{{ $brandLabel($row->brand) }}</td>
                            <td class="px-4 sm:px-5 py-3 text-slate-300" title="{{ $row->certificate_issued_at?->format('j F Y \a\t g:i A') }}">{{ $row->certificate_issued_at?->format('j M Y') ?? 'Unknown' }}<span class="block text-[11px] text-slate-400">{{ $row->source === 'new-domains' ? 'newly registered' : 'new certificate' }} &middot; found {{ $row->created_at->diffForHumans() }}</span></td>
                            <td class="px-4 sm:px-5 py-3">
                                @if ($scan)
                                    <a href="{{ route('scan.show', $scan) }}" class="font-semibold hover:underline {{ $verdictTone[$verdict] ?? 'text-slate-300' }}">{{ $verdictLabel[$verdict] ?? 'Scanned' }}</a>
                                    <span class="block text-[11px] text-slate-400">{{ $scan->created_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-slate-400">Not scanned yet</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($row->status === 'new')
                                        <form method="POST" action="{{ route('scan.store') }}">
                                            @csrf
                                            <input type="hidden" name="url" value="https://{{ $row->domain }}">
                                            <button type="submit" class="h-btn h-btn-soft">Scan</button>
                                        </form>
                                        <form method="POST" action="{{ route('lookalike-watch.dismiss', $row) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="h-btn h-btn-quiet" title="It is genuine or not about a Brunei brand">Dismiss</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-400 mr-1">@if ($row->dismissedByUser) by {{ $row->dismissedByUser->name }}@endif</span>
                                        <form method="POST" action="{{ route('lookalike-watch.restore', $row) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="h-btn h-btn-quiet">Put back</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                                {{ $show === 'new' ? 'No look-alike websites on the list. New ones appear here after each check.' : 'Nothing has been dismissed.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Phones: one card per website instead of the wide table. --}}
    <div class="md:hidden space-y-3">
        @forelse ($domains as $row)
            @php
                $scan = $scans[$row->id] ?? null;
                $verdict = $scan?->analyses->first()?->verdict;
            @endphp
            <div class="h-card p-4">
                <p class="font-mono text-[13px] text-slate-100 break-all">{{ $row->domain }}</p>
                @if ($row->address_points >= 25)
                    <p class="text-[11px] text-orange-300 mt-0.5">The address itself looks risky</p>
                @endif
                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 mt-3 text-xs">
                    <div><dt class="text-slate-400">Copies</dt><dd class="text-slate-200 font-semibold">{{ $brandLabel($row->brand) }}</dd></div>
                    <div><dt class="text-slate-400">Appeared</dt><dd class="text-slate-200">{{ $row->certificate_issued_at?->format('j M Y') ?? 'Unknown' }} <span class="text-slate-400">({{ $row->source === 'new-domains' ? 'registered' : 'certificate' }})</span></dd></div>
                    <div class="col-span-2"><dt class="text-slate-400">PhishCore scan</dt>
                        <dd>
                            @if ($scan)
                                <a href="{{ route('scan.show', $scan) }}" class="font-semibold hover:underline {{ $verdictTone[$verdict] ?? 'text-slate-300' }}">{{ $verdictLabel[$verdict] ?? 'Scanned' }}</a> <span class="text-slate-400">{{ $scan->created_at->diffForHumans() }}</span>
                            @else
                                <span class="text-slate-300">Not scanned yet</span>
                            @endif
                        </dd>
                    </div>
                </dl>
                <div class="flex gap-2 mt-3">
                    @if ($row->status === 'new')
                        <form method="POST" action="{{ route('scan.store') }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="url" value="https://{{ $row->domain }}">
                            <button type="submit" class="h-btn h-btn-soft w-full">Scan</button>
                        </form>
                        <form method="POST" action="{{ route('lookalike-watch.dismiss', $row) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="h-btn h-btn-quiet w-full">Dismiss</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('lookalike-watch.restore', $row) }}" class="flex-1">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="h-btn h-btn-quiet w-full">Put back</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="h-card px-5 py-10 text-center text-sm text-slate-400">
                {{ $show === 'new' ? 'No look-alike websites on the list. New ones appear here after each check.' : 'Nothing has been dismissed.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $domains->links() }}</div>

    <p class="h-in mt-4 text-xs text-slate-400 leading-relaxed" style="--d:.16s">Sources: the free daily lists of newly registered domains (WhoisDS, common endings such as .com, .net, .top and .xyz) and the public certificate logs (crt.sh). A name is only listed when it really copies a brand: the brand as a word of its own or joined to words like "login" or "bank" (bibd-login, mybibd, baiduribank), never inside another word, and everyday words (DST, Imagine, Pos, Polis) only next to "brunei". The brands' own websites and all .gov.bn sites are never listed.</p>
</x-layouts.dashboard>