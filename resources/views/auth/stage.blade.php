{{--
    Login + Register share one animated stage. The floating glass card glides between the
    left (sign in) and the right (create account) while the side content swaps.
    GET /login and GET /register render this same view; only the starting $mode differs,
    so every URL, redirect and validation error keeps working with or without JavaScript.
--}}
@php
    $isLogin = $mode === 'login';
    $isMid = in_array($mode, ['forgot', 'reset'], true);
    $titles = ['login' => 'Sign in', 'register' => 'Create account', 'forgot' => 'Forgot password', 'reset' => 'Reset password'];
    $check = 'M4.5 12.75l6 6 9-13.5';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titles[$mode] }} · {{ config('app.name', 'PhishCore') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function () {
            var n = navigator, c = n.connection || {};
            var weak = (n.hardwareConcurrency && n.hardwareConcurrency <= 4) || (n.deviceMemory && n.deviceMemory <= 4) || c.saveData ||
                window.matchMedia('(max-width: 1023px)').matches || window.matchMedia('(pointer: coarse)').matches;
            if (weak) document.documentElement.classList.add('as-lite');
        })();
    </script>
    <style>
        :root { --as-m: clamp(1.5rem, 5vw, 6.5rem); --as-w: clamp(28rem, 30vw, 34rem); --as-pw: clamp(22rem, 31vw, 40rem); --as-gap: clamp(4rem, 9vw, 11rem); --as-mid: clamp(1.25rem, 3vw, 3rem); --as-L: max(var(--as-m), calc((100vw - var(--as-w) - var(--as-gap) - var(--as-pw)) / 2)); --as-ease: cubic-bezier(.65, 0, .25, 1); }
        [x-cloak] { display: none !important; }

        .as-stage { position: relative; z-index: 10; min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; }
        .as-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.5rem 1.25rem 0; }
        .as-back { display: inline-flex; align-items: center; gap: .5rem; font-size: .875rem; font-weight: 600; color: #7dd3fc; transition: color .2s; }
        .as-back:hover { color: #e0f2fe; }
        .as-main { position: relative; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.5rem; padding: 1.25rem 1.25rem calc(2rem + env(safe-area-inset-bottom)); }

        /* ----- the floating card ----- */
        .as-card-wrap { position: relative; width: 100%; max-width: var(--as-w); }
        .as-card {
            position: relative; overflow: hidden; border-radius: 2rem;
            background: linear-gradient(165deg, rgba(255,255,255,.14), rgba(255,255,255,.05) 55%, rgba(255,255,255,.08));
            border: 1px solid rgba(255,255,255,.2);
            box-shadow: 0 50px 100px -30px rgba(0,0,0,.9), 0 0 80px -20px rgba(56,189,248,.3), inset 0 1px 0 rgba(255,255,255,.3), inset 0 -34px 70px -34px rgba(56,189,248,.16);
            -webkit-backdrop-filter: blur(28px) saturate(160%); backdrop-filter: blur(28px) saturate(160%);
            transition: height .9s var(--as-ease);
        }
        .as-depth { transform-origin: 50% 50%; }
        /* arriving from another page: the card and the side content come forward out of the background */
        .as-depth, .as-pin { animation: as-enter .8s cubic-bezier(.2, .9, .3, 1) both; }
        .as-pin { animation-delay: .12s; }
        @keyframes as-enter { from { opacity: 0; transform: scale(.88); filter: blur(8px); } to { opacity: 1; transform: none; filter: none; } }
        .as-glow { display: none; }
        .as-note { display: flex; align-items: flex-start; gap: .75rem; padding: .8rem 1rem; border-radius: .9rem; background: rgba(3,9,24,.45); border: 1px solid rgba(255,255,255,.1); font-size: .78rem; line-height: 1.45; color: #a9b8cd; }

        .as-form { position: absolute; top: 0; left: 0; right: 0; padding: 2.25rem 1.5rem 2rem; opacity: 0; visibility: hidden; transform: scale(.94); filter: blur(5px);
            transition: opacity .35s ease, transform .5s ease, filter .4s ease, visibility 0s linear .35s; }
        .as-form.is-on { position: relative; opacity: 1; visibility: visible; transform: none; filter: none;
            transition: opacity .4s ease .4s, transform .6s cubic-bezier(.2,.9,.3,1) .4s, filter .45s ease .4s, visibility 0s; }
        @media (min-width: 640px) { .as-form { padding: 3rem 3rem 2.75rem; } }

        /* phones and tablets: one centred card, form fields sized for thumbs (16px text stops iOS zooming in) */
        .as-form input[type=checkbox] { width: 1.15rem; height: 1.15rem; flex-shrink: 0; }
        @media (max-width: 1023px) {
            .as-input { font-size: 1rem; }
            .as-card { border-radius: 1.6rem; }
            .as-head { padding-top: calc(1.25rem + env(safe-area-inset-top)); }
        }

        .as-icon-tile { width: 3.25rem; height: 3.25rem; flex-shrink: 0; display: grid; place-items: center; border-radius: 1.05rem; color: #e0f2fe;
            background: linear-gradient(160deg, rgba(125,211,252,.3), rgba(14,165,233,.06)); border: 1px solid rgba(125,211,252,.42);
            box-shadow: 0 10px 26px -8px rgba(14,165,233,.6), inset 0 1px 0 rgba(255,255,255,.35); }
        .as-tight { padding-top: 2.4rem; padding-bottom: 2.2rem; }
        .as-tight .as-input { height: 3.1rem; }
        .as-tight .as-btn { height: 3.3rem; }
        .as-title { font-size: 2.1rem; line-height: 1.1; font-weight: 800; color: #fff; letter-spacing: -.01em; }
        .as-sub { margin-top: .4rem; font-size: .98rem; color: #9fb4d0; }

        .as-field { display: flex; flex-direction: column; gap: .45rem; }
        .as-label { font-size: .7rem; font-weight: 700; letter-spacing: .09em; color: #a9b8cd; text-transform: uppercase; }
        .as-ico { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.1rem; height: 1.1rem; color: #8193ad; pointer-events: none; }
        .as-input { width: 100%; height: 3.4rem; padding: 0 1rem 0 3rem; border-radius: .9rem; border: 1px solid rgba(255,255,255,.14);
            background: rgba(3,9,24,.55); color: #e2e8f0; font-size: .95rem; font-weight: 500; box-shadow: inset 0 2px 6px rgba(0,0,0,.35);
            transition: border-color .2s, box-shadow .2s; }
        .as-input::placeholder { color: #7f91ab; }
        .as-input:focus { outline: none; border-color: rgba(125,211,252,.55); box-shadow: inset 0 2px 6px rgba(0,0,0,.35), 0 0 0 3px rgba(56,189,248,.14); }
        .as-input-eye { padding-right: 3rem; }
        .as-eye { position: absolute; right: .45rem; top: 50%; transform: translateY(-50%); width: 2.25rem; height: 2.25rem; display: grid; place-items: center; border-radius: .65rem; color: #94a3b8; transition: color .2s; }
        .as-eye:hover { color: #e2e8f0; }
        .as-err { font-size: .75rem; color: #f87171; }

        .as-btn { display: flex; align-items: center; justify-content: center; gap: .6rem; width: 100%; height: 3.6rem; border-radius: 1.05rem; font-size: 1rem; font-weight: 800; color: #04111f; cursor: pointer;
            background: linear-gradient(180deg, #7dd3fc 0%, #38bdf8 45%, #0ea5e9 100%);
            box-shadow: 0 14px 34px -10px rgba(14,165,233,.85), inset 0 1px 0 rgba(255,255,255,.6), inset 0 -3px 8px rgba(2,132,199,.45);
            transition: filter .2s, transform .2s, opacity .2s; }
        .as-btn:hover { filter: brightness(1.07); }
        .as-btn:active { transform: scale(.98); }
        .as-btn:disabled { opacity: .45; cursor: not-allowed; filter: grayscale(.4); }
        .as-btn-ghost { display: flex; align-items: center; justify-content: center; width: 100%; height: 3.3rem; border-radius: 1.05rem; font-size: 1rem; font-weight: 700; color: #e2e8f0;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.18); box-shadow: inset 0 1px 0 rgba(255,255,255,.12); transition: background-color .2s, border-color .2s; }
        .as-btn-ghost:hover { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.28); }
        .as-link { color: #7dd3fc; font-weight: 600; transition: color .2s; }
        .as-link:hover { color: #e0f2fe; }

        /* ----- side content (large screens only) ----- */
        .as-panel { display: none; }
        .as-stat { text-align: center; padding: .9rem .25rem; border-radius: 1.1rem; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); }
        .as-glass { background: linear-gradient(165deg, rgba(255,255,255,.1), rgba(255,255,255,.035)); border: 1px solid rgba(255,255,255,.14);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.16); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); }
        .as-chip { position: absolute; display: none; align-items: center; gap: .5rem; padding: .55rem .9rem; border-radius: .9rem; font-size: .78rem; font-weight: 700; white-space: nowrap;
            }
        .as-chip i { width: .45rem; height: .45rem; border-radius: 9999px; background: currentColor; }
        .as-chip-r { color: #fecdd3; background: rgba(244,63,94,.16); border: 1px solid rgba(251,113,133,.45); box-shadow: 0 14px 30px -12px rgba(244,63,94,.5); }
        .as-chip-a { color: #fde68a; background: rgba(245,158,11,.16); border: 1px solid rgba(251,191,36,.45); box-shadow: 0 14px 30px -12px rgba(245,158,11,.5); }
        .as-chip-b { color: #bae6fd; background: rgba(14,165,233,.16); border: 1px solid rgba(125,211,252,.45); box-shadow: 0 14px 30px -12px rgba(14,165,233,.5); }
        .as-orb { width: 2.9rem; height: 2.9rem; flex-shrink: 0; display: grid; place-items: center; border-radius: 9999px; color: #7dd3fc;
            background: radial-gradient(circle at 30% 25%, rgba(125,211,252,.4), rgba(14,165,233,.12)); border: 1px solid rgba(125,211,252,.5); box-shadow: 0 0 24px rgba(56,189,248,.4); }
        .as-phil { display: block; width: 5rem; height: auto; image-rendering: pixelated; filter: drop-shadow(0 0 14px rgba(56,189,248,.7)); animation: as-bob 4.6s ease-in-out infinite; }

        @keyframes as-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
        @keyframes as-glow { 0%, 100% { opacity: .55; transform: scaleX(1); } 50% { opacity: .45; transform: scaleX(.96); } }
        @keyframes as-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
        @keyframes as-in { from { opacity: 0; transform: translateY(16px) scale(.985); } to { opacity: 1; transform: none; } }
        .as-in { animation: as-in .7s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }

        @media (min-width: 1024px) {
            .as-stage { height: 100vh; height: 100dvh; overflow: hidden; }
            .as-head { position: absolute; top: 0; left: 0; right: 0; z-index: 5; padding: 1.75rem var(--as-m) 0; }
            .as-main { position: absolute; inset: 3.75rem 0 0 0; display: block; min-height: 0; padding: 0; }
            .as-card-wrap {
                position: absolute; top: 50%; left: var(--as-L); width: var(--as-w); max-width: none;
                --ry: 4deg; --tx: 0px;
                transform: translate3d(var(--tx), -50%, 0) perspective(1600px) rotateY(var(--ry)) rotateX(1.5deg);
                transition: transform .9s var(--as-ease);
                will-change: transform;
            }
            .as-card-wrap.is-reg { --tx: calc(var(--as-pw) + var(--as-gap)); --ry: -4deg; }
            /* forgot / reset: the card and the info box sit together in the middle */
            .as-card-wrap.is-mid { --tx: calc(50vw - var(--as-w) - var(--as-mid) - var(--as-L)); --ry: 3deg; }
            .as-panel-recover { left: calc(50vw + var(--as-mid)); width: var(--as-w); }
            .as-glow { display: block; position: absolute; left: 14%; right: 14%; bottom: -2.6rem; height: 1.6rem; border-radius: 50%;
                background: radial-gradient(ellipse at center, rgba(56,189,248,.55), transparent 70%); filter: blur(10px); animation: as-glow 8s ease-in-out infinite; }

            .as-panel { display: flex; flex-direction: column; justify-content: center; position: absolute; top: 0; bottom: 0; opacity: 0; visibility: hidden; transform: scale(.9); filter: blur(8px);
                transition: opacity .45s ease, transform .6s ease, filter .5s ease, visibility 0s linear .5s; }
            .as-panel.is-on { opacity: 1; visibility: visible; transform: none; filter: none; transition: opacity .5s ease .45s, transform .85s cubic-bezier(.2,.9,.3,1) .4s, filter .6s ease .4s, visibility 0s; }
            /* login: content on the right of the card */
            .as-panel-login { left: calc(var(--as-L) + var(--as-w) + var(--as-gap)); width: var(--as-pw); }
            /* register: content on the left of the card */
            .as-panel-join { left: var(--as-L); width: var(--as-pw); }
            /* centre the content between the card and the screen edge so both states feel balanced */
            .as-panel { align-items: center;  }
            /* the side content is exactly as tall as the card (--as-ch is measured in JS) and spreads its blocks evenly */
            .as-pin { width: 100%; min-height: var(--as-h-login, 40rem); display: flex; flex-direction: column; justify-content: space-between; gap: 1.25rem; }
            .as-panel-join .as-pin { min-height: var(--as-h-register, 40rem); }
            .as-panel-recover .as-pin { min-height: var(--as-h-reset, var(--as-h-forgot, 40rem)); }
            .as-pin > * { margin: 0 !important; }
            .as-pin .as-rec { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
            .as-h { font-size: clamp(1.7rem, 2.5vw, 2.6rem); line-height: 1.06; font-weight: 800; color: #fff; letter-spacing: -.025em; }
            .as-h span { color: #38bdf8; }
        }
        /* LITE MODE (phones, tablets, low-memory / few-core devices, data saver): no live blur, no idle animation,
           transitions use only opacity + scale, which every GPU can do cheaply */
        .as-lite .as-card, .as-lite .as-glass { -webkit-backdrop-filter: none; backdrop-filter: none; }
        .as-lite .as-card { background: linear-gradient(165deg, rgba(34,66,122,.94), rgba(13,30,68,.96)); box-shadow: 0 26px 50px -26px rgba(0,0,0,.85), inset 0 1px 0 rgba(255,255,255,.22); }
        .as-lite .as-glass { background: rgba(20,38,80,.85); box-shadow: none; }
        .as-lite .as-form, .as-lite .as-form.is-on, .as-lite .as-panel, .as-lite .as-panel.is-on { filter: none !important; }
        .as-lite .as-depth, .as-lite .as-pin { animation-name: as-enter-lite; }
        .as-lite .as-phil, .as-lite .as-chip, .as-lite .as-shield b, .as-lite .as-glow, .as-lite .as-float { animation: none !important; }
        .as-lite .as-glow { display: none !important; }
        .as-lite .hero-aurora span { animation: none !important; will-change: auto; }
        @keyframes as-enter-lite { from { opacity: 0; transform: scale(.94); } to { opacity: 1; transform: none; } }

        /* recovery box */
        .as-rec { position: relative; overflow: hidden; border-radius: 2rem; padding: 2.5rem 2.25rem 2rem; text-align: center; }
        .as-rec::before { content: ''; position: absolute; inset: -40% -20% auto -20%; height: 70%; background: radial-gradient(ellipse at center, rgba(56,189,248,.22), transparent 65%); pointer-events: none; }
        .as-shield { position: relative; width: 5.5rem; height: 5.5rem; margin: 0 auto 1.5rem; display: grid; place-items: center; color: #e0f2fe; }
        .as-shield > svg { position: relative; z-index: 2; width: 2.9rem; height: 2.9rem; filter: drop-shadow(0 0 14px rgba(56,189,248,.8)); }
        .as-shield i { position: absolute; inset: 0; border-radius: 9999px; border: 1px solid rgba(125,211,252,.45); background: radial-gradient(circle at 30% 25%, rgba(125,211,252,.28), rgba(14,165,233,.06)); }
        .as-shield b { position: absolute; inset: -.55rem; border-radius: 9999px; border: 1px solid rgba(125,211,252,.3); animation: as-ring 3.4s ease-out infinite; }
        .as-shield b + b { animation-delay: 1.7s; }
        @keyframes as-ring { 0% { transform: scale(.82); opacity: .9; } 100% { transform: scale(1.5); opacity: 0; } }
        .as-steps { position: relative; display: flex; flex-direction: column; gap: 1.15rem; margin: 1.75rem 0 1.5rem; text-align: left; list-style: none; padding: 0; }
        .as-step { position: relative; display: flex; align-items: flex-start; gap: 1rem; opacity: .5; transition: opacity .4s; }
        .as-step:not(:last-child)::after { content: ''; position: absolute; left: 1.1rem; top: 2.5rem; bottom: -1.15rem; width: 2px; margin-left: -1px; background: rgba(255,255,255,.12); }
        .as-step .n { width: 2.2rem; height: 2.2rem; flex-shrink: 0; display: grid; place-items: center; border-radius: 9999px; font-size: .85rem; font-weight: 800; color: #94a3b8; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.16); transition: all .4s; }
        .as-step .n svg { display: none; width: 1rem; height: 1rem; }
        .as-step h3 { font-size: .98rem; font-weight: 700; color: #fff; line-height: 1.3; }
        .as-step p { margin-top: .15rem; font-size: .82rem; line-height: 1.45; color: #a9b8cd; }
        .as-step.is-act { opacity: 1; }
        .as-step.is-act .n { color: #04111f; background: linear-gradient(180deg, #7dd3fc, #0ea5e9); border-color: transparent; box-shadow: 0 0 0 4px rgba(56,189,248,.18), 0 8px 20px -6px rgba(14,165,233,.8); }
        .as-step.is-done { opacity: .85; }
        .as-step.is-done .n { color: #04111f; background: #34d399; border-color: transparent; }
        .as-step.is-done .n span { display: none; }
        .as-step.is-done .n svg { display: block; }
        .as-badges { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; }
        .as-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .38rem .62rem; white-space: nowrap; border-radius: 9999px; font-size: .68rem; font-weight: 700; color: #bae6fd; background: rgba(14,165,233,.12); border: 1px solid rgba(125,211,252,.3); }
        .as-badge i { width: .4rem; height: .4rem; border-radius: 9999px; background: #38bdf8; }
        .as-say { position: relative; padding: .8rem 1rem; font-family: 'Press Start 2P', 'Courier New', ui-monospace, monospace; font-size: .62rem; line-height: 1.8; color: #0b1220; background: #e0f2fe; border: 2px solid #7dd3fc; box-shadow: 3px 3px 0 rgba(0,0,0,.35); }
        .as-lead { margin: 1rem 0 1.75rem; max-width: 28rem; font-size: .95rem; line-height: 1.6; color: #94a3b8; }
        .as-gauge { width: 200px; height: auto; }
        @media (min-width: 1280px) { .as-chip { display: inline-flex; } }

        /* shorter laptop screens: tighten everything so nothing needs scrolling */
        @media (min-width: 1024px) and (max-height: 820px) {
            .as-form { padding: 1.5rem 2rem 1.25rem; }
            .as-form form > * + * { margin-top: .7rem !important; }
            .as-input { height: 2.7rem; }
            .as-btn { height: 2.9rem; }
            .as-btn-ghost { height: 2.7rem; }
            .as-title { font-size: 1.6rem; }
            .as-sub { font-size: .8rem; }
            .as-icon-tile { width: 2.9rem; height: 2.9rem; }
            .as-lead { margin: .75rem 0 1.1rem; }
            .as-gauge { width: 150px; }
            .as-stat { padding: .6rem .25rem; }
            .as-row { padding-top: .6rem; padding-bottom: .6rem; }
            .as-chip { display: none !important; }
            .as-rec { padding: 1.75rem 2rem 1.5rem; }
            .as-shield { width: 4.2rem; height: 4.2rem; margin-bottom: 1rem; }
            .as-shield > svg { width: 2.2rem; height: 2.2rem; }
            .as-steps { margin: 1.1rem 0 1rem; gap: .85rem; }
            .as-h { font-size: clamp(1.6rem, 2.3vw, 2.2rem); }
        }
        @media (min-width: 1024px) and (max-height: 700px) { .as-stage { overflow-y: auto; } }

        @media (prefers-reduced-motion: reduce) {
            .as-pin, .as-card, .as-depth, .as-glow, .as-phil, .as-in, .as-chip, .as-float, .as-shield b { animation: none !important; }
            .as-card, .as-card-wrap, .as-form, .as-form.is-on, .as-panel, .as-panel.is-on { transition-duration: .01s !important; transition-delay: 0s !important; }
        }

        @media (max-width: 639px) {
            /* phones: a compact card so even the register form fits without much scrolling */
            .as-main { padding: .6rem .8rem calc(1.25rem + env(safe-area-inset-bottom)); }
            .as-head { padding-left: .9rem; padding-right: .9rem; padding-top: calc(.9rem + env(safe-area-inset-top)); }
            .as-head img { width: 2rem; height: 2rem; }
            .as-card { border-radius: 1.35rem; }
            .as-form, .as-form.as-tight { padding: 1.35rem 1.15rem 1.2rem; }
            .as-form .mb-7, .as-form .mb-6 { margin-bottom: 1rem; }
            .as-title { font-size: 1.4rem; }
            .as-sub { font-size: .84rem; margin-top: .25rem; }
            .as-form form > * + * { margin-top: .8rem !important; }
            .as-field { gap: .35rem; }
            .as-label { font-size: .62rem; }
            .as-input, .as-tight .as-input { height: 2.8rem; padding-left: 2.6rem; border-radius: .8rem; font-size: 1rem; }
            .as-ico { left: .85rem; width: 1rem; height: 1rem; }
            .as-input-eye { padding-right: 2.6rem; }
            .as-eye { width: 2rem; height: 2rem; right: .35rem; }
            .as-btn, .as-tight .as-btn { height: 2.9rem; border-radius: .9rem; font-size: .92rem; }
            .as-btn-ghost { height: 2.7rem; border-radius: .9rem; font-size: .9rem; }
            .as-form .text-sm { font-size: .8rem; }
            .as-note { font-size: .7rem; padding: .6rem .75rem; gap: .55rem; }
            .as-form input[type=checkbox] { width: 1.05rem; height: 1.05rem; }
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">

    <div class="hero-aurora" aria-hidden="true"><span></span><span></span><span></span><span></span></div>

    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><symbol id="as-phil" viewBox="0 0 17 9"><rect x="8" y="0" width="2" height="1" fill="#0ea5e9"/><rect x="7" y="1" width="2" height="1" fill="#0ea5e9"/><rect x="9" y="1" width="3" height="1" fill="#38bdf8"/><rect x="0" y="2" width="1" height="1" fill="#0ea5e9"/><rect x="6" y="2" width="8" height="1" fill="#38bdf8"/><rect x="0" y="3" width="2" height="1" fill="#0ea5e9"/><rect x="5" y="3" width="3" height="1" fill="#38bdf8"/><rect x="8" y="3" width="6" height="1" fill="#0b1220"/><rect x="14" y="3" width="1" height="1" fill="#38bdf8"/><rect x="0" y="4" width="3" height="1" fill="#0ea5e9"/><rect x="4" y="4" width="4" height="1" fill="#38bdf8"/><rect x="8" y="4" width="1" height="1" fill="#0b1220"/><rect x="9" y="4" width="1" height="1" fill="#e0f2fe"/><rect x="10" y="4" width="4" height="1" fill="#0b1220"/><rect x="14" y="4" width="2" height="1" fill="#38bdf8"/><rect x="0" y="5" width="2" height="1" fill="#0ea5e9"/><rect x="4" y="5" width="12" height="1" fill="#38bdf8"/><rect x="16" y="5" width="1" height="1" fill="#0b1220"/><rect x="0" y="6" width="1" height="1" fill="#0ea5e9"/><rect x="4" y="6" width="1" height="1" fill="#38bdf8"/><rect x="5" y="6" width="10" height="1" fill="#bae6fd"/><rect x="15" y="6" width="1" height="1" fill="#38bdf8"/><rect x="4" y="7" width="2" height="1" fill="#38bdf8"/><rect x="6" y="7" width="7" height="1" fill="#bae6fd"/><rect x="13" y="7" width="2" height="1" fill="#38bdf8"/><rect x="6" y="8" width="6" height="1" fill="#38bdf8"/></symbol></svg>

    <div class="as-stage"
         x-data="{
            mode: @js($mode),
            urls: @js(['login' => route('login'), 'register' => route('register'), 'forgot' => route('password.request')]),
            titles: @js($titles),
            get stepNow() { return this.mode === 'reset' ? 3 : 1; },
            showPassword: false, showRegPassword: false, showConfirm: false, showResetPassword: false, showResetConfirm: false, agreed: false, busy: false,
            moving: false,
            syncH() {
                ['login', 'register', 'forgot', 'reset'].forEach((k) => {
                    const f = this.$refs[k];
                    if (f) this.$root.style.setProperty('--as-h-' + k, f.offsetHeight + 'px');
                });
            },
            setMode(m, push = true) {
                if (m === this.mode) return;
                const card = this.$refs.card;
                card.style.height = card.offsetHeight + 'px';
                this.mode = m;
                this.moving = true;
                setTimeout(() => { this.moving = false; }, 1000);
                this.$nextTick(() => this.syncH());
                if (push) history.pushState({ mode: m }, '', this.urls[m]);
                document.title = this.titles[m] + ' · {{ config('app.name', 'PhishCore') }}';
                this.$nextTick(() => {
                    card.style.height = this.$refs[m].offsetHeight + 'px';
                    setTimeout(() => { card.style.height = ''; }, 950);
                    if (window.matchMedia('(min-width: 1024px)').matches) setTimeout(() => { const f = this.$refs[m + 'First']; if (f) f.focus({ preventScroll: true }); }, 650);
                });
            },
         }"
         x-init="
            window.addEventListener('popstate', () => {
                if (mode === 'reset') return;
                const path = location.pathname.replace(/\/+$/, '');
                setMode(path.endsWith('/register') ? 'register' : (path.endsWith('/forgot-password') ? 'forgot' : 'login'), false);
            });
            window.addEventListener('pageshow', (e) => { if (e.persisted) busy = false; });
            $nextTick(() => { syncH(); const f = $refs[mode + 'First']; if (f && window.matchMedia('(min-width: 1024px)').matches) f.focus({ preventScroll: true }); });
            window.addEventListener('resize', () => syncH());
            if (window.ResizeObserver) { const ro = new ResizeObserver(() => syncH()); ['login','register','forgot','reset'].forEach(k => $refs[k] && ro.observe($refs[k])); }
         ">

        <header class="as-head">
            <a href="{{ route('welcome') }}" class="as-in inline-flex items-center gap-2.5">
                <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-9 h-9 object-contain">
                <span class="leading-tight">
                    <span class="block font-bold text-white">PhishCore</span>
                    <span class="block text-[11px] font-medium tracking-wide text-sky-300/90 mt-0.5">DETECTION PLATFORM</span>
                </span>
            </a>
            <a href="{{ route('welcome') }}" class="as-back as-in" style="--d:.1s">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Back to home
            </a>
        </header>

        <main class="as-main">

            {{-- ===== SIGN-IN SIDE CONTENT (right of the card) ===== --}}
            <section class="as-panel as-panel-login {{ $isLogin ? 'is-on' : '' }}" :class="{ 'is-on': mode === 'login' }" :aria-hidden="mode !== 'login'">
                <div class="as-pin">
                <div>
                    <h2 class="as-h">Check it before <span>you click.</span></h2>
                    <p class="as-lead" style="margin-bottom:0">Scan links, emails, phone numbers, screenshots and files in one place, and get a clear answer in seconds.</p>
                </div>

                <div class="relative w-full">
                    <div class="as-glass rounded-[1.6rem] px-6 pt-5 pb-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold tracking-[.14em] text-slate-300">LIVE SCAN PREVIEW</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-300"><i class="w-1.5 h-1.5 rounded-full bg-rose-400 shadow-[0_0_10px_#fb7185]"></i>Phishing</span>
                        </div>
                        <div class="flex items-center gap-2.5 h-10 px-3.5 rounded-xl bg-[rgba(3,9,24,.6)] border border-white/10 font-mono text-[12.5px] text-slate-300">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
                            https://suspicious-example.com
                        </div>
                        <div class="flex flex-col items-center mt-2">
                            <svg class="as-gauge" viewBox="0 0 260 150" aria-hidden="true">
                                <defs><linearGradient id="as-gauge" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fbbf24"/><stop offset="1" stop-color="#fb7185"/></linearGradient></defs>
                                <path d="M 35 125 A 95 95 0 0 1 225 125" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="14" stroke-linecap="round"/>
                                <path d="M 35 125 A 95 95 0 0 1 225 125" fill="none" stroke="url(#as-gauge)" stroke-width="14" stroke-linecap="round" stroke-dasharray="274 299"/>
                                <text x="130" y="108" text-anchor="middle" font-weight="800" font-size="56" fill="#fb7185">92</text>
                                <text x="130" y="132" text-anchor="middle" font-weight="600" font-size="12" letter-spacing="2" fill="#94a3b8">RISK SCORE</text>
                            </svg>
                            <p class="text-[12.5px] font-extrabold tracking-wider text-rose-300">HIGH RISK &mdash; PHISHING DETECTED</p>
                        </div>
                    </div>
                    <span class="as-chip as-chip-r as-float" style="left:-3.5rem; top:2.5rem; animation: as-float 6s ease-in-out -1s infinite;"><i></i>SSL invalid</span>
                    <span class="as-chip as-chip-a" style="right:-2.5rem; top:6rem; animation: as-float 7s ease-in-out -3s infinite;"><i></i>Domain under 7 days</span>
                    <span class="as-chip as-chip-r" style="left:-2rem; bottom:4rem; animation: as-float 7s ease-in-out -5s infinite;"><i></i>Blacklisted</span>
                    <span class="as-chip as-chip-b" style="right:-1.5rem; bottom:3rem; animation: as-float 6s ease-in-out -2s infinite;"><i></i>Scan done in seconds</span>
                </div>

                <div class="flex items-center gap-3 mt-5 w-full">
                    <span class="as-orb">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.4l8.4 3.3v6.3c0 4.9-3.5 8.6-8.4 10-4.9-1.4-8.4-5.1-8.4-10V5.7L12 2.4z"/><ellipse cx="9.2" cy="10.4" rx="1.1" ry="1.55" fill="currentColor" stroke="none"/><ellipse cx="14.8" cy="10.4" rx="1.1" ry="1.55" fill="currentColor" stroke="none"/><path d="M9.7 14.3q2.3 1.9 4.6 0" stroke-width="1.5"/></svg>
                    </span>
                    <p class="px-4 py-2.5 rounded-[6px_18px_18px_18px] text-[13px] leading-snug text-slate-200 bg-white/[0.07] border border-white/[0.14]">Cora: This link looks like a scam. Do not open it.</p>
                </div>

                {{-- live figures (same definitions as the landing page) --}}
                <div class="grid grid-cols-3 gap-3 mt-6 w-full">
                    @foreach ([[number_format($guestStats['scans']), 'SCANS COMPLETED'], [number_format($guestStats['threats']), 'THREATS DETECTED'], [$guestStats['avgSeconds'] > 0 ? $guestStats['avgSeconds'] . 's' : '-', 'AVG SCAN TIME']] as $stat)
                        <div class="as-stat">
                            <p class="text-xl font-bold text-sky-300 tabular-nums">{{ $stat[0] }}</p>
                            <p class="text-[10px] tracking-wider text-slate-300 mt-1">{{ $stat[1] }}</p>
                        </div>
                    @endforeach
                </div>
                </div>
            </section>

            {{-- ===== CREATE-ACCOUNT SIDE CONTENT (left of the card) ===== --}}
            <section class="as-panel as-panel-join {{ $mode === 'register' ? 'is-on' : '' }}" :class="{ 'is-on': mode === 'register' }" :aria-hidden="mode !== 'register'">
                <div class="as-pin">
                <div>
                <p class="mb-3 text-xs font-bold tracking-[.16em] text-sky-300">FREE FOR EVERYONE IN BRUNEI</p>
                <h2 class="as-h">Join the <span>PhishCore</span> platform.</h2>
                <p class="as-lead" style="margin-bottom:0">Help protect your digital life by checking suspicious websites, and reporting the ones that should not be there.</p>
                </div>

                <div class="space-y-3">
                    @foreach ([
                        ['M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z', 'Scan suspicious links', 'Also emails, phone numbers, screenshots and files.'],
                        ['M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'Get a clear result', 'A risk score and every check explained.'],
                        ['M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z', 'Keep your history', 'Review past scans and security reports any time.'],
                    ] as $row)
                        <div class="as-glass as-row flex items-center gap-4 rounded-[1.25rem] px-6 py-5">
                            <span class="icon-tile shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $row[0] }}" /></svg>
                            </span>
                            <span>
                                <span class="block text-[15px] font-bold text-white">{{ $row[1] }}</span>
                                <span class="block text-[13px] text-slate-400">{{ $row[2] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-3.5 mt-7">
                    <svg class="as-phil" viewBox="0 0 17 9" aria-hidden="true"><use href="#as-phil"/></svg>
                    <p class="as-say ml-2">Welcome aboard! Let&rsquo;s catch some phish.</p>
                </div>
                </div>
            </section>

            {{-- ===== ACCOUNT RECOVERY SIDE CONTENT (forgot / reset: sits beside the card in the middle) ===== --}}
            <section class="as-panel as-panel-recover {{ $isMid ? 'is-on' : '' }}" :class="{ 'is-on': mode === 'forgot' || mode === 'reset' }" :aria-hidden="mode !== 'forgot' && mode !== 'reset'">
                <div class="as-pin">
                    <div class="as-glass as-rec">
                        <div class="as-shield" aria-hidden="true">
                            <b></b><b></b><i></i>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.4l8.4 3.3v6.3c0 4.9-3.5 8.6-8.4 10-4.9-1.4-8.4-5.1-8.4-10V5.7L12 2.4z"/><rect x="9" y="10.6" width="6" height="4.6" rx="1.1"/><path d="M10.3 10.6V9.3a1.7 1.7 0 013.4 0v1.3"/></svg>
                        </div>
                        <h2 class="text-2xl font-extrabold text-white">Secure Account Recovery</h2>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-slate-400">PhishCore uses secure, time-limited links to protect your account during password recovery.</p>

                        <ol class="as-steps">
                            @foreach ([
                                ['Enter your email', 'Use the address you registered with.'],
                                ['Open the reset link', 'We email it to you. It expires after 15 minutes.'],
                                ['Choose a new password', 'Each link works once, and we never email your password.'],
                            ] as $i => $step)
                                @php $n = $i + 1; $now = $mode === 'reset' ? 3 : 1; @endphp
                                <li class="as-step {{ $n < $now ? 'is-done' : '' }} {{ $n === $now ? 'is-act' : '' }}"
                                    :class="{ 'is-done': {{ $n }} < stepNow, 'is-act': {{ $n }} === stepNow }">
                                    <span class="n"><span>{{ $n }}</span><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg></span>
                                    <div>
                                        <h3>{{ $step[0] }}</h3>
                                        <p>{{ $step[1] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>

                        <div class="as-badges">
                            <span class="as-badge"><i></i>15-minute links</span>
                            <span class="as-badge"><i></i>One-time use</span>
                            <span class="as-badge"><i></i>Never sent by email</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== THE FLOATING CARD ===== --}}
            <div class="as-card-wrap {{ $mode === 'register' ? 'is-reg' : '' }} {{ $isMid ? 'is-mid' : '' }}" :class="{ 'is-reg': mode === 'register', 'is-mid': mode === 'forgot' || mode === 'reset', 'is-moving': moving }">
                <div class="as-depth">
                <div class="as-card" x-ref="card">

                    {{-- SIGN IN --}}
                    <div class="as-form {{ $isLogin ? 'is-on' : '' }}" :class="{ 'is-on': mode === 'login' }" x-ref="login" :inert="mode !== 'login'">
                        <div class="mb-7">
                            <div>
                                <h1 class="as-title">Welcome back</h1>
                                <p class="as-sub">Sign in to your security dashboard</p>
                            </div>
                        </div>

                        @if ($isLogin && session('status'))
                            <div class="mb-5 flex items-center gap-2 text-sm text-emerald-300 rounded-xl border border-emerald-300/20 bg-emerald-400/[0.06] px-4 py-3">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $check }}" /></svg>
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="space-y-5" @submit="busy = true">
                            @csrf
                            <x-auth-field id="login-email" name="email" label="Email address" type="email" placeholder="yourname@example.com"
                                          icon="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6"
                                          autocomplete="username" :value="$isLogin ? old('email', '') : ''" :show-error="$isLogin" focus-ref="loginFirst" />
                            <x-auth-field id="login-password" name="password" label="Password" type="password" placeholder="Enter your password"
                                          icon="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                          autocomplete="current-password" toggle="showPassword" :show-error="$isLogin" />

                            <div class="flex items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer select-none">
                                    <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-sky-500 focus:ring-sky-500/50 focus:ring-offset-0">
                                    Remember me
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" @click.prevent="setMode('forgot')" class="as-link text-sm">Forgot password?</a>
                                @endif
                            </div>

                            <button type="submit" :disabled="busy" class="as-btn">
                                <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="busy ? 'Signing in...' : 'Sign In'">Sign In</span>
                                <svg x-show="!busy" class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                            </button>

                            <div class="flex items-center gap-3.5">
                                <span class="flex-1 h-px bg-white/[0.14]"></span>
                                <span class="text-[10px] font-bold tracking-[.16em] text-slate-400">NEW HERE?</span>
                                <span class="flex-1 h-px bg-white/[0.14]"></span>
                            </div>

                            <a href="{{ route('register') }}" @click.prevent="setMode('register')" class="as-btn-ghost">Create an account</a>
                        </form>
                    </div>

                    {{-- CREATE ACCOUNT --}}
                    <div class="as-form as-tight {{ $mode === 'register' ? 'is-on' : '' }}" :class="{ 'is-on': mode === 'register' }" x-ref="register" :inert="mode !== 'register'">
                        <div class="mb-6">
                            <div>
                                <h1 class="as-title">Create your account</h1>
                                <p class="as-sub">It only takes a minute.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="space-y-3.5" @submit="busy = true">
                            @csrf
                            <x-auth-field id="reg-name" name="name" label="Full name" placeholder="e.g. Ahmad Razif bin Haji Rosli"
                                          icon="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z"
                                          autocomplete="name" :value="$mode === 'register' ? old('name', '') : ''" :show-error="$mode === 'register'" focus-ref="registerFirst" />
                            <x-auth-field id="reg-email" name="email" label="Email address" type="email" placeholder="yourname@example.com"
                                          icon="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6"
                                          autocomplete="username" :value="$mode === 'register' ? old('email', '') : ''" :show-error="$mode === 'register'" />
                            <x-auth-field id="reg-password" name="password" label="Password" type="password" placeholder="At least 8 characters"
                                          icon="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                          autocomplete="new-password" toggle="showRegPassword" :show-error="$mode === 'register'" />
                            <x-auth-field id="reg-password-confirm" name="password_confirmation" label="Confirm password" type="password" placeholder="Type it again"
                                          icon="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                          autocomplete="new-password" toggle="showConfirm" :show-error="$mode === 'register'" />

                            <label class="flex items-start gap-3 text-sm text-slate-400 cursor-pointer">
                                <input type="checkbox" x-model="agreed" required class="mt-0.5 rounded border-white/20 bg-white/5 text-sky-500 focus:ring-sky-500/50 focus:ring-offset-0">
                                <span>
                                    I agree to the
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener" class="as-link">Terms of Use</a>
                                    and
                                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="as-link">Privacy Policy</a>
                                </span>
                            </label>

                            <button type="submit" :disabled="!agreed || busy" class="as-btn">
                                <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="busy ? 'Creating account...' : 'Create Account'">Create Account</span>
                                <svg x-show="!busy" class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                            </button>

                            <p class="text-center text-sm text-slate-400">
                                Already have an account?
                                <a href="{{ route('login') }}" @click.prevent="setMode('login')" class="as-link">Sign in</a>
                            </p>
                        </form>
                    </div>

                    {{-- FORGOT PASSWORD --}}
                    <div class="as-form {{ $mode === 'forgot' ? 'is-on' : '' }}" :class="{ 'is-on': mode === 'forgot' }" x-ref="forgot" :inert="mode !== 'forgot'">
                        <a href="{{ route('login') }}" @click.prevent="setMode('login')" class="as-link mb-6 inline-flex items-center gap-1 text-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            Back to Sign In
                        </a>
                        <div class="mb-7">
                            <h1 class="as-title">Forgot your password?</h1>
                            <p class="as-sub">Enter your registered email address and we'll send you instructions to reset your password.</p>
                        </div>

                        @if ($mode === 'forgot' && session('status'))
                            <div class="mb-5 flex items-center gap-2 text-sm text-emerald-300 rounded-xl border border-emerald-300/20 bg-emerald-400/[0.06] px-4 py-3">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $check }}" /></svg>
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" @submit="busy = true">
                            @csrf
                            <x-auth-field id="forgot-email" name="email" label="Email address" type="email" placeholder="yourname@example.com"
                                          icon="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6"
                                          autocomplete="username" :value="$mode === 'forgot' ? old('email', '') : ''" :show-error="$mode === 'forgot'" focus-ref="forgotFirst" />
                            <button type="submit" :disabled="busy" class="as-btn">
                                <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="busy ? 'Sending...' : 'Send Reset Link'">Send Reset Link</span>
                            </button>
                            <p class="text-center text-sm text-slate-400">
                                Remember your password?
                                <a href="{{ route('login') }}" @click.prevent="setMode('login')" class="as-link">Sign in</a>
                            </p>
                            <div class="as-note">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                For your security, the reset link will expire after 15 minutes.
                            </div>
                        </form>
                    </div>

                    {{-- SET A NEW PASSWORD (arrives from the e-mail link; its own page load) --}}
                    @if ($mode === 'reset')
                        <div class="as-form is-on" x-ref="reset">
                            <div class="mb-6">
                                <h1 class="as-title">Set a new password</h1>
                                <p class="as-sub">Choose a strong new password for your PhishCore account.</p>
                            </div>
                            <form method="POST" action="{{ route('password.store') }}" class="space-y-4" @submit="busy = true">
                                @csrf
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                                <x-auth-field id="reset-email" name="email" label="Email address" type="email" placeholder="yourname@example.com"
                                              icon="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6"
                                              autocomplete="username" :value="old('email', $request->email ?? '')" focus-ref="resetFirst" />
                                <x-auth-field id="reset-password" name="password" label="New password" type="password" placeholder="At least 8 characters"
                                              icon="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                              autocomplete="new-password" toggle="showResetPassword" />
                                <x-auth-field id="reset-password-confirm" name="password_confirmation" label="Confirm new password" type="password" placeholder="Type it again"
                                              icon="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                              autocomplete="new-password" toggle="showResetConfirm" />
                                <button type="submit" :disabled="busy" class="as-btn">
                                    <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                    <span x-text="busy ? 'Resetting...' : 'Reset Password'">Reset Password</span>
                                </button>
                                <div class="as-note">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                    This reset link can only be used once. If it has expired, request a new one from the sign-in page.
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
                </div>
                <div class="as-glow" aria-hidden="true"></div>
            </div>

        </main>
    </div>
</body>
</html>