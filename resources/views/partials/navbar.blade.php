@php
    $navLinks = [
        ['key' => 'home',          'label' => 'Home',          'href' => route('welcome') . '#home', 'icon' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
        ['key' => 'features',      'label' => 'Features',      'href' => route('welcome') . '#features', 'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z'],
        ['key' => 'how-it-works',  'label' => 'How It Works',  'href' => route('welcome') . '#how-it-works', 'icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0z M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z'],
        ['key' => 'about',         'label' => 'About',         'href' => route('welcome') . '#about', 'icon' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'],
        ['key' => 'contact',       'label' => 'Contact',       'href' => route('welcome') . '#contact', 'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        ['key' => 'public-reports','label' => 'Public Reports','href' => route('reports.public'), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
    ];
@endphp

<div
    x-data="{
        isWelcome: {{ request()->routeIs('welcome') ? 'true' : 'false' }},
        active: '{{ request()->routeIs('reports.public') ? 'public-reports' : 'home' }}',
        hlKey: null,
        mobileOpen: false,
        scrolled: false,
        lock: false,
        lockTimer: null,
        hl: { l: 0, r: 0, lt: 0, rt: 0, le: 'ease', re: 'ease', ready: false },
        sections: ['home', 'features', 'how-it-works', 'about', 'contact'],

        init() {
            let prev = null;
            try {
                prev = sessionStorage.getItem('navPrev');
                sessionStorage.removeItem('navPrev');
            } catch (e) {}

            const hash = location.hash.slice(1);
            if (this.isWelcome && this.sections.includes(hash)) {
                this.active = hash;
                this.lockFor(1200);
            }

            this.$nextTick(() => this.place(prev));
            document.fonts?.ready.then(() => this.resetHl(true));
            this.$watch('active', () => this.resetHl());
            window.addEventListener('resize', () => this.resetHl(true));

            /* throttled to once per frame, so scrolling stays smooth */
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(() => {
                    ticking = false;
                    this.scrolled = window.scrollY > 20;
                    if (this.lock) {
                        clearTimeout(this.lockTimer);
                        this.lockTimer = setTimeout(() => { this.lock = false; this.updateActive(); }, 150);
                        return;
                    }
                    this.updateActive();
                });
            }, { passive: true });

            if (this.isWelcome && !hash) this.updateActive();
        },

        reduced() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },
        linkEl(key) {
            return this.$refs.links?.querySelector(`[data-key=${key}]`);
        },
        place(prev) {
            const target = this.linkEl(this.active);
            const from = prev && prev !== this.active ? this.linkEl(prev) : null;
            if (from) {
                this.moveTo(from, true);
                requestAnimationFrame(() => requestAnimationFrame(() => this.moveTo(target)));
            } else {
                this.moveTo(target, true);
            }
        },
        moveTo(el, snap = false) {
            if (!el || !this.$refs.links) return;
            const cw = this.$refs.links.offsetWidth;
            const l = el.offsetLeft;
            const r = cw - l - el.offsetWidth;
            const dx = l - this.hl.l;
            const still = snap || !this.hl.ready || this.reduced();
            const ease = 'cubic-bezier(0.34, 1.4, 0.64, 1)';

            /* leading edge moves first, trailing edge follows = liquid stretch */
            let lt = 0, rt = 0;
            if (!still) {
                const fast = 0.28, slow = 0.5;
                if (dx > 0)      { lt = slow; rt = fast; }
                else if (dx < 0) { lt = fast; rt = slow; }
                else             { lt = rt = 0.35; }
            }

            this.hlKey = el.dataset.key;
            this.hl = { l, r, lt, rt, le: ease, re: ease, ready: true };
            if (!still && Math.abs(dx) > 4) this.settle(Math.abs(dx));
        },
        settle(d) {
            /* one small squash on landing, nothing more */
            const sx = 1 + Math.min(d / 600, 0.12);
            const sy = 1 - Math.min(d / 2000, 0.05);
            this.$refs.blob?.animate([
                { transform: `scale(${sx}, ${sy})`, offset: 0 },
                { transform: `scale(${1 - (sx - 1) * 0.4}, ${1 + (1 - sy) * 0.8})`, offset: 0.5 },
                { transform: 'scale(1, 1)', offset: 1 }
            ], { duration: 480, easing: 'ease-out' });
        },
        resetHl(snap = false) {
            this.moveTo(this.linkEl(this.active), snap);
        },
        lockFor(ms) {
            this.lock = true;
            clearTimeout(this.lockTimer);
            this.lockTimer = setTimeout(() => { this.lock = false; }, ms);
        },
        go(key, href) {
            /* leaving this page: remember where the pill is so the next page starts there */
            if (href) {
                try {
                    const u = new URL(href, location.href);
                    if (u.pathname !== location.pathname) {
                        sessionStorage.setItem('navPrev', this.hlKey || this.active);
                    }
                } catch (e) {}
            }
            this.active = key;
            this.lockFor(1200);
        },
        updateActive() {
            if (this.lock || !this.isWelcome) return;
            const atBottom = (window.innerHeight + window.scrollY) >= (document.body.scrollHeight - 10);
            if (atBottom) { this.active = this.sections[this.sections.length - 1]; return; }
            let current = 'home';
            for (const id of this.sections) {
                const el = document.getElementById(id);
                if (el && el.getBoundingClientRect().top <= 140) current = id;
            }
            this.active = current;
        }
    }"
    class="pointer-events-none fixed top-4 inset-x-0 mx-auto z-50 w-[calc(100%-2rem)] min-[1180px]:w-max max-w-full"
>
    {{-- PILL --}}
    <nav class="pointer-events-auto nav-glass rounded-full pl-5 pr-2 xl:pl-6 py-2 flex items-center justify-between min-[1180px]:gap-6 xl:gap-12"
         :class="{ 'nav-glass--scrolled': scrolled }">

        <a href="{{ route('welcome') }}#home" @click="go('home', $el.href)" class="flex items-center gap-2.5 shrink-0">
            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-10 h-10 object-contain">
            <span class="flex flex-col items-start leading-tight">
                <span class="block text-left font-bold text-white text-sm">PhishCore</span>
                <span class="hidden xl:block text-left text-[11px] font-medium tracking-wide text-sky-200/90 mt-0.5">Detection Platform</span>
            </span>
        </a>

        {{-- DESKTOP LINKS --}}
        <div x-ref="links" @mouseleave="resetHl()" class="relative hidden min-[1180px]:flex items-center p-1 rounded-full text-sm">
            <span class="nav-highlight absolute top-1 bottom-1 rounded-full pointer-events-none"
                  :style="`left:${hl.l}px;right:${hl.r}px;opacity:${hl.ready ? 1 : 0};transition:left ${hl.lt}s ${hl.le},right ${hl.rt}s ${hl.re},opacity .2s ease`">
                <span x-ref="blob" class="nav-blob block w-full h-full rounded-full"></span>
            </span>

            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}"
                   data-key="{{ $link['key'] }}"
                   @mouseenter="moveTo($el)"
                   @click="go('{{ $link['key'] }}', $el.href)"
                   @focus="moveTo($el)"
                   @blur="resetHl()"
                   :class="active === '{{ $link['key'] }}' ? 'text-sky-300' : 'text-slate-300 hover:text-white'"
                   class="relative z-10 px-3.5 xl:px-5 py-2 rounded-full whitespace-nowrap transition-colors duration-200">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        {{-- DESKTOP AUTH --}}
        <div class="hidden min-[1180px]:flex items-center gap-1.5 xl:gap-2">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="btn-shine text-sm font-medium whitespace-nowrap px-4 xl:px-5 py-2 rounded-full bg-gradient-to-b from-sky-400 to-sky-500 text-white hover:from-sky-300 hover:to-sky-400 transition-colors">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="signin-glow text-sm text-slate-300 whitespace-nowrap px-3 xl:px-4 py-2 rounded-full">Sign In</a>
                <a href="{{ route('register') }}"
                   class="btn-shine text-sm font-medium whitespace-nowrap px-4 xl:px-5 py-2 rounded-full bg-gradient-to-b from-sky-400 to-sky-500 text-white hover:from-sky-300 hover:to-sky-400 transition-colors">
                    Create Account
                </a>
            @endauth
        </div>

        {{-- HAMBURGER --}}
        <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-controls="nav-sheet"
                class="nav-burger min-[1180px]:hidden" aria-label="Menu">
            <i></i><i></i><i></i>
        </button>
    </nav>

    {{-- MOBILE / TABLET MENU --}}
    <div class="nav-scrim min-[1180px]:hidden" :data-open="mobileOpen" @click="mobileOpen = false" aria-hidden="true"></div>

    <div class="nav-stage md:max-w-[34rem] md:ml-auto min-[1180px]:hidden">
    <div id="nav-sheet" class="nav-glass nav-glass--scrolled nav-sheet"
         :data-open="mobileOpen" :inert="!mobileOpen" @keydown.escape.window="mobileOpen = false">
        <span class="nav-sheet-shade" aria-hidden="true"></span>
        <div class="nav-sheet-fx" aria-hidden="true">
            <span class="nav-sheet-glow g1"></span>
            <span class="nav-sheet-glow g2"></span>
            <span class="nav-sheet-sheen"></span>
        </div>

        <div class="nav-sheet-in">
        <div class="grid gap-1.5 md:grid-cols-2 md:gap-2">
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" style="--i: {{ $loop->index }}"
                   @click="mobileOpen = false; go('{{ $link['key'] }}', $el.href)"
                   :data-on="active === '{{ $link['key'] }}'"
                   class="nav-item nav-m">
                    <span class="nav-m-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg></span>
                    <span class="nav-m-tx">{{ $link['label'] }}</span>
                    <svg class="nav-m-go" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endforeach
        </div>

        <div class="nav-item nav-m-auth" style="--i: 6">
            @auth
                <a href="{{ route('dashboard') }}" class="nav-m-cta btn-shine md:col-span-2">
                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z"/></svg> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-m-ghost">
                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg> Sign In
                </a>
                <a href="{{ route('register') }}" class="nav-m-cta btn-shine">
                    <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg> Create Account
                </a>
            @endauth
        </div>
        </div>
    </div>
    </div>
</div>

{{-- Spacer: the navbar is fixed, so reserve its height --}}
<div class="h-20" aria-hidden="true"></div>