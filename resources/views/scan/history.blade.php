@php
    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'label' => 'PHISHING'],
        'review' => ['bg' => 'bg-sky-500/10', 'text' => 'text-sky-400', 'label' => 'REVIEW'],
    ];
    $scoreBarColor = [
        'clean' => 'bg-emerald-400',
        'suspicious' => 'bg-orange-400',
        'phishing' => 'bg-red-400',
        'review' => 'bg-sky-400',
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
            <h1 class="text-2xl font-bold text-white mb-1">Scan History</h1>
            <p class="text-slate-400 text-sm">Review and manage all reports previously analysed by PhishCore.</p>
        </div>
        <span class="px-4 py-2.5 rounded-lg border border-slate-800 text-slate-600 text-sm cursor-not-allowed" title="Coming soon">
            ⬇ Export History
        </span>
    </div>

           <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-sky-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">TOTAL REPORTS</p>
            <p class="relative text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-sky-400 shadow-[0_0_14px_3px_rgba(56,189,248,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-emerald-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">SAFE REPORTS</p>
            <p class="relative text-3xl font-bold text-emerald-400">{{ $stats['safe'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-emerald-400 shadow-[0_0_14px_3px_rgba(52,211,153,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-orange-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">SUSPICIOUS REPORTS</p>
            <p class="relative text-3xl font-bold text-orange-400">{{ $stats['suspicious'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-orange-400 shadow-[0_0_14px_3px_rgba(251,146,60,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-red-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">PHISHING REPORTS</p>
            <p class="relative text-3xl font-bold text-red-400">{{ $stats['phishing'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-red-400 shadow-[0_0_14px_3px_rgba(248,113,113,0.5)]"></span>
        </div>
    </div>

    
    <form method="GET" action="{{ route('scan.history') }}" class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5 mb-6">
        <div class="flex flex-wrap gap-3 mb-4">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Search by URL, email, phone number, or scan reference ID"
                   class="flex-1 min-w-[240px] bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">

            @php $currentStatus = $filters['status'] ?? 'all'; @endphp
            <div class="flex gap-1 bg-slate-950 border border-slate-800 rounded-lg p-1">
                @foreach (['all' => 'All Results', 'safe' => 'Safe', 'suspicious' => 'Suspicious', 'phishing' => 'Phishing'] as $key => $label)
                    <button type="submit" name="status" value="{{ $key }}"
                            class="px-3 py-1.5 rounded-md text-xs font-medium {{ $currentStatus === $key ? 'bg-sky-500/20 text-sky-400' : 'text-slate-400 hover:text-slate-200' }} transition">
                        {{ strtoupper($label) }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
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
            <div>
                <label class="block text-[10px] tracking-wide text-slate-500 mb-1">MIN SCORE</label>
                <input type="number" name="min_score" value="{{ $filters['min_score'] ?? '' }}" min="0" max="100" placeholder="0"
                       class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
            </div>
            <div>
                <label class="block text-[10px] tracking-wide text-slate-500 mb-1">MAX SCORE</label>
                <input type="number" name="max_score" value="{{ $filters['max_score'] ?? '' }}" min="0" max="100" placeholder="100"
                       class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition">
                    Apply Filters
                </button>
                <a href="{{ route('scan.history') }}" class="px-3 py-2 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <div class="flex items-center justify-between mb-3 text-sm text-slate-500">
        <span>Showing {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }} total reports</span>
        <form method="GET" action="{{ route('scan.history') }}" class="flex items-center gap-2">
            @foreach ($filters as $key => $value)
                @if ($key !== 'rows' && $value !== null)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
                       <span>ROWS</span>
            <div class="relative">
                             <select name="rows" onchange="this.form.submit()"
                        class="appearance-none bg-none bg-slate-900 border border-slate-800 rounded-lg pl-3 pr-7 py-1.5 text-slate-200 text-sm focus:outline-none focus:border-sky-500 transition cursor-pointer">
                    @foreach ([8, 16, 32] as $n)
                        <option value="{{ $n }}" {{ ($filters['rows'] ?? 8) == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
                <svg class="w-3.5 h-3.5 text-slate-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>
    </div>

      <div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
            <thead>
                <tr class="border-b border-slate-800 text-left text-xs tracking-wide text-slate-500">
                    <th class="px-5 py-3 w-8"><input type="checkbox" class="rounded border-slate-700 bg-slate-900"></th>
                    <th class="px-5 py-3">REPORTED ITEM</th>
                    <th class="px-5 py-3">SCAN RESULT</th>
                    <th class="px-5 py-3">RISK SCORE</th>
                    <th class="px-5 py-3">DATE &amp; TIME</th>
                    <th class="px-5 py-3">SCANNED BY</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($reports as $report)
                    @php
                        $verdict = $report->analyses->first()->verdict ?? 'clean';
                        $score = $report->analyses->first()->risk_score ?? 0;
                        $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                        $barColor = $scoreBarColor[$verdict] ?? $scoreBarColor['clean'];
                        $refId = 'PG-' . $report->created_at->format('Y-md') . '-' . strtoupper(substr(md5($report->id), 0, 5));
                        $itemLabel = match ($report->type) {
                            'email' => $report->sender_email,
                            'phone' => $report->phone_number,
                            'screenshot' => 'Uploaded screenshot',
                            default => $report->url,
                        };
                        $icon = $typeIcons[$report->type] ?? '🔗';
                    @endphp
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="px-5 py-4"><input type="checkbox" class="rounded border-slate-700 bg-slate-900"></td>
                        <td class="px-5 py-4">
                            <p class="text-slate-200 truncate max-w-[220px] flex items-center gap-1.5">
                                <span class="shrink-0">{{ $icon }}</span> {{ $itemLabel }}
                            </p>
                            <p class="text-xs text-slate-600">{{ $refId }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-medium {{ $badge['text'] }}">{{ $score }}</span>
                                <span class="w-16 h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                    <span class="block h-full {{ $barColor }}" style="width: {{ $score }}%"></span>
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-400">{{ $report->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-4">
                            <span class="flex items-center gap-2 text-slate-400">
                                <span class="w-6 h-6 rounded-full bg-sky-500 text-white text-[10px] font-bold flex items-center justify-center">
                                    {{ collect(explode(' ', auth()->user()->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') }}
                                </span>
                                {{ auth()->user()->name }}
                            </span>
                        </td>
                                               <td class="px-5 py-4 text-right">
                            <a href="{{ route('scan.show', $report) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-sky-500/30 bg-sky-500/10 text-sky-400 text-xs font-medium hover:bg-sky-500/20 transition whitespace-nowrap">
                                View Details →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                            No reports found. Try adjusting your filters, or <a href="{{ route('scan.index') }}" class="text-sky-400">submit your first scan</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $reports->links() }}
    </div>

</x-layouts.dashboard>