@php
    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'label' => 'PHISHING'],
        'review' => ['bg' => 'bg-sky-500/10', 'text' => 'text-sky-400', 'label' => 'REVIEW'],
    ];
    $scoreColor = [
        'clean' => 'text-emerald-400',
        'suspicious' => 'text-orange-400',
        'phishing' => 'text-red-400',
        'review' => 'text-sky-400',
    ];
    $typeIcons = [
        'url' => '🔗',
        'email' => '✉️',
        'phone' => '📱',
        'screenshot' => '🖼️',
    ];
@endphp

<x-layouts.dashboard>

    <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">
                @php $hour = now()->hour; @endphp
                Good {{ $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }} 👋
            </h1>
            <p class="text-slate-400 text-sm">
                Here is your security overview for <span class="text-white">{{ now()->format('l, j F Y') }}</span>.
            </p>
        </div>
        <span class="flex items-center gap-2 text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> System operational
        </span>
    </div>

    {{-- REDESIGNED STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        {{-- TOTAL --}}
        <div class="group relative overflow-hidden bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-sky-500/40 transition-all duration-300">
            <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-sky-500/10 blur-2xl group-hover:bg-sky-500/20 transition"></div>

            <div class="relative">
                <div class="flex items-center gap-3 mb-5">
                    <span class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-300">TOTAL REPORTS</p>
                        <p class="text-[10px] text-slate-600 uppercase tracking-wider">SUBMITTED</p>
                    </div>
                </div>

                <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
                <p class="text-xs text-slate-600 mt-1">URLs, emails, phone numbers &amp; screenshots</p>
            </div>
        </div>

        {{-- SAFE --}}
        <div class="group relative overflow-hidden bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-emerald-500/40 transition-all duration-300">
            <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-emerald-500/10 blur-2xl group-hover:bg-emerald-500/20 transition"></div>

            <div class="relative">
                <div class="flex items-center gap-3 mb-5">
                    <span class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-300">SAFE REPORTS</p>
                        <p class="text-[10px] text-slate-600 uppercase tracking-wider">VERIFIED</p>
                    </div>
                </div>

                <p class="text-3xl font-bold text-emerald-400">{{ $stats['safe'] }}</p>
                <p class="text-xs text-slate-600 mt-1">No threats detected</p>
            </div>
        </div>

        {{-- SUSPICIOUS --}}
        <div class="group relative overflow-hidden bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-orange-500/40 transition-all duration-300">
            <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-orange-500/10 blur-2xl group-hover:bg-orange-500/20 transition"></div>

            <div class="relative">
                <div class="flex items-center gap-3 mb-5">
                    <span class="w-10 h-10 rounded-xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-300">SUSPICIOUS REPORTS</p>
                        <p class="text-[10px] text-slate-600 uppercase tracking-wider">NEEDS REVIEW</p>
                    </div>
                </div>

                <p class="text-3xl font-bold text-orange-400">{{ $stats['suspicious'] }}</p>
                <p class="text-xs text-slate-600 mt-1">Potential threats</p>
            </div>
        </div>

        {{-- PHISHING --}}
        <div class="group relative overflow-hidden bg-slate-900/60 border border-slate-800 rounded-2xl p-5 hover:border-red-500/40 transition-all duration-300">
            <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-red-500/10 blur-2xl group-hover:bg-red-500/20 transition"></div>

            <div class="relative">
                <div class="flex items-center gap-3 mb-5">
                    <span class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs font-medium text-slate-300">PHISHING REPORTS</p>
                        <p class="text-[10px] text-slate-600 uppercase tracking-wider">THREATS DETECTED</p>
                    </div>
                </div>

                <p class="text-3xl font-bold text-red-400">{{ $stats['phishing'] }}</p>
                <p class="text-xs text-slate-600 mt-1">Confirmed malicious</p>
            </div>
        </div>

    </div>

    {{-- QUICK URL CHECK --}}
    <div class="relative bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden mb-8">

        <div class="absolute -left-16 -top-16 w-56 h-56 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -right-16 -bottom-16 w-56 h-56 rounded-full bg-blue-500/5 blur-3xl pointer-events-none"></div>

        <div class="relative flex items-center justify-between px-6 py-5 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                </span>

                <div>
                    <p class="text-white font-semibold text-sm">Quick URL Check</p>
                    <p class="text-xs text-slate-500 mt-0.5">Paste a link for an instant security check</p>
                </div>
            </div>

            <span class="hidden sm:flex items-center gap-2 text-[10px] tracking-wide text-slate-500 bg-slate-950/60 border border-slate-800 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                PHISHCORE ENGINE
            </span>
        </div>

        <div class="relative p-6">
            <p class="text-sm text-slate-400 mb-5 max-w-3xl leading-relaxed">
                Each scan checks the URL structure, SSL certificate validity, domain registration age, and known phishing indicators across multiple threat databases.
            </p>

            <form method="POST" action="{{ route('scan.store') }}" class="flex flex-col sm:flex-row gap-3 mb-4">
                @csrf

                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18" />
                    </svg>

                    <input type="text" name="url" required placeholder="https://example.com"
                           class="w-full bg-slate-950/80 border border-slate-800 rounded-xl pl-11 pr-4 py-3.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500/60 focus:ring-2 focus:ring-sky-500/10 transition">
                </div>

                <button type="submit"
                        class="flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold hover:opacity-90 transition whitespace-nowrap shadow-[0_0_18px_1px_rgba(56,189,248,0.18)]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    Scan URL
                </button>
            </form>

            @error('url')
                <p class="mb-4 text-xs text-red-400">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap gap-x-4 gap-y-2 pt-4 border-t border-slate-800/70">
                <span class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    URL structure
                </span>

                <span class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    Domain age analysis
                </span>

                <span class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    HTTPS validation
                </span>

                <span class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    Blacklist verification
                </span>
            </div>

            <p class="text-xs text-slate-500 mt-4">
                Need to report a sender email, phone number, or screenshot instead?
                <a href="{{ route('scan.index') }}" class="text-sky-400 hover:text-sky-300">Go to the full Scan page →</a>
            </p>
        </div>
    </div>

    {{-- DETECTION OVERVIEW --}}
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-white font-semibold">Detection Overview</h2>
            <span class="text-xs text-slate-500">{{ $weekRangeLabel }}</span>
        </div>
        <p class="text-sm text-slate-500 mb-5">Safe, Suspicious and Phishing results by day</p>

        <canvas id="weekChart" height="90"></canvas>

        <div class="flex items-center justify-center gap-6 mt-4 mb-5 text-xs text-slate-400">
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Safe</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-orange-400"></span> Suspicious</span>
            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-400"></span> Phishing</span>
        </div>

        <div class="grid grid-cols-3 text-center border-t border-slate-800 pt-5">
            <div>
                <p class="text-2xl font-bold text-emerald-400">{{ $weekTotals['safe'] }}</p>
                <p class="text-xs text-slate-500">Safe</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-orange-400">{{ $weekTotals['suspicious'] }}</p>
                <p class="text-xs text-slate-500">Suspicious</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-400">{{ $weekTotals['phishing'] }}</p>
                <p class="text-xs text-slate-500">Phishing</p>
            </div>
        </div>
    </div>

    {{-- RECENT SCANS --}}
    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6 mb-8">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-white font-semibold">Recent Reports</h2>
                <p class="text-sm text-slate-500">Latest {{ $recentScans->count() }} scan results</p>
            </div>
            <a href="{{ route('scan.history') }}" class="text-sky-400 text-sm font-medium hover:text-sky-300 flex items-center gap-1">
                View All Scans →
            </a>
        </div>

              @if ($recentScans->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead>
                    <tr class="text-left text-xs tracking-wide text-slate-500 border-b border-slate-800">
                        <th class="pb-3 pr-4">REPORTED ITEM</th>
                        <th class="pb-3 pr-4">SCAN RESULT</th>
                        <th class="pb-3 pr-4">RISK SCORE</th>
                        <th class="pb-3 pr-4">DATE &amp; TIME</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach ($recentScans as $scan)
                        @php
                            $verdict = $scan->analyses->first()->verdict ?? 'clean';
                            $score = $scan->analyses->first()->risk_score ?? 0;
                            $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                            $itemLabel = match ($scan->type) {
                                'email' => $scan->sender_email,
                                'phone' => $scan->phone_number,
                                'screenshot' => 'Uploaded screenshot',
                                default => $scan->url,
                            };
                            $icon = $typeIcons[$scan->type] ?? '🔗';
                        @endphp
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="flex items-center gap-2 text-slate-300 truncate max-w-[220px]">
                                    <span class="shrink-0">{{ $icon }}</span>
                                    {{ $itemLabel }}
                                </span>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="font-medium {{ $scoreColor[$verdict] ?? 'text-slate-400' }}">{{ $score }}</span>
                            </td>
                            <td class="py-3 pr-4 text-slate-400">{{ $scan->created_at->format('Y-m-d H:i') }}</td>
                            <td class="py-3 text-right">
                                <a href="{{ route('scan.show', $scan) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-sky-500/30 bg-sky-500/10 text-sky-400 text-xs font-medium hover:bg-sky-500/20 transition whitespace-nowrap">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-800">
                <p class="text-xs text-slate-500">Showing {{ $recentScans->count() }} of {{ $totalScansCount }} total records</p>
                <a href="{{ route('scan.history') }}"
                   class="text-sky-400 text-xs font-medium hover:text-sky-300 flex items-center gap-1">
                    View All Scans →
                </a>
            </div>
        @else
            <p class="text-sm text-slate-500 text-center py-6">
                No reports yet — <a href="{{ route('scan.index') }}" class="text-sky-400">submit your first scan</a> to see it here.
            </p>
        @endif
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('scan.index') }}" class="bg-slate-900/50 border border-slate-800 rounded-xl p-5 hover:border-sky-500/40 transition">
            <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
            </span>
            <p class="text-white font-medium text-sm mb-0.5">Run New Scan</p>
            <p class="text-xs text-slate-500">URL, email, phone, or screenshot</p>
        </a>
        <a href="{{ route('scan.history') }}" class="bg-slate-900/50 border border-slate-800 rounded-xl p-5 hover:border-sky-500/40 transition">
            <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <p class="text-white font-medium text-sm mb-0.5">Scan History</p>
            <p class="text-xs text-slate-500">{{ $totalScansCount }} recent records</p>
        </a>
        <a href="{{ route('analytics') }}" class="bg-slate-900/50 border border-slate-800 rounded-xl p-5 hover:border-sky-500/40 transition">
            <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5M3 19.5h18" /></svg>
            </span>
            <p class="text-white font-medium text-sm mb-0.5">Analytics</p>
            <p class="text-xs text-slate-500">Charts &amp; insights</p>
        </a>
                     @if (auth()->user()->is_team_member)
        <a href="{{ route('reports.index') }}" class="bg-slate-900/50 border border-slate-800 rounded-xl p-5 hover:border-sky-500/40 transition">
            <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25" /></svg>
            </span>
            <p class="text-white font-medium text-sm mb-0.5">Reports</p>
            <p class="text-xs text-slate-500">Platform-wide reports</p>
        </a>
        @else
        <a href="{{ route('profile.edit') }}" class="bg-slate-900/50 border border-slate-800 rounded-xl p-5 hover:border-sky-500/40 transition">
            <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </span>
            <p class="text-white font-medium text-sm mb-0.5">Settings</p>
            <p class="text-xs text-slate-500">Manage your account</p>
        </a>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const weekCtx = document.getElementById('weekChart');
        new Chart(weekCtx, {
            type: 'bar',
            data: {
                labels: @json($weekLabels),
                datasets: [
                    { label: 'Safe', data: @json($weekSafe), backgroundColor: '#34d399', borderRadius: 3 },
                    { label: 'Suspicious', data: @json($weekSuspicious), backgroundColor: '#fb923c', borderRadius: 3 },
                    { label: 'Phishing', data: @json($weekPhishing), backgroundColor: '#f87171', borderRadius: 3 },
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b' }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: '#64748b', precision: 0 }, grid: { color: 'rgba(100,116,139,0.1)' } }
                }
            }
        });
    </script>
    @endpush

</x-layouts.dashboard>