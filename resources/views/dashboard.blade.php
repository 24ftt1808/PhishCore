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

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">TOTAL URLS<br>SCANNED</p>
                <span class="w-8 h-8 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">SAFE<br>WEBSITES</p>
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-emerald-400">{{ $stats['safe'] }}</p>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">SUSPICIOUS<br>WEBSITES</p>
                <span class="w-8 h-8 rounded-lg bg-orange-500/10 border border-orange-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-orange-400">{{ $stats['suspicious'] }}</p>
        </div>
        <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
            <div class="flex items-start justify-between mb-3">
                <p class="text-xs tracking-wide text-slate-500">PHISHING<br>WEBSITES</p>
                <span class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-red-400">{{ $stats['phishing'] }}</p>
        </div>
    </div>

    <div class="bg-slate-900/50 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <p class="text-sky-400 font-medium flex items-center gap-2">🔍 Scan a Website</p>
            <span class="text-[10px] tracking-wide text-slate-600">POWERED BY PHISHCORE ENGINE</span>
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-400 mb-6">
                Each scan checks the URL structure, SSL certificate validity, domain registration age, and known phishing indicators across multiple threat databases.
            </p>
            <form method="POST" action="{{ route('scan.store') }}" class="flex gap-3 mb-4">
                @csrf
                <input type="text" name="url" required placeholder="Enter or paste a website URL"
                       class="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                <button type="submit" class="px-6 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition whitespace-nowrap">
                    🔍 Scan URL
                </button>
            </form>
            @error('url')
                <p class="mb-4 text-xs text-red-400">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap gap-2">
                <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400">URL structure</span>
                <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400">Domain age analysis</span>
                <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400">HTTPS validation</span>
                <span class="text-xs px-3 py-1.5 rounded-full bg-slate-950 border border-slate-800 text-slate-400">Blacklist verification</span>
            </div>
        </div>
    </div>

</x-layouts.dashboard>