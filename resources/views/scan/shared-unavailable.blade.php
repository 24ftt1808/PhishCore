<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Link unavailable — PhishCore</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #020617;
            color: #e2e8f0;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Animated aurora — same recipe as the front page */
        .aurora { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; contain: strict; }
        .aurora span { position: absolute; border-radius: 50%; will-change: transform; }
        .aurora span:nth-child(1) {
            width: 70vmax; height: 70vmax; top: -25vmax; left: -15vmax;
            background: radial-gradient(circle, rgba(3,105,161,.42), transparent 65%);
            animation: drift1 22s ease-in-out infinite alternate;
        }
        .aurora span:nth-child(2) {
            width: 65vmax; height: 65vmax; bottom: -30vmax; right: -20vmax;
            background: radial-gradient(circle, rgba(29,78,216,.38), transparent 65%);
            animation: drift2 26s ease-in-out infinite alternate;
        }
        .aurora span:nth-child(3) {
            width: 55vmax; height: 55vmax; top: 25%; left: 35%;
            background: radial-gradient(circle, rgba(30,58,138,.49), transparent 65%);
            animation: drift3 30s ease-in-out infinite alternate;
        }
        @keyframes drift1 { to { transform: translate3d(18vmax, 12vmax, 0) scale(1.15); } }
        @keyframes drift2 { to { transform: translate3d(-16vmax, -10vmax, 0) scale(1.1); } }
        @keyframes drift3 { to { transform: translate3d(-12vmax, 14vmax, 0) scale(.9); } }

        /* Glass card */
        .card {
            position: relative; z-index: 1;
            width: 100%; max-width: 480px;
            padding: 44px 36px 32px;
            text-align: center;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.025));
            border: 1px solid rgba(255,255,255,.12);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            backdrop-filter: blur(16px) saturate(140%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 24px 60px -20px rgba(2,6,23,.8);
        }

        .logo {
            width: 76px; height: 76px; margin: 0 auto 22px;
            display: grid; place-items: center;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,.1), rgba(255,255,255,.03));
            border: 1px solid rgba(255,255,255,.14);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.16), 0 0 40px -6px rgba(56,189,248,.35);
        }
        .logo img { width: 44px; height: 44px; object-fit: contain; display: block; }

        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            padding: 6px 14px; border-radius: 999px; margin-bottom: 18px;
            color: #7dd3fc;
            background: rgba(14,165,233,.1);
            border: 1px solid rgba(125,211,252,.25);
        }
        .badge i { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; animation: blink 1.6s ease-in-out infinite; }
        @keyframes blink { 50% { opacity: .25; } }

        h1 { margin: 0 0 12px; font-size: clamp(28px, 7vw, 38px); line-height: 1.12; font-weight: 800; letter-spacing: -.02em; color: #f8fafc; }
        h1 span { background: linear-gradient(90deg, #7dd3fc, #38bdf8 45%, #2563eb); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .lead { margin: 0 auto 26px; max-width: 360px; font-size: 15px; line-height: 1.65; color: #94a3b8; }

        .chips { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .chip {
            font-size: 12px; font-weight: 600; color: #cbd5e1;
            padding: 6px 12px; border-radius: 999px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
        }

        .foot { margin-top: 26px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.08); font-size: 12px; color: #64748b; }

        @media (max-width: 420px) {
            .card { padding: 34px 22px 26px; border-radius: 20px; }
            .aurora span:nth-child(1), .aurora span:nth-child(2), .aurora span:nth-child(3) { width: 90vmax; height: 90vmax; }
        }
        @media (prefers-reduced-motion: reduce) {
            .aurora span, .badge i { animation: none; }
        }
    
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 4px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 11px 20px; border-radius: 12px; font-size: 14px; font-weight: 700;
            text-decoration: none; transition: transform .2s, opacity .2s;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
        .btn-ghost { color: #e2e8f0; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.14); }
        @media (max-width: 420px) { .btn { width: 100%; } }
    </style>
</head>
<body>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>

    <main class="card">
        <div class="logo"><img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore"></div>
        <div class="badge"><i></i> Shared result</div>
        <h1>This link is <span>no longer available</span></h1>
        <p class="lead">The owner may have turned sharing off, or the link was typed incorrectly. Ask them for a new link, or run a fresh scan yourself.</p>
        <div class="actions">
            <a class="btn btn-main" href="{{ route('scan.index') }}">Scan a link yourself</a>
            <a class="btn btn-ghost" href="{{ route('reports.public') }}">Public Reports</a>
        </div>
        <div class="foot">PhishCore Detection Platform</div>
    </main>
</body>
</html>