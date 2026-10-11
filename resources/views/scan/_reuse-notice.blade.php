{{-- Shown once after a result is reused from a recent scan of the same link. --}}
@if (session('info'))
    <div class="r-in mb-6 mx-auto max-w-5xl flex items-start gap-2.5 rounded-xl border border-sky-300/20 bg-sky-400/[0.07] px-4 py-3 text-sm text-sky-100" style="--d:.12s" role="status">
        <svg class="w-4 h-4 mt-0.5 shrink-0 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
        <span>{{ session('info') }}</span>
    </div>
@endif