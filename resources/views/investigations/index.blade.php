@php
    $statusBadge = [
        'active' => ['bg' => 'bg-sky-500/10', 'text' => 'text-sky-400', 'label' => 'ACTIVE'],
        'completed' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'label' => 'COMPLETED'],
        'takedown_requested' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'label' => 'TAKEDOWN REQUESTED'],
        'takedown_confirmed' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'label' => 'TAKEDOWN CONFIRMED'],
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
            <h1 class="text-2xl font-bold text-white mb-1">Investigations</h1>
            <p class="text-slate-400 text-sm">Track and manage takedown progress across all reported threats.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-violet-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">TOTAL CASES</p>
            <p class="relative text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-violet-400 shadow-[0_0_14px_3px_rgba(167,139,250,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-sky-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">ACTIVE</p>
            <p class="relative text-3xl font-bold text-sky-400">{{ $stats['active'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-sky-400 shadow-[0_0_14px_3px_rgba(56,189,248,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-orange-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">TAKEDOWN REQUESTED</p>
            <p class="relative text-3xl font-bold text-orange-400">{{ $stats['takedown_requested'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-orange-400 shadow-[0_0_14px_3px_rgba(251,146,60,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-emerald-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">TAKEDOWN CONFIRMED</p>
            <p class="relative text-3xl font-bold text-emerald-400">{{ $stats['takedown_confirmed'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-emerald-400 shadow-[0_0_14px_3px_rgba(52,211,153,0.5)]"></span>
        </div>
        <div class="relative bg-slate-900/50 border border-slate-800 rounded-xl p-5 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-slate-500/20 blur-2xl"></div>
            <p class="relative text-xs tracking-wide text-slate-500 mb-3">COMPLETED</p>
            <p class="relative text-3xl font-bold text-slate-300">{{ $stats['completed'] }}</p>
            <span class="absolute right-5 top-1/2 -translate-y-1/2 w-1 h-10 rounded-full bg-slate-400 shadow-[0_0_14px_3px_rgba(148,163,184,0.5)]"></span>
        </div>
    </div>

    <form method="GET" action="{{ route('investigations.index') }}" class="bg-slate-900/50 border border-slate-800 rounded-2xl p-5 mb-6">
        <div class="flex flex-wrap gap-3 mb-4">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Search by URL, email, or phone number"
                   class="flex-1 min-w-[240px] bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 transition">

            @php $currentStatus = $filters['status'] ?? 'all'; @endphp
            <div class="flex flex-wrap gap-1 bg-slate-950 border border-slate-800 rounded-lg p-1">
                @foreach (['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'takedown_requested' => 'Requested', 'takedown_confirmed' => 'Confirmed'] as $key => $label)
                    <button type="submit" name="status" value="{{ $key }}"
                            class="px-3 py-1.5 rounded-md text-xs font-medium {{ $currentStatus === $key ? 'bg-violet-500/20 text-violet-400' : 'text-slate-400 hover:text-slate-200' }} transition">
                        {{ strtoupper($label) }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="py-2 px-5 rounded-lg bg-gradient-to-r from-violet-500 to-purple-600 text-white text-sm font-medium hover:opacity-90 transition">
                Apply Filters
            </button>
            <a href="{{ route('investigations.index') }}" class="px-3 py-2 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition">
                Reset
            </a>
        </div>
    </form>

    <div class="flex items-center justify-between mb-3 text-sm text-slate-500">
        <span>Showing {{ $investigations->firstItem() ?? 0 }}–{{ $investigations->lastItem() ?? 0 }} of {{ $investigations->total() }} total cases</span>
        <form method="GET" action="{{ route('investigations.index') }}" class="flex items-center gap-2">
            @foreach ($filters as $key => $value)
                @if ($key !== 'rows' && $value !== null)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <span>ROWS</span>
            <div class="relative">
                <select name="rows" onchange="this.form.submit()"
                        class="appearance-none bg-none bg-slate-900 border border-slate-800 rounded-lg pl-3 pr-7 py-1.5 text-slate-200 text-sm focus:outline-none focus:border-violet-500 transition cursor-pointer">
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
                    <th class="px-5 py-3">REPORTED ITEM</th>
                    <th class="px-5 py-3">STATUS</th>
                    <th class="px-5 py-3">ASSIGNED TO</th>
                    <th class="px-5 py-3">OPENED</th>
                    <th class="px-5 py-3">RESOLVED</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($investigations as $investigation)
                    @php
                        $report = $investigation->report;
                        $badge = $statusBadge[$investigation->status] ?? $statusBadge['active'];
                        $itemLabel = match ($report->type) {
                            'email' => $report->sender_email,
                            'phone' => $report->phone_number,
                            'screenshot' => 'Uploaded screenshot',
                            default => $report->url,
                        };
                        $icon = $typeIcons[$report->type] ?? '🔗';
                    @endphp
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="px-5 py-4">
                            <p class="text-slate-200 truncate max-w-[220px] flex items-center gap-1.5">
                                <span class="shrink-0">{{ $icon }}</span> {{ $itemLabel }}
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-400">
                            {{ $investigation->assignedUser?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-5 py-4 text-slate-400">{{ $investigation->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-4 text-slate-400">
                            {{ $investigation->resolved_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('scan.show', $report) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-violet-500/30 bg-violet-500/10 text-violet-400 text-xs font-medium hover:bg-violet-500/20 transition whitespace-nowrap">
                                View Case →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                            No investigations found. Try adjusting your filters, or open one from a scan result page.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $investigations->links() }}
    </div>

</x-layouts.dashboard>