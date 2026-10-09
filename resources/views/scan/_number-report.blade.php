@if ($report->type === 'phone' && filled($report->phone_number) && auth()->check() && $report->canBeViewedBy(auth()->user()))
    @php
        $myReport = \App\Models\NumberReport::where('user_id', auth()->id())
            ->where('phone', \App\Models\NumberReport::key((string) $report->phone_number))
            ->first();
    @endphp
    <section class="r-card r-in mt-6 mb-6 p-5 sm:p-6" style="--d:.25s">
        <div class="mb-4">
            <h2 class="text-white font-semibold text-base sm:text-lg">Is this number a scam?</h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl leading-relaxed">
                If you got a scam call or message from this number, report it. When two or more people report the same number, PhishCore warns everyone who checks it and lists it under "Most reported phone numbers". Your name is never shown.
            </p>
        </div>

        @if (session('number_reported'))
            <p class="text-xs text-emerald-300 mb-3" role="status">{{ session('number_reported') }}</p>
        @endif

        @error('category')
            <p class="text-xs text-red-300 mb-3" role="alert">Please pick what kind of scam it was.</p>
        @enderror

        @if ($myReport)
            <p class="text-xs text-slate-300 mb-3">
                You reported this number as: <span class="font-semibold text-white">{{ \App\Models\NumberReport::CATEGORIES[$myReport->category] ?? 'Other scam' }}</span>. You can change it below.
            </p>
        @endif

        <form method="POST" action="{{ route('number-report.store', $report) }}" class="flex flex-col sm:flex-row gap-2.5">
            @csrf
            <select name="category" required aria-label="What kind of scam was it?"
                    class="r-well flex-1 min-w-0 px-3.5 py-2.5 text-sm text-slate-100 focus:outline-none focus:border-sky-300/50"
                    style="color-scheme: dark; background-color: rgba(8, 15, 32, .5); padding-right: 2.5rem; background-repeat: no-repeat; background-position: right .8rem center; background-size: 1.1rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>')">
                <option value="" disabled @selected(! $myReport)>What kind of scam was it?</option>
                @foreach (\App\Models\NumberReport::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" @selected($myReport && $myReport->category === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="r-btn r-btn-main !py-2.5 justify-center w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                {{ $myReport ? 'Update my report' : 'Report as scam' }}
            </button>
        </form>
    </section>
@endif