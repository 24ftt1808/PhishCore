@if ($report->type === 'url' && $report->status === 'completed' && $report->canBeViewedBy(auth()->user()))
    @php $shareUrl = $report->share_token ? route('scan.shared', $report->share_token) : null; @endphp
    <section class="r-card r-in mt-6 mb-6 p-5 sm:p-6" style="--d:.25s">
        <div class="mb-4">
            <h2 class="text-white font-semibold text-base sm:text-lg">Share this result</h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Anyone with the link can see the verdict, score and checks. It never shows who scanned it, and you can turn the link off at any time.
            </p>
        </div>

        @if (session('shared'))
            <p class="text-xs text-emerald-300 mb-3" role="status">{{ session('shared') }}</p>
        @endif

        @if ($shareUrl)
            <div class="flex flex-col md:flex-row gap-2.5">
                <input type="text" readonly value="{{ $shareUrl }}" onfocus="this.select()"
                       class="r-well flex-1 min-w-0 px-3.5 py-2.5 text-xs sm:text-sm font-mono truncate text-slate-200 bg-transparent focus:outline-none focus:border-sky-300/50"
                       aria-label="Share link">
                <div class="grid grid-cols-2 md:flex gap-2.5">
                    <button type="button" class="r-btn r-btn-main !py-2.5 justify-center" data-copy="{{ $shareUrl }}">Copy link</button>
                    <form method="POST" action="{{ route('scan.share.destroy', $report) }}" class="contents md:block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="r-btn r-btn-ghost !py-2.5 justify-center w-full">Turn off</button>
                    </form>
                </div>
            </div>
            <script>
                document.querySelectorAll('[data-copy]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var text = btn.dataset.copy, done = function () {
                            var old = btn.textContent; btn.textContent = 'Copied!';
                            setTimeout(function () { btn.textContent = old; }, 1600);
                        };
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).then(done);
                        } else {
                            var input = btn.closest('section').querySelector('input');
                            input.select(); document.execCommand('copy'); done();
                        }
                    });
                });
            </script>
        @elseif ($report->isShareable())
            <form method="POST" action="{{ route('scan.share.store', $report) }}">
                @csrf
                <button type="submit" class="r-btn r-btn-main justify-center w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                    Create share link
                </button>
            </form>
        @endif
    </section>
@endif