{{-- LATEST SCAM NEWS: filled once a day by news:fetch, managed on the Scam News page (team only) --}}
@php
    $newsCount = $scamNews->count();
    $fetchedAt = $scamNewsFetchedAt ? \Illuminate\Support\Carbon::parse($scamNewsFetchedAt) : null;
@endphp

<style>
    /* only the visible slide takes clicks and keyboard focus; all slides share one grid cell so the box keeps a steady height */
    .sn-slide { transition: opacity .4s ease; }
    .sn-on { opacity: 1; visibility: visible; }
    .sn-off { opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .4s ease, visibility 0s linear .4s; }

    /* shown behind the picture, and on its own when an article has no picture or the picture fails to load */
    .sn-art { background: radial-gradient(120% 120% at 0% 0%, rgba(56, 189, 248, .28), transparent 60%), radial-gradient(100% 100% at 100% 100%, rgba(99, 102, 241, .3), transparent 60%), linear-gradient(160deg, #0f2147, #0a1630); }

    .sn-dot { position: relative; width: .5rem; height: .5rem; border-radius: 999px; background: rgba(148, 163, 184, .4); transition: width .25s, background-color .25s; }
    .sn-dot::after { content: ""; position: absolute; inset: -.5rem -.25rem; }
    .sn-dot-on { width: 1.4rem; background: #38bdf8; }

    .sn-title { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    .sn-nav { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .25); transition: background-color .15s, color .15s; }
    .sn-nav:hover { background: rgba(255, 255, 255, .08); color: #fff; }

    @media (prefers-reduced-motion: reduce) { .sn-slide, .sn-off, .sn-dot { transition: none; } }
</style>

<section class="d-in d-card overflow-hidden mb-6" style="--d:.24s" aria-label="Latest scam news"
         x-data="{
             i: 0,
             n: {{ $newsCount }},
             paused: false,
             timer: null,
             broken: {},
             reduce: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
             go(k) { this.i = (k + this.n) % this.n; },
             next() { this.go(this.i + 1); },
             prev() { this.go(this.i - 1); },
             init() {
                 if (this.n > 1 && ! this.reduce) {
                     this.timer = setInterval(() => { if (! this.paused && ! document.hidden) { this.next(); } }, 8000);
                 }
             },
             destroy() { clearInterval(this.timer); }
         }"
         @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false">

    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3.5 sm:py-4 border-b border-white/10">
        <div class="flex items-center gap-3 min-w-0">
            <span class="d-tile shrink-0 text-sky-300" style="--c: 56,189,248">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" /></svg>
            </span>
            <div class="min-w-0">
                <p class="text-white font-semibold text-sm">Latest Scam News</p>
                <p class="text-xs text-slate-300 mt-0.5 truncate">Stay one step ahead of the scams making headlines</p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            @if ($fetchedAt)
                <span class="hidden sm:inline text-[11px] text-slate-400">Refreshed {{ $fetchedAt->isToday() ? 'today' : $fetchedAt->format('j M') }}</span>
            @endif
            @if ($newsCount > 1)
                <div class="flex items-center gap-1.5">
                    <button type="button" class="sn-nav" @click="prev()" aria-label="Previous article">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button type="button" class="sn-nav" @click="next()" aria-label="Next article">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if ($newsCount > 0)
        <div class="grid">
            @foreach ($scamNews as $k => $article)
                <article class="sn-slide {{ $k === 0 ? 'sn-on' : 'sn-off' }} col-start-1 row-start-1 flex flex-col sm:flex-row min-w-0"
                         :class="{ 'sn-on': i === {{ $k }}, 'sn-off': i !== {{ $k }} }">
                    <div class="sn-art relative h-36 sm:h-auto sm:w-2/5 sm:min-h-[11rem] shrink-0 overflow-hidden">
                        <div class="absolute inset-0 grid place-items-center text-sky-300/70" aria-hidden="true">
                            <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        </div>
                        @if ($article->image_url)
                            <img src="{{ $article->image_url }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                                 class="absolute inset-0 w-full h-full object-cover"
                                 x-show="! broken[{{ $k }}]" x-on:error="broken[{{ $k }}] = true">
                        @endif
                    </div>

                    <div class="flex-1 min-w-0 p-5 sm:p-6 flex flex-col justify-center">
                        <p class="flex items-center gap-2 text-[11px] font-semibold tracking-wide uppercase text-sky-300">
                            <span class="truncate">{{ $article->source }}</span>
                            <span class="text-slate-500" aria-hidden="true">·</span>
                            <time datetime="{{ $article->published_at->toDateString() }}" class="text-slate-300 normal-case font-medium whitespace-nowrap">{{ $article->published_at->format('j M Y') }}</time>
                        </p>
                        <h3 class="sn-title mt-2 text-lg sm:text-xl font-bold leading-snug text-white">
                            <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="hover:text-sky-200 transition-colors">{{ $article->title }}</a>
                        </h3>
                        <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer" class="d-link mt-4 inline-flex items-center gap-1 self-start text-sm font-medium text-sky-300 hover:text-sky-200">
                            Read article <span class="d-arrow inline-block" aria-hidden="true">→</span><span class="sr-only">(opens in a new tab)</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($newsCount > 1)
            <div class="flex items-center justify-center gap-1.5 pt-1 pb-4" role="group" aria-label="Choose an article">
                @foreach ($scamNews as $k => $article)
                    <button type="button" class="sn-dot {{ $k === 0 ? 'sn-dot-on' : '' }}" :class="{ 'sn-dot-on': i === {{ $k }} }"
                            @click="go({{ $k }})" aria-label="Show article {{ $k + 1 }} of {{ $newsCount }}"></button>
                @endforeach
            </div>
        @endif
    @else
        <div class="flex items-center gap-4 px-5 sm:px-6 py-8">
            <span class="d-tile shrink-0 text-sky-300" style="--c: 56,189,248">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" /></svg>
            </span>
            <div>
                <p class="text-sm text-slate-100">No scam news yet.</p>
                <p class="text-xs text-slate-300 mt-0.5">New stories appear here once the daily update has run.</p>
            </div>
        </div>
    @endif
</section>