@php
    $shared = $shared ?? false;
    $totalScore = max($analysis->risk_score, 1);
    $breakdownRows = collect($analysis->flags ?? [])
        ->filter(fn ($check) => is_array($check))
        ->map(function ($check) use ($totalScore) {
            $pct = round((($check['points'] ?? 0) / $totalScore) * 100);
            return array_merge($check, ['pct' => $pct]);
        })
        ->sortByDesc('points')
        ->values();

    $refId = 'PG-' . $report->created_at->format('Y-md') . '-' . strtoupper(substr(md5($report->id), 0, 5));

    $typeLabel = match ($report->type) {
        'email' => 'REPORTED SENDER EMAIL',
        'phone' => 'REPORTED PHONE NUMBER',
        'screenshot' => 'UPLOADED SCREENSHOT',
        default => 'SCANNED URL',
    };
    $typeValue = match ($report->type) {
        'email' => $report->sender_email,
        'phone' => $report->phone_number,
        'screenshot' => 'Image submitted for OCR analysis (see below)',
        default => $report->url,
    };
    $scanAnotherLabel = match ($report->type) {
        'email' => 'Scan Another Email',
        'phone' => 'Scan Another Number',
        'screenshot' => 'Scan Another Screenshot',
        default => 'Scan Another URL',
    };
    if ($shared) {
        $scanAnotherLabel = 'Scan a link yourself';
    }

    $topReason = collect($analysis->flags ?? [])->filter(fn ($check) => is_array($check))->sortByDesc('points')->first();
@endphp

@php
    $headline = match ($analysis->verdict) {
        'phishing' => 'Phishing detected',
        'suspicious' => 'This looks suspicious',
        'review' => 'Needs a manual review',
        default => $report->type === 'url' ? 'This appears safe' : 'No threats detected',
    };
    $cleanCaveat = ($analysis->verdict === 'clean' || ! in_array($analysis->verdict, ['phishing', 'suspicious', 'review'], true)) && $report->type !== 'url'
        ? 'This is not a guarantee of safety. Be careful with anything that asks for money, passwords or codes.'
        : null;
    $verdictIcon = match ($analysis->verdict) {
        'clean' => 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
        'review' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        default => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    };
    $checkList = collect($analysis->flags ?? [])->filter(fn ($check) => is_array($check));
    $heroVt = $ctiLookup?->raw_response['data']['attributes']['last_analysis_stats'] ?? null;
    $heroVtTotal = $heroVt ? array_sum(array_map('intval', $heroVt)) : 0;
    $heroFacts = array_values(array_filter([
        ['Checks with warnings', $checkList->where('points', '>', 0)->count() . ' of ' . $checkList->count(), $checkList->where('points', '>', 0)->isNotEmpty() ? $style['text'] : 'text-white'],
        $heroVtTotal > 0 ? ['Security companies', (($heroVt['malicious'] ?? 0) + ($heroVt['suspicious'] ?? 0)) . ' of ' . $heroVtTotal . ' flagged', (($heroVt['malicious'] ?? 0) + ($heroVt['suspicious'] ?? 0)) > 0 ? 'text-red-300' : 'text-emerald-300'] : null,
        $analysis->domain_age_days !== null ? ['Website age', number_format($analysis->domain_age_days) . ' days', $analysis->domain_age_days < 30 ? 'text-orange-300' : 'text-white'] : null,
        $analysis->duration_ms !== null ? ['Time taken', round($analysis->duration_ms / 1000, 1) . 's', 'text-white'] : null,
    ]));
@endphp

{{-- VERDICT HERO --}}
<section class="r-card r-hero r-in mb-6 overflow-hidden" style="--c: {{ $style['rgb'] }}; --d:.15s">
    <div class="p-6 sm:p-8 grid lg:grid-cols-[minmax(0,1fr)_17rem] gap-x-10 gap-y-7">
        <div class="min-w-0">
            <div class="flex items-start gap-4">
                <span class="r-tile" style="width:3rem;height:3rem">
                    <svg class="w-6 h-6 {{ $style['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $verdictIcon }}" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="r-headline text-xl sm:text-2xl lg:text-3xl font-bold break-words {{ $style['text'] }} leading-tight">{{ $headline }}</h2>
                    <p class="mt-1 text-xs text-slate-400">
                        <span class="font-mono">{{ $refId }}</span> &middot; Scanned {{ $report->created_at->format('j F Y \a\t g:i A') }}@if ($report->user && ! $shared) &middot; by {{ $report->user->name }}@endif
                    </p>
                </div>
            </div>

            @if ($cleanCaveat)
                <p class="mt-5 text-sm sm:text-base text-slate-200 leading-relaxed max-w-2xl">{{ $cleanCaveat }}</p>
            @endif

            @if ($topReason && ($topReason['points'] ?? 0) > 0)
                <p class="mt-5 text-sm sm:text-base text-slate-200 leading-relaxed max-w-2xl">{{ \App\Services\CheckExplainer::plain((string) $topReason['message']) }}</p>
            @endif

            <div class="mt-6">
                <p class="text-[11px] tracking-[0.14em] text-slate-400 mb-1.5">{{ $typeLabel }}</p>
                <div class="r-well flex items-center gap-3 px-4 py-3">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcons[$report->type] ?? $typeIcons['url'] }}" /></svg>
                    <p class="flex-1 min-w-0 font-mono text-sm text-white break-all">{{ $typeValue }}</p>
                </div>
            </div>
        </div>

        <div class="lg:border-l lg:border-white/10 lg:pl-10 flex flex-col items-center justify-center">
            <svg width="200" height="110" viewBox="0 0 200 110" style="overflow: visible;">
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="rgba(148,163,184,.2)" stroke-width="14" stroke-linecap="round" />
                <path class="r-arc" d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="{{ $style['ring'] }}" stroke-width="14" stroke-linecap="round"
                      stroke-dasharray="{{ $arcLength }}" stroke-dashoffset="{{ $arcOffset }}" style="--from: {{ $arcLength }}; --to: {{ $arcOffset }}" />
            </svg>
            <div style="margin-top: -40px;" class="text-center">
                <span class="block text-4xl font-bold text-white tabular-nums">{{ $analysis->risk_score }}</span>
            </div>
            <p class="text-[11px] tracking-[0.14em] text-slate-300 mt-2">RISK SCORE / 100</p>
            <p class="text-[11px] text-slate-400 mt-1 text-center">0 = very likely safe, 100 = very dangerous</p>
            <p class="text-xs font-bold tracking-wide {{ $style['text'] }} mt-0.5">{{ $severityLabel }}</p>
        </div>
    </div>

    <div class="r-facts grid grid-cols-2 border-t border-white/10 bg-black/10" style="--n: {{ max(count($heroFacts), 1) }}">
        @foreach ($heroFacts as [$factLabel, $factValue, $factText])
            <div class="px-6 sm:px-8 py-3.5">
                <p class="text-[11px] tracking-[0.12em] text-slate-400">{{ strtoupper($factLabel) }}</p>
                <p class="mt-1 text-sm font-semibold tabular-nums {{ $factText }}">{{ $factValue }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-3 px-6 sm:px-8 py-4 border-t border-white/10">
        <a href="{{ route('scan.index') }}" class="r-btn r-btn-main">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
            {{ $scanAnotherLabel }}
        </a>
@unless ($shared)
                <a href="{{ route('scan.pdf', $report) }}" class="r-btn r-btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
            Download PDF Report
        </a>
        @endunless
        @if (! $shared)
        @guest
            <a href="{{ route('register') }}" class="r-btn r-btn-ghost">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                Create Account to Save History
            </a>
        @endguest
        @endif
    </div>
</section>

{{-- CTI / VIRUSTOTAL --}}
@if ($ctiLookup)
    @php
        $vtStats = $ctiLookup->raw_response['data']['attributes']['last_analysis_stats'] ?? null;
        $vtMalicious = $vtStats['malicious'] ?? 0;
        $vtSuspicious = $vtStats['suspicious'] ?? 0;
        $vtHarmless = $vtStats['harmless'] ?? 0;
        $vtUndetected = $vtStats['undetected'] ?? 0;
        $vtTotal = $vtMalicious + $vtSuspicious + $vtHarmless + $vtUndetected;
        $vtFlagged = $vtMalicious + $vtSuspicious;
        $vtColor = $vtFlagged > 0 ? 'text-red-400' : 'text-emerald-400';
        $vtBadge = $vtFlagged > 0 ? 'bg-red-500/10 border-red-500/25 text-red-300' : 'bg-emerald-500/10 border-emerald-500/25 text-emerald-300';
    @endphp
    <div class="r-card r-in p-6 mb-6" style="--d:.25s">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
            <div class="flex items-center gap-3">
                <span class="r-tile" style="--c: 56,189,248">
                    <svg width="20" height="20" class="text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ $ctiLookup->source }} / CTI Lookup</h2>
                    <p class="text-sm text-slate-300">Cross-referenced against {{ $vtTotal }} independent security vendors</p>
                </div>
            </div>
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full border {{ $vtBadge }}">
                {{ $vtFlagged }} / {{ $vtTotal }} FLAGGED
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="r-well p-4 text-center">
                <p class="text-2xl font-bold {{ $vtColor }}">{{ $vtMalicious }}</p>
                <p class="text-xs text-slate-300 mt-1">Malicious</p>
            </div>
            <div class="r-well p-4 text-center">
                <p class="text-2xl font-bold text-orange-400">{{ $vtSuspicious }}</p>
                <p class="text-xs text-slate-300 mt-1">Suspicious</p>
            </div>
            <div class="r-well p-4 text-center">
                <p class="text-2xl font-bold text-emerald-400">{{ $vtHarmless }}</p>
                <p class="text-xs text-slate-300 mt-1">Harmless</p>
            </div>
            <div class="r-well p-4 text-center">
                <p class="text-2xl font-bold text-slate-300">{{ $vtUndetected }}</p>
                <p class="text-xs text-slate-300 mt-1">Undetected</p>
            </div>
        </div>

        <p class="text-xs text-slate-400 mt-4">
            Threat score: {{ $ctiLookup->threat_score }}% &middot; Looked up {{ $ctiLookup->created_at->diffForHumans() }} via VirusTotal's public API (70+ integrated antivirus and security engines).
        </p>

        @php
            $vendorResults = collect($ctiLookup->raw_response['data']['attributes']['last_analysis_results'] ?? [])
                ->map(function ($result, $vendor) {
                    return [
                        'vendor' => $vendor,
                        'category' => $result['category'] ?? 'undetected',
                        'result' => $result['result'] ?? null,
                    ];
                })
                ->values()
                ->sortBy(function ($row) {
                    // Show the interesting ones first: malicious, then suspicious,
                    // then everything else, alphabetically within each group.
                    $rank = match ($row['category']) {
                        'malicious' => 0,
                        'suspicious' => 1,
                        default => 2,
                    };
                    return [$rank, $row['vendor']];
                })
                ->values();

            $categoryStyles = [
                'malicious' => 'bg-red-500/10 text-red-300 border-red-500/25',
                'suspicious' => 'bg-orange-500/10 text-orange-300 border-orange-500/25',
                'harmless' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/25',
                'undetected' => 'bg-slate-500/10 text-slate-300 border-slate-500/25',
            ];
        @endphp

        @if ($vendorResults->isNotEmpty())
            <div class="mt-5 border-t border-slate-600/40 pt-4" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between text-left">
                    <span class="text-sm font-medium text-slate-100">View all {{ $vendorResults->count() }} security vendor results</span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 max-sm:hidden" x-text="open ? 'Click to collapse' : 'Click to expand'">Click to expand</span>
                        <svg class="w-4 h-4 text-slate-300 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </span>
                </button>
                <div x-show="open" x-collapse class="mt-4">
                    <div class="grid sm:grid-cols-2 gap-2 max-h-96 overflow-y-auto pr-1">
                        @foreach ($vendorResults as $row)
                            @php
                                $badgeClass = $categoryStyles[$row['category']] ?? $categoryStyles['undetected'];
                            @endphp
                            <div class="flex items-center justify-between gap-3 r-well r-row px-3 py-2">
                                <span class="text-sm text-slate-100 truncate">{{ $row['vendor'] }}</span>
                                <span class="text-[10px] font-medium px-2 py-1 rounded-full border shrink-0 {{ $badgeClass }}">
                                    {{ $row['result'] ? Str::limit($row['result'], 20) : ucfirst($row['category']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif

@if ($report->screenshot_path)
    <div class="r-in mb-6" style="--d:.3s">
        <h2 class="text-lg font-bold text-white mb-1">
            {{ $report->type === 'screenshot' ? 'Uploaded Screenshot' : 'Website Screenshot' }}
        </h2>
        <p class="text-sm text-slate-300 mb-4">
            {{ $report->type === 'screenshot' ? 'The image submitted for this report, analyzed via OCR text extraction.' : 'A live capture of the scanned page at the time of analysis.' }}
        </p>
        <div class="r-card overflow-hidden flex items-center justify-center">
            <img src="{{ $report->screenshot_path }}" alt="Submitted screenshot" loading="lazy"
                 class="w-full max-h-[500px] object-contain block" onerror="this.closest('div.r-in').style.display='none'">
        </div>
    </div>
@endif

{{-- WHAT WE CHECKED (plain-language view of the detection layers) --}}
@php
    $allChecks = collect($analysis->flags ?? [])->filter(fn ($c) => is_array($c))->values();
    $checkGroups = [
        'warn' => ['title' => 'Things that look wrong', 'note' => null, 'open' => true, 'rgb' => '251, 191, 36'],
        'ok' => ['title' => 'Things that look fine', 'note' => null, 'open' => false, 'rgb' => '52, 211, 153'],
        'info' => ['title' => "Things we couldn't check, or that are just for information", 'rgb' => '148, 163, 184', 'open' => false, 'note' => "\"Couldn't check\" is not good or bad by itself. It only means we couldn't get an answer, so it did not count against the score.", 'open' => false],
    ];
    $checkIconPaths = [
        'SSL Certificate' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
        'Domain Age' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'URL Structure' => 'M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5',
        'Blacklist Database' => 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
        'Sender Domain Analysis' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
        'Phone Number Analysis' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'Screenshot Text Extraction' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];
    $defaultCheckIcon = 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z';
@endphp

<style>
    /* the three groups under "What we checked": a clear bar with a chevron, so people can see it opens */
    .rg-sum { list-style: none; cursor: pointer; user-select: none; display: flex; align-items: center; gap: .75rem; min-height: 3.1rem; padding: .7rem .9rem; border-radius: .9rem;
        background: rgba(8, 15, 32, .5); border: 1px solid rgba(var(--c), .35); transition: background-color .15s, border-color .15s; }
    .rg-sum::-webkit-details-marker { display: none; }
    .rg-sum:hover { background: rgba(var(--c), .08); border-color: rgba(var(--c), .55); }
    .rg-sum:focus-visible { outline: 2px solid rgba(125, 211, 252, .8); outline-offset: 2px; }
    .rg-count { flex: none; display: grid; place-items: center; min-width: 1.75rem; height: 1.75rem; padding: 0 .4rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: rgb(var(--c)); background: rgba(var(--c), .16); border: 1px solid rgba(var(--c), .4); }
    .rg-title { font-size: .95rem; font-weight: 600; color: #f1f5f9; line-height: 1.25; }
    .rg-peek { margin-top: .15rem; font-size: .75rem; color: #94a3b8; line-height: 1.3; }
    .rg-act { flex: none; margin-left: auto; display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 600; color: #7dd3fc; }
    .rg-act svg { width: 1rem; height: 1rem; transition: transform .2s; }
    details[open] > .rg-sum { margin-bottom: .9rem; }
    details[open] > .rg-sum .rg-act svg { transform: rotate(180deg); }
    details[open] > .rg-sum .rg-peek, details[open] > .rg-sum .rg-show, details:not([open]) > .rg-sum .rg-hide { display: none; }
    @media (prefers-reduced-motion: reduce) { .rg-act svg { transition: none; } }
</style>

<h2 class="text-lg font-bold text-white mb-1">What we checked</h2>
<p class="text-sm text-slate-300 mb-5">{{ \App\Services\CheckExplainer::summary($allChecks) }}</p>

@foreach ($checkGroups as $groupKey => $group)
    @php $groupChecks = $allChecks->filter(fn ($c) => \App\Services\CheckExplainer::group((string) ($c['status'] ?? '')) === $groupKey); @endphp
    @continue($groupChecks->isEmpty())

    <details class="mb-6" @if ($group['open']) open @endif>
        @php
            $peekNames = $groupChecks->take(3)->map(fn ($c) => \App\Services\CheckExplainer::title($c['name']))->implode(', ');
            $peekMore = $groupChecks->count() - 3;
        @endphp
        <summary class="rg-sum" style="--c: {{ $group['rgb'] }}">
            <span class="rg-count">{{ $groupChecks->count() }}</span>
            <span class="min-w-0">
                <span class="rg-title block">{{ $group['title'] }}</span>
                <span class="rg-peek block truncate">{{ $peekNames }}{{ $peekMore > 0 ? ' and '.$peekMore.' more' : '' }}</span>
            </span>
            <span class="rg-act">
                <span class="rg-show">Show</span><span class="rg-hide">Hide</span>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </span>
        </summary>
        @if ($group['note'])
            <p class="text-xs text-slate-400 mb-3">{{ $group['note'] }}</p>
        @endif

        <div class="grid md:grid-cols-2 gap-4">
            @foreach ($groupChecks as $check)
                @php
                    $colorClass = $statusColors[$check['status']] ?? $statusColors['SAFE'];
                    $iconPath = $checkIconPaths[$check['name']] ?? $defaultCheckIcon;
                    $meaning = \App\Services\CheckExplainer::meaning($check['name']);
                @endphp
                <div class="r-card r-lift r-in p-5" style="--d: {{ min($loop->index, 8) * 0.06 + 0.1 }}s">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-white font-semibold flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                            </svg>
                            <span>{{ \App\Services\CheckExplainer::title($check['name']) }}</span>
                        </p>
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full border shrink-0 {{ $colorClass }}">{{ \App\Services\CheckExplainer::statusLabel((string) $check['status']) }}</span>
                    </div>
                    <p class="text-sm text-slate-200 leading-relaxed">{{ \App\Services\CheckExplainer::plain((string) ($check['message'] ?? '')) }}</p>
                    @if ($meaning)
                        <details class="mt-3">
                            <summary class="cursor-pointer select-none text-xs text-sky-300">What does this mean?</summary>
                            <p class="mt-2 text-xs text-slate-400 leading-relaxed">{{ $meaning }}</p>
                        </details>
                    @endif
                    @if (\App\Services\CheckExplainer::hasFriendlyTitle($check['name']))
                        <p class="mt-3 text-[11px] text-slate-500">Technical name: {{ $check['name'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </details>
@endforeach

<div class="mb-10"></div>

{{-- WHY THIS SCORE --}}
<div class="r-card r-in p-6 mb-6" style="--d:.1s">
    <h2 class="text-lg font-bold text-white mb-1">Why this score?</h2>
    <p class="text-sm text-slate-300 mb-1">The score goes from 0 (very likely safe) to 100 (very dangerous). These are the checks that pushed it up, biggest first.</p>
    <p class="text-xs text-slate-400 mb-5">The percentage is how much of the score each check is responsible for.</p>

    @if ($analysis->risk_score > 0 && $breakdownRows->where('points', '>', 0)->count() > 0)
        <div class="space-y-4">
            @foreach ($breakdownRows->where('points', '>', 0) as $row)
                @php
                    $barColor = $row['pct'] >= 80 ? 'bg-red-400' : ($row['pct'] >= 40 ? 'bg-orange-400' : 'bg-sky-400');
                    $textColor = $row['pct'] >= 80 ? 'text-red-300' : ($row['pct'] >= 40 ? 'text-orange-300' : 'text-sky-300');
                @endphp
                <div>
                    <div class="flex items-center justify-between gap-3 text-sm mb-1.5">
                        <span class="text-slate-100">{{ \App\Services\CheckExplainer::title($row['name']) }}</span>
                        <span class="{{ $textColor }} font-semibold shrink-0">{{ $row['pct'] }}%</span>
                    </div>
                    <span class="block h-2 rounded-full bg-slate-700/40 overflow-hidden">
                        <span class="r-grow block h-full rounded-full {{ $barColor }}" style="width: {{ $row['pct'] }}%; --d: {{ 0.25 + $loop->index * 0.08 }}s"></span>
                    </span>
                </div>
            @endforeach
        </div>
    @elseif ($analysis->verdict === 'review')
        <p class="text-sm text-slate-300">No automatic score was generated for this report &mdash; it needs a human to review the extracted content above.</p>
    @else
        <p class="text-sm text-slate-300">Nothing pushed the score up &mdash; this report passed every check cleanly.</p>
    @endif
</div>

@include('scan._advice')

{{-- TECHNICAL INFORMATION --}}
<div class="r-card r-in overflow-hidden" style="--d:.2s" x-data="{ open: false }">
    <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left">
        <span class="flex items-center gap-2 text-white font-semibold">
            <svg class="w-4 h-4 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
            Technical Information
        </span>
        <span class="flex items-center gap-2">
            <span class="text-xs text-slate-400 max-sm:hidden" x-text="open ? 'Click to collapse' : 'Click to expand'">Click to expand</span>
            <svg class="w-4 h-4 text-slate-300 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </span>
    </button>
    <div x-show="open" x-collapse class="px-6 pb-6 border-t border-slate-600/40 pt-4">
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-xs text-slate-400 mb-1">SCAN REFERENCE ID</dt>
                <dd class="text-slate-100">{{ $refId }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-1">REPORT TYPE</dt>
                <dd class="text-slate-100 uppercase">{{ $report->type }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-1">VERDICT</dt>
                <dd class="text-slate-100 uppercase">{{ $analysis->verdict }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-1">RISK SCORE</dt>
                <dd class="text-slate-100">{{ $analysis->risk_score }} / 100</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-1">DOMAIN AGE</dt>
                <dd class="text-slate-100">{{ $analysis->domain_age_days !== null ? $analysis->domain_age_days . ' days' : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-1">IP ADDRESS</dt>
                <dd class="text-slate-100 font-mono">{{ $analysis->ip_address ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-1">IP REPUTATION</dt>
                <dd class="text-slate-100">{{ $analysis->ip_reputation ?? 'Unavailable' }}</dd>
            </div>
@unless ($shared)
            <div>
                <dt class="text-xs text-slate-400 mb-1">SCANNED BY</dt>
                <dd class="text-slate-100">{{ $report->user?->name ?? 'Guest (unregistered)' }}</dd>
            </div>
@endunless
            <div>
                <dt class="text-xs text-slate-400 mb-1">SCAN DURATION</dt>
                <dd class="text-slate-100">{{ $analysis->duration_ms !== null ? round($analysis->duration_ms / 1000, 2) . 's' : 'Not recorded' }}</dd>
            </div>
        </dl>
        @if (!empty($analysis->redirect_chain) && count($analysis->redirect_chain) > 1)
            <p class="text-xs text-slate-400 mt-5 mb-2">REDIRECT CHAIN</p>
            <div class="r-well p-4 space-y-2">
                @foreach ($analysis->redirect_chain as $i => $hop)
                    <div class="flex items-start gap-2 text-xs font-mono">
                        <span class="text-slate-400 shrink-0">{{ $i + 1 }}.</span>
                        <span class="{{ $i === count($analysis->redirect_chain) - 1 ? 'text-orange-300' : 'text-slate-200' }} break-all">{{ $hop }}</span>
                        @if ($i === count($analysis->redirect_chain) - 1 && count($analysis->redirect_chain) > 1)
                            <span class="text-[10px] text-orange-300 shrink-0">(final)</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <p class="text-xs text-slate-400 mt-5 mb-2">RAW CHECK RESULTS</p>
        <div class="r-well p-4 space-y-2">
            @foreach (collect($analysis->flags ?? [])->filter(fn ($c) => is_array($c)) as $check)
                <p class="text-xs text-slate-300 font-mono">
                    <span class="text-white">{{ $check['name'] }}:</span>
                    {{ $check['status'] }} ({{ $check['points'] ?? 0 }} pts) &mdash; {{ $check['message'] }}
                </p>
            @endforeach
        </div>
    </div>
</div>