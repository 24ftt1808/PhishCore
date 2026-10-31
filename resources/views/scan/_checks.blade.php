{{-- WHAT WE CHECKED
     One segment per check, the worst finding as a featured card with its evidence, the other warnings as
     compact cards, passed checks as chips, unavailable / information checks as dashed chips.
     Reads $analysis from the including view. Only changes how results are shown: scores, statuses and the
     stored results are untouched, and the PDF report keeps its own layout. --}}
@php
    $ex = \App\Services\CheckExplainer::class;

    $allChecks = collect($analysis->flags ?? [])->filter(fn ($c) => is_array($c))->values();

    // full class strings on purpose, so Tailwind can see them
    $tones = [
        'red' => ['badge' => 'text-red-300 bg-red-400/15 border-red-400/40', 'tile' => 'bg-red-400/15 text-red-300', 'bar' => 'bg-red-400'],
        'amber' => ['badge' => 'text-amber-300 bg-amber-400/15 border-amber-400/40', 'tile' => 'bg-amber-400/15 text-amber-300', 'bar' => 'bg-amber-400'],
        'green' => ['badge' => 'text-emerald-300 bg-emerald-400/15 border-emerald-400/40', 'tile' => 'bg-emerald-400/15 text-emerald-300', 'bar' => 'bg-emerald-400'],
        'slate' => ['badge' => 'text-slate-300 bg-slate-400/10 border-slate-400/40', 'tile' => 'bg-slate-400/15 text-slate-300', 'bar' => 'bg-slate-500'],
    ];
    $toneOf = fn (string $status) => match ($status) {
        'HIGH RISK', 'DETECTED' => 'red',
        'SUSPICIOUS' => 'amber',
        'SAFE', 'LIVE' => 'green',
        default => 'slate',
    };
    $severity = ['HIGH RISK' => 0, 'DETECTED' => 1, 'SUSPICIOUS' => 2];

    // icons: the four warning checks use the mockup's icons, the rest keep the icons the page already had
    $iconInner = [
        'Page Content Analysis' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'Blacklist Database' => '<circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>',
        'VirusTotal / CTI Check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4M12 16h.01"/>',
        'IP Reputation & Location' => '<rect x="2" y="3" width="20" height="8" rx="2"/><rect x="2" y="13" width="20" height="8" rx="2"/><path d="M6 7h.01M6 17h.01"/>',
    ];
    $checkIconPaths = [
        'SSL Certificate' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
        'Domain Age' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'URL Structure' => 'M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5',
        'Sender Domain Analysis' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'Phone Number Analysis' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'Screenshot Text Extraction' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];
    $defaultCheckIcon = 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z';
    $icon = function (string $name, string $cls = 'w-4 h-4') use ($iconInner, $checkIconPaths, $defaultCheckIcon) {
        $inner = $iconInner[$name] ?? '<path d="'.($checkIconPaths[$name] ?? $defaultCheckIcon).'"/>';

        return '<svg class="'.$cls.'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
    };

    // every check, in plain words
    $checks = $allChecks->map(function (array $c) use ($ex, $toneOf) {
        $name = (string) ($c['name'] ?? 'Check');
        $status = $ex::shownStatus($c);

        return [
            'name' => $name,
            'title' => $ex::title($name),
            'status' => $status,
            'label' => $ex::statusLabel($status),
            'group' => $ex::group($status),
            'tone' => $toneOf($status),
            'points' => (int) ($c['points'] ?? 0),
            'message' => trim($ex::plain((string) ($c['message'] ?? ''))),
            'meaning' => $ex::meaning($name),
            'technical' => $ex::hasFriendlyTitle($name) ? $name : null,
        ];
    });

    // warnings: worst first, most points first inside the same level
    $warn = $checks->where('group', 'warn')
        ->sortBy(fn ($c) => ($severity[$c['status']] ?? 3) * 100000 - $c['points'])
        ->values();
    $ok = $checks->where('group', 'ok')->values();
    $info = $checks->where('group', 'info')->values();
    $segments = $warn->concat($ok)->concat($info);

    $total = $checks->count();
    $warnCount = $warn->count();
    $okCount = $ok->count();
    $infoCount = $info->count();

    // only a HIGH RISK check is featured; two High risk checks = the first is featured, the rest are normal cards
    $featuredKey = $warn->search(fn ($c) => $c['status'] === 'HIGH RISK');
    $featured = $featuredKey === false ? null : $warn[$featuredKey];
    $rest = $featuredKey === false ? $warn : $warn->forget($featuredKey)->values();
    // Every row is full: 3 cards per row on wide screens (a 6-column grid, so a short last row can stretch),
    // 4 cards = 2 + 2, 5 = 3 + 2, 7 = 3 + 2 + 2. On medium screens it is 2 per row and an odd last card stretches.
    $rows = [];
    for ($left = $rest->count(); $left > 0;) {
        $take = $left > 4 ? 3 : ($left === 4 ? 2 : $left);
        $rows[] = $take;
        $left -= $take;
    }
    $spanFor = [1 => 'xl:col-span-6', 2 => 'xl:col-span-3', 3 => 'xl:col-span-2'];
    $lgSpans = [];
    foreach ($rows as $size) {
        for ($k = 0; $k < $size; $k++) {
            $lgSpans[] = $spanFor[$size];
        }
    }
    $smSpan = fn (int $i) => ($rest->count() % 2 === 1 && $i === $rest->count() - 1) ? 'sm:col-span-2' : '';

    // how each check is laid out (headline, body, evidence): shared with the PDF report
    $present = fn (array $c): array => \App\Support\CheckPresenter::present($c);

    // The "i" button for a card's header and its explanation panel (what the check means + technical name).
    // Returns [button, panel]; both are empty when there is nothing to explain.
    $explain = function (array $c, string $id, ?string $looked = null): array {
        if (! $c['meaning'] && ! $c['technical']) {
            return ['', ''];
        }
        $button = '<button type="button" class="ck-info" aria-expanded="false" aria-controls="'.e($id).'" aria-label="'.e('What does the "'.$c['title'].'" check mean?').'">'
            .'<svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01" stroke-width="2.6"/></svg></button>';
        $panel = '<div id="'.e($id).'" class="ck-pop" role="region" aria-label="'.e('About the "'.$c['title'].'" check').'" hidden>'
            .'<p class="text-[11px] font-bold tracking-widest uppercase text-sky-300">What this check means</p>';
        if ($looked) {
            $panel .= '<p class="mt-1.5 text-[13px] text-slate-300 leading-relaxed"><span class="font-semibold text-slate-100">Looked at:</span> '.e($looked).'</p>';
        }
        if ($c['meaning']) {
            $panel .= '<p class="mt-1.5 text-[13px] text-slate-200 leading-relaxed">'.e($c['meaning']).'</p>';
        }
        if ($c['technical']) {
            $panel .= '<p class="mt-2 font-mono text-[11px] text-slate-400">Technical name: '.e($c['technical']).'</p>';
        }

        return [$button, $panel.'</div>'];
    };

    $lookedAt = fn (string $name): ?string => \App\Support\CheckPresenter::lookedAt($name);

@endphp

@if ($checks->isNotEmpty())
<style>
    /* "i" button in each card header and the explanation panel it opens. The panel floats over the card, so opening
       it never changes the height of the card or of its neighbours. */
    .ck-pill { transition: background-color .15s, border-color .15s; }
    .ck-pill:hover { background-color: rgba(255, 255, 255, .09); border-color: rgba(255, 255, 255, .22); }
    .ck-pill:focus-visible { outline: 2px solid rgba(125, 211, 252, .8); outline-offset: 2px; }
    html { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    .ck-card { position: relative; }
    .ck-card.is-open { z-index: 30; }
    .ck-info { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; margin: -.25rem 0 -.25rem -.25rem; flex-shrink: 0; border-radius: 9999px; color: #94a3b8; cursor: pointer; transition: color .15s, background-color .15s; }
    .ck-info:hover, .ck-info[aria-expanded="true"] { color: #7dd3fc; background-color: rgba(125, 211, 252, .12); }
    .ck-info:focus-visible { outline: 2px solid rgba(125, 211, 252, .8); outline-offset: 2px; }
    .ck-pop { position: absolute; z-index: 40; left: .75rem; right: .75rem; top: 3.4rem; max-width: 24rem; padding: .85rem 1rem; border-radius: .8rem; background: #0b1530; border: 1px solid rgba(125, 211, 252, .35); box-shadow: 0 18px 40px -12px rgba(0, 0, 0, .75); animation: ck-pop .14s ease-out; }
    .ck-pop[hidden] { display: none; }
    .ck-row .ck-pop { top: calc(100% + .35rem); left: 2.75rem; }
    /* the ⓘ follows the last word of the name, even when a long name wraps (rows and cards alike) */
    .ck-card .ck-info { vertical-align: middle; margin: -.25rem 0 -.25rem .2rem; }
    /* a bigger tap target for the ⓘ on touch screens, without changing the layout */
    @media (pointer: coarse) { .ck-card .ck-info { width: 2.25rem; height: 2.25rem; margin: -.5rem -.25rem -.5rem .1rem; } }
    @keyframes ck-pop { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .ck-pop { animation: none; } .ck-info { transition: none; } }
</style>

@php
    // one pill per kind of result that exists, each jumping to its section
    $legend = [
        'red' => ['label' => 'Serious', 'href' => '#ck-attention'],
        'amber' => ['label' => 'Caution', 'href' => '#ck-attention'],
        'green' => ['label' => 'Passed', 'href' => '#ck-passed'],
        'slate' => ['label' => 'Unavailable', 'href' => '#ck-unavailable'],
    ];
    $toneCounts = $segments->countBy('tone');
@endphp
<div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-3">
    <h2 class="text-lg font-bold text-white">What we checked</h2>
    <p class="text-sm text-slate-400">{{ $total }} {{ $total === 1 ? 'check' : 'checks' }} ran on this scan</p>
</div>
<p class="sr-only">{{ $ex::summary($allChecks) }}</p>
<ul class="flex flex-wrap gap-2 mb-7">
    @foreach ($legend as $tone => $item)
        @if (($toneCounts[$tone] ?? 0) > 0)
            <li>
                <a href="{{ $item['href'] }}" class="ck-pill inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3 py-1.5 text-sm">
                    <span class="w-2.5 h-2.5 rounded-full {{ $tones[$tone]['bar'] }}" aria-hidden="true"></span>
                    <span class="font-bold text-white">{{ $toneCounts[$tone] }}</span>
                    <span class="text-slate-300">{{ $item['label'] }}</span>
                </a>
            </li>
        @endif
    @endforeach
</ul>

{{-- NEEDS ATTENTION --}}
@if ($warnCount > 0)
    <h3 id="ck-attention" class="scroll-mt-6 text-xs font-bold tracking-widest uppercase text-slate-300 mb-3">Needs attention · {{ $warnCount }}</h3>

    <div class="space-y-4">
        @if ($featured)
            @php $p = $present($featured); $t = $tones[$featured['tone']]; [$infoBtn, $infoPop] = $explain($featured, 'ck-info-featured'); @endphp
            <div class="ck-card r-card r-in p-5 sm:p-6 grid gap-6 items-start {{ $p['facts'] ? 'xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]' : '' }}"
                 style="--d: .1s; border-color: rgba(248, 113, 113, .4);">
                <div class="flex flex-col gap-3 min-w-0">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 shrink-0 rounded-[9px] flex items-center justify-center {{ $t['tile'] }}">{!! $icon($featured['name'], 'w-[18px] h-[18px]') !!}</span>
                        @php $tw = explode(' ', $featured['title']); $tl = array_pop($tw); @endphp
                        <span class="flex-1 min-w-0 text-[15px] leading-8"><span class="font-bold text-white">{{ $tw ? implode(' ', $tw).' ' : '' }}<span class="whitespace-nowrap">{{ $tl }}{!! $infoBtn !!}</span></span></span>
                        <span class="shrink-0 mt-1 text-xs font-bold px-2.5 py-1 rounded-full border {{ $t['badge'] }}">{{ $featured['label'] }}</span>
                    </div>
                    @if ($p['headline'])
                        <h4 class="text-xl font-extrabold leading-snug tracking-tight text-white">{{ $p['headline'] }}</h4>
                    @endif
                    @if ($p['body'] !== '')
                        <p class="text-sm text-slate-200 leading-relaxed">{{ $p['body'] }}</p>
                    @endif
                </div>

                @if ($p['facts'])
                    <dl class="r-well px-4 divide-y divide-white/10 min-w-0">
                        @foreach ($p['facts'] as $fact)
                            <div class="grid grid-cols-[6.5rem_minmax(0,1fr)] sm:grid-cols-[120px_minmax(0,1fr)] gap-3 py-2.5 items-baseline">
                                <dt class="text-[13px] text-slate-400">{{ $fact['label'] }}</dt>
                                <dd class="m-0 min-w-0 font-bold {{ $fact['mono'] ? 'font-mono text-[13px] break-all' : 'text-sm' }} {{ $fact['danger'] ? 'text-red-300' : 'text-white' }}">{{ $fact['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                {!! $infoPop !!}
            </div>
        @endif

        @if ($rest->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                @foreach ($rest as $c)
                    @php $p = $present($c); $t = $tones[$c['tone']]; [$infoBtn, $infoPop] = $explain($c, 'ck-info-'.$loop->index); @endphp
                    <div class="ck-card r-card r-lift r-in p-4 flex flex-col gap-2.5 min-w-0 {{ $lgSpans[$loop->index] }} {{ $smSpan($loop->index) }}" style="--d: {{ min($loop->index, 8) * 0.06 + 0.15 }}s">
                        <div class="flex items-start gap-2.5">
                            <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center {{ $t['tile'] }}">{!! $icon($c['name']) !!}</span>
                            @php $tw = explode(' ', $c['title']); $tl = array_pop($tw); @endphp
                            <span class="flex-1 min-w-0 text-sm leading-7"><span class="font-bold text-white">{{ $tw ? implode(' ', $tw).' ' : '' }}<span class="whitespace-nowrap">{{ $tl }}{!! $infoBtn !!}</span></span></span>
                            <span class="shrink-0 mt-0.5 text-xs font-bold px-2.5 py-1 rounded-full border {{ $t['badge'] }}">{{ $c['label'] }}</span>
                        </div>
                        @if ($p['headline'])
                            <h4 class="text-base font-extrabold leading-snug text-white">{{ $p['headline'] }}</h4>
                        @endif
                        @if ($p['meter'])
                            <div class="h-1.5 rounded-full bg-slate-700/50 overflow-hidden" role="img" aria-label="{{ $p['meter']['value'] }} of {{ $p['meter']['total'] }} vendors flagged this link">
                                <div class="h-full min-w-[6px] rounded-full bg-red-400" style="width: {{ $p['meter']['pct'] }}%"></div>
                            </div>
                        @endif
                        @if ($p['body'] !== '')
                            <p class="text-sm text-slate-300 leading-relaxed">{{ $p['body'] }}</p>
                        @endif
                        {!! $infoPop !!}
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif

{{-- PASSED --}}
@if ($okCount > 0)
    <div class="mt-8">
        <h3 id="ck-passed" class="scroll-mt-6 text-xs font-bold tracking-widest uppercase text-slate-300">Passed · {{ $okCount }}</h3>
        <p class="text-xs text-slate-400 mt-1 mb-3">Each of these checks found nothing wrong on its own. The overall result is based on all checks together.</p>
        <div class="r-well divide-y divide-white/10">
            @foreach ($ok as $c)
                @php
                    $looked = $lookedAt($c['name']);
                    [$infoBtn, $infoPop] = $explain($c, 'ck-info-ok-'.$loop->index, $looked);
                    $generic = in_array(rtrim($c['message'], '.'), ['', 'No problems were found'], true);
                @endphp
                <div class="ck-card ck-row grid grid-cols-[1.25rem_minmax(0,1fr)] sm:grid-cols-[1.25rem_13rem_minmax(0,1fr)] items-start gap-x-3 gap-y-0.5 px-4 py-2.5">
                    <svg class="col-start-1 row-start-1 w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                    <span class="col-start-2 row-start-1 min-w-0 leading-5"><span class="text-sm font-bold text-white min-w-0">{{ $c['title'] }}</span>{!! $infoBtn !!}</span>
                    <p class="col-start-2 row-start-2 sm:col-start-3 sm:row-start-1 text-[13px] leading-5 min-w-0 {{ $generic ? 'text-slate-400' : 'text-slate-200' }}">{{ $generic ? 'No problems found' : $c['message'] }}</p>
                    {!! $infoPop !!}
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- UNAVAILABLE OR FOR INFORMATION --}}
@if ($infoCount > 0)
    <div class="mt-8">
        <h3 id="ck-unavailable" class="scroll-mt-6 text-xs font-bold tracking-widest uppercase text-slate-300">Unavailable or for information · {{ $infoCount }}</h3>
        <p class="text-xs text-slate-400 mt-1 mb-3">"Unavailable" means the check could not get an answer, for example because the website is offline. It is neither good nor bad, and it does not affect the score.</p>
        <div class="r-well divide-y divide-white/10" style="border-style: dashed; border-color: rgba(148, 163, 184, .4);">
            @foreach ($info as $c)
                @php
                    $looked = $lookedAt($c['name']);
                    [$infoBtn, $infoPop] = $explain($c, 'ck-info-na-'.$loop->index, $looked);
                @endphp
                <div class="ck-card ck-row grid grid-cols-[1.25rem_minmax(0,1fr)] sm:grid-cols-[1.25rem_13rem_minmax(0,1fr)] items-start gap-x-3 gap-y-0.5 px-4 py-2.5">
                    <svg class="col-start-1 row-start-1 w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
                    <span class="col-start-2 row-start-1 min-w-0 leading-5"><span class="text-sm font-bold text-white min-w-0">{{ $c['title'] }}</span>{!! $infoBtn !!}</span>
                    <p class="col-start-2 row-start-2 sm:col-start-3 sm:row-start-1 text-[13px] leading-5 text-slate-300 min-w-0"><span class="font-semibold text-slate-100">{{ $c['label'] }}.</span>@if ($c['message'] !== '') {{ $c['message'] }}@endif</p>
                    {!! $infoPop !!}
                </div>
            @endforeach
        </div>
    </div>
@endif

<script>
    (function () {
        if (window.__ckInfoReady) { return; }
        window.__ckInfoReady = true;

        function setOpen(button, open) {
            var panel = document.getElementById(button.getAttribute('aria-controls'));
            var card = button.closest('.ck-card');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (panel) { panel.hidden = !open; }
            if (card) { card.classList.toggle('is-open', open); }
        }
        function closeAll(except) {
            document.querySelectorAll('.ck-info[aria-expanded="true"]').forEach(function (b) {
                if (b !== except) { setOpen(b, false); }
            });
        }
        document.addEventListener('click', function (e) {
            var button = e.target.closest('.ck-info');
            if (button) {
                var willOpen = button.getAttribute('aria-expanded') !== 'true';
                closeAll(button);
                setOpen(button, willOpen);
            } else if (!e.target.closest('.ck-pop')) {
                closeAll(null);
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') { return; }
            var open = document.querySelector('.ck-info[aria-expanded="true"]');
            if (open) { setOpen(open, false); open.focus(); }
        });
    })();
</script>
@endif