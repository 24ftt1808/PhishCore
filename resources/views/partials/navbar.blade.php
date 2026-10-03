@php
    $navLinks = [
        ['key' => 'home',          'label' => 'Home',          'href' => route('welcome') . '#home'],
        ['key' => 'features',      'label' => 'Features',      'href' => route('welcome') . '#features'],
        ['key' => 'how-it-works',  'label' => 'How It Works',  'href' => route('welcome') . '#how-it-works'],
        ['key' => 'about',         'label' => 'About',         'href' => route('welcome') . '#about'],
        ['key' => 'contact',       'label' => 'Contact',       'href' => route('welcome') . '#contact'],
        ['key' => 'public-reports','label' => 'Public Reports','href' => route('reports.public')],
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
    class="fixed top-4 inset-x-0 mx-auto z-50 w-[calc(100%-2rem)] md:w-max max-w-full"
>
    {{-- PILL --}}
    <nav class="nav-glass rounded-full pl-6 pr-3 py-2.5 flex items-center justify-between md:gap-6 lg:gap-12"
         :class="{ 'nav-glass--scrolled': scrolled }">

        <a href="{{ route('welcome') }}#home" @click="go('home', $el.href)" class="flex items-center gap-2 shrink-0">
            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-8 h-8 object-contain">
            <span class="flex flex-col items-start leading-tight">
                <span class="block text-left font-bold text-white text-sm">PhishCore</span>
                <span class="block text-left text-[11px] font-medium tracking-wide text-sky-200/90 mt-0.5">Detection Platform</span>
            </span>
        </a>

        {{-- DESKTOP LINKS --}}
        <div x-ref="links" @mouseleave="resetHl()" class="relative hidden md:flex items-center p-1 rounded-full text-sm">
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
                   class="relative z-10 px-4 lg:px-5 py-2 rounded-full whitespace-nowrap transition-colors duration-200">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        {{-- DESKTOP AUTH --}}
        <div class="hidden md:flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="btn-shine text-sm font-medium px-5 py-2 rounded-full bg-gradient-to-b from-sky-400 to-sky-500 text-white hover:from-sky-300 hover:to-sky-400 transition-colors">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="signin-glow text-sm text-slate-300 px-4 py-2 rounded-full">Sign In</a>
                <a href="{{ route('register') }}"
                   class="btn-shine text-sm font-medium px-5 py-2 rounded-full bg-gradient-to-b from-sky-400 to-sky-500 text-white hover:from-sky-300 hover:to-sky-400 transition-colors">
                    Create Account
                </a>
            @endauth
        </div>

        {{-- HAMBURGER --}}
        <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 mr-1 text-slate-200" aria-label="Menu">
            <svg x-show="!mobileOpen" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
            <svg x-show="mobileOpen" x-cloak width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </nav>

    {{-- MOBILE MENU --}}
    <div x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="nav-glass nav-glass--scrolled md:hidden mt-2 rounded-3xl p-3 space-y-1 max-h-[75vh] overflow-y-auto">

        @foreach ($navLinks as $link)
            <a href="{{ $link['href'] }}" @click="mobileOpen = false; go('{{ $link['key'] }}', $el.href)"
               :class="active === '{{ $link['key'] }}' ? 'text-white bg-white/10' : 'text-slate-300'"
               class="block px-4 py-2.5 rounded-xl text-sm transition">
                {{ $link['label'] }}
            </a>
        @endforeach

        <div class="pt-2 mt-2 border-t border-white/10 space-y-2">
            @auth
                <a href="{{ route('dashboard') }}" class="block text-center text-sm font-medium px-4 py-2.5 rounded-xl bg-sky-500 text-white">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="block px-4 py-2.5 rounded-xl text-sm text-slate-300">Sign In</a>
                <a href="{{ route('register') }}" class="block text-center text-sm font-medium px-4 py-2.5 rounded-xl bg-sky-500 text-white">Create Account</a>
            @endauth
        </div>
    </div>
</div>

{{-- Spacer: the navbar is fixed, so reserve its height --}}
<div class="h-20" aria-hidden="true"></div>