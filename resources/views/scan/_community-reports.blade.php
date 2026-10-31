{{-- COMMUNITY REPORTS
     What other PhishCore scans found for the same website, sender domain, phone number or file, the people who
     reported a number as a scam, and how far the PhishCore team has taken it. Counts only, never who. Display
     only: it does not change the score. The team's progress is left off the public shared page, the same as on
     the Public Reports page. Reads $report and $shared from the including view. --}}
@php
    $community = \App\Services\CommunityReports::for($report);
    $isShared = $shared ?? false;
@endphp

@if ($community['subject'] !== null)
    @php
        $seen = $community['first_seen'];
        $seenLabel = $seen === null ? '—' : ($seen->isToday() ? 'Today' : $seen->format('j M Y'));
        $hasHistory = $community['others'] > 0 || $community['number_reports'] > 0;
        $showTeam = ! $isShared && $community['team_label'] !== null;
        $teamTone = in_array($community['team_status'], ['takedown_requested', 'active'], true)
            ? 'text-orange-300 bg-orange-400/10 border-orange-400/30'
            : 'text-emerald-300 bg-emerald-400/10 border-emerald-400/30';
    @endphp
    <section class="r-card r-in p-5 sm:p-6 mb-6" style="--d:.28s" aria-labelledby="community-reports-title">
        <div class="flex items-start gap-3 mb-4">
            <span class="r-tile" style="--c: 56,189,248" aria-hidden="true">
                <svg class="w-5 h-5 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198v.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="community-reports-title" class="text-white font-semibold text-base sm:text-lg">Community reports</h2>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5 leading-relaxed">What other PhishCore users found when they checked {{ $community['subject_label'] }}.</p>
            </div>
        </div>

        @if ($hasHistory)
            <div class="grid grid-cols-2 {{ $report->type === 'phone' ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }} gap-2.5">
                <div class="r-well px-3.5 py-3">
                    <p class="text-[11px] uppercase tracking-wider text-slate-400">Checked by</p>
                    <p class="text-white font-bold text-lg mt-0.5">{{ $community['people'] }} <span class="text-sm font-medium text-slate-400">{{ $community['people'] === 1 ? 'other person' : 'other people' }}</span></p>
                </div>
                <div class="r-well px-3.5 py-3">
                    <p class="text-[11px] uppercase tracking-wider text-slate-400">Flagged</p>
                    <p class="font-bold text-lg mt-0.5 {{ $community['flagged'] > 0 ? 'text-orange-300' : 'text-white' }}">{{ $community['flagged'] }} <span class="text-sm font-medium text-slate-400">of {{ $community['others'] }} {{ $community['others'] === 1 ? 'scan' : 'scans' }}</span></p>
                </div>
                @if ($report->type === 'phone')
                    <div class="r-well px-3.5 py-3">
                        <p class="text-[11px] uppercase tracking-wider text-slate-400">Reported as a scam</p>
                        <p class="font-bold text-lg mt-0.5 {{ $community['number_reports'] > 0 ? 'text-red-300' : 'text-white' }}">{{ $community['number_reports'] }} <span class="text-sm font-medium text-slate-400">{{ $community['number_reports'] === 1 ? 'person' : 'people' }}</span></p>
                    </div>
                @endif
                <div class="r-well px-3.5 py-3 {{ $report->type === 'phone' ? '' : 'col-span-2 sm:col-span-1' }}">
                    <p class="text-[11px] uppercase tracking-wider text-slate-400">First seen on PhishCore</p>
                    <p class="text-white font-bold text-lg mt-0.5">{{ $seenLabel }}</p>
                </div>
            </div>

            @if ($community['others'] > 0)
                <p class="text-xs text-slate-400 mt-3">
                    Earlier results:
                    <span class="text-red-300 font-semibold">{{ $community['phishing'] }} phishing</span> ·
                    <span class="text-orange-300 font-semibold">{{ $community['suspicious'] }} suspicious</span> ·
                    <span class="text-emerald-300 font-semibold">{{ $community['clean'] }} no threats found</span>
                    @if ($community['top_category'])
                        · Most often reported as: <span class="text-slate-200 font-semibold">{{ $community['top_category'] }}</span>
                    @endif
                </p>
            @elseif ($community['top_category'])
                <p class="text-xs text-slate-400 mt-3">Most often reported as: <span class="text-slate-200 font-semibold">{{ $community['top_category'] }}</span></p>
            @endif

            @if ($community['yours_before'])
                <p class="text-xs text-slate-400 mt-1.5">You have checked this before too. Your own earlier scans are in the results above but are not counted as other people.</p>
            @endif
        @else
            <div class="r-well px-4 py-3.5">
                <p class="text-sm text-slate-200">You are the first to check {{ $community['subject_label'] }} on PhishCore.</p>
                <p class="text-xs text-slate-400 mt-1">No one else has scanned or reported it yet. A first sighting is not proof either way, so go by the checks below.</p>
            </div>
        @endif

        @if ($showTeam)
            <p class="mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold {{ $teamTone }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                {{ $community['team_label'] }}
            </p>
        @endif

        <p class="text-[11px] text-slate-500 mt-3">Counts only: PhishCore never shows who scanned or reported anything. This box does not change the score.</p>
    </section>
@endif