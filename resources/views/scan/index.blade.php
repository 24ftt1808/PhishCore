<x-guest-layout>

    <div class="max-w-2xl mx-auto py-16 px-6">
        <div class="text-center mb-10">
            <div class="w-14 h-14 mx-auto rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Scan a Website</h1>
            <p class="text-slate-400">Analyse a website URL for phishing threats and suspicious activity.</p>
        </div>

        <form method="POST" action="{{ route('scan.store') }}" class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
            @csrf

            <label for="url" class="block text-xs tracking-wide text-slate-400 mb-2">WEBSITE URL</label>
            <div class="flex gap-3">
                <input id="url" type="text" name="url" value="{{ old('url') }}" required autofocus
                       placeholder="Enter or paste a website URL, e.g. https://example.com"
                       class="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                <button type="submit"
                        class="px-6 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition">
                    Scan URL
                </button>
            </div>
            @error('url')
                <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
            @enderror

            <p class="mt-3 text-xs text-slate-500">
                Include the complete URL starting with <span class="text-slate-400">http://</span> or <span class="text-slate-400">https://</span> for accurate results.
            </p>
        </form>

        <div class="flex flex-wrap gap-2 mt-6 justify-center text-xs text-slate-500">
            <span class="px-3 py-1 rounded-full bg-slate-900 border border-slate-800">URL structure</span>
            <span class="px-3 py-1 rounded-full bg-slate-900 border border-slate-800">Domain age</span>
            <span class="px-3 py-1 rounded-full bg-slate-900 border border-slate-800">Suspicious keywords</span>
        </div>
    </div>

</x-guest-layout>