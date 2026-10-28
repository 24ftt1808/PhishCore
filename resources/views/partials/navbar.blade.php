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
        active: '{{ request()->routeIs('reports.public', 'reports.public.show') ? 'public-reports' : 'home' }}',
        hlKey: null,
        mobileOpen: false,
        sheetH: 0,
        lite: false,
        scrolled: false,
        lock: false,
        lockTimer: null,
        hl: { ready: false },
        sections: ['home', 'features', 'how-it-works', 'about', 'contact'],

        /* the menu's glass is drawn as one shape behind the links; it needs the links' height */
        measure() {
            this.sheetH = this.$refs.sheet ? this.$refs.sheet.offsetHeight : 0;
        },

        init() {
            /* weak phones and data-saver mode get the plain morph, without the liquid neck */
            try {
                const c = navigator.connection || {};
                this.lite = !!((navigator.deviceMemory && navigator.deviceMemory <= 2) || (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2) || c.saveData);
            } catch (e) {}
            /* the glass is one spring value (see .nav-pour in app.css): 0 is the closed button, 1 is the open menu. It bounces a
               little as it opens, closes without a bounce, and can be turned around in mid-flight. */
            const root = this.$root;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
            const OPEN = { response: 0.6, damping: 0.74 };
            const CLOSE = { response: 0.48, damping: 1 };
            let x = 0;
            let v = 0;
            let raf = 0;
            let last = 0;
            let opening = false;
            const paint = () => {
                const value = x.toFixed(4);
                root.style.setProperty('--p', value);
                root.style.setProperty('--q', value);
            };
            const rest = (open) => {
                x = open ? 1 : 0;
                v = 0;
                paint();
                root.dataset.pour = open ? 'open' : 'rest';
            };
            const frame = (now) => {
                const h = Math.min((now - last) / 1000, 1 / 20) / 4;
                last = now;
                const set = opening ? OPEN : CLOSE;
                const w = (2 * Math.PI) / set.response;
                const k = w * w;
                const c = 2 * set.damping * w;
                const to = opening ? 1 : 0;
                for (let i = 0; i < 4; i++) {
                    v += (-k * (x - to) - c * v) * h;
                    x += v * h;
                    if (x < 0) { x = 0; v = 0; }
                }
                paint();
                if (Math.abs(x - to) < 0.0008 && Math.abs(v) < 0.01) {
                    raf = 0;
                    rest(opening);
                    return;
                }
                raf = requestAnimationFrame(frame);
            };
            this.$watch('mobileOpen', (open) => {
                if (open) this.measure();
                opening = open;
                if (reduce.matches) {
                    if (raf) cancelAnimationFrame(raf);
                    raf = 0;
                    rest(open);
                    return;
                }
                root.dataset.pour = 'moving';
                if (!raf) {
                    last = performance.now();
                    raf = requestAnimationFrame(frame);
                }
            });
            window.addEventListener('resize', () => { if (this.mobileOpen) this.measure(); });

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
        /* The pill is two edges, each on its own spring (like an iOS spring animation).
           The edge that leads is stiffer, the one that trails is softer, so the pill stretches
           and then catches up. Retargeting mid-flight keeps the speed, so sweeping across the
           links and then leaving always flows smoothly back instead of snapping. */
        moveTo(el, snap = false) {
            const pill = this.$refs.pill;
            if (!el || !this.$refs.links || !pill) return;
            const l = el.offsetLeft;
            const r = l + el.offsetWidth;
            this.hlKey = el.dataset.key;
            let s = pill.__sp;
            if (!s || snap || !this.hl.ready || this.reduced()) {
                if (s && s.raf) cancelAnimationFrame(s.raf);
                s = pill.__sp = { l, r, vl: 0, vr: 0, T: { l, r }, k: {}, c: {}, raf: 0, t: 0 };
                this.hl.ready = true;
                this.paint(s);
                return;
            }
            const dist = Math.abs((l + r) / 2 - (s.l + s.r) / 2);
            const dir = Math.sign((l + r) / 2 - (s.l + s.r) / 2);
            /* fluid and bouncy: the springy end always overshoots by about the same few pixels,
               however far the pill travelled, so short hops and long trips both get a clear bounce */
            const resp = Math.min(0.46, 0.3 + dist / 2800);
            const lnOs = -Math.log(Math.min(0.2, Math.max(0.01, 13 / Math.max(dist, 1))));
            const zl = lnOs / Math.sqrt(Math.PI * Math.PI + lnOs * lnOs);
            const spring = (response, zeta) => { const w = 2 * Math.PI / response; return [w * w, 2 * zeta * w]; };
            const lead = spring(resp * 0.8, zl), trail = spring(resp * 1.15, Math.min(zl + 0.08, 0.95)), even = spring(resp, zl + 0.04);
            const [kl, cl] = dir > 0 ? trail : dir < 0 ? lead : even;
            const [kr, cr] = dir > 0 ? lead : dir < 0 ? trail : even;
            s.T = { l, r };
            s.k = { l: kl, r: kr };
            s.c = { l: cl, r: cr };
            if (!s.raf) { s.t = performance.now(); s.raf = requestAnimationFrame((n) => this.tick(n)); }
        },
        tick(now) {
            const s = this.$refs.pill.__sp;
            const dt = Math.min((now - s.t) / 1000, 1 / 30);
            s.t = now;
            const steps = Math.max(1, Math.ceil(dt * 240)), h = dt / steps;
            for (let i = 0; i < steps; i++) {
                s.vl += (-s.k.l * (s.l - s.T.l) - s.c.l * s.vl) * h; s.l += s.vl * h;
                s.vr += (-s.k.r * (s.r - s.T.r) - s.c.r * s.vr) * h; s.r += s.vr * h;
            }
            const done = Math.abs(s.l - s.T.l) < 0.05 && Math.abs(s.r - s.T.r) < 0.05 && Math.abs(s.vl) < 0.5 && Math.abs(s.vr) < 0.5;
            if (done) { s.l = s.T.l; s.r = s.T.r; s.vl = s.vr = 0; s.raf = 0; } else { s.raf = requestAnimationFrame((n) => this.tick(n)); }
            this.paint(s);
        },
        paint(s) {
            const pill = this.$refs.pill, blob = this.$refs.blob;
            pill.style.left = s.l + 'px';
            pill.style.width = Math.max(s.r - s.l, 24) + 'px';
            /* faster movement = a touch flatter and longer, like liquid in motion */
            const speed = (Math.abs(s.vl) + Math.abs(s.vr)) / 2;
            blob.style.transform = speed < 1 ? '' : `scale(${1 + Math.min(speed / 9000, 0.04)}, ${1 - Math.min(speed / 6000, 0.09)})`;
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
    data-pour="rest" :data-lite="lite"
    class="pointer-events-none fixed top-4 inset-x-0 mx-auto z-50 w-[calc(100%-3rem)] min-[1180px]:max-w-[77rem]"
>
    {{-- The menu's liquid-glass body: the burger's circle and the menu are one gooey shape (see .nav-pour in app.css) --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <defs>
            <filter id="nav-goo" x="-5%" y="-10%" width="110%" height="120%" color-interpolation-filters="sRGB">
                <feGaussianBlur in="SourceGraphic" stdDeviation="8" result="blur"/>
                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 20 -8" result="goo"/>
                <feComposite in="SourceGraphic" in2="goo" operator="atop"/>
            </filter>
        </defs>
    </svg>
    <div class="nav-pour min-[1180px]:hidden" :data-open="mobileOpen" :data-lite="lite" :style="`--pour-h:${sheetH}px`" aria-hidden="true">
        <span class="nav-pour-blob"></span>
        <span class="nav-pour-blob is-sheet"></span>
    </div>

    {{-- THREE ISLANDS: brand | links | auth (phones/tablets: brand | menu button) --}}
    <div class="relative z-10 flex items-center justify-between gap-3">

        {{-- BRAND --}}
        <a href="{{ route('welcome') }}#home" @click="go('home', $el.href)"
           :class="{ 'nav-glass--scrolled': scrolled }"
           class="pointer-events-auto nav-glass rounded-full h-[60px] pl-3 pr-5 flex items-center gap-2.5 shrink-0">
            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-10 h-10 object-contain">
            <span class="flex flex-col items-start leading-tight">
                <span class="block text-left font-bold text-white text-[15px]">PhishCore</span>
                <span class="hidden xl:block text-left text-[11px] font-semibold tracking-wide text-sky-300 mt-0.5">Detection Platform</span>
            </span>
        </a>

        {{-- LINKS --}}
        <nav aria-label="Main" :class="{ 'nav-glass--scrolled': scrolled }"
             class="pointer-events-auto nav-glass rounded-full hidden min-[1180px]:block p-[7px]">
            <div x-ref="links" @mouseleave="resetHl()" class="relative flex items-center text-sm">
                <span x-ref="pill" class="nav-highlight absolute inset-y-0 rounded-full pointer-events-none"
                      :style="`opacity:${hl.ready ? 1 : 0};transition:opacity .2s ease`">
                    <span x-ref="blob" class="nav-blob block w-full h-full rounded-full"></span>
                </span>

                @foreach ($navLinks as $link)
                    @if ($link['key'] === 'public-reports')
                        <span class="w-px h-5 bg-white/15 mx-1.5" aria-hidden="true"></span>
                    @endif
                    <a href="{{ $link['href'] }}"
                       data-key="{{ $link['key'] }}"
                       @mouseenter="moveTo($el)"
                       @click="go('{{ $link['key'] }}', $el.href)"
                       @focus="moveTo($el)"
                       @blur="resetHl()"
                       :class="active === '{{ $link['key'] }}' ? 'text-sky-300' : 'text-slate-300 hover:text-white'"
                       class="relative z-10 h-11 px-3.5 xl:px-[1.125rem] inline-flex items-center rounded-full font-medium whitespace-nowrap transition-colors duration-200">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- AUTH --}}
        <div :class="{ 'nav-glass--scrolled': scrolled }"
             class="pointer-events-auto nav-glass rounded-full hidden min-[1180px]:flex items-center gap-1 h-[60px] pl-2 pr-2">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="btn-shine group inline-flex items-center gap-2 h-11 px-5 rounded-full text-sm font-semibold whitespace-nowrap bg-gradient-to-b from-sky-300 via-sky-400 to-sky-500 text-slate-950 shadow-[0_10px_24px_-10px_rgba(14,165,233,.9)] hover:from-sky-200 hover:via-sky-300 hover:to-sky-400 transition-colors">
                    Dashboard
                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            @else
                <a href="{{ route('login') }}" class="signin-glow inline-flex items-center h-11 px-4 xl:px-5 rounded-full text-sm font-medium text-slate-300 whitespace-nowrap">Sign In</a>
                <a href="{{ route('register') }}"
                   class="btn-shine group inline-flex items-center gap-2 h-11 px-5 rounded-full text-sm font-semibold whitespace-nowrap bg-gradient-to-b from-sky-300 via-sky-400 to-sky-500 text-slate-950 shadow-[0_10px_24px_-10px_rgba(14,165,233,.9)] hover:from-sky-200 hover:via-sky-300 hover:to-sky-400 transition-colors">
                    Create Account
                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            @endauth
        </div>

        {{-- HAMBURGER (own island) --}}
        <div :class="{ 'nav-glass--scrolled': scrolled, 'nav-island--open': mobileOpen }"
             class="pointer-events-auto nav-glass rounded-full p-[9px] flex min-[1180px]:hidden">
            <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-controls="nav-sheet"
                    class="nav-burger" aria-label="Menu">
                <i></i><i></i><i></i>
            </button>
        </div>
    </div>

    {{-- MOBILE / TABLET MENU --}}
    <div class="nav-scrim min-[1180px]:hidden" :data-open="mobileOpen" @click="mobileOpen = false" aria-hidden="true"></div>

    <div class="nav-stage md:max-w-[34rem] md:ml-auto min-[1180px]:hidden">
    <div id="nav-sheet" x-ref="sheet" class="nav-sheet"
         :data-open="mobileOpen" :inert="!mobileOpen" @keydown.escape.window="mobileOpen = false">
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

{{-- A soft fade behind the floating bar, so page text doesn't show through the gaps between the pills (not on the home page, which has its own hero) --}}
@unless (request()->routeIs('welcome'))
    <div class="pointer-events-none fixed top-0 inset-x-0 z-40 h-24 bg-gradient-to-b from-slate-950 via-slate-950/85 to-transparent" aria-hidden="true"></div>
@endunless

{{-- Spacer: the navbar is fixed, so reserve its height --}}
<div class="h-20" aria-hidden="true"></div>