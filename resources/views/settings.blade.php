@php
    $userInitials = collect(explode(' ', $user->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
    $startTab = ($errors->userDeletion->isNotEmpty() || $errors->updatePassword->isNotEmpty() || session('status') === 'password-updated') ? 'security' : 'profile';
    $eyeOn = 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z';
    $eyeOff = 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88';
    $badgeId = 'PC-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
    $isAdmin = $user->role === 'admin';
    $lockIcon = 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z';
@endphp

<x-layouts.dashboard>
    <style>
        html { scrollbar-gutter: stable; overflow-y: scroll; }
        .s-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .s-well { background: rgba(8, 15, 32, .5); border: 1px solid rgba(148, 163, 184, .16); border-radius: .8rem; }

        .s-field { width: 100%; background: rgba(8, 15, 32, .55); border: 1px solid rgba(148, 163, 184, .22); border-radius: .85rem; color: #f1f5f9; font-size: .9rem; padding: .75rem 1rem .75rem 2.9rem; transition: border-color .15s, box-shadow .15s; }
        .s-field::placeholder { color: #94a3b8; }
        .s-field:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 4px rgba(56, 189, 248, .14); }
        .s-field-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.1rem; height: 1.1rem; color: #94a3b8; pointer-events: none; }
        .s-label { display: block; font-size: .7rem; font-weight: 600; letter-spacing: .05em; color: #cbd5e1; margin-bottom: .4rem; }

        .s-btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; padding: .65rem 1.25rem; border-radius: .8rem; font-size: .875rem; font-weight: 600; transition: background-color .15s, border-color .15s, opacity .15s; }
        .s-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .s-btn-main:hover { opacity: .92; }
        .s-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); }
        .s-btn-ghost:hover { background: rgba(255, 255, 255, .06); border-color: rgba(148, 163, 184, .5); }
        .s-btn-danger { color: #fca5a5; border: 1px solid rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .08); }
        .s-btn-danger:hover { background: rgba(248, 113, 113, .18); border-color: rgba(248, 113, 113, .6); }

        .s-tabs { position: relative; display: inline-flex; padding: .25rem; gap: .1rem; margin-bottom: 1.25rem; border-radius: 999px; background: rgba(255, 255, 255, .04); border: 1px solid rgba(148, 163, 184, .18); }
        .s-pill { position: absolute; top: .25rem; bottom: .25rem; left: 0; width: 0; border-radius: 999px; pointer-events: none; will-change: transform, width;
            background: linear-gradient(180deg, rgba(255, 255, 255, .13), rgba(255, 255, 255, .06));
            border: 1px solid rgba(255, 255, 255, .18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .16), 0 2px 8px -2px rgba(2, 8, 23, .5);
            transition: transform .42s cubic-bezier(.3, 1.15, .6, 1), width .42s cubic-bezier(.3, 1.15, .6, 1); }
        .s-tab { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: .5rem; padding: .45rem 1.15rem; border-radius: 999px; font-size: .85rem; font-weight: 600; color: #b9c5d6; transition: color .2s; }
        .s-tab svg { transition: transform .25s ease-out; }
        .s-tab:hover { color: #fff; }
        .s-tab:hover svg { transform: scale(1.1); }
        .s-tab-on { color: #fff; }

        .s-btn:disabled { opacity: .4; cursor: not-allowed; }
        .s-hint { margin-top: .45rem; font-size: .72rem; line-height: 1.4; color: #94a3b8; }
        .s-stat { padding: .9rem; border-radius: .95rem; background: rgba(var(--c), .08); border: 1px solid rgba(var(--c), .24); display: flex; flex-direction: column; justify-content: space-between; transition: border-color .2s, background-color .2s; }
        .s-stat:hover { background: rgba(var(--c), .13); border-color: rgba(var(--c), .42); }
        .s-stat .a-ic { background: rgba(var(--c), .16); }
        .s-kv { display: flex; align-items: center; gap: .75rem; padding: .7rem 0; border-top: 1px solid rgba(148, 163, 184, .14); }
        .s-kv:first-child { border-top: 0; padding-top: 0; }
        .s-kv:last-child { padding-bottom: 0; }
        .s-kv .a-ic { background: rgba(var(--c), .14); }
        .s-rule { display: flex; align-items: center; gap: .7rem; font-size: .85rem; color: #94a3b8; transition: color .2s; }
        .s-rule-ic { display: grid; place-items: center; flex-shrink: 0; width: 1.35rem; height: 1.35rem; border-radius: 999px; border: 1px solid rgba(148, 163, 184, .35); color: #6ee7b7; transition: background-color .25s, border-color .25s; }
        .s-rule-ic svg { transform: scale(0); transition: transform .3s cubic-bezier(.34, 1.56, .64, 1); }
        .s-rule-ok { color: #e2e8f0; }
        .s-rule-ok .s-rule-ic { background: rgba(52, 211, 153, .18); border-color: rgba(52, 211, 153, .6); }
        .s-rule-ok .s-rule-ic svg { transform: scale(1); }

        .s-note li { position: relative; padding-left: 1.1rem; }
        .s-note li::before { content: ""; position: absolute; left: 0; top: .55em; width: 5px; height: 5px; border-radius: 999px; background: var(--dot, #38bdf8); }

        /* ---- ID badge on a V-lanyard ---- */
        .b-persp { perspective: 800px; max-width: 17rem; margin: 0 auto; }
        .b-v-short { -webkit-mask-image: linear-gradient(to bottom, transparent, #000 40%); mask-image: linear-gradient(to bottom, transparent, #000 40%); }
        @media (min-width: 1024px) { .b-persp { max-width: 21rem; } }
        .b-swing { display: flow-root; transform-origin: 50% 0; transform-style: preserve-3d; animation: b-swing 9s ease-in-out .9s infinite; will-change: transform; }
        .b-swing.b-hold { animation-play-state: paused; }
        @keyframes b-swing { 0%, 100% { transform: none; } 25% { transform: rotate(-.8deg) rotateY(-7deg) rotateX(1deg); } 75% { transform: rotate(.8deg) rotateY(7deg) rotateX(-1deg); } }
        @media (min-width: 1024px) { .b-swing { transform-origin: 50% -28rem; } }

        .b-v { width: 100%; height: auto; overflow: visible; margin-bottom: -.2rem; }
        .b-v-long { margin-top: -133.333%; }

        .b-crimp { position: relative; z-index: 4; width: 1.7rem; height: 1.5rem; margin: -1.3rem auto 0; border-radius: .3rem; background: linear-gradient(90deg, #6b7686, #cdd5df 45%, #6b7686); box-shadow: 0 2px 5px rgba(0, 0, 0, .5), inset 0 0 0 1px rgba(255, 255, 255, .45); }
        .b-tail { position: relative; width: 1rem; height: 2.4rem; margin: 0 auto; background: linear-gradient(90deg, #0f1c3a, #243d73 50%, #0f1c3a); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08); }
        .b-swivel { position: absolute; z-index: 5; top: -1.7rem; left: 50%; width: 1.25rem; height: 3.3rem; margin-left: -.625rem; border-radius: .35rem .35rem .6rem .6rem; transform: translateZ(5px);
            background: linear-gradient(90deg, #6b7686, #dbe2ea 45%, #7d8899 70%, #aeb8c6); box-shadow: 0 3px 6px rgba(0, 0, 0, .5), inset 0 0 0 1px rgba(255, 255, 255, .5); }
        .b-swivel::before { content: ""; position: absolute; left: 50%; top: .55rem; width: .35rem; height: 1.1rem; margin-left: -.175rem; border-radius: 999px; background: #334155; }
        .b-swivel::after { content: ""; position: absolute; left: 50%; bottom: .3rem; width: .6rem; height: .35rem; margin-left: -.3rem; border-radius: 999px; background: #1e293b; }

        .b-pull { display: flow-root; transform-style: preserve-3d; transform-origin: 50% 0; will-change: transform; }
        @media (min-width: 1024px) { .b-pull { transform-origin: 50% -28rem; } }
        .b-hang { display: flow-root; transform-style: preserve-3d; will-change: transform; }
        .b-v { transform-origin: 50% 0; will-change: transform; }
        .b-card { cursor: grab; touch-action: pan-y; }
        .b-card.b-drag { cursor: grabbing; user-select: none; }
        .b-tilt { display: flow-root; transform-style: preserve-3d; transform-origin: 50% 11.7rem; transition: transform .18s ease-out; will-change: transform; }
        @media (min-width: 1024px) { .b-tilt { transform-origin: 50% 8.8rem; } }
        .b-card { position: relative; transform-style: preserve-3d; }
        .b-z { transform: translateZ(var(--zz)); transform-style: preserve-3d; }
        .b-shine { position: absolute; inset: 0; border-radius: inherit; overflow: hidden; pointer-events: none; transform: translateZ(1px); }
        .b-shine::after { content: ""; position: absolute; inset: -20% -60%; background: linear-gradient(105deg, transparent 42%, rgba(186, 230, 253, .2) 50%, transparent 58%); transform: translateX(-70%); }
        .b-card:hover .b-shine::after { animation: b-sheen 1.1s ease-out; }
        .b-layer { position: absolute; inset: 0; border-radius: 1.4rem; background: #0a1430; border: 1px solid rgba(148, 163, 184, .35); transform: translateZ(var(--z)); }
        .b-face { position: relative; transform-style: preserve-3d; border-radius: 1.4rem; background: linear-gradient(170deg, #1a2d58 0%, #0d1a38 55%, #0a1430 100%); border: 1px solid rgba(148, 163, 184, .3); box-shadow: 0 22px 40px -16px rgba(0, 0, 0, .75), inset 0 1px 0 rgba(255, 255, 255, .1); }
        @keyframes b-sheen { from { transform: translateX(-70%); } to { transform: translateX(70%); } }

        .b-head { border-radius: 1.4rem 1.4rem 0 0; display: flex; align-items: center; gap: .6rem; padding: 2.5rem 1.25rem .85rem; background: linear-gradient(90deg, rgba(56, 189, 248, .22), rgba(37, 99, 235, .12)); border-bottom: 1px solid rgba(148, 163, 184, .2); }
        .b-slot { position: absolute; top: 1rem; left: 50%; width: 3.2rem; height: .55rem; margin-left: -1.6rem; border-radius: 999px; background: #050b1e; box-shadow: inset 0 1px 2px rgba(0, 0, 0, .8), 0 0 0 1px rgba(148, 163, 184, .25); }

        .b-photo { position: relative; display: block; width: 8.5rem; height: 8.5rem; border-radius: 1.2rem; overflow: hidden; box-shadow: 0 0 0 3px rgba(56, 189, 248, .45), 0 10px 24px -8px rgba(0, 0, 0, .6); }
        .b-photo img, .b-photo .s-initials { width: 100%; height: 100%; object-fit: cover; }
        .s-initials { display: grid; place-items: center; background: linear-gradient(135deg, #38bdf8, #2563eb); color: #fff; font-size: 2.8rem; font-weight: 700; }
        .b-cam { position: absolute; inset: 0; background: rgba(2, 6, 23, .55); display: grid; place-items: center; opacity: 0; transition: opacity .2s; }
        .b-photo:hover .b-cam, .b-photo:focus-within .b-cam { opacity: 1; }

        .b-bars { height: 2.1rem; border-radius: .3rem; background:
            repeating-linear-gradient(90deg, #e2e8f0 0 2px, transparent 2px 4px, #e2e8f0 4px 5px, transparent 5px 9px, #e2e8f0 9px 12px, transparent 12px 14px, #e2e8f0 14px 15px, transparent 15px 19px);
            opacity: .85; }

        .s-ri { display: flex; align-items: center; gap: .8rem; padding: .85rem 0; border-top: 1px solid rgba(148, 163, 184, .14); }
        .s-ri:first-child { border-top: 0; padding-top: .25rem; }
        .s-ri .a-ic { width: 2rem; height: 2rem; border-radius: .6rem; }
        .s-ri p { font-size: .85rem; line-height: 1.45; color: #e2e8f0; }
        .a-tile { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border-radius: .9rem; background: rgba(8, 15, 32, .5); border: 1px solid rgba(148, 163, 184, .16); transition: border-color .2s, background-color .2s; }
        .a-tile:hover { border-color: rgba(148, 163, 184, .35); }
        .a-ic { display: grid; place-items: center; width: 2.25rem; height: 2.25rem; border-radius: .7rem; flex-shrink: 0; background: rgba(var(--c), .14); border: 1px solid rgba(var(--c), .38); color: rgb(var(--c)); }

        .s-fade { animation: s-fade .22s ease-out; }
        @keyframes s-fade { from { opacity: 0; } to { opacity: 1; } }
        .s-in { animation: s-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes s-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        .b-drop { animation: b-drop 1.1s cubic-bezier(.3, 1.25, .5, 1) both; }
        @keyframes b-drop { from { transform: translateY(-34rem); } to { transform: none; } }
        @media (prefers-reduced-motion: reduce) { .s-fade, .s-in, .b-drop, .b-swing { animation: none; } .b-card:hover .b-shine::after { animation: none; } .b-tilt { transition: none; transform: none !important; } }
    </style>

    <script>
        window.idBadge = function () {
            const rem = () => parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const rubber = (v, lim) => lim * Math.tanh(v / lim);

            return {
                preview: null, rx: 0, ry: 0, hold: false,
                th: 0, py: 0, sy: 1,
                drag: false, moved: false, raf: null,
                x: 0, y: 0, vx: 0, vy: 0, tx: 0, ty: 0,
                downX: 0, downY: 0, baseX: 0, baseY: 0, pid: null, last: 0,

                len() { return (window.innerWidth >= 1024 ? 43 : 18) * rem(); },

                render() {
                    const L = this.len();
                    this.th = -Math.atan(this.x / L) * 180 / Math.PI;
                    this.py = this.y;
                    this.sy = Math.max(0.85, 1 + this.y / (L - 6 * rem()));
                    this.ry = Math.max(-22, Math.min(22, this.vx * 0.03));
                    this.rx = Math.max(-18, Math.min(18, -this.vy * 0.02));
                },

                step(t) {
                    const dt = Math.min((t - this.last) / 1000 || 0.016, 1 / 30);
                    this.last = t;
                    if (this.drag) {
                        const nx = this.x + (this.tx - this.x) * 0.35;
                        const ny = this.y + (this.ty - this.y) * 0.35;
                        this.vx = (nx - this.x) / dt;
                        this.vy = (ny - this.y) / dt;
                        this.x = nx; this.y = ny;
                    } else {
                        const k = 170, c = reduce ? 2 * Math.sqrt(k) : 8;
                        this.vx += (-k * this.x - c * this.vx) * dt;
                        this.vy += (-k * this.y - c * this.vy) * dt;
                        this.x += this.vx * dt;
                        this.y += this.vy * dt;
                        if (Math.abs(this.x) < 0.2 && Math.abs(this.y) < 0.2 && Math.abs(this.vx) < 3 && Math.abs(this.vy) < 3) {
                            this.x = this.y = this.vx = this.vy = 0;
                            this.render();
                            this.raf = null;
                            this.hold = false;
                            this.rx = 0; this.ry = 0;
                            return;
                        }
                    }
                    this.render();
                    this.raf = requestAnimationFrame((n) => this.step(n));
                },

                run() {
                    if (!this.raf) {
                        this.last = performance.now();
                        this.raf = requestAnimationFrame((n) => this.step(n));
                    }
                },

                down(e) {
                    if (e.pointerType === 'mouse' && e.button !== 0) return;
                    this.pid = e.pointerId;
                    this.downX = e.clientX; this.downY = e.clientY;
                    this.baseX = this.x; this.baseY = this.y;
                    this.moved = false;
                },

                move(e) {
                    if (this.pid !== e.pointerId) return;
                    const dx = e.clientX - this.downX, dy = e.clientY - this.downY;
                    if (!this.drag) {
                        if (Math.hypot(dx, dy) < 5) return;
                        this.drag = true; this.moved = true; this.hold = true;
                        e.currentTarget.setPointerCapture(e.pointerId);
                        this.run();
                    }
                    const rx = this.baseX + dx, ry = this.baseY + dy;
                    this.tx = rubber(rx, 110);
                    this.ty = ry >= 0 ? rubber(ry, 90) : rubber(ry, 20);
                },

                up(e) {
                    if (this.pid !== e.pointerId) return;
                    this.pid = null;
                    if (this.drag) {
                        this.drag = false;
                        try { e.currentTarget.releasePointerCapture(e.pointerId); } catch (_) {}
                        this.run();
                    }
                },
            };
        };
    </script>

    <div class="grid lg:grid-cols-[21rem_minmax(0,1fr)] lg:grid-rows-[auto_1fr] gap-x-6 items-start" x-data="{ tab: '{{ $startTab }}', placed: false, place() { const b = this.$refs[this.tab === 'profile' ? 'tProfile' : 'tSecurity']; const p = this.$refs.pill; if (!b || !p) return; if (!this.placed) { p.style.transition = 'none'; } p.style.width = b.offsetWidth + 'px'; p.style.transform = 'translateX(' + b.offsetLeft + 'px)'; if (!this.placed) { p.offsetWidth; p.style.transition = ''; this.placed = true; } } }" x-init="$nextTick(() => place()); document.fonts && document.fonts.ready.then(() => place())" x-effect="tab; $nextTick(() => place())" @resize.window="place()">

        <div class="s-in mb-6 lg:col-start-2 lg:row-start-1">
            <h1 class="text-2xl font-bold text-white mb-1">Settings</h1>
            <p class="text-slate-300 text-sm">Manage your account and security preferences.</p>
        </div>

        {{-- ID BADGE --}}
        <aside class="relative z-20 mb-6 lg:mb-0 lg:col-start-1 lg:row-start-1 lg:row-span-2" x-data="idBadge()">
            @if (session('status') === 'photo-updated')
                <div class="mb-4 text-sm text-emerald-300 s-well px-4 py-2.5" style="border-color: rgba(52,211,153,.4)">Profile photo updated.</div>
            @endif
            @if (session('status') === 'photo-removed')
                <div class="mb-4 text-sm text-emerald-300 s-well px-4 py-2.5" style="border-color: rgba(52,211,153,.4)">Profile photo removed.</div>
            @endif
            @error('photo')
                <div class="mb-4 text-sm text-red-300 s-well px-4 py-2.5" style="border-color: rgba(248,113,113,.4)">{{ $message }}</div>
            @enderror

            <div class="b-drop b-persp" @mousemove="if (drag || raf) return; const r = $el.getBoundingClientRect(); ry = ((($event.clientX - r.left) / r.width) - .5) * 40; rx = -((($event.clientY - r.top) / r.height) - .5) * 26; hold = true"
                 @mouseleave="if (drag || raf) return; rx = 0; ry = 0; hold = false">
                
                <div class="b-swing" :class="hold ? 'b-hold' : ''">
                    <div class="b-pull" :style="`transform: rotate(${th}deg)`">
                    <div class="b-tilt" :style="`transform: rotateX(${rx}deg) rotateY(${ry}deg)`">
                    <svg class="b-v b-v-long hidden lg:block" :style="`transform: scaleY(${sy})`" viewBox="0 -400 300 530" aria-hidden="true">
                    <g fill="none">
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="#080f22" stroke-width="20"/>
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="#1a2c55" stroke-width="17"/>
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="#243d73" stroke-width="7"/>
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="rgba(255,255,255,.07)" stroke-width="17" stroke-dasharray="1 2.5"/>
                    </g>
                </svg>
                <svg class="b-v b-v-short block lg:hidden" :style="`transform: scaleY(${sy})`" viewBox="0 -80 300 210" aria-hidden="true">
                    <g fill="none">
                        <path d="M78 -80 L78 -4 C78 44 128 92 150 126 M222 -80 L222 -4 C222 44 172 92 150 126" stroke="#080f22" stroke-width="20"/>
                        <path d="M78 -80 L78 -4 C78 44 128 92 150 126 M222 -80 L222 -4 C222 44 172 92 150 126" stroke="#1a2c55" stroke-width="17"/>
                        <path d="M78 -80 L78 -4 C78 44 128 92 150 126 M222 -80 L222 -4 C222 44 172 92 150 126" stroke="#243d73" stroke-width="7"/>
                        <path d="M78 -80 L78 -4 C78 44 128 92 150 126 M222 -80 L222 -4 C222 44 172 92 150 126" stroke="rgba(255,255,255,.07)" stroke-width="17" stroke-dasharray="1 2.5"/>
                    </g>
                </svg>
                    <div class="b-hang" :style="`transform: translateY(${py}px)`">
                    <div class="b-crimp"></div>
                    <div class="b-tail"></div>

                    <div class="b-card" :class="drag ? 'b-drag' : ''" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="up($event)" @click.capture="if (moved) { $event.preventDefault(); $event.stopPropagation(); moved = false }">
                        <span class="b-layer" style="--z:-1.5px"></span>
                        <span class="b-layer" style="--z:-3px"></span>
                        <span class="b-layer" style="--z:-4.5px"></span>
                        <span class="b-layer" style="--z:-6px"></span>
                        <span class="b-layer" style="--z:-7.5px"></span>
                        <span class="b-layer" style="--z:-9px"></span>
                        <span class="b-swivel"></span>
                        <div class="b-face">
                        <span class="b-shine"></span>
                        <span class="b-slot"></span>

                        <div class="b-head b-z" style="--zz:8px">
                            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="" class="w-9 h-9 object-contain">
                            <div class="leading-tight">
                                <p class="text-white font-bold tracking-wider text-sm">PHISHCORE</p>
                                <p class="text-sky-300 text-[10px] tracking-[.18em] font-semibold">DETECTION PLATFORM</p>
                            </div>
                        </div>

                        <div class="px-6 pt-6 pb-5 flex flex-col items-center text-center b-z" style="--zz:0px">
                            <form id="photo-upload-form" method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="contents">
                                @csrf
                                <label for="photo-input" class="b-photo cursor-pointer block" title="Change photo">
                                    <template x-if="preview">
                                        <img :src="preview" alt="">
                                    </template>
                                    <template x-if="!preview">
                                        @if ($user->photoUrl())
                                            <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}">
                                        @else
                                            <span class="s-initials">{{ $userInitials }}</span>
                                        @endif
                                    </template>
                                    <span class="b-cam">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.132.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.132-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                        </span>
                                </label>
                                <input id="photo-input" type="file" name="photo" accept="image/*" class="sr-only"
                                       @change="
                                           const file = $event.target.files[0];
                                           if (file) {
                                               preview = URL.createObjectURL(file);
                                               document.getElementById('photo-upload-form').submit();
                                           }
                                       ">
                            </form>

                            <p class="text-white font-bold text-xl mt-5 leading-tight break-words max-w-full">{{ $user->name }}</p>
                            <p class="mt-1 text-[11px] font-bold tracking-[.2em] {{ $isAdmin ? 'text-sky-300' : 'text-slate-300' }}">{{ $isAdmin ? 'ADMINISTRATOR' : 'MEMBER' }}</p>
                            <p class="mt-2 text-xs text-slate-300 break-all max-w-full" style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace">{{ $user->email }}</p>

                            @if ($user->is_team_member)
                                <span class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-violet-300 px-2.5 py-1 rounded-md" style="background: rgba(167,139,250,.14); border: 1px solid rgba(167,139,250,.4)">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> TEAM MEMBER
                                </span>
                            @endif
                        </div>

                        <div class="mx-6 grid grid-cols-2 gap-4 py-3 border-t border-dashed b-z" style="--zz:10px; border-color: rgba(148,163,184,.3)">
                            <div>
                                <p class="text-[10px] font-semibold tracking-[.16em] text-slate-400">ID NO.</p>
                                <p class="text-sm text-white font-semibold" style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace">{{ $badgeId }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-semibold tracking-[.16em] text-slate-400">SINCE</p>
                                <p class="text-sm text-white font-semibold">{{ $user->created_at->format('j M Y') }}</p>
                            </div>
                        </div>

                        <div class="px-6 pb-6 pt-1 b-z" style="--zz:6px">
                            <div class="b-bars"></div>
                        </div>
                        </div>
                    </div>
                    </div>
                    </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center gap-2 mt-5">
                <label for="photo-input" class="s-btn s-btn-ghost !py-1.5 !px-3 !text-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    Change photo
                </label>
                @if ($user->photoUrl())
                    <form method="POST" action="{{ route('profile.photo.destroy') }}" onsubmit="return confirm('Remove your profile photo?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="s-btn s-btn-danger !py-1.5 !px-3 !text-xs">Remove</button>
                    </form>
                @endif
            </div>
        </aside>

        {{-- RIGHT SIDE --}}
        <div class="min-w-0 lg:col-start-2 lg:row-start-2">
            <div class="s-in s-tabs" style="--d:.08s">
                <span class="s-pill" x-ref="pill" aria-hidden="true"></span>
                <button type="button" x-ref="tProfile" @click="tab = 'profile'" class="s-tab" :class="tab === 'profile' ? 's-tab-on' : ''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    Profile
                </button>
                <button type="button" x-ref="tSecurity" @click="tab = 'security'" class="s-tab" :class="tab === 'security' ? 's-tab-on' : ''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    Security
                </button>
            </div>

            {{-- PROFILE TAB --}}
            <div x-show="tab === 'profile'" x-cloak class="grid lg:grid-cols-3 gap-5 items-stretch">
                <div class="s-card p-6 lg:col-span-2" x-data="{ n: {{ Js::from(old('name', $user->name)) }}, e: {{ Js::from(old('email', $user->email)) }}, n0: {{ Js::from($user->name) }}, e0: {{ Js::from($user->email) }}, get dirty() { return this.n !== this.n0 || this.e !== this.e0; } }">
                    <div class="flex items-start gap-3.5 mb-6">
                            <span class="a-ic" style="--c:56,189,248; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></span>
                            <div>
                                <h2 class="text-white font-semibold leading-tight">Profile information</h2>
                                <p class="text-sm text-slate-300 mt-0.5">Keep your name and email up to date.</p>
                            </div>
                        </div>

                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>

                    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                        @csrf
                        @method('patch')
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label for="name" class="s-label">NAME</label>
                                <div class="relative">
                                    <svg class="s-field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                    <input id="name" name="name" type="text" x-model="n" required autocomplete="name" class="s-field">
                                </div>
                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                                <p class="s-hint">Shown on the scans and investigations you handle.</p>
                            </div>
                            <div>
                                <label for="email" class="s-label">EMAIL ADDRESS</label>
                                <div class="relative">
                                    <svg class="s-field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                    <input id="email" name="email" type="email" x-model="e" required autocomplete="username" class="s-field">
                                </div>
                                @error('email')
                                    <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                                <p class="s-hint">Changing it means you will need to verify the new address.</p>
                            </div>
                        </div>

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="s-well px-4 py-3" style="border-color: rgba(251,146,60,.4)">
                                <p class="text-sm text-orange-200">
                                    Your email address is unverified.
                                    <button form="send-verification" class="underline hover:text-white">Click here to re-send the verification email.</button>
                                </p>
                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-1.5 text-xs text-emerald-300">A new verification link has been sent to your email address.</p>
                                @endif
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-3 pt-5 border-t" style="border-color: rgba(148,163,184,.16)">
                            <button type="submit" class="s-btn s-btn-main" :disabled="! dirty">Save changes</button>
                            <button type="button" class="s-btn s-btn-ghost" x-show="dirty" x-cloak @click="n = n0; e = e0">Discard</button>
                            <span class="text-xs text-amber-300 inline-flex items-center gap-1.5" x-show="dirty" x-cloak><span class="w-1.5 h-1.5 rounded-full bg-current"></span> Unsaved changes</span>
                            @if (session('status') === 'profile-updated')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-emerald-300">Saved.</p>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="s-card p-6 flex flex-col">
                    <div class="flex items-start gap-3.5 mb-6">
                            <span class="a-ic" style="--c:167,139,250; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18" /></svg></span>
                            <div>
                                <h2 class="text-white font-semibold leading-tight">Your activity</h2>
                                <p class="text-sm text-slate-300 mt-0.5">From the scans you submitted.</p>
                            </div>
                        </div>
                    <div class="flex flex-col flex-1 justify-between">
                        <div class="s-kv" style="--c:56,189,248">
                            <span class="a-ic" style="width:2.1rem; height:2.1rem; border-radius:.65rem"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg></span>
                            <span class="text-sm text-slate-300">Scans submitted</span>
                            <span class="ml-auto text-base font-semibold text-white">{{ number_format($activity['scans']) }}</span>
                        </div>
                        <div class="s-kv" style="--c:248,113,113">
                            <span class="a-ic" style="width:2.1rem; height:2.1rem; border-radius:.65rem"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-4.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></span>
                            <span class="text-sm text-slate-300">Phishing detected</span>
                            <span class="ml-auto text-base font-semibold text-white">{{ number_format($activity['phishing']) }}</span>
                        </div>
                        <div class="s-kv" style="--c:52,211,153">
                            <span class="a-ic" style="width:2.1rem; height:2.1rem; border-radius:.65rem"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                            <span class="text-sm text-slate-300">Clean results</span>
                            <span class="ml-auto text-base font-semibold text-white">{{ number_format($activity['clean']) }}</span>
                        </div>
                        <div class="s-kv" style="--c:167,139,250">
                            <span class="a-ic" style="width:2.1rem; height:2.1rem; border-radius:.65rem"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                            <span class="text-sm text-slate-300">Last scan</span>
                            <span class="ml-auto text-base font-semibold text-white">{{ $activity['last_scan'] ? \Illuminate\Support\Carbon::parse($activity['last_scan'])->diffForHumans(['short' => true]) : 'None yet' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECURITY TAB --}}
            <div x-show="tab === 'security'" x-cloak class="space-y-5">
                <div class="grid lg:grid-cols-3 gap-5 items-stretch" x-data="{ pw: '', cf: '', get r() { const p = this.pw; return { len: p.length >= 12, mix: /[a-z]/.test(p) && /[A-Z]/.test(p), num: /\d/.test(p), sym: /[^A-Za-z0-9]/.test(p), match: p.length > 0 && p === this.cf }; }, get score() { return [this.r.len, this.r.mix, this.r.num, this.r.sym].filter(Boolean).length; } }">
                    <div class="s-card p-6 lg:col-span-2">
                        <div class="flex items-start gap-3.5 mb-6">
                            <span class="a-ic" style="--c:56,189,248; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg></span>
                            <div>
                                <h2 class="text-white font-semibold leading-tight">Change password</h2>
                                <p class="text-sm text-slate-300 mt-0.5">Use a long, unique password you do not use anywhere else.</p>
                            </div>
                        </div>

                        <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                            @csrf
                            @method('put')

                            <div x-data="{ show: false }">
                                    <label for="update_password_current_password" class="s-label">CURRENT PASSWORD</label>
                                    <div class="relative">
                                        <svg class="s-field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $lockIcon }}" /></svg>
                                        <input id="update_password_current_password" name="current_password" :type="show ? 'text' : 'password'" autocomplete="current-password" class="s-field !pr-12">
                                        <button type="button" @click="show = !show" tabindex="-1" aria-label="Show or hide password" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                                            <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOn }}" /></svg>
                                            <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOff }}" /></svg>
                                        </button>
                                    </div>
                                    @error('current_password', 'updatePassword')
                                        <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div x-data="{ show: false }">
                                    <label for="update_password_password" class="s-label">NEW PASSWORD</label>
                                    <div class="relative">
                                        <svg class="s-field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $lockIcon }}" /></svg>
                                        <input id="update_password_password" name="password" x-model="pw" :type="show ? 'text' : 'password'" autocomplete="new-password" class="s-field !pr-12">
                                        <button type="button" @click="show = !show" tabindex="-1" aria-label="Show or hide password" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                                            <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOn }}" /></svg>
                                            <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOff }}" /></svg>
                                        </button>
                                    </div>
                                    @error('password', 'updatePassword')
                                        <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div x-data="{ show: false }">
                                    <label for="update_password_password_confirmation" class="s-label">CONFIRM PASSWORD</label>
                                    <div class="relative">
                                        <svg class="s-field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $lockIcon }}" /></svg>
                                        <input id="update_password_password_confirmation" name="password_confirmation" x-model="cf" :type="show ? 'text' : 'password'" autocomplete="new-password" class="s-field !pr-12">
                                        <button type="button" @click="show = !show" tabindex="-1" aria-label="Show or hide password" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                                            <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOn }}" /></svg>
                                            <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $eyeOff }}" /></svg>
                                        </button>
                                    </div>
                                    @error('password_confirmation', 'updatePassword')
                                        <p class="mt-1.5 text-xs text-red-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-5 border-t" style="border-color: rgba(148,163,184,.16)">
                                <button type="submit" class="s-btn s-btn-main">Update password</button>
                                @if (session('status') === 'password-updated')
                                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-emerald-300">Saved.</p>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="s-card p-6 flex flex-col">
                        <div class="flex items-start gap-3.5 mb-6">
                            <span class="a-ic" style="--c:52,211,153; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg></span>
                            <div>
                                <h2 class="text-white font-semibold leading-tight">Password strength</h2>
                                <p class="text-sm text-slate-300 mt-0.5">Updates as you type.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-1.5">
                            <template x-for="i in 4">
                                <span class="h-1.5 rounded-full transition-colors duration-300" :style="`background: ${i <= score ? ['#f87171','#fb923c','#fbbf24','#34d399'][score - 1] : 'rgba(148,163,184,.2)'}`"></span>
                            </template>
                        </div>
                        <p class="text-xs font-semibold mt-2 mb-5 h-4" :class="score ? 'text-slate-200' : 'text-slate-400'" x-text="['Start typing a new password', 'Weak', 'Fair', 'Good', 'Strong'][score]"></p>
                        <ul class="space-y-3.5">
                            <li class="s-rule" :class="r.len ? 's-rule-ok' : ''"><span class="s-rule-ic"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span><span>At least 12 characters</span></li>
                            <li class="s-rule" :class="r.mix ? 's-rule-ok' : ''"><span class="s-rule-ic"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span><span>Upper and lowercase letters</span></li>
                            <li class="s-rule" :class="r.num ? 's-rule-ok' : ''"><span class="s-rule-ic"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span><span>At least one number</span></li>
                            <li class="s-rule" :class="r.sym ? 's-rule-ok' : ''"><span class="s-rule-ic"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span><span>At least one symbol</span></li>
                            <li class="s-rule" :class="r.match ? 's-rule-ok' : ''"><span class="s-rule-ic"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></span><span>Both passwords match</span></li>
                        </ul>
                        <p class="s-hint mt-auto pt-5">Never share your password, even with PhishCore team members.</p>
                    </div>
                </div>

                {{-- Danger zone --}}
                <div class="s-card p-5 sm:p-6" style="border-color: rgba(248,113,113,.35)" x-data="{ confirmingDeletion: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-start gap-3.5">
                            <span class="a-ic" style="--c:248,113,113; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></span>
                            <div>
                                <h2 class="text-red-300 font-semibold leading-tight">Delete account</h2>
                                <p class="text-sm text-slate-300 mt-0.5 max-w-lg">This permanently deletes your account and all of its data. It cannot be undone.</p>
                            </div>
                        </div>
                        <button type="button" @click="confirmingDeletion = true" class="s-btn s-btn-danger shrink-0">Delete account</button>
                    </div>

                    <div x-show="confirmingDeletion" x-cloak x-transition.opacity class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
                        <div @click.outside="confirmingDeletion = false" class="rounded-2xl p-6 max-w-md w-full" style="background: #0c1631; border: 1px solid rgba(148,163,184,.25);">
                            <h3 class="text-white font-semibold mb-2">Are you sure you want to delete your account?</h3>
                            <p class="text-sm text-slate-300 mb-4">Once your account is deleted, all of its resources and data will be permanently deleted. Enter your password to confirm.</p>
                            <form method="post" action="{{ route('profile.destroy') }}">
                                @csrf
                                @method('delete')
                                <div class="relative">
                                    <svg class="s-field-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $lockIcon }}" /></svg>
                                    <input type="password" name="password" placeholder="Password" class="s-field">
                                </div>
                                @error('password', 'userDeletion')
                                    <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                                <div class="flex justify-end gap-2 mt-5">
                                    <button type="button" @click="confirmingDeletion = false" class="s-btn s-btn-ghost !py-2">Cancel</button>
                                    <button type="submit" class="s-btn !py-2 text-white bg-red-500 hover:bg-red-600">Delete Account</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.dashboard>