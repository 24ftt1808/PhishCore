@php
    $userInitials = collect(explode(' ', $user->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
    $startTab = ($errors->userDeletion->isNotEmpty() || $errors->updatePassword->isNotEmpty() || session('status') === 'password-updated') ? 'security' : (session('status') === 'preferences-updated' ? 'preferences' : 'profile');
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

        /* the four tabs: a frosted-glass tray with a glossy droplet that slides to the chosen tab and squishes like jelly on the way */
        .s-tabs { position: relative; display: inline-flex; padding: .25rem; gap: .1rem; margin-bottom: 1.25rem; border-radius: 999px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(148, 163, 184, .2);
            -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
        .fx-lite .s-tabs { background: rgba(12, 24, 52, .85); -webkit-backdrop-filter: none; backdrop-filter: none; }
        .s-pill { position: absolute; top: .25rem; bottom: .25rem; left: 0; width: 0; border-radius: 999px; pointer-events: none; will-change: transform, width;
            transition: transform .55s cubic-bezier(.3, 1.35, .55, 1), width .55s cubic-bezier(.3, 1.35, .55, 1); }
        .s-pill::before { content: ""; position: absolute; inset: 0; border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, .15), rgba(255, 255, 255, .07));
            border: 1px solid rgba(255, 255, 255, .2);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .2), 0 3px 8px -3px rgba(2, 8, 23, .5); }
        .s-pill::after { content: ""; position: absolute; left: 12%; right: 12%; top: 3px; height: 38%; border-radius: 999px; display: none; }
        .s-pill.s-jelly::before { animation: s-jelly .6s cubic-bezier(.3, 1, .5, 1); }
        @keyframes s-jelly { 0% { transform: scale(1, 1); } 25% { transform: scale(1.07, .9); } 55% { transform: scale(.98, 1.05); } 80% { transform: scale(1.01, .99); } 100% { transform: scale(1, 1); } }
        .s-tab { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: .5rem; padding: .45rem 1.15rem; border-radius: 999px; font-size: .85rem; font-weight: 600; color: #b9c5d6; transition: color .2s, transform .3s cubic-bezier(.3, 1.5, .5, 1); -webkit-tap-highlight-color: transparent; }
        .s-tab:active { transform: scale(.93); }
        .s-tab svg { transition: transform .25s ease-out; }
        .s-tab:hover { color: #fff; }
        .s-tab:hover svg { transform: scale(1.1); }
        .s-tab-on { color: #fff; }
        .fx-lite .s-pill::before, .fx-lite .s-pill.s-jelly::before { animation: none; }
        @media (prefers-reduced-motion: reduce) { .s-pill, .s-tab { transition: none; } .s-pill.s-jelly::before { animation: none; } }
        /* phones: the four tabs share the full width, each with its icon above the label (like a bottom navigation bar) so nothing is squeezed side by side */
        @media (max-width: 639px) {
            .s-tabs { display: flex; width: 100%; border-radius: 1.1rem; }
            .s-pill { border-radius: .9rem; }
            .s-tab { flex: 1 1 0; flex-direction: column; justify-content: center; min-width: 0; padding: .55rem .15rem .5rem; gap: .2rem; font-size: .72rem; line-height: 1.1; border-radius: .9rem; }
            .s-tab svg { width: 1.15rem; height: 1.15rem; flex-shrink: 0; }
        }
        @media (pointer: coarse) { .s-tab { min-height: 2.5rem; } }

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
        /* Preferences: choice buttons that save as soon as one is pressed */
        .pf-chip { text-align: left; border-radius: .85rem; border: 1px solid rgba(148, 163, 184, .22); background: rgba(8, 15, 32, .45); color: #cbd5e1; transition: border-color .2s, background-color .2s, color .2s, transform .25s cubic-bezier(.3, 1.5, .5, 1); -webkit-tap-highlight-color: transparent; }
        .pf-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .pf-chip { padding: .5rem 1rem; font-size: .85rem; font-weight: 600; border-radius: 999px; }
        .pf-chip:hover { border-color: rgba(148, 163, 184, .5); color: #fff; }
        .pf-chip:active { transform: scale(.96); }
        .pf-on { color: #fff; border-color: rgba(125, 211, 252, .6); background: rgba(56, 189, 248, .14); }
        @media (prefers-reduced-motion: reduce) { .pf-chip { transition: none; } }
        .s-note li::before { content: ""; position: absolute; left: 0; top: .55em; width: 5px; height: 5px; border-radius: 999px; background: var(--dot, #38bdf8); }

        /* ---- ID badge on a V-lanyard ---- */
        /* phones: a fixed width (the tag is short now). Bigger screens: the badge grows with the screen height (lanyard + card is about 2.3x its width). svh is used because dvh changes while the browser bar hides on scroll, and the badge would widen by itself */
        .b-persp { --w: 17.5rem; perspective: 800px; width: 100%; max-width: var(--w); margin: 0 auto; }
        @media (min-width: 640px) and (max-width: 1279px) { .b-persp { --w: 20rem; } }
        @media (min-width: 1280px) { .b-persp { --w: clamp(19rem, calc((100svh - 9.5rem) / 2.31), 24rem); } }
        .b-v-short { -webkit-mask-image: linear-gradient(to bottom, transparent, #000 40%); mask-image: linear-gradient(to bottom, transparent, #000 40%); }
        @media (min-width: 1280px) { .b-persp { max-width: 21rem; } }
        .b-swing { display: flow-root; transform-origin: 50% 0; transform-style: preserve-3d; animation: b-swing 9s ease-in-out .9s infinite; will-change: transform; }
        .b-swing.b-hold { animation-play-state: paused; }
        @keyframes b-swing { 0%, 100% { transform: none; } 25% { transform: rotate(-.8deg) rotateY(-7deg) rotateX(1deg); } 75% { transform: rotate(.8deg) rotateY(7deg) rotateX(-1deg); } }
        @media (min-width: 1280px) { .b-swing { transform-origin: 50% -28rem; } }

        .b-v { width: 100%; height: auto; overflow: visible; margin-bottom: -.2rem; }
        .b-v-long { margin-top: -133.333%; }

        .b-crimp { position: relative; z-index: 4; width: 1.7rem; height: 1.5rem; margin: -1.3rem auto 0; border-radius: .3rem; background: linear-gradient(90deg, #6b7686, #cdd5df 45%, #6b7686); box-shadow: 0 2px 5px rgba(0, 0, 0, .5), inset 0 0 0 1px rgba(255, 255, 255, .45); }
        .b-tail { position: relative; width: 1rem; height: 2.4rem; margin: 0 auto; background: linear-gradient(90deg, #0f1c3a, #243d73 50%, #0f1c3a); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08); }
        .b-swivel { position: absolute; z-index: 5; top: -1.7rem; left: 50%; width: 1.25rem; height: 3.3rem; margin-left: -.625rem; border-radius: .35rem .35rem .6rem .6rem; transform: translateZ(5px);
            background: linear-gradient(90deg, #6b7686, #dbe2ea 45%, #7d8899 70%, #aeb8c6); box-shadow: 0 3px 6px rgba(0, 0, 0, .5), inset 0 0 0 1px rgba(255, 255, 255, .5); }
        .b-swivel::before { content: ""; position: absolute; left: 50%; top: .55rem; width: .35rem; height: 1.1rem; margin-left: -.175rem; border-radius: 999px; background: #334155; }
        .b-swivel::after { content: ""; position: absolute; left: 50%; bottom: .3rem; width: .6rem; height: .35rem; margin-left: -.3rem; border-radius: 999px; background: #1e293b; }

        .b-pull { display: flow-root; transform-style: preserve-3d; transform-origin: 50% 0; will-change: transform; }
        @media (min-width: 1280px) { .b-pull { transform-origin: 50% -28rem; } }
        .b-hang { display: flow-root; transform-style: preserve-3d; will-change: transform; }
        .b-v { transform-origin: 50% 0; will-change: transform; }
        .b-card { cursor: grab; touch-action: pan-y; }
        .b-card.b-drag { cursor: grabbing; user-select: none; }
        .b-tilt { display: flow-root; transform-style: preserve-3d; transform-origin: 50% calc(var(--w) * .5333 - .2rem); transition: transform .18s ease-out; will-change: transform; }
        @media (min-width: 1280px) { .b-tilt { transform-origin: 50% 8.8rem; } }
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
        @media (max-width: 1279px) { .b-photo { width: 4.5rem; height: 4.5rem; border-radius: 1rem; box-shadow: 0 0 0 2px rgba(56, 189, 248, .45), 0 6px 14px -6px rgba(0, 0, 0, .6); } .s-initials { font-size: 1.6rem; } }
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
        /* phones: the badge rests as a short tag (header + photo + name). Pull it down and the rest of the card unrolls from underneath, let go and it springs back */
        /* tablets get the same short tag as phones: photo on the left, name on the right (the Tailwind max-sm classes only reach phones) */
        @media (min-width: 640px) and (max-width: 1279px) {
            .b-persp .b-id { flex-direction: row; text-align: left; gap: .875rem; padding-top: .875rem; padding-bottom: .75rem; }
            .b-persp .b-who { margin-top: 0; }
            .b-persp .b-mail p:first-child { margin-top: .25rem; }
        }
        .b-ext, .b-ext-in { display: contents; }
        .b-hint, .b-close { display: none; }
        @media (max-width: 1279px) {
            .b-swing, .b-card, .b-hint, .b-close { touch-action: none; }
            .b-hint, .b-close { cursor: pointer; }
            .b-ext { display: block; position: absolute; z-index: 3; left: -1px; right: -1px; top: 100%; height: 0; overflow: hidden; visibility: hidden; border-radius: 0 0 1.4rem 1.4rem; background: #0a1430; box-shadow: 0 12px 20px -12px rgba(0, 0, 0, .7); }
            .b-ext.b-ext-on { visibility: visible; border: 1px solid rgba(148, 163, 184, .3); border-top: 0; }
            .b-ext-in { display: block; }
            .b-face.b-open { border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom-color: transparent; box-shadow: none; }
            .b-hint { display: flex; align-items: center; justify-content: center; gap: .35rem; padding: .35rem 1rem .85rem; max-height: 3.5rem; font-size: 10px; font-weight: 600; letter-spacing: .14em; color: rgba(125, 211, 252, .85); text-transform: uppercase; overflow: hidden; transition: opacity .25s, max-height .25s, padding .25s; }
            .b-face.b-open .b-hint { max-height: 0; padding-top: 0; padding-bottom: 0; }
            .b-hint { transform: translateZ(10px); }
            .b-hint svg { animation: b-nudge 1.8s ease-in-out infinite; }
            .b-close { display: flex; align-items: center; justify-content: center; gap: .35rem; width: 100%; padding: .75rem 1rem .95rem; font-size: 10px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: rgba(125, 211, 252, .85); }
            .b-persp { margin-top: -1.6rem; }
        }
        /* About tab: the name tag swings up and away, the space under it closes; any other tab and it drops back in with a little bounce */
        .b-wrap { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .6s cubic-bezier(.3, 1.1, .4, 1); }
        .b-wrap-in { min-height: 0; }
        @media (min-width: 1280px) {
            .b-wrap { align-self: stretch; }
            .b-wrap-in { cursor: grab; touch-action: pan-y; }
            .b-wrap-in button, .b-wrap-in label { cursor: pointer; }
        }
        .b-card-wrap { transition: transform .6s cubic-bezier(.3, 1.35, .5, 1), opacity .35s ease-out .05s; transform-origin: 50% 0; }
        .b-wrap.b-away { grid-template-rows: 0fr; overflow: hidden; visibility: hidden; pointer-events: none; transition: grid-template-rows .5s cubic-bezier(.5, 0, .2, 1), visibility 0s linear .5s; }
        .b-away .b-card-wrap { opacity: 0; transform: translateY(-120%) rotate(-5deg) scale(.9); transition: transform .45s cubic-bezier(.55, 0, .8, .4), opacity .3s ease-in; }
        @media (min-width: 1280px) {
            .b-wrap-in { width: 21rem; }
            .s-grid { transition: grid-template-columns .55s cubic-bezier(.3, 1, .4, 1), column-gap .55s cubic-bezier(.3, 1, .4, 1); }
            .s-grid.s-away { grid-template-columns: 0 minmax(0, 1fr); column-gap: 0; }
        }
        @media (prefers-reduced-motion: reduce) { .b-wrap, .b-card-wrap, .b-away .b-card-wrap, .s-grid { transition: none !important; } }
        @keyframes b-nudge { 0%, 100% { transform: translateY(-1px); } 50% { transform: translateY(2px); } }
        @media (prefers-reduced-motion: reduce) { .s-fade, .s-in, .b-drop, .b-swing, .b-hint svg { animation: none; } .b-card:hover .b-shine::after { animation: none; } .b-tilt { transition: none; transform: none !important; } }
    </style>

    <script>
        window.idBadge = function () {
            const rem = () => parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const rubber = (v, lim) => lim * Math.tanh(v / lim);

            return {
                preview: null, rx: 0, ry: 0, hold: false,
                th: 0, py: 0, sy: 1, ext: 0, extH: 0, lanH: 0, handle: false, e: 0, ve: 0, gap: 0, pinned: false, keep: false,
                drag: false, moved: false, raf: null,
                x: 0, y: 0, vx: 0, vy: 0, tx: 0, ty: 0,
                downX: 0, downY: 0, baseX: 0, baseY: 0, pid: null, last: 0,

                len() { return (window.innerWidth >= 1280 ? 43 : 18) * rem(); },

                compact() { return window.innerWidth < 1280; },

                onHandle(e) {
                    if (e.target.closest('.b-hint, .b-close')) { return true; }
                    const row = this.pinned ? this.$refs.closeRow : this.$refs.hintRow;
                    if (!row) { return false; }
                    const r = row.getBoundingClientRect();
                    return e.clientX >= r.left - 8 && e.clientX <= r.right + 8 && e.clientY >= r.top - 8 && e.clientY <= r.bottom + 8;
                },

                measure() {
                    const inner = this.$refs.extIn;
                    this.extH = inner ? inner.offsetHeight : 0;
                    this.lanH = this.$refs.lan ? this.$refs.lan.getBoundingClientRect().width * 160 / 300 : 0;
                },

                setOpen(open) {
                    this.measure();
                    this.keep = true;
                    clearTimeout(this.keepTimer);
                    this.keepTimer = setTimeout(() => { this.keep = false; this.render(); }, 600);
                    this.pinned = open;
                    this.run();
                },

                close() { this.setOpen(false); },

                render() {
                    const L = this.len();
                    const phone = this.compact();
                    this.th = -Math.atan(this.x / L) * 180 / Math.PI;
                    this.py = this.y;
                    // on a phone the lanyard stretches by exactly as much as the card moves, so the two never come apart
                    this.sy = phone && this.lanH ? Math.max(0.85, 1 + this.y / this.lanH) : Math.min(1.5, Math.max(0.85, 1 + this.y / (L - 6 * rem())));
                    this.ext = phone ? Math.max(0, Math.min(this.extH, this.e)) : 0;
                    this.gap = phone && (this.pinned || this.keep) ? this.extH : 0;
                    this.ry = Math.max(-22, Math.min(22, this.vx * 0.03));
                    this.rx = Math.max(-18, Math.min(18, -this.vy * 0.02));
                },

                step(t) {
                    const dt = Math.min((t - this.last) / 1000 || 0.016, 1 / 30);
                    this.last = t;
                    const phone = this.compact();
                    const H = phone ? this.extH : 0;
                    const goal = phone && this.pinned ? H : 0;
                    if (this.drag) {
                        const nx = this.x + (this.tx - this.x) * 0.35;
                        const ny = this.y + (this.ty - this.y) * 0.35;
                        this.vx = (nx - this.x) / dt;
                        this.vy = (ny - this.y) / dt;
                        this.x = nx; this.y = ny;
                        // pulling only stretches the tag: whether the full card is open is decided by tapping alone
                        this.e += (goal - this.e) * 0.35;
                        this.ve = 0;
                    } else {
                        const ke = 170, ce = reduce ? 2 * Math.sqrt(ke) : 19;
                        this.ve += (-ke * (this.e - goal) - ce * this.ve) * dt;
                        this.e += this.ve * dt;
                        const k = 170, c = reduce ? 2 * Math.sqrt(k) : 8;
                        this.vx += (-k * this.x - c * this.vx) * dt;
                        this.vy += (-k * this.y - c * this.vy) * dt;
                        this.x += this.vx * dt;
                        this.y += this.vy * dt;
                        if (Math.abs(this.x) < 0.2 && Math.abs(this.y) < 0.2 && Math.abs(this.vx) < 3 && Math.abs(this.vy) < 3 && Math.abs(this.e - goal) < 0.5 && Math.abs(this.ve) < 3) {
                            this.x = this.y = this.vx = this.vy = 0;
                            this.e = goal; this.ve = 0;
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
                    // the whole badge column can be grabbed on big screens (not the buttons under it); on phones and tablets only the tag itself, so the page still scrolls
                    const onTag = !!e.target.closest('.b-swing');
                    if (!onTag && (this.compact() || e.target.closest('button, a, input, label'))) return;
                    // phones: the whole tag can be pulled and stretched, but a tap only opens or closes it from the strip at the bottom (or the Close row)
                    this.handle = this.compact() && this.onHandle(e);
                    this.measure();
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
                    this.tx = rubber(rx, this.compact() ? 60 : 110);
                    this.ty = ry >= 0 ? rubber(ry, 90) : rubber(ry, 20);
                },

                up(e) {
                    if (this.pid !== e.pointerId) return;
                    this.pid = null;
                    if (this.drag) {
                        this.drag = false;
                        // pulled all the way down: it stays open (so it can be read). Pulled up a little while open: it closes.
                        try { e.currentTarget.releasePointerCapture(e.pointerId); } catch (_) {}
                        this.run();
                    } else if (this.compact() && this.handle) {
                        // a tap on the tag opens the full card, or closes it when it is already open
                        this.setOpen(!this.pinned);
                    }
                },
            };
        };
    </script>

    <div class="s-grid grid scroll-mt-20 lg:scroll-mt-8 xl:grid-cols-[21rem_minmax(0,1fr)] xl:grid-rows-[auto_1fr] gap-x-6 items-start" :class="tab === 'about' ? 's-away' : ''" x-data="{ tab: '{{ $startTab }}', placed: false, place(jelly) { const b = this.$refs[{ profile: 'tProfile', security: 'tSecurity', preferences: 'tPreferences', about: 'tAbout' }[this.tab]]; const p = this.$refs.pill; if (!b || !p) return; if (!this.placed) { p.style.transition = 'none'; } p.style.width = b.offsetWidth + 'px'; p.style.transform = 'translateX(' + b.offsetLeft + 'px)'; if (!this.placed) { p.offsetWidth; p.style.transition = ''; this.placed = true; } else if (jelly) { p.classList.remove('s-jelly'); p.offsetWidth; p.classList.add('s-jelly'); } } }" x-init="$nextTick(() => place()); document.fonts && document.fonts.ready.then(() => place())" x-effect="tab; $nextTick(() => place(true))" @resize.window="place()">

        <div class="s-in relative mb-3 sm:mb-6 text-center xl:text-left xl:col-start-2 xl:row-start-1" style="z-index: 25">
            <h1 class="text-2xl font-bold text-white mb-1">Settings</h1>
            <p class="text-slate-300 text-sm">Manage your account and security preferences.</p>
        </div>

        {{-- ID BADGE --}}
        <div class="b-wrap xl:col-start-1 xl:row-start-1 xl:row-span-2" :class="tab === 'about' ? 'b-away' : ''" :aria-hidden="tab === 'about' ? 'true' : 'false'"><div class="b-wrap-in" x-data="idBadge()" @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="up($event)" @click.capture="if (moved) { $event.preventDefault(); $event.stopPropagation(); moved = false }">
        <aside class="b-card-wrap relative z-20 mx-auto w-full mb-6 xl:mb-0">
            <div class="b-drop b-persp" :style="`margin-bottom: ${gap}px`" @mousemove="if (compact() || drag || raf) return; const r = $el.getBoundingClientRect(); ry = ((($event.clientX - r.left) / r.width) - .5) * 40; rx = -((($event.clientY - r.top) / r.height) - .5) * 26; hold = true"
                 @mouseleave="if (compact() || drag || raf) return; rx = 0; ry = 0; hold = false">
                
                <div class="b-swing" :class="hold ? 'b-hold' : ''">
                    <div class="b-pull" :style="`transform: rotate(${th}deg)`">
                    <div class="b-tilt" :style="`transform: rotateX(${rx}deg) rotateY(${ry}deg)`">
                    <svg class="b-v b-v-long hidden xl:block" :style="`transform: scaleY(${sy})`" viewBox="0 -400 300 530" aria-hidden="true">
                    <g fill="none">
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="#080f22" stroke-width="20"/>
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="#1a2c55" stroke-width="17"/>
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="#243d73" stroke-width="7"/>
                        <path d="M78 -400 L78 -4 C78 44 128 92 150 126 M222 -400 L222 -4 C222 44 172 92 150 126" stroke="rgba(255,255,255,.07)" stroke-width="17" stroke-dasharray="1 2.5"/>
                    </g>
                </svg>
                <svg class="b-v b-v-short block xl:hidden" x-ref="lan" :style="`transform: scaleY(${sy})`" viewBox="0 -30 300 160" aria-hidden="true">
                    <g fill="none">
                        <path d="M78 -30 L78 -4 C78 44 128 92 150 126 M222 -30 L222 -4 C222 44 172 92 150 126" stroke="#080f22" stroke-width="20"/>
                        <path d="M78 -30 L78 -4 C78 44 128 92 150 126 M222 -30 L222 -4 C222 44 172 92 150 126" stroke="#1a2c55" stroke-width="17"/>
                        <path d="M78 -30 L78 -4 C78 44 128 92 150 126 M222 -30 L222 -4 C222 44 172 92 150 126" stroke="#243d73" stroke-width="7"/>
                        <path d="M78 -30 L78 -4 C78 44 128 92 150 126 M222 -30 L222 -4 C222 44 172 92 150 126" stroke="rgba(255,255,255,.07)" stroke-width="17" stroke-dasharray="1 2.5"/>
                    </g>
                </svg>
                    <div class="b-hang" :style="`transform: translateY(${py}px)`">
                    <div class="b-crimp"></div>
                    <div class="b-tail"></div>

                    <div class="b-card" :class="drag ? 'b-drag' : ''">
                        <span class="b-layer" style="--z:-1.5px"></span>
                        <span class="b-layer" style="--z:-3px"></span>
                        <span class="b-layer" style="--z:-4.5px"></span>
                        <span class="b-layer" style="--z:-6px"></span>
                        <span class="b-layer" style="--z:-7.5px"></span>
                        <span class="b-layer" style="--z:-9px"></span>
                        <span class="b-swivel"></span>
                        <div class="b-face" :class="(ext > 0.5 || keep) ? 'b-open' : ''">
                        <span class="b-shine"></span>
                        <span class="b-slot"></span>

                        <div class="b-head b-z" style="--zz:8px">
                            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="" class="w-9 h-9 object-contain">
                            <div class="leading-tight">
                                <p class="text-white font-bold tracking-wider text-sm">PHISHCORE</p>
                                <p class="text-sky-300 text-[10px] tracking-[.18em] font-semibold">DETECTION PLATFORM</p>
                            </div>
                        </div>

                        <div class="b-id px-5 sm:px-6 pt-5 sm:pt-6 pb-4 sm:pb-0 flex flex-col items-center text-center b-z max-sm:flex-row max-sm:text-left max-sm:gap-3.5 max-sm:pt-3.5 max-sm:pb-3" style="--zz:0px">
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

                            <div class="b-who mt-4 sm:mt-5 max-sm:mt-0 min-w-0 max-w-full">
                                <p class="text-white font-bold text-xl max-sm:text-lg leading-tight break-words max-w-full">{{ $user->name }}</p>
                                <p class="mt-1 text-[11px] font-bold tracking-[.2em] {{ $isAdmin ? 'text-sky-300' : 'text-slate-300' }}">{{ $isAdmin ? 'ADMINISTRATOR' : 'MEMBER' }}</p>
                            </div>
                        </div>

                        <div class="b-ext" :class="(ext > 0.5 || keep) ? 'b-ext-on' : ''" :style="`height: ${ext}px`">
                        <div class="b-ext-in" x-ref="extIn">
                        <div class="b-mail px-5 sm:px-6 pb-4 sm:pb-5 flex flex-col items-center text-center b-z" style="--zz:0px">
                            <p class="mt-2 max-sm:mt-1 text-xs text-slate-300 break-all max-w-full" style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace">{{ $user->email }}</p>

                            @if ($user->is_team_member)
                                <span class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-semibold text-violet-300 px-2.5 py-1 rounded-md" style="background: rgba(167,139,250,.14); border: 1px solid rgba(167,139,250,.4)">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> TEAM MEMBER
                                </span>
                            @endif
                        </div>

                        <div class="mx-5 sm:mx-6 grid grid-cols-2 gap-4 py-3 border-t border-dashed b-z" style="--zz:10px; border-color: rgba(148,163,184,.3)">
                            <div>
                                <p class="text-[10px] font-semibold tracking-[.16em] text-slate-400">ID NO.</p>
                                <p class="text-sm text-white font-semibold" style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace">{{ $badgeId }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-semibold tracking-[.16em] text-slate-400">SINCE</p>
                                <p class="text-sm text-white font-semibold">{{ $user->created_at->format('j M Y') }}</p>
                            </div>
                        </div>

                        <div class="b-barwrap px-5 sm:px-6 pb-5 sm:pb-6 pt-1 b-z" style="--zz:6px">
                            <div class="b-bars"></div>
                        </div>
                        <button type="button" class="b-close" x-ref="closeRow" @click="close()">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                            Close
                        </button>
                        </div>
                        </div>

                        <div class="b-hint" x-ref="hintRow" :class="ext < 1 ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            Tap for full ID
                        </div>
                        </div>
                    </div>
                    </div>
                    </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center gap-2 mt-4 sm:mt-5">
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

            {{-- photo feedback sits right under the buttons you just used, and fades by itself --}}
            @if (in_array(session('status'), ['photo-updated', 'photo-removed'], true))
                <p x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show" x-transition.opacity.duration.400ms
                   class="mt-3 flex items-center justify-center gap-1.5 text-xs text-emerald-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    {{ session('status') === 'photo-updated' ? 'Profile photo updated.' : 'Profile photo removed.' }}
                </p>
            @endif
            @error('photo')
                <p class="mt-3 text-center text-xs text-red-300">{{ $message }}</p>
            @enderror
        </aside>
        </div></div>

        {{-- RIGHT SIDE --}}
        <div class="min-w-0 w-full max-w-3xl mx-auto xl:max-w-none xl:mx-0 xl:col-start-2 xl:row-start-2">
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
                <button type="button" x-ref="tPreferences" @click="tab = 'preferences'" class="s-tab" :class="tab === 'preferences' ? 's-tab-on' : ''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                    Preferences
                </button>
                <button type="button" x-ref="tAbout" @click="tab = 'about'; if (window.matchMedia('(max-width: 1279px)').matches) { $nextTick(() => $root.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' })) }" class="s-tab" :class="tab === 'about' ? 's-tab-on' : ''">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    About
                </button>
            </div>

            {{-- ABOUT TAB --}}
            <div x-show="tab === 'about'" x-cloak class="space-y-5">
                <style>
                    .ab-hero { position: relative; overflow: hidden; background: radial-gradient(120% 140% at 0% 0%, rgba(56, 189, 248, .22), transparent 55%), radial-gradient(90% 120% at 100% 100%, rgba(99, 102, 241, .22), transparent 55%), linear-gradient(180deg, rgba(24, 40, 76, .72), rgba(14, 25, 50, .72)); }
                    .ab-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .28rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 600; color: #cfe6fb; background: rgba(56, 189, 248, .1); border: 1px solid rgba(56, 189, 248, .3); }
                    .ab-stat { padding: .85rem .75rem; border-radius: .9rem; text-align: center; background: rgba(255, 255, 255, .04); border: 1px solid rgba(148, 163, 184, .16); }
                    .ab-tile { padding: 1rem; border-radius: .95rem; background: rgba(255, 255, 255, .03); border: 1px solid rgba(148, 163, 184, .14); transition: transform .2s ease-out, border-color .2s, background-color .2s; }
                    .ab-tile:hover { transform: translateY(-2px); border-color: rgba(var(--c), .5); background: rgba(var(--c), .06); }
                    .ab-step { position: relative; display: flex; align-items: flex-start; gap: .8rem; padding: 1rem; border-radius: .95rem; background: rgba(255, 255, 255, .03); border: 1px solid rgba(148, 163, 184, .14); }
                    .ab-num { position: absolute; top: .55rem; right: .8rem; font-size: 1.6rem; font-weight: 800; line-height: 1; color: rgba(var(--c), .22); }
                    @media (prefers-reduced-motion: reduce) { .ab-tile { transition: none; } .ab-tile:hover { transform: none; } }
                </style>

                <div class="s-card ab-hero p-6 sm:p-8 text-center">
                    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="" class="w-14 h-14 object-contain mx-auto">
                    <p class="text-[11px] font-semibold tracking-[.2em] text-sky-300 mt-4">WHO WE ARE</p>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white mt-1.5">About PhishCore</h2>
                    <p class="text-sm sm:text-base text-slate-300 mt-3 leading-relaxed max-w-xl mx-auto">Built to help people in Brunei check links, emails, phone numbers, screenshots and files before they trust them.</p>
                    <div class="flex flex-wrap justify-center gap-2 mt-4">
                        <span class="ab-chip">Final year project</span>
                        <span class="ab-chip">Politeknik Brunei</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                        <div class="ab-stat"><p class="text-xl sm:text-2xl font-bold text-white leading-none">7</p><p class="text-[11px] tracking-wider text-slate-400 mt-1.5">DETECTION CHECKS</p></div>
                        <div class="ab-stat"><p class="text-xl sm:text-2xl font-bold text-white leading-none">5</p><p class="text-[11px] tracking-wider text-slate-400 mt-1.5">THINGS YOU CAN SCAN</p></div>
                        <div class="ab-stat"><p class="text-xl sm:text-2xl font-bold text-white leading-none">Free</p><p class="text-[11px] tracking-wider text-slate-400 mt-1.5">TO USE</p></div>
                        <div class="ab-stat"><p class="text-xl sm:text-2xl font-bold text-white leading-none">BN</p><p class="text-[11px] tracking-wider text-slate-400 mt-1.5">MADE IN BRUNEI</p></div>
                    </div>
                </div>

                <div class="s-card p-5 sm:p-6">
                    <h3 class="text-white font-semibold">How it works</h3>
                    <div class="grid md:grid-cols-3 gap-3 mt-3">
                    <div class="ab-step" style="--c:56,189,248">
                        <span class="ab-num">1</span>
                        <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.25 2.25 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z" /></svg></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white">Paste or upload</p>
                            <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">A link, an email, a phone number, a screenshot, or an app or PDF file.</p>
                        </div>
                    </div>
                    <div class="ab-step" style="--c:167,139,250">
                        <span class="ab-num">2</span>
                        <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28zM15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white">We run the checks</p>
                            <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">Automated checks plus what other people have reported.</p>
                        </div>
                    </div>
                    <div class="ab-step" style="--c:52,211,153">
                        <span class="ab-num">3</span>
                        <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white">You get a clear answer</p>
                            <p class="text-xs text-slate-300 mt-0.5 leading-relaxed">A risk level and the reasons behind it, so you know what to do next.</p>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div class="s-card p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="a-ic" style="--c:56,189,248"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg></span>
                            <h3 class="text-white font-semibold">Our mission</h3>
                        </div>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">Online scams are a growing problem in Brunei, from phishing links to scam calls and messages. PhishCore gives anyone a quick, free way to check if something looks like a scam before it is too late.</p>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">It combines automated checks with reports from the people who use it, so a scam one person spots can warn the next.</p>
                    </div>
                    <div class="s-card p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="a-ic" style="--c:244,114,182"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg></span>
                            <h3 class="text-white font-semibold">Our story</h3>
                        </div>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">PhishCore started as a final year project at Politeknik Brunei. Many people around us were falling for scams they could have avoided.</p>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">We wanted something anyone could use, simple enough for a first-time user and still thorough enough to catch convincing phishing attempts.</p>
                    </div>
                </div>

                <div class="s-card p-5 sm:p-6">
                    <h3 class="text-white font-semibold">What we stand for</h3>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                        <div class="ab-tile" style="--c:56,189,248">
                            <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg></span>
                            <p class="text-sm font-semibold text-white mt-3">Protection first</p>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">Every feature is built to keep people safe from scams.</p>
                        </div>
                        <div class="ab-tile" style="--c:52,211,153">
                            <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></span>
                            <p class="text-sm font-semibold text-white mt-3">Honest results</p>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">Each result lists what was checked and why, so you can judge it yourself.</p>
                        </div>
                        <div class="ab-tile" style="--c:251,191,36">
                            <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg></span>
                            <p class="text-sm font-semibold text-white mt-3">Built for Brunei</p>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">Made for local users, with local numbers and scam patterns in mind.</p>
                        </div>
                        <div class="ab-tile" style="--c:167,139,250">
                            <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg></span>
                            <p class="text-sm font-semibold text-white mt-3">Always improving</p>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">New scam patterns reported by the community help us tune the checks.</p>
                        </div>
                        <div class="ab-tile" style="--c:244,114,182">
                            <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg></span>
                            <p class="text-sm font-semibold text-white mt-3">Privacy respected</p>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">Your account details stay yours. The Privacy Policy explains what we keep and why.</p>
                        </div>
                        <div class="ab-tile" style="--c:45,212,191">
                            <span class="a-ic"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></span>
                            <p class="text-sm font-semibold text-white mt-3">Community driven</p>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">Each report you make helps warn other people.</p>
                        </div>
                    </div>
                </div>

                <div class="s-card p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5 min-w-0">
                            <span class="a-ic" style="--c:56,189,248; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg></span>
                            <div class="min-w-0">
                                <h3 class="text-white font-semibold leading-tight">Get in touch</h3>
                                <p class="text-sm text-slate-300 mt-0.5 leading-relaxed">Have a question, feedback, or want to collaborate? We would love to hear from you.</p>
                                <p class="text-sm text-sky-300 mt-1 break-all">phishcorebn@gmail.com</p>
                            </div>
                        </div>
                        <a href="mailto:phishcorebn@gmail.com" class="s-btn s-btn-main shrink-0">Send an email</a>
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 mt-4 pt-4 border-t border-white/10 text-sm">
                        <a href="{{ route('privacy') }}" class="text-slate-300 hover:text-white underline-offset-2 hover:underline">Privacy Policy</a>
                        <a href="{{ route('terms') }}" class="text-slate-300 hover:text-white underline-offset-2 hover:underline">Terms of Service</a>
                    </div>
                </div>
            </div>

            {{-- PREFERENCES TAB: add new app options here as new cards --}}
            <div x-show="tab === 'preferences'" x-cloak class="space-y-5">
                <div class="s-card p-5 sm:p-6">
                    <form method="POST" action="{{ route('profile.preferences.update') }}" x-data="{ on: {{ $user->wantsFloatingChat() ? 'true' : 'false' }} }">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="floating_chat_enabled" :value="on ? 1 : 0">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3.5 min-w-0">
                                <span class="a-ic" style="--c:56,189,248; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12 20.25c4.97 0 9-3.694 9-8.25S16.97 3.75 12 3.75 3 7.444 3 12c0 1.6.496 3.09 1.35 4.35L3.75 20.25l4.2-.9A9.8 9.8 0 0012 20.25z" /></svg></span>
                                <div class="min-w-0">
                                    <h2 class="text-white font-semibold leading-tight">Floating Cora chat</h2>
                                    <p class="text-sm text-slate-300 mt-0.5 leading-relaxed">Show the round Cora button in the corner of every page. Turn it off if it gets in the way. You can still talk to Cora from the AI Chat page.</p>
                                </div>
                            </div>
                            <button type="button" role="switch" :aria-checked="on" aria-label="Floating Cora chat" @click="on = ! on; $nextTick(() => $el.form.requestSubmit())"
                                    class="relative shrink-0 mt-1 w-11 h-6 rounded-full border transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300/70"
                                    :class="on ? 'bg-sky-500 border-sky-300/50' : 'bg-white/10 border-white/20'">
                                <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200" :class="on ? 'translate-x-5' : ''"></span>
                            </button>
                        </div>

                        <noscript><button type="submit" class="s-btn s-btn-main mt-4">Save</button></noscript>

                        @if (session('status') === 'preferences-updated' && session('pref', 'floating_chat_enabled') === 'floating_chat_enabled')
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-emerald-300 mt-4" role="status">Saved.</p>
                        @endif
                    </form>
                </div>

                @php
                    $prefSaved = fn (string $key) => session('status') === 'preferences-updated' && session('pref') === $key;
                    $tabChoices = ['url' => 'URL', 'email' => 'Email', 'phone' => 'Phone', 'screenshot' => 'Screenshot', 'file' => 'File'];
                    $keepChoices = ['forever' => 'Keep them', '90' => '90 days', '30' => '30 days'];
                    $keepNow = $user->scanRetentionDays() === null ? 'forever' : (string) $user->scanRetentionDays();
                @endphp

                {{-- DEFAULT SCAN TYPE --}}
                <div class="s-card p-5 sm:p-6">
                    <form method="POST" action="{{ route('profile.preferences.update') }}">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-start gap-3.5 min-w-0">
                            <span class="a-ic" style="--c:52,211,153; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg></span>
                            <div class="min-w-0">
                                <h2 class="text-white font-semibold leading-tight">Default scan type</h2>
                                <p class="text-sm text-slate-300 mt-0.5 leading-relaxed">The tab that is open when you go to the Scan page. You can still switch to any other tab there.</p>
                            </div>
                        </div>
                        <div class="pf-chips mt-4" role="radiogroup" aria-label="Default scan type">
                            @foreach ($tabChoices as $value => $label)
                                <button type="submit" name="default_scan_tab" value="{{ $value }}" role="radio" aria-checked="{{ $user->defaultScanTab() === $value ? 'true' : 'false' }}" class="pf-chip {{ $user->defaultScanTab() === $value ? 'pf-on' : '' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        @if ($prefSaved('default_scan_tab'))
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-emerald-300 mt-3" role="status">Saved.</p>
                        @endif
                    </form>
                </div>

                {{-- HOW LONG SCANS ARE KEPT --}}
                <div class="s-card p-5 sm:p-6">
                    <form method="POST" action="{{ route('profile.preferences.update') }}">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-start gap-3.5 min-w-0">
                            <span class="a-ic" style="--c:251,146,60; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></span>
                            <div class="min-w-0">
                                <h2 class="text-white font-semibold leading-tight">Keep my scans</h2>
                                <p class="text-sm text-slate-300 mt-0.5 leading-relaxed">Scans older than this are deleted for good every night, together with their results and pictures. Your account stays. Deleted scans can no longer be opened or used when we compare reports.</p>
                            </div>
                        </div>
                        <div class="pf-chips mt-4" role="radiogroup" aria-label="How long scans are kept">
                            @foreach ($keepChoices as $value => $label)
                                <button type="submit" name="scan_retention" value="{{ $value }}" role="radio" aria-checked="{{ $keepNow === $value ? 'true' : 'false' }}" class="pf-chip {{ $keepNow === $value ? 'pf-on' : '' }}"
                                        @if ($value !== 'forever' && $keepNow !== $value) onclick="return confirm('Scans older than {{ $value }} days will be deleted for good tonight, and every night after. Continue?')" @endif>{{ $label }}</button>
                            @endforeach
                        </div>
                        @if ($prefSaved('scan_retention'))
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-emerald-300 mt-3" role="status">Saved.</p>
                        @endif
                    </form>
                </div>
            </div>

            {{-- PROFILE TAB --}}
            <div x-show="tab === 'profile'" x-cloak class="space-y-5">
                <div class="s-card p-5 sm:p-6" x-data="{ n: {{ Js::from(old('name', $user->name)) }}, e: {{ Js::from(old('email', $user->email)) }}, n0: {{ Js::from($user->name) }}, e0: {{ Js::from($user->email) }}, get dirty() { return this.n !== this.n0 || this.e !== this.e0; } }">
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

                <div class="s-card p-5 sm:p-6">
                    <div class="flex items-start gap-3.5 mb-5">
                        <span class="a-ic" style="--c:167,139,250; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18" /></svg></span>
                        <div>
                            <h2 class="text-white font-semibold leading-tight">Your activity</h2>
                            <p class="text-sm text-slate-300 mt-0.5">From the scans you submitted.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                        <div class="s-well flex items-center gap-3.5 px-4 py-3.5" style="--c:56,189,248">
                            <span class="a-ic" style="width:2.4rem; height:2.4rem; border-radius:.75rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg></span>
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-white leading-none tabular-nums">{{ number_format($activity['scans']) }}</p>
                                <p class="text-xs text-slate-300 mt-1.5">Scans submitted</p>
                            </div>
                        </div>
                        <div class="s-well flex items-center gap-3.5 px-4 py-3.5" style="--c:248,113,113">
                            <span class="a-ic" style="width:2.4rem; height:2.4rem; border-radius:.75rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-4.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></span>
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-white leading-none tabular-nums">{{ number_format($activity['phishing']) }}</p>
                                <p class="text-xs text-slate-300 mt-1.5">Phishing detected</p>
                            </div>
                        </div>
                        <div class="s-well flex items-center gap-3.5 px-4 py-3.5" style="--c:52,211,153">
                            <span class="a-ic" style="width:2.4rem; height:2.4rem; border-radius:.75rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-white leading-none tabular-nums">{{ number_format($activity['clean']) }}</p>
                                <p class="text-xs text-slate-300 mt-1.5">Clean results</p>
                            </div>
                        </div>
                        <div class="s-well flex items-center gap-3.5 px-4 py-3.5" style="--c:167,139,250">
                            <span class="a-ic" style="width:2.4rem; height:2.4rem; border-radius:.75rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-white leading-none tabular-nums">{{ $activity['last_scan'] ? \Illuminate\Support\Carbon::parse($activity['last_scan'])->diffForHumans(['short' => true]) : 'None yet' }}</p>
                                <p class="text-xs text-slate-300 mt-1.5">Last scan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECURITY TAB --}}
            <div x-show="tab === 'security'" x-cloak class="space-y-5">
                <div class="grid lg:grid-cols-3 gap-5 items-stretch" x-data="{ pw: '', cf: '', get r() { const p = this.pw; return { len: p.length >= 12, mix: /[a-z]/.test(p) && /[A-Z]/.test(p), num: /\d/.test(p), sym: /[^A-Za-z0-9]/.test(p), match: p.length > 0 && p === this.cf }; }, get score() { return [this.r.len, this.r.mix, this.r.num, this.r.sym].filter(Boolean).length; } }">
                    <div class="s-card p-5 sm:p-6 lg:col-span-2">
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

                    <div class="s-card p-5 sm:p-6 flex flex-col">
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

                {{-- bottom row: signed-in devices (only when sessions are stored in the database) next to the danger zone, so the tab stays short --}}
                <div class="grid lg:grid-cols-3 gap-5 items-stretch">
                @if ($devices !== null)
                    <div class="s-card p-5 sm:p-6 lg:col-span-2">
                        <div class="flex items-start gap-3.5 mb-4">
                            <span class="a-ic" style="--c:56,189,248; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg></span>
                            <div>
                                <h2 class="text-white font-semibold leading-tight">Where you are signed in</h2>
                                <p class="text-sm text-slate-300 mt-0.5">Not yours? Change your password.</p>
                            </div>
                        </div>
                        @forelse (array_slice($devices, 0, 3) as $device)
                            <div class="s-kv" style="--c:56,189,248">
                                <span class="a-ic" style="width:2.1rem; height:2.1rem; border-radius:.65rem"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $device['mobile'] ? 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3' : 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25' }}" /></svg></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-slate-100 truncate">{{ $device['label'] }}</p>
                                    <p class="text-xs text-slate-400 font-mono truncate">{{ $device['ip'] ?? 'Unknown address' }}</p>
                                </div>
                                @if ($device['current'])
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-300"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>This device</span>
                                @else
                                    <span class="text-xs text-slate-400">Active {{ $device['last_active']->diffForHumans() }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-300">No active sessions found.</p>
                        @endforelse
                        @if (count($devices) > 3)
                            <p class="text-xs text-slate-400 pt-3 border-t border-white/10">and {{ count($devices) - 3 }} more {{ count($devices) - 3 === 1 ? 'device' : 'devices' }}</p>
                        @endif
                    </div>
                @endif

                {{-- Danger zone --}}
                <div class="s-card p-5 sm:p-6 flex flex-col {{ $devices === null ? 'lg:col-span-3' : '' }}" style="border-color: rgba(248,113,113,.35)" x-data="{ confirmingDeletion: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
                    <div class="{{ $devices !== null ? 'flex flex-col flex-1 gap-4' : 'flex items-center justify-between gap-4 flex-wrap' }}">
                        <div class="{{ $devices !== null ? '' : 'flex items-start gap-3.5' }}">
                            <div class="flex items-center gap-3.5">
                                <span class="a-ic" style="--c:248,113,113; width:2.6rem; height:2.6rem; border-radius:.8rem"><svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></span>
                                <h2 class="text-red-300 font-semibold leading-tight">Delete account</h2>
                            </div>
                            <p class="text-sm text-slate-300 leading-relaxed {{ $devices !== null ? 'mt-3' : 'mt-2 max-w-xl' }}">Permanently removes your account and all of its data. This cannot be undone.</p>
                        </div>
                        <button type="button" @click="confirmingDeletion = true" class="s-btn s-btn-danger shrink-0 {{ $devices !== null ? 'w-full mt-auto' : '' }}">Delete account</button>
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
    </div>
</x-layouts.dashboard>