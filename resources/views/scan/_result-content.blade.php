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
        'file' => 'UPLOADED FILE',
        default => 'SCANNED URL',
    };
    $typeValue = match ($report->type) {
        'email' => $report->sender_email,
        'phone' => $report->phone_number,
        'screenshot' => 'Image submitted for OCR analysis (see below)',
        'file' => $report->file_name ?: 'Uploaded file',
        default => $report->url,
    };
    $scanAnotherLabel = match ($report->type) {
        'email' => 'Scan Another Email',
        'phone' => 'Scan Another Number',
        'screenshot' => 'Scan Another Screenshot',
        'file' => 'Scan Another File',
        default => 'Scan Another URL',
    };
    if ($shared) {
        $scanAnotherLabel = 'Scan a link yourself';
    }

    $topReason = collect($analysis->flags ?? [])->filter(fn ($check) => is_array($check))->sortByDesc('points')->first();
@endphp

@php
    $shownVerdict = $shownVerdict ?? $analysis->verdict;
    $headline = match ($shownVerdict) {
        'phishing' => 'Phishing detected',
        'suspicious' => 'This looks suspicious',
        'review' => 'Needs a manual review',
        default => match ($report->type) {
            'url' => 'This appears safe',
            'phone' => 'No reports found',
            'file' => 'No known threats found',
            default => 'No threats detected',
        },
    };
    $cleanCaveat = ($analysis->verdict === 'clean' || ! in_array($analysis->verdict, ['phishing', 'suspicious', 'review'], true)) && $report->type !== 'url'
        ? ($report->type === 'phone'
            ? 'This is not a guarantee of safety. A new scam number can have no reports yet. Be careful with anything that asks for money, passwords or codes.'
            : ($report->type === 'file'
                ? 'This is not a guarantee of safety. A brand-new scam file may not be known yet. Only install apps from the Play Store or App Store, and do not open files from people you do not know.'
                : 'This is not a guarantee of safety. Be careful with anything that asks for money, passwords or codes.'))
        : null;
    $aiDisagrees = $shownVerdict === 'review' && $analysis->verdict !== 'review';
    $verdictIcon = match ($shownVerdict) {
        'clean' => 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
        'review' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        default => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    };
    $checkList = collect($analysis->flags ?? [])->filter(fn ($check) => is_array($check));
    $aiSkipped = $checkList->contains(fn ($c) => in_array($c['name'] ?? '', ['AI Message Review', 'AI Page Review'], true) && ($c['status'] ?? '') === 'UNKNOWN');
    $heroVt = $ctiLookup?->raw_response['data']['attributes']['last_analysis_stats'] ?? null;
    $heroVtTotal = $heroVt ? array_sum(array_map('intval', $heroVt)) : 0;
    $heroFacts = array_values(array_filter([
        ['Checks with warnings', $checkList->where('points', '>', 0)->count() . ' of ' . $checkList->count(), $checkList->where('points', '>', 0)->isNotEmpty() ? $style['text'] : 'text-white'],
        $heroVtTotal > 0 ? ['Security companies', (($heroVt['malicious'] ?? 0) + ($heroVt['suspicious'] ?? 0)) . ' of ' . $heroVtTotal . ' flagged', (($heroVt['malicious'] ?? 0) + ($heroVt['suspicious'] ?? 0)) > 0 ? 'text-red-300' : 'text-emerald-300'] : null,
        $analysis->domain_age_days !== null ? ['Domain age', number_format($analysis->domain_age_days) . ' days', $analysis->domain_age_days < 30 ? 'text-orange-300' : 'text-white'] : null,
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

            @if ($aiDisagrees)
                <p data-ai-disagrees class="mt-5 text-sm sm:text-base text-slate-200 leading-relaxed max-w-2xl">{{ \App\Support\VerdictWording::CAVEAT }}</p>
            @endif

            @if ($topReason && ($topReason['points'] ?? 0) > 0)
                <p class="mt-5 text-sm sm:text-base text-slate-200 leading-relaxed max-w-2xl">{{ \App\Services\CheckExplainer::plain((string) $topReason['message']) }}</p>
            @endif

            @if ($aiSkipped)
                <p data-ai-rules-only class="mt-5 max-w-2xl rounded-xl px-4 py-3 text-xs sm:text-sm leading-relaxed text-slate-300" style="background: rgba(148, 163, 184, .1); border: 1px solid rgba(148, 163, 184, .25)">
                    The AI review could not run this time, so this result uses PhishCore's built-in rules only. Scanning again in a few minutes may add the AI check.
                </p>
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

    {{-- Administrators can mark the exact file as safe when a scan got it wrong --}}
    @if (! $shared && $report->type === 'file' && $report->file_sha256 && auth()->user()?->role === 'admin')
        @php $fileTrusted = \App\Models\TrustedFile::isTrusted($report->file_sha256); @endphp
        <div class="flex flex-wrap items-center gap-3 px-6 sm:px-8 py-4 border-t border-white/10 bg-black/10">
            @if ($fileTrusted)
                <p class="text-sm text-emerald-300 flex-1 min-w-0">This exact file is marked as safe. New scans of it show as safe.</p>
                <form method="POST" action="{{ route('scan.untrust-file', $report) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="r-btn r-btn-ghost">Remove the safe mark</button>
                </form>
            @else
                <p class="text-sm text-slate-300 flex-1 min-w-0">Administrator: if this scan got the file wrong, you can mark this exact file as safe.</p>
                <form method="POST" action="{{ route('scan.trust-file', $report) }}" onsubmit="return confirm('Mark this exact file as safe? Anyone who scans it later will see it as safe.')">
                    @csrf
                    <button type="submit" class="r-btn r-btn-ghost">Mark this file as safe</button>
                </form>
            @endif
            @if (session('trusted_file_status'))
                <p class="basis-full text-xs text-sky-300">{{ session('trusted_file_status') }}</p>
            @endif
        </div>
    @endif
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
        $vtDen = max($vtTotal, 1);
        $vtColor = $vtFlagged > 0 ? 'text-red-400' : 'text-emerald-400';
        $vtBadge = $vtFlagged > 0 ? 'bg-red-500/10 border-red-500/25 text-red-300' : 'bg-emerald-500/10 border-emerald-500/25 text-emerald-300';

        $vtParts = [
            ['label' => 'Malicious', 'count' => $vtMalicious, 'color' => '#f87171'],
            ['label' => 'Suspicious', 'count' => $vtSuspicious, 'color' => '#fb923c'],
            ['label' => 'Harmless', 'count' => $vtHarmless, 'color' => '#34d399'],
            ['label' => 'Undetected', 'count' => $vtUndetected, 'color' => '#64748b'],
        ];

        // donut: one arc per group on a circle of radius 54, each arc a little shorter than its share so a thin gap shows between groups
        $vtCirc = 2 * M_PI * 54;
        $vtArcs = [];
        $vtUsed = 0.0;
        foreach ($vtParts as $part) {
            $share = $part['count'] / $vtDen * $vtCirc;
            if ($part['count'] > 0) {
                $dash = max($share - 2, 0.8);
                $vtArcs[] = ['color' => $part['color'], 'dash' => round($dash, 2), 'rest' => round($vtCirc - $dash, 2), 'offset' => $vtUsed > 0 ? round(-$vtUsed, 2) : 0];
            }
            $vtUsed += $share;
        }

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

        // the vendors that flagged it, already in malicious-first order; the first few are shown as chips under the chart
        $flaggedVendors = $vendorResults->filter(fn ($row) => in_array($row['category'], ['malicious', 'suspicious'], true))->values();
        $flaggedShown = $flaggedVendors->take(6);
        $flaggedMore = $flaggedVendors->count() - $flaggedShown->count();

        $categoryStyles = [
            'malicious' => 'bg-red-500/10 text-red-300 border-red-500/25',
            'suspicious' => 'bg-orange-500/10 text-orange-300 border-orange-500/25',
            'harmless' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/25',
            'undetected' => 'bg-slate-500/10 text-slate-300 border-slate-500/25',
        ];
    @endphp
    <div class="r-card r-in vt p-6 mb-6" style="--d:.25s">
        <div class="vt-head">
            <div class="vt-id">
                <span class="r-tile" style="--c: 56,189,248">
                    <svg width="20" height="20" class="text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5.2" /><path d="M12 12l6.2-6.2" /><circle cx="8.6" cy="9" r=".9" fill="#f87171" stroke="none" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-white">{{ $ctiLookup->source }} / CTI Lookup</h2>
                    <p class="text-sm text-slate-300">Looked up {{ $ctiLookup->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <div class="vt-pills">
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full border shrink-0 {{ $vtBadge }}">
                    {{ $vtFlagged }} / {{ $vtTotal }} FLAGGED
                </span>
                <span class="vt-score">Threat score {{ $ctiLookup->threat_score }}%</span>
            </div>
        </div>

        @if ($vtTotal > 0)
            <div class="vt-hero">
                <div class="vt-donut" role="img" aria-label="{{ $vtMalicious }} malicious, {{ $vtSuspicious }} suspicious, {{ $vtHarmless }} harmless and {{ $vtUndetected }} undetected, out of {{ $vtTotal }} security vendors">
                    <svg viewBox="0 0 140 140" aria-hidden="true">
                        <circle cx="70" cy="70" r="54" fill="none" stroke="rgba(148,163,184,.14)" stroke-width="14" />
                        @foreach ($vtArcs as $arc)
                            <circle cx="70" cy="70" r="54" fill="none" stroke="{{ $arc['color'] }}" stroke-width="14" stroke-dasharray="{{ $arc['dash'] }} {{ $arc['rest'] }}" stroke-dashoffset="{{ $arc['offset'] }}" />
                        @endforeach
                    </svg>
                    <div class="vt-donut-mid">
                        <span class="vt-donut-num {{ $vtColor }}">{{ $vtFlagged }}</span>
                        <span class="vt-donut-cap"><span>OF {{ $vtTotal }}</span><span>FLAGGED</span></span>
                    </div>
                </div>

                <ul class="vt-legend">
                    @foreach ($vtParts as $part)
                        <li class="vt-row">
                            <span class="vt-dot" style="background: {{ $part['color'] }}"></span>
                            <span class="vt-label">{{ $part['label'] }}</span>
                            <span class="vt-track"><span class="vt-fill r-grow" style="--d:.45s; width: {{ round($part['count'] / $vtDen * 100, 1) }}%; min-width: {{ $part['count'] > 0 ? '.35rem' : '0' }}; background: {{ $part['color'] }}"></span></span>
                            <span class="vt-count">{{ $part['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($flaggedVendors->isNotEmpty())
                <div class="vt-flagged r-well">
                    <div class="vt-flagged-top">
                        <p class="vt-eyebrow">FLAGGED BY</p>
                        <p class="vt-note">showing {{ $flaggedShown->count() }} of {{ $flaggedVendors->count() }}, malicious first</p>
                    </div>
                    <div class="vt-chips">
                        @foreach ($flaggedShown as $row)
                            <span class="vt-chip vt-chip-{{ $row['category'] }}">
                                <span class="vt-chip-name">{{ $row['vendor'] }}</span>
                                <span class="vt-chip-res">{{ Str::limit($row['result'] ?: ucfirst($row['category']), 14) }}</span>
                            </span>
                        @endforeach
                        @if ($flaggedMore > 0)
                            <span class="vt-chip vt-chip-more">+{{ $flaggedMore }} more</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="vt-clear r-well">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
                    <span>No security vendor flagged this.</span>
                </div>
            @endif
        @else
            <p class="vt-note vt-empty">VirusTotal returned no vendor results for this lookup.</p>
        @endif

        <div class="vt-foot-wrap" x-data="{ open: false }">
            <div class="vt-foot">
                @if ($vendorResults->isNotEmpty())
                    <button type="button" @click="open = ! open" :aria-expanded="open ? 'true' : 'false'" class="vt-btn">
                        <span>View all {{ $vendorResults->count() }} security vendor results</span>
                        <svg class="vt-btn-ic" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                @endif
                <p class="vt-src">VirusTotal public API &middot; 70+ antivirus and security engines</p>
            </div>

            @if ($vendorResults->isNotEmpty())
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
            @endif
        </div>
    </div>
@endif

@if ($report->screenshot_path && $report->type === 'screenshot')
    <div class="r-in mb-6" style="--d:.3s">
        <h2 class="text-lg font-bold text-white mb-1">Uploaded Screenshot</h2>
        <p class="text-sm text-slate-300 mb-4">The image submitted for this report, analyzed via OCR text extraction.</p>
        <div class="r-card overflow-hidden flex items-center justify-center">
            <img src="{{ $report->screenshot_path }}" alt="Submitted screenshot" loading="lazy"
                 class="w-full max-h-[500px] object-contain block" onerror="this.closest('div.r-in').style.display='none'">
        </div>
    </div>
@endif

{{-- PAGE PREVIEW (optional, display only; never changes the score and never shown on public share links) --}}
@if (! $shared && $report->previewLink() && $report->status === 'completed' && app(\App\Services\PagePreview::class)->enabled())
    <section class="r-card r-in p-5 sm:p-6 mb-6" style="--d:.3s"
             x-data="{
                src: @js(app(\App\Services\PagePreview::class)->exists($report->previewPicture()) ? $report->previewPicture() : null),
                state: @js(app(\App\Services\PagePreview::class)->exists($report->previewPicture()) ? 'ready' : 'idle'),
                note: @js(app(\App\Services\PagePreview::class)->exists($report->previewPicture()) ? $report->preview_note : null),
                message: '',
                tips: @js([
                    'Real banks and government offices never ask for your password or OTP by SMS, WhatsApp or email.',
                    'Scammers like pressure. Words such as "urgent", "last chance" or "your account will be closed" are a warning sign.',
                    'A shortened link (bit.ly and similar) hides where it really goes. Be extra careful before opening one.',
                    'Check the address, not the look of the page. Anyone can copy a bank logo, but not its real web address.',
                    'Look closely at the spelling of the link. Fake sites often swap or add a letter, like "paypa1" or "bibd-secure".',
                    'Unexpected fine, parcel or prize messages are a very common scam. Check with the real organisation yourself.',
                    'Do not install an app (APK) sent to you in a chat or message. It can take over your phone.',
                    'If you already clicked and typed your details, change that password at once and tell your bank.',
                    'A padlock in the browser only means the connection is private. It does not mean the site is honest.',
                    'When in doubt, close the message and open the official website or app yourself instead of using the link.',
                ]),
                tip: '',
                tipTimer: null,
                startTips() {
                    this.stopTips();
                    this.tip = this.tips[Math.floor(Math.random() * this.tips.length)];
                    this.tipTimer = setInterval(() => {
                        const others = this.tips.filter(t => t !== this.tip);
                        this.tip = others[Math.floor(Math.random() * others.length)];
                    }, 6000);
                },
                stopTips() {
                    if (this.tipTimer) { clearInterval(this.tipTimer); this.tipTimer = null; }
                },
                shown: false,
                viewer: false,
                zoom: 1,
                pinchStart: 0,
                pinchZoom: 1,
                openViewer() {
                    if (! this.shown || ! this.src) { return; }
                    this.zoom = 1;
                    this.viewer = true;
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => { const close = document.getElementById('preview-close'); if (close) { close.focus(); } });
                },
                closeViewer() {
                    if (! this.viewer) { return; }
                    this.viewer = false;
                    document.body.style.overflow = '';
                },
                setZoom(value) {
                    const box = document.getElementById('preview-pan');
                    const next = Math.min(5, Math.max(1, value));
                    if (! box || next === this.zoom) { this.zoom = next; return; }
                    const cx = (box.scrollLeft + box.clientWidth / 2) / box.scrollWidth;
                    const cy = (box.scrollTop + box.clientHeight / 2) / box.scrollHeight;
                    this.zoom = next;
                    this.$nextTick(() => {
                        box.scrollLeft = cx * box.scrollWidth - box.clientWidth / 2;
                        box.scrollTop = cy * box.scrollHeight - box.clientHeight / 2;
                    });
                },
                toggleZoom() { this.setZoom(this.zoom > 1 ? 1 : 2.5); },
                distance(e) { return Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY); },
                touchStart(e) { if (e.touches.length === 2) { this.pinchStart = this.distance(e); this.pinchZoom = this.zoom; } },
                touchMove(e) { if (e.touches.length === 2 && this.pinchStart > 0) { e.preventDefault(); this.setZoom(this.pinchZoom * this.distance(e) / this.pinchStart); } },
                async load() {
                    this.state = 'loading';
                    this.message = '';
                    try {
                        for (let i = 0; i < 40; i++) {
                            const res = await fetch(@js(route('scan.preview', $report)), {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' },
                            });
                            if (res.status === 429) { this.fail('Please wait a minute before trying again.'); return; }
                            const data = await res.json();
                            if (data.status === 'ready') { this.src = data.url; this.note = data.note || null; this.state = 'ready'; return; }
                            if (data.status !== 'pending') { this.fail(data.message); return; }
                            await new Promise(r => setTimeout(r, 4000));
                        }
                        this.fail('The preview is taking too long. Please try again later.');
                    } catch (e) {
                        this.fail('');
                    }
                },
                fail(text) {
                    this.message = text || 'The preview could not be loaded. Please try again.';
                    this.state = 'failed';
                },
             }"
             x-init="$watch('state', value => value === 'loading' ? startTips() : stopTips())">
        <h2 class="text-lg font-bold text-white mb-1">Page preview</h2>
        <p class="text-sm text-slate-300 mb-4">
            @if ($report->type === 'screenshot')
                A picture of how the link in your screenshot looks.
            @elseif ($report->type === 'email')
                A picture of how the link in your email looks.
            @elseif ($report->type === 'file')
                A picture of how the link in your file looks.
            @else
                A picture of how the page looks.
            @endif
            It is only for you to look at and does not change the result.
        </p>
        @if ($report->type !== 'url')
            <p class="text-xs text-slate-400 mb-4 break-all">Link found: {{ parse_url($report->previewLink(), PHP_URL_HOST) ?: 'unknown address' }}</p>
        @endif

        <div x-show="state === 'idle' || state === 'failed'" style="display: none;">
            <button type="button" x-on:click="load()"
                    class="px-4 py-2 rounded-lg text-sm font-semibold bg-white/10 hover:bg-white/15 text-white border border-white/15">
                <span x-text="state === 'failed' ? 'Try again' : 'Show page preview'"></span>
            </button>
            <p class="text-xs text-slate-400 mt-2">The link is sent to urlscan.io, a security service, to take the picture.</p>
            <p class="text-sm text-amber-300 mt-3" x-show="state === 'failed'" x-text="message" style="display: none;"></p>
        </div>

        <div x-show="state === 'loading'" style="display: none;" role="status" aria-live="polite">
            <div class="pv-skel" aria-hidden="true">
                <div class="pv-skel-bar">
                    <span class="pv-skel-dot"></span><span class="pv-skel-dot"></span><span class="pv-skel-dot"></span>
                    <span class="pv-skel-url"></span>
                </div>
                <div class="pv-skel-body">
                    <div class="pv-skel-row">
                        <span class="pv-sk" style="width: 5.5rem; height: 1.6rem;"></span>
                        <span class="pv-skel-nav">
                            <span class="pv-sk" style="width: 3rem; height: .8rem;"></span>
                            <span class="pv-sk" style="width: 3rem; height: .8rem;"></span>
                            <span class="pv-sk" style="width: 3rem; height: .8rem;"></span>
                        </span>
                    </div>
                    <span class="pv-sk" style="height: 6.5rem;"></span>
                    <span class="pv-sk" style="width: 68%; height: 1rem;"></span>
                    <span class="pv-sk" style="width: 46%; height: 1rem;"></span>
                    <div class="pv-skel-cards">
                        <span class="pv-sk" style="height: 3.2rem;"></span>
                        <span class="pv-sk" style="height: 3.2rem;"></span>
                        <span class="pv-sk" style="height: 3.2rem;"></span>
                    </div>
                </div>
            </div>
            <p class="text-sm text-slate-300 mt-3">Taking the picture. This usually takes 10 to 30 seconds...</p>
            <p class="pv-tip" aria-live="off">
                <span class="pv-tip-label">Did you know?</span>
                <span x-text="tip"></span>
            </p>
        </div>

        <div x-show="state === 'ready'" style="display: none;">
            <div class="relative overflow-hidden rounded-lg border border-white/10 bg-black/30">
                <img :src="src" alt="Preview of the scanned page" loading="lazy"
                     :class="shown ? 'cursor-zoom-in' : 'blur-xl'"
                     class="w-full max-h-[500px] object-contain block transition"
                     x-on:click="openViewer()"
                     x-on:error="state = 'idle'; src = null; note = null">
                <button type="button" x-show="! shown" x-on:click="shown = true"
                        class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-white bg-black/40" style="display: none;">
                    Click to show the picture
                </button>
            </div>
            <button type="button" x-show="shown" x-on:click="openViewer()" style="display: none;"
                    class="mt-3 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/10 hover:bg-white/15 text-white border border-white/15">
                Enlarge picture
            </button>
            {{-- What the page looked like once it had loaded in the sandbox. Display only: it never changes the score. Alpine's x-text escapes everything. --}}
            <template x-if="note">
                <div class="mt-4 rounded-lg border px-4 py-3"
                     :class="{ 'border-amber-400/30 bg-amber-400/10': note.tone === 'warn', 'border-sky-400/25 bg-sky-400/10': note.tone === 'info', 'border-emerald-400/25 bg-emerald-400/10': note.tone === 'ok' }">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">After the page loaded</p>
                    <p class="text-sm font-semibold text-white" x-text="note.headline"></p>
                    <ul class="mt-1.5 space-y-1 text-sm text-slate-300 list-disc pl-5">
                        <template x-for="(line, i) in note.lines" :key="i">
                            <li x-text="line"></li>
                        </template>
                    </ul>
                    <p class="text-xs text-slate-400 mt-2">Read from the page after it loaded in the sandbox. It is for information only and is not part of the score.</p>
                </div>
            </template>
            <p class="text-xs text-slate-400 mt-2">The picture is blurred until you click it, because the page may be harmful. It was taken when you asked for it, and the page may have changed since.</p>
        </div>

        {{-- Full-screen viewer: moved to the end of the page so the cards' effects cannot trap it. Drag to move, pinch or use the buttons to zoom. --}}
        <template x-teleport="body">
            <div x-show="viewer" x-transition.opacity style="display: none;"
                 x-on:keydown.escape.window="closeViewer()"
                 class="fixed inset-0 z-[70] flex flex-col bg-black/95" role="dialog" aria-modal="true" aria-label="Page preview, enlarged">
                <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-white">
                    <p class="text-xs text-slate-300 max-sm:hidden">Drag to move around. Pinch or use the buttons to zoom.</p>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" x-on:click="setZoom(zoom - 0.5)" aria-label="Zoom out"
                                class="w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 text-xl leading-none">&minus;</button>
                        <span class="w-12 text-center text-sm tabular-nums" x-text="Math.round(zoom * 100) + '%'">100%</span>
                        <button type="button" x-on:click="setZoom(zoom + 0.5)" aria-label="Zoom in"
                                class="w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 text-xl leading-none">+</button>
                        <button type="button" x-on:click="setZoom(1)"
                                class="h-10 px-3 rounded-lg bg-white/10 hover:bg-white/20 text-sm">Fit</button>
                        <button type="button" id="preview-close" x-on:click="closeViewer()"
                                class="h-10 px-4 rounded-lg bg-sky-500 hover:bg-sky-400 text-sm font-semibold">Close</button>
                    </div>
                </div>
                <div id="preview-pan" class="flex-1 overflow-auto overscroll-contain" style="touch-action: pan-x pan-y;"
                     x-on:touchstart="touchStart($event)" x-on:touchmove="touchMove($event)" x-on:dblclick="toggleZoom()">
                    <img :src="src" alt="Preview of the scanned page, enlarged" draggable="false"
                         class="block max-w-none h-auto mx-auto select-none"
                         :style="'width: ' + (zoom * 100) + '%'">
                </div>
            </div>
        </template>
    </section>
@endif

{!! $afterResultCard ?? '' !!}

@include('scan._checks')

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