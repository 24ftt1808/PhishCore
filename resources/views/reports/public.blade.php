@php
    $verdictBadge = [
        'suspicious' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'label' => 'PHISHING'],
    ];
@endphp

<x-layouts.guest-landing>

    <section class="max-w-6xl mx-auto px-6 py-16">

        <div class="mb-8">
            <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> Community Transparency Feed
            </span>
            <h1 class="text-3xl font-bold text-white mb-2">Public Threat Reports</h1>
            <p class="text-slate-400 text-sm max-w-2xl">Confirmed suspicious and phishing submissions across the PhishCore community. Safe results are not shown here.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-sky-500/20 blur-2xl"></div>
                <p class="relative text-xs tracking-wide text-slate-500 mb-3">TOTAL FLAGGED</p>
                <p class="relative text-3xl font-bold text-sky-400">{{ $stats['suspicious'] + $stats['phishing'] }}</p>
                <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-sky-400 shadow-[0_0_14px_3px_rgba(56,189,248,0.5)]"></span>
            </div>
            <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-orange-500/20 blur-2xl"></div>
                <p class="relative text-xs tracking-wide text-slate-500 mb-3">SUSPICIOUS</p>
                <p class="relative text-3xl font-bold text-orange-400">{{ $stats['suspicious'] }}</p>
                <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-orange-400 shadow-[0_0_14px_3px_rgba(251,146,60,0.5)]"></span>
            </div>
            <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-red-500/20 blur-2xl"></div>
                <p class="relative text-xs tracking-wide text-slate-500 mb-3">PHISHING</p>
                <p class="relative text-3xl font-bold text-red-400">{{ $stats['phishing'] }}</p>
                <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-red-400 shadow-[0_0_14px_3px_rgba(248,113,113,0.5)]"></span>
            </div>
            <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-emerald-500/20 blur-2xl"></div>
                <p class="relative text-xs tracking-wide text-slate-500 mb-3">MOST RECENT</p>
                <p class="relative text-3xl font-bold text-emerald-400">{{ $stats['latest']?->diffForHumans(short: true) ?? '—' }}</p>
                <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-emerald-400 shadow-[0_0_14px_3px_rgba(52,211,153,0.5)]"></span>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.public') }}" class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5 mb-6">
            <div class="flex flex-wrap gap-3 mb-4">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search by URL, email, or phone number"
                       class="flex-1 min-w-[240px] bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">

                @php $currentStatus = $filters['status'] ?? 'all'; @endphp
                <div class="flex gap-1 bg-slate-950 border border-slate-800 rounded-lg p-1">
                    @foreach (['all' => 'All', 'suspicious' => 'Suspicious', 'phishing' => 'Phishing'] as $key => $label)
                        <button type="submit" name="status" value="{{ $key }}"
                                class="px-3 py-1.5 rounded-md text-xs font-medium {{ $currentStatus === $key ? 'bg-sky-500/20 text-sky-400' : 'text-slate-400 hover:text-slate-200' }} transition">
                            {{ strtoupper($label) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-[10px] tracking-wide text-slate-500 mb-1">DATE FROM</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] tracking-wide text-slate-500 mb-1">DATE TO</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition">
                        Apply Filters
                    </button>
                    <a href="{{ route('reports.public') }}" class="px-3 py-2 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <div class="flex items-center justify-between mb-3 text-sm text-slate-500">
            <span>Showing {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }} total reports</span>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead>
                    <tr class="border-b border-slate-800 text-left text-xs tracking-wide text-slate-500">
                        <th class="px-5 py-3">REPORTED ITEM</th>
                        <th class="px-5 py-3">SCAN RESULT</th>
                        <th class="px-5 py-3">SUBMITTED BY</th>
                        <th class="px-5 py-3">DATE &amp; TIME</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($reports as $report)
                        @php
                            $verdict = $report->analyses->first()->verdict ?? 'suspicious';
                            $badge = $verdictBadge[$verdict] ?? $verdictBadge['suspicious'];
                            $itemLabel = match ($report->type) {
                                'email' => $report->sender_email,
                                'phone' => $report->phone_number,
                                'screenshot' => (function () use ($report) {
    $extraction = collect($report->analyses->first()?->flags ?? [])
        ->firstWhere('name', 'Screenshot Text Extraction');
    if ($extraction && preg_match('/(?:URL(?:\s\(from QR code\))?|Sender|Phone number):\s*([^|]+)/', $extraction['message'], $matches)) {
        return trim($matches[1]);
    }
    return 'Uploaded screenshot';
})(),
                                default => $report->url,
                            };
                            $iconSvg = match ($report->type) {
                                'email' => '<svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>',
                                'phone' => '<svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>',
                                'screenshot' => '<svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>',
                                default => '<svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>',
                            };
                            $firstName = $report->user?->name ? Str::before($report->user->name, ' ') : 'Anonymous';
                        @endphp
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="px-5 py-4">
                                <p title="{{ $itemLabel }}" class="text-slate-200 truncate flex items-center gap-2">
                                    {!! $iconSvg !!} {{ $itemLabel }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-400">{{ $firstName }}</td>
                            <td class="px-5 py-4 text-slate-400">{{ $report->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                No suspicious or phishing reports found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reports->links() }}
        </div>

    </section>

</x-layouts.guest-landing>