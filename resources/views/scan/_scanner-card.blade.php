@php
    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'border' => 'border-emerald-500/20', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'border' => 'border-orange-500/20', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'border' => 'border-red-500/20', 'label' => 'PHISHING'],
    ];
@endphp

<div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden mb-8" x-data="{ url: '' }">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                </svg>
            </span>
            <div>
                <p class="text-white font-medium">PhishCore URL Scanner</p>
                <p class="text-xs text-slate-500">AI-POWERED · 4 DETECTION CHECKS · ~2S RESULTS</p>
            </div>
        </div>
        <span class="flex items-center gap-2 text-xs text-emerald-400">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Engine online
        </span>
    </div>

    <div class="p-6">
        <label class="block text-xs tracking-wide text-slate-400 mb-2">WEBSITE URL</label>
        <form method="POST" action="{{ route('scan.store') }}" class="flex gap-3 mb-2">
            @csrf
            <input type="text" name="url" x-model="url" required
                   placeholder="Enter or paste a website URL, for example https://example.com"
                   class="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
            <button type="button"
                    @click="navigator.clipboard.readText().then(text => url = text)"
                    class="px-4 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition whitespace-nowrap">
                📋 Paste
            </button>
            <button type="submit"
                    class="px-6 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition whitespace-nowrap">
                🔍 Scan URL
            </button>
        </form>
        @error('url')
            <p class="mb-3 text-xs text-red-400">{{ $message }}</p>
        @enderror
        <p class="text-xs text-slate-500 mb-4">
            Include the complete URL starting with <span class="text-slate-400">http://</span> or <span class="text-slate-400">https://</span> for accurate results.
        </p>

        <div class="flex flex-wrap gap-2 pt-4 border-t border-slate-800">
            <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400 flex items-center gap-1">✓ URL structure</span>
            <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400 flex items-center gap-1">✓ SSL certificate</span>
            <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400 flex items-center gap-1">✓ Domain age</span>
            <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400 flex items-center gap-1">✓ Blacklists</span>
        </div>
    </div>
</div>

<h2 class="text-xl font-bold text-white mb-1">How PhishCore Checks Websites</h2>
<p class="text-sm text-slate-500 mb-6">Every scan runs through four independent detection layers simultaneously.</p>

<div class="grid md:grid-cols-2 gap-4 mb-10">
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">SSL Certificate Validation</h3>
        <p class="text-sm text-slate-400">Verifies whether the site uses a valid, trusted HTTPS certificate and checks for a name mismatch or expiry.</p>
    </div>
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">Domain Age &amp; Registration</h3>
        <p class="text-sm text-slate-400">New domains registered within 30 days are a strong phishing signal and are flagged automatically.</p>
    </div>
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">URL Structure Analysis</h3>
        <p class="text-sm text-slate-400">Detects IP-based addresses, lookalike brand names, excessive hyphens, and other suspicious URL patterns.</p>
    </div>
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">Blacklist Database Check</h3>
        <p class="text-sm text-slate-400">Cross-references the URL against Google Safe Browsing's live threat database.</p>
    </div>
</div>

@if (count($recentScans ?? []) > 0)
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-white">Recently Scanned</h2>
        <span class="text-xs text-slate-500">Your three most recent URL scans</span>
    </div>
    <div class="space-y-3">
        @foreach ($recentScans as $scan)
            @php
                $verdict = $scan->analyses->first()->verdict ?? 'clean';
                $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
            @endphp
            <a href="{{ route('scan.show', $scan) }}"
               class="flex items-center justify-between bg-slate-900/50 border border-slate-800 rounded-xl px-5 py-4 hover:border-sky-500/40 transition">
                <span class="text-sm text-slate-300 truncate max-w-md">{{ $scan->url }}</span>
                <span class="flex items-center gap-3 shrink-0">
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} border {{ $badge['border'] }}">{{ $badge['label'] }}</span>
                    <span class="text-xs text-slate-500">{{ $scan->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @endforeach
    </div>
@endif