@php
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

        $topReason = collect($analysis->flags ?? [])->filter(fn ($check) => is_array($check))->sortByDesc('points')->first();
@endphp

@php
    $glowColor = match ($analysis->verdict) {
        'phishing' => 'rgba(248,113,113,0.25)',
        'suspicious' => 'rgba(251,146,60,0.25)',
        'review' => 'rgba(56,189,248,0.25)',
        default => 'rgba(52,211,153,0.25)',
    };
@endphp
<div class="relative {{ $style['bg'] }} {{ $style['border'] }} border rounded-2xl p-8 mb-10 overflow-hidden" style="box-shadow: 0 0 60px -15px {{ $glowColor }};">

    <div class="flex items-start justify-between gap-6 flex-wrap mb-6">
        <div class="flex items-start gap-4">
            <span class="w-11 h-11 rounded-xl {{ $style['bg'] }} border {{ $style['border'] }} flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 {{ $style['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </span>
            <div>
                <p class="{{ $style['text'] }} font-semibold text-sm tracking-wide">{{ $style['label'] }}</p>
                <p class="text-xs text-slate-500 mt-1">
                    Scanned {{ $report->created_at->format('j F Y \a\t g:i A') }}
                    @if ($report->user)
                        &middot; by {{ $report->user->name }}
                    @endif
                </p>
            </div>
        </div>
        <span class="text-xs font-medium px-3 py-1.5 rounded-full {{ $style['threatBg'] }}">{{ $style['threat'] }}</span>
    </div>

    <div class="grid md:grid-cols-3 gap-8 items-center">
        <div class="md:col-span-2 space-y-4">
            <div>
                <p class="text-xs text-slate-500 mb-2">{{ $typeLabel }}</p>
                <div class="bg-slate-950/60 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-300 font-mono break-all">
                    {{ $typeValue }}
                </div>
            </div>

            @if ($topReason && ($topReason['points'] ?? 0) > 0)
                <div class="flex items-start gap-3 bg-slate-950/40 border {{ $style['border'] }} rounded-lg px-4 py-3">
                                       <svg width="18" height="18" class="{{ $style['text'] }} shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-slate-300 leading-relaxed">{{ $topReason['message'] }}</p>
                </div>
            @endif
        </div>

                <div class="flex flex-col items-center">
            <svg width="176" height="96" viewBox="0 0 200 110" style="overflow: visible;">
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#1e293b" stroke-width="14" stroke-linecap="round" />
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="{{ $style['ring'] }}" stroke-width="14" stroke-linecap="round"
                      stroke-dasharray="{{ $arcLength }}" stroke-dashoffset="{{ $arcOffset }}" />
            </svg>
            <div style="margin-top: -32px;" class="text-center">
                <span class="block text-3xl font-bold text-white">{{ $analysis->risk_score }}</span>
            </div>
            <p class="text-[11px] tracking-wide text-slate-500 mt-1">RISK SCORE / 100</p>
            <p class="text-xs font-bold tracking-wide {{ $style['text'] }} mt-0.5">{{ $severityLabel }}</p>
        </div>
    </div>
</div>

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
        $vtBadge = $vtFlagged > 0 ? 'bg-red-500/10 border-red-500/20 text-red-400' : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400';
    @endphp
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-10">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                    <svg width="18" height="18" class="text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ $ctiLookup->source }} / CTI Lookup</h2>
                    <p class="text-sm text-slate-500">Cross-referenced against {{ $vtTotal }} independent security vendors</p>
                </div>
            </div>
            <span class="text-xs font-medium px-3 py-1.5 rounded-full border {{ $vtBadge }}">
                {{ $vtFlagged }} / {{ $vtTotal }} FLAGGED
            </span>
        </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-slate-950/50 border border-slate-800 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold {{ $vtColor }}">{{ $vtMalicious }}</p>
                <p class="text-xs text-slate-500 mt-1">Malicious</p>
            </div>
            <div class="bg-slate-950/50 border border-slate-800 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-orange-400">{{ $vtSuspicious }}</p>
                <p class="text-xs text-slate-500 mt-1">Suspicious</p>
            </div>
            <div class="bg-slate-950/50 border border-slate-800 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-emerald-400">{{ $vtHarmless }}</p>
                <p class="text-xs text-slate-500 mt-1">Harmless</p>
            </div>
            <div class="bg-slate-950/50 border border-slate-800 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-slate-400">{{ $vtUndetected }}</p>
                <p class="text-xs text-slate-500 mt-1">Undetected</p>
            </div>
        </div>

        <p class="text-xs text-slate-600 mt-4">
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
                'malicious' => 'bg-red-500/10 text-red-400 border-red-500/20',
                'suspicious' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                'harmless' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                'undetected' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            ];
        @endphp

        @if ($vendorResults->isNotEmpty())
            <div class="mt-5 border-t border-slate-800 pt-4" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between text-left">
                    <span class="text-sm font-medium text-slate-300">View all {{ $vendorResults->count() }} security vendor results</span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Click to expand</span>
                        <svg class="w-4 h-4 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </span>
                </button>
                <div x-show="open" x-collapse class="mt-4">
                    <div class="grid sm:grid-cols-2 gap-2 max-h-96 overflow-y-auto pr-1">
                        @foreach ($vendorResults as $row)
                            @php
                                $badgeClass = $categoryStyles[$row['category']] ?? $categoryStyles['undetected'];
                            @endphp
                            <div class="flex items-center justify-between gap-3 bg-slate-950/50 border border-slate-800 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-300 truncate">{{ $row['vendor'] }}</span>
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
    <div class="mb-10">
        <h2 class="text-lg font-bold text-white mb-1">
            {{ $report->type === 'screenshot' ? 'Uploaded Screenshot' : 'Website Screenshot' }}
        </h2>
        <p class="text-sm text-slate-500 mb-4">
            {{ $report->type === 'screenshot' ? 'The image submitted for this report, analyzed via OCR text extraction.' : 'A live capture of the scanned page at the time of analysis.' }}
        </p>
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden flex items-center justify-center">
            <img src="{{ $report->screenshot_path }}" alt="Submitted screenshot" loading="lazy"
                 class="w-full max-h-[500px] object-contain block" onerror="this.closest('div.mb-10').style.display='none'">
        </div>
    </div>
@endif

<div class="flex flex-wrap gap-3 mb-8">
    <a href="{{ route('scan.index') }}"
       class="flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold hover:opacity-90 transition shadow-[0_0_16px_-2px_rgba(56,189,248,0.5)]">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
        {{ $scanAnotherLabel }}
    </a>
    <span class="flex items-center gap-2 px-5 py-2.5 rounded-lg border border-slate-800 text-slate-600 text-sm cursor-not-allowed" title="Coming soon">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
        Download PDF Report
    </span>
    @guest
        <a href="{{ route('register') }}"
           class="flex items-center gap-2 px-5 py-2.5 rounded-lg border border-slate-700 text-slate-200 text-sm hover:bg-slate-900 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
            Create Account to Save History
        </a>
    @endguest
</div>

<h2 class="text-lg font-bold text-white mb-1">Detection Details</h2>
<p class="text-sm text-slate-500 mb-5">Results from each detection layer relevant to this report.</p>

<div class="grid md:grid-cols-2 gap-4 mb-10">
    @foreach (collect($analysis->flags ?? [])->filter(fn ($c) => is_array($c)) as $check)
        @php
            $colorClass = $statusColors[$check['status']] ?? $statusColors['SAFE'];
            $checkIconPaths = [
                'SSL Certificate' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
                'Domain Age' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
                'URL Structure' => 'M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5',
                'Blacklist Database' => 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
                'Sender Domain Analysis' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
                'Phone Number Analysis' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
                'Screenshot Text Extraction' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
            ];
            $iconPath = $checkIconPaths[$check['name']] ?? 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z';
        @endphp
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-white font-medium flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                    </svg>
                    {{ $check['name'] }}
                </p>
                <span class="text-[10px] font-medium px-2 py-1 rounded-full border {{ $colorClass }}">{{ $check['status'] }}</span>
            </div>
            <p class="text-sm text-slate-400">{{ $check['message'] }}</p>
        </div>
    @endforeach
</div>

{{-- RISK BREAKDOWN --}}
<div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8">
    <h2 class="text-lg font-bold text-white mb-1">Risk Breakdown</h2>
    <p class="text-sm text-slate-500 mb-5">Contribution of each detection layer to the overall risk score.</p>

    @if ($analysis->risk_score > 0 && $breakdownRows->where('points', '>', 0)->count() > 0)
        <div class="space-y-4">
            @foreach ($breakdownRows->where('points', '>', 0) as $row)
                @php
                    $barColor = $row['pct'] >= 80 ? 'bg-red-400' : ($row['pct'] >= 40 ? 'bg-orange-400' : 'bg-sky-400');
                    $textColor = $row['pct'] >= 80 ? 'text-red-400' : ($row['pct'] >= 40 ? 'text-orange-400' : 'text-sky-400');
                @endphp
                <div>
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span class="text-slate-300">{{ $row['name'] }}</span>
                        <span class="{{ $textColor }} font-medium">{{ $row['pct'] }}%</span>
                    </div>
                    <span class="block h-2 rounded-full bg-slate-800 overflow-hidden">
                        <span class="block h-full {{ $barColor }}" style="width: {{ $row['pct'] }}%"></span>
                    </span>
                </div>
            @endforeach
        </div>
    @elseif ($analysis->verdict === 'review')
        <p class="text-sm text-slate-500">No automatic score was generated for this report &mdash; it needs a human to review the extracted content above.</p>
    @else
        <p class="text-sm text-slate-500">No risk contributors &mdash; this report passed every detection layer cleanly.</p>
    @endif
</div>

{{-- RECOMMENDED ACTIONS --}}
@if (in_array($analysis->verdict, ['suspicious', 'phishing']))
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8">
        <div class="flex items-start gap-4 mb-5">
            <span class="w-10 h-10 rounded-lg bg-orange-500/10 border border-orange-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
            </span>
            <div>
                <h2 class="text-lg font-bold text-white">Recommended Actions</h2>
                <p class="text-sm text-slate-500">Steps to take if you interacted with this website, email, or phone number.</p>
            </div>
        </div>
        <ol class="space-y-3">
            @foreach ([
                'Do not enter usernames, passwords, or payment information on this site.',
                'Do not reply to the email or call back the number if this was a report.',
                'Do not download any files or click links originating from this source.',
                'Report the link to the organisation being impersonated, if applicable.',
                'If you already entered login details, change your password immediately.',
            ] as $i => $action)
                <li class="flex items-start gap-3 text-sm text-slate-300">
                    <span class="w-5 h-5 rounded-full bg-orange-500/20 text-orange-400 text-xs font-medium flex items-center justify-center shrink-0 mt-0.5">{{ $i + 1 }}</span>
                    {{ $action }}
                </li>
            @endforeach
        </ol>
    </div>
@endif

{{-- TECHNICAL INFORMATION --}}
<div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden" x-data="{ open: false }">
    <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left">
        <span class="flex items-center gap-2 text-white font-medium">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
            Technical Information
        </span>
        <span class="flex items-center gap-2">
            <span class="text-xs text-slate-500">Click to expand</span>
            <svg class="w-4 h-4 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </span>
    </button>
    <div x-show="open" x-collapse class="px-6 pb-6 border-t border-slate-800 pt-4">
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-xs text-slate-500 mb-1">SCAN REFERENCE ID</dt>
                <dd class="text-slate-300">{{ $refId }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">REPORT TYPE</dt>
                <dd class="text-slate-300 uppercase">{{ $report->type }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">VERDICT</dt>
                <dd class="text-slate-300 uppercase">{{ $analysis->verdict }}</dd>
            </div>
                       <div>
                <dt class="text-xs text-slate-500 mb-1">RISK SCORE</dt>
                <dd class="text-slate-300">{{ $analysis->risk_score }} / 100</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">DOMAIN AGE</dt>
                <dd class="text-slate-300">{{ $analysis->domain_age_days !== null ? $analysis->domain_age_days . ' days' : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">IP ADDRESS</dt>
                <dd class="text-slate-300 font-mono">{{ $analysis->ip_address ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">IP REPUTATION</dt>
                <dd class="text-slate-300">{{ $analysis->ip_reputation ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">SCANNED BY</dt>
                <dd class="text-slate-300">{{ $report->user?->name ?? 'Guest (unregistered)' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500 mb-1">SCAN DURATION</dt>
                <dd class="text-slate-300">{{ $analysis->duration_ms !== null ? round($analysis->duration_ms / 1000, 2) . 's' : 'Not recorded' }}</dd>
            </div>
        </dl>
        @if (!empty($analysis->redirect_chain) && count($analysis->redirect_chain) > 1)
            <p class="text-xs text-slate-500 mt-5 mb-2">REDIRECT CHAIN</p>
            <div class="bg-slate-950/60 border border-slate-800 rounded-lg p-4 space-y-2">
                @foreach ($analysis->redirect_chain as $i => $hop)
                    <div class="flex items-start gap-2 text-xs font-mono">
                        <span class="text-slate-600 shrink-0">{{ $i + 1 }}.</span>
                        <span class="{{ $i === count($analysis->redirect_chain) - 1 ? 'text-orange-400' : 'text-slate-400' }} break-all">{{ $hop }}</span>
                        @if ($i === count($analysis->redirect_chain) - 1 && count($analysis->redirect_chain) > 1)
                            <span class="text-[10px] text-orange-500 shrink-0">(final)</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <p class="text-xs text-slate-500 mt-5 mb-2">RAW CHECK RESULTS</p>
<div class="bg-slate-950/60 border border-slate-800 rounded-lg p-4 space-y-2">
    @foreach (collect($analysis->flags ?? [])->filter(fn ($c) => is_array($c)) as $check)
                <p class="text-xs text-slate-400 font-mono">
                    <span class="text-slate-200">{{ $check['name'] }}:</span>
                    {{ $check['status'] }} ({{ $check['points'] ?? 0 }} pts) &mdash; {{ $check['message'] }}
                </p>
            @endforeach
        </div>
    </div>
</div>