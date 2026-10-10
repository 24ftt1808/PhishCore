@php
    $periods = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 3 Months', 180 => 'Last 6 Months'];

    if (! function_exists('trendArrow')) {
        function trendArrow($value) {
            return $value >= 0 ? '↗' : '↘';
        }
    }
    if (! function_exists('trendColor')) {
        function trendColor($value, $goodWhenUp = true) {
            $isGood = $goodWhenUp ? $value >= 0 : $value <= 0;
            return $isGood ? 'text-emerald-400' : 'text-red-400';
        }
    }
    if (! function_exists('msLabel')) {
        function msLabel($ms) {
            return $ms === null ? '—' : round($ms / 1000, 1) . 's';
        }
    }

    // explicit class map so Tailwind always generates these colours
    $tones = [
        'emerald' => ['bg' => 'bg-emerald-400', 'text' => 'text-emerald-400', 'rgb' => '52,211,153'],
        'sky' => ['bg' => 'bg-sky-400', 'text' => 'text-sky-300', 'rgb' => '56,189,248'],
        'orange' => ['bg' => 'bg-orange-400', 'text' => 'text-orange-400', 'rgb' => '251,146,60'],
        'red' => ['bg' => 'bg-red-400', 'text' => 'text-red-400', 'rgb' => '248,113,113'],
    ];

    $statCards = [
        ['label' => 'TOTAL REPORTS', 'value' => $stats['total'], 'suffix' => '', 'change' => $stats['total_change'], 'goodUp' => true, 'rgb' => '56,189,248', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['label' => 'PHISHING DETECTION RATE', 'value' => $stats['phishing_rate'], 'suffix' => '%', 'change' => $stats['phishing_rate_change'], 'goodUp' => false, 'rgb' => '248,113,113', 'icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z'],
        ['label' => 'AVERAGE RISK SCORE', 'value' => $stats['avg_risk'], 'suffix' => '', 'change' => $stats['avg_risk_change'], 'goodUp' => false, 'rgb' => '251,191,36', 'icon' => 'M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18'],
        ['label' => 'SUSPICIOUS RATE', 'value' => $stats['suspicious_rate'], 'suffix' => '%', 'change' => $stats['suspicious_rate_change'], 'goodUp' => false, 'rgb' => '251,146,60', 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
    ];

    $breakdownRows = [
        ['key' => 'safe', 'label' => 'Safe', 'tone' => 'emerald'],
        ['key' => 'suspicious', 'label' => 'Suspicious', 'tone' => 'orange'],
        ['key' => 'phishing', 'label' => 'Phishing', 'tone' => 'red'],
    ];
@endphp

<x-layouts.dashboard>
    <style>
        .a-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
        .a-well { background: rgba(8, 15, 32, .5); border: 1px solid rgba(148, 163, 184, .16); border-radius: .8rem; }

        .a-stat { position: relative; overflow: hidden; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
        .a-stat::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(120% 140% at 100% 0%, rgba(var(--c), .24), transparent 62%); opacity: .75; transition: opacity .3s; }
        .a-stat:hover { transform: translateY(-2px); border-color: rgba(var(--c), .5); }
        .a-stat:hover::before { opacity: 1; }
        .a-stat > * { position: relative; }
        .a-tile { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .8rem; background: rgba(var(--c), .14); border: 1px solid rgba(var(--c), .38); color: rgb(var(--c)); box-shadow: 0 0 18px -4px rgba(var(--c), .55); flex-shrink: 0; }

        .a-chip { display: inline-flex; align-items: center; padding: .5rem 1rem; border-radius: 999px; font-size: .8rem; font-weight: 600; color: #cbd5e1; border: 1px solid rgba(148, 163, 184, .22); background: rgba(8, 15, 32, .35); transition: background-color .15s, border-color .15s, color .15s; white-space: nowrap; }
        .a-chip:hover { color: #fff; border-color: rgba(148, 163, 184, .45); }
        .a-chip-on { color: #fff; background: rgba(56, 189, 248, .16); border-color: rgba(56, 189, 248, .5); }

        .a-chip { flex-shrink: 0; white-space: nowrap; }
        .a-scroll { scrollbar-width: none; }
        .a-scroll::-webkit-scrollbar { display: none; }
        @media (max-width: 639px) {
            .a-tile { width: 2.1rem; height: 2.1rem; border-radius: .65rem; }
            .a-tile svg { width: 1rem; height: 1rem; }
            .a-chip { padding: .42rem .85rem; font-size: .75rem; }
        }

        .a-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; letter-spacing: -.01em; }
        .a-row { transition: background-color .15s; }
        .a-row:hover { background-color: rgba(125, 211, 252, .06); }

        .a-grow { transform-origin: left; animation: a-grow .8s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, .2s); }
        @keyframes a-grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        .a-in { animation: a-in .45s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
        @keyframes a-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        /* Community section: reacts to the width of its own box (the sidebar eats screen width on tablets) */
        .c-sec { container-type: inline-size; }
        .c-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .c-panels { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
        .c-panel { display: flex; flex-direction: column; }
        .c-pad { padding: 1.1rem; }
        .c-stat-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
        .c-stat-lbl { font-size: .7rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #cbd5e1; padding-top: .15rem; }
        .c-stat-val { margin-top: .55rem; font-size: 1.9rem; line-height: 1.05; font-weight: 800; color: rgb(var(--c)); font-variant-numeric: tabular-nums; }
        .c-share { margin-top: .75rem; }
        .c-share-bar { height: 5px; border-radius: 999px; background: rgba(255, 255, 255, .09); overflow: hidden; }
        .c-share-bar > span { display: block; height: 100%; border-radius: 999px; background: rgb(var(--c)); }
        .c-share-txt { margin-top: .4rem; font-size: .72rem; color: #94a3b8; }
        .c-share-txt + .c-share-txt { margin-top: .1rem; }
        .c-spark { display: block; width: 100%; height: 2.1rem; margin-top: .65rem; overflow: visible; }
        .c-spark .c-spark-line { fill: none; stroke: rgb(var(--c)); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; vector-effect: non-scaling-stroke; }
        .c-spark .c-spark-area { fill: rgba(var(--c), .14); stroke: none; }
        .c-spark .c-spark-dot { fill: rgb(var(--c)); }
        .c-h3 { color: #fff; font-weight: 700; font-size: 1.02rem; }
        .c-sub { color: #cbd5e1; font-size: .85rem; margin-top: .15rem; }
        .c-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .7rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: #7dd3fc; background: rgba(56, 189, 248, .12); border: 1px solid rgba(56, 189, 248, .3); white-space: nowrap; }
        .c-chartbox { position: relative; height: 12.5rem; margin-top: 1rem; }
        .c-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: auto; padding-top: 1rem; }
        .c-fact { padding: .6rem .7rem; border-radius: .75rem; background: rgba(8, 15, 32, .5); border: 1px solid rgba(148, 163, 184, .14); min-width: 0; }
        .c-fact b { display: block; font-size: .95rem; color: #f1f5f9; font-weight: 700; font-variant-numeric: tabular-nums; }
        .c-fact span { display: block; margin-top: .15rem; font-size: .64rem; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; }
        .c-top-row { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; column-gap: .75rem; padding: .85rem .5rem; margin: 0 -.5rem; border-top: 1px solid rgba(148, 163, 184, .14); border-radius: .6rem; }
        .c-top-row:first-child { border-top-color: transparent; }
        .c-rank { width: 1.85rem; height: 1.85rem; border-radius: .55rem; display: grid; place-items: center; font-size: .8rem; font-weight: 800; color: #fff; background: rgba(14, 116, 144, .85); }
        .c-rank-1 { background: linear-gradient(135deg, #fde047, #f59e0b); color: #111827; box-shadow: 0 0 14px -2px rgba(250, 204, 21, .6); }
        .c-rank-2 { background: linear-gradient(135deg, #e2e8f0, #94a3b8); color: #111827; }
        .c-rank-3 { background: linear-gradient(135deg, #fdba74, #ea580c); color: #111827; }
        .c-kind { display: none; width: 2.1rem; height: 2.1rem; border-radius: .6rem; place-items: center; background: rgba(var(--k), .13); border: 1px solid rgba(var(--k), .35); color: rgb(var(--k)); }
        .c-item { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; overflow: hidden; overflow-wrap: anywhere; font-size: .82rem; line-height: 1.3; color: #f1f5f9; }
        .c-meta { display: flex; align-items: flex-start; gap: .4rem; margin-top: .25rem; font-size: .72rem; line-height: 1.35; color: #94a3b8; min-width: 0; }
        .c-meta > span:last-child { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; overflow: hidden; overflow-wrap: anywhere; }
        .c-chip { flex-shrink: 0; margin-top: .05rem; font-size: .62rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; padding: .1rem .4rem; border-radius: .3rem; color: rgb(var(--k)); background: rgba(var(--k), .12); }
        .c-count { text-align: right; line-height: 1.1; }
        .c-count b { display: block; font-size: 1.3rem; font-weight: 800; color: #7dd3fc; font-variant-numeric: tabular-nums; }
        .c-count span { display: block; margin-top: .15rem; font-size: .6rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #fca5a5; }
        .c-ppl { display: none; font-style: normal; }
        .c-bar { grid-column: 2 / -1; margin-top: .55rem; height: 4px; border-radius: 999px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .c-bar > span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #38bdf8, #6366f1); }
        .c-cta { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; gap: .85rem; margin-top: auto; padding: .95rem 1rem; border-radius: .9rem; text-decoration: none; background: linear-gradient(135deg, rgba(56, 189, 248, .14), rgba(99, 102, 241, .12)); border: 1px dashed rgba(125, 211, 252, .4); transition: border-color .15s, background-color .15s; }
        .c-cta:hover { border-color: rgba(125, 211, 252, .75); }
        .c-cta-ico { width: 2.4rem; height: 2.4rem; border-radius: .75rem; display: grid; place-items: center; color: #7dd3fc; background: rgba(56, 189, 248, .14); border: 1px solid rgba(56, 189, 248, .35); }
        .c-cta-txt b { display: block; color: #fff; font-size: .9rem; }
        .c-cta-txt span { display: block; margin-top: .15rem; color: #cbd5e1; font-size: .78rem; line-height: 1.4; }
        .c-cta-go { grid-column: 1 / -1; justify-self: start; font-size: .8rem; font-weight: 700; color: #7dd3fc; }
        .c-note { margin-top: .9rem; padding-top: .8rem; border-top: 1px dashed rgba(148, 163, 184, .22); font-size: .74rem; color: #94a3b8; line-height: 1.45; }
        .c-list-gap { margin-bottom: 1rem; }
        .c-empty { display: grid; justify-items: center; text-align: center; gap: .5rem; padding: 1.4rem .5rem; color: #94a3b8; font-size: .85rem; }
        .c-empty svg { width: 2rem; height: 2rem; color: #475569; }
        @container (min-width: 400px) {
            .c-ppl { display: inline; }
            .c-cta { grid-template-columns: auto minmax(0, 1fr) auto; }
            .c-cta-go { grid-column: auto; justify-self: auto; white-space: nowrap; }
        }
        @container (min-width: 560px) {
            .c-cards { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
            .c-pad { padding: 1.35rem; }
            .c-stat-val { font-size: 2.15rem; }
            .c-chartbox { height: 15rem; }
            .c-kind { display: grid; }
            .c-top-row { grid-template-columns: auto auto minmax(0, 1fr) auto; }
            .c-bar { grid-column: 3 / -1; }
            .c-item { font-size: .88rem; }
            .c-fact b { font-size: 1.05rem; }
        }
        @container (min-width: 900px) {
            .c-panels { grid-template-columns: minmax(0, 2fr) minmax(0, 3fr); align-items: stretch; }
            /* The chart card matches the Top 10 card's height; the chart grows to fill it */
            .c-chartbox { height: auto; min-height: 16rem; flex: 1 1 auto; }
            .c-chartbox canvas { position: absolute; inset: 0; width: 100% !important; height: 100% !important; }
        }
        /* Tabs: My scans / Community. A soft glass pill glides between the two and stretches a little as it moves. */
        .a-tabs { position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: .25rem; padding: .25rem; margin-bottom: 1.25rem; width: 100%; max-width: 26rem; border-radius: 999px; isolation: isolate; background: linear-gradient(180deg, rgba(20, 33, 62, .75), rgba(8, 15, 32, .7)); border: 1px solid rgba(148, 163, 184, .2); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06), inset 0 -8px 16px rgba(2, 6, 23, .35), 0 8px 24px rgba(2, 6, 23, .35); }
        .a-glider { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .75rem) / 2); transform: translateX(0); transition: transform .6s cubic-bezier(.34, 1.5, .5, 1); pointer-events: none; will-change: transform; }
        .a-glider.is-right { transform: translateX(calc(100% + .25rem)); }
        .a-glider-blob { position: absolute; inset: 0; border-radius: 999px; overflow: hidden; background: linear-gradient(180deg, rgba(125, 211, 252, .32), rgba(56, 189, 248, .12) 55%, rgba(14, 165, 233, .18)); border: 1px solid rgba(186, 230, 253, .45); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .4), inset 0 -8px 14px rgba(56, 189, 248, .14), 0 6px 20px rgba(56, 189, 248, .22); }
        .a-glider-blob::after { content: ''; position: absolute; left: 10%; right: 10%; top: 3px; height: 38%; border-radius: 999px; background: linear-gradient(180deg, rgba(255, 255, 255, .28), rgba(255, 255, 255, 0)); }
        .a-glider.is-squish .a-glider-blob { animation: a-squish .6s cubic-bezier(.3, .7, .3, 1); }
        @keyframes a-squish { 0% { transform: scale(1, 1); } 30% { transform: scale(1.14, .88); } 65% { transform: scale(.97, 1.06); } 100% { transform: scale(1, 1); } }
        .a-tab { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center; gap: .45rem; padding: .6rem .9rem; border-radius: 999px; font-size: .85rem; font-weight: 600; color: #94a3b8; background: transparent; border: 0; cursor: pointer; transition: color .3s ease, transform .2s ease; white-space: nowrap; }
        .a-tab:hover { color: #e2e8f0; }
        .a-tab:active { transform: scale(.96); }
        .a-tab:focus-visible { outline: 2px solid #38bdf8; outline-offset: 2px; }
        .a-tab-on { color: #f0f9ff; }
        .a-tab-count { font-size: .68rem; font-weight: 700; padding: .05rem .45rem; border-radius: 999px; background: rgba(148, 163, 184, .18); color: #cbd5e1; transition: background-color .3s ease, color .3s ease; }
        .a-tab-on .a-tab-count { background: rgba(255, 255, 255, .22); color: #f0f9ff; }
        @media (max-width: 380px) { .a-tab { padding: .55rem .5rem; font-size: .8rem; } .a-tab-count { display: none; } }
        /* The panels fade out quickly, then the new one settles in */
        .a-pan-enter { transition: opacity .4s ease .12s, transform .5s cubic-bezier(.22, 1, .36, 1) .12s; }
        .a-pan-enter-start { opacity: 0; transform: translateY(12px) scale(.992); }
        .a-pan-enter-end { opacity: 1; transform: none; }
        .a-pan-leave { transition: opacity .12s ease; }
        .a-pan-leave-start { opacity: 1; }
        .a-pan-leave-end { opacity: 0; }
        /* Soft press and glow on the other buttons */
        .a-more, .a-chip { transition: background-color .25s ease, border-color .25s ease, transform .25s cubic-bezier(.34, 1.4, .5, 1), box-shadow .25s ease; }
        .a-more:hover, .a-chip:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(56, 189, 248, .18); }
        .a-more:active, .a-chip:active { transform: scale(.96); box-shadow: none; }
        @media (prefers-reduced-motion: reduce) { .a-glider, .a-tab, .a-more, .a-chip { transition: none; } .a-glider.is-squish .a-glider-blob { animation: none; } .a-pan-enter, .a-pan-leave { transition: none; } }
        /* Indicators: the card shows the top rows; the full list opens in a window */
        [x-cloak] { display: none !important; }
        .a-more { display: inline-flex; align-items: center; gap: .4rem; margin-top: 1.1rem; padding: .5rem 1rem; border-radius: 999px; font-size: .8rem; font-weight: 600; color: #7dd3fc; background: rgba(56, 189, 248, .1); border: 1px solid rgba(56, 189, 248, .3); cursor: pointer; transition: background-color .15s, border-color .15s; }
        .a-more:hover { background: rgba(56, 189, 248, .18); border-color: rgba(56, 189, 248, .5); }
        .a-more svg { width: .9rem; height: .9rem; }
        .a-modal { position: fixed; inset: 0; z-index: 70; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(2, 6, 23, .82); }
        .a-modal-panel { display: flex; flex-direction: column; width: 100%; max-width: 34rem; max-height: 85vh; max-height: 85dvh; border-radius: 1.1rem; background: #0a1224; border: 1px solid rgba(148, 163, 184, .25); box-shadow: 0 10px 30px rgba(0, 0, 0, .55); overflow: hidden; }
        .a-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid rgba(148, 163, 184, .16); }
        .a-modal-x { flex-shrink: 0; width: 2.25rem; height: 2.25rem; display: grid; place-items: center; border-radius: .6rem; color: #cbd5e1; background: rgba(148, 163, 184, .1); border: 1px solid rgba(148, 163, 184, .2); cursor: pointer; }
        .a-modal-x:hover { color: #fff; background: rgba(148, 163, 184, .2); }
        .a-modal-body { overflow-y: auto; overscroll-behavior: contain; padding: 1rem 1.25rem 1.25rem; }
        @media (max-width: 639px) {
            .a-modal { align-items: flex-end; padding: 0; }
            .a-modal-panel { max-width: none; max-height: 88vh; max-height: 88dvh; border-radius: 1.1rem 1.1rem 0 0; }
        }
        @media (max-height: 480px) {
            .a-modal { align-items: center; padding: .5rem 1rem; }
            .a-modal-panel { max-width: 34rem; max-height: 94vh; max-height: 94dvh; border-radius: 1rem; }
            .a-modal-head { padding: .7rem 1rem; }
            .a-modal-body { padding: .75rem 1rem 1rem; }
        }
        /* Top Phishing Sources on phones: the name gets its own line, the numbers sit underneath */
        .a-bottom-spacer { height: 4.5rem; }
        @media (max-width: 767px) {
            .a-src-row > .a-src-name { flex: 1 1 calc(100% - 2.25rem); }
            .a-src-text { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; white-space: normal; overflow: hidden; overflow-wrap: anywhere; text-overflow: clip; line-height: 1.35; }
        }
        @media (max-width: 360px) { .a-src-row > td:last-child { margin-left: 2.25rem; } }
        @media (prefers-reduced-motion: reduce) { .a-in, .a-grow { animation: none; } .a-stat:hover { transform: none; } }
    </style>

    <div class="a-in flex items-start justify-between mb-5 sm:mb-6 md:max-xl:landscape:mb-4 sm:flex-wrap gap-3 sm:gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-white mb-1">Detection Analytics</h1>
            <p class="text-slate-300 text-sm">Monitor phishing trends, report activity and detection performance.</p>
        </div>
        <span class="inline-flex items-center justify-center shrink-0 gap-2 max-sm:w-10 max-sm:h-10 sm:px-4 sm:py-2.5 rounded-xl border border-slate-500/30 text-slate-200 text-sm font-semibold opacity-60 cursor-not-allowed" title="Coming soon">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            <span class="hidden sm:inline">Export Analytics</span>
        </span>
    </div>

    <div x-data="{ tab: location.hash === '#community' ? 'community' : 'mine', squish: false, go(to) { if (this.tab === to) return; this.tab = to; this.squish = true; clearTimeout(this.sq); this.sq = setTimeout(() => this.squish = false, 650); } }" x-init="$watch('tab', v => { history.replaceState(null, '', v === 'community' ? '#community' : location.pathname + location.search); $nextTick(() => window.dispatchEvent(new Event('resize'))); })" data-analytics-tabs>
    {{-- COMMUNITY (everyone's scans, counts only) --}}
    @php
        $t = $community['totals'];
        $communityTotal = max(1, (int) $t['total']);
        $weekTotals = array_map(fn ($d) => $d['clean'] + $d['suspicious'] + $d['phishing'], $community['days']);
        $communityWeek = array_sum($weekTotals);
        $busiestAt = $communityWeek > 0 ? array_keys($weekTotals, max($weekTotals))[0] : null;
        $weekHigh = array_sum(array_column($community['days'], 'phishing'));
        $weekAvg = round($communityWeek / 7, 1);
        $sparkMax = max(1, max($weekTotals));
        $sparkPts = [];
        foreach ($weekTotals as $i => $v) {
            $sparkPts[] = [round($i * (100 / 6), 2), round(30 - ($v / $sparkMax) * 26, 2)];
        }
        $sparkLine = implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $sparkPts));
        $sparkArea = '0,34 '.$sparkLine.' 100,34';
        $weekBy = ['phishing' => array_sum(array_column($community['days'], 'phishing')), 'suspicious' => array_sum(array_column($community['days'], 'suspicious')), 'clean' => array_sum(array_column($community['days'], 'clean'))];
        $communityCards = [
            ['label' => 'Total scans', 'value' => $t['total'], 'rgb' => '56,189,248', 'share' => false, 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
            ['label' => 'High risk', 'week' => $weekBy['phishing'], 'value' => $t['phishing'], 'rgb' => '248,113,113', 'share' => true, 'icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z'],
            ['label' => 'Medium risk', 'week' => $weekBy['suspicious'], 'value' => $t['suspicious'], 'rgb' => '251,146,60', 'share' => true, 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
            ['label' => 'Safe', 'week' => $weekBy['clean'], 'value' => $t['clean'], 'rgb' => '52,211,153', 'share' => true, 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286z'],
        ];
        $communityShow = 5;
        $communityMax = max(1, (int) max(array_merge([0], array_column($community['top'], 'count'))));
    @endphp
    <div class="a-tabs a-in" role="tablist" aria-label="Analytics view">
        <span class="a-glider" :class="{ 'is-right': tab === 'community', 'is-squish': squish }" aria-hidden="true"><span class="a-glider-blob"></span></span>
        <button type="button" role="tab" id="tab-mine" class="a-tab" :class="tab === 'mine' ? 'a-tab-on' : ''" :aria-selected="tab === 'mine'" aria-controls="panel-mine" @click="go('mine')" data-tab-mine>My scans</button>
        <button type="button" role="tab" id="tab-community" class="a-tab" :class="tab === 'community' ? 'a-tab-on' : ''" :aria-selected="tab === 'community'" aria-controls="panel-community" @click="go('community')" data-tab-community>Community <span class="a-tab-count">{{ number_format($t['total']) }}</span></button>
    </div>

    <section id="panel-community" role="tabpanel" aria-labelledby="tab-community" x-show="tab === 'community'" x-cloak x-transition:enter="a-pan-enter" x-transition:enter-start="a-pan-enter-start" x-transition:enter-end="a-pan-enter-end" x-transition:leave="a-pan-leave" x-transition:leave-start="a-pan-leave-start" x-transition:leave-end="a-pan-leave-end" class="c-sec mb-8 sm:mb-10" data-community aria-label="Brunei scam trends">
        <div class="a-in mb-4">
            <span class="inline-block text-[10px] font-semibold tracking-[.14em] text-sky-300 border border-sky-400/30 rounded px-2 py-0.5 mb-2">COMMUNITY · ALL USERS</span>
            <h2 id="community-title" class="text-xl sm:text-2xl font-bold text-white mb-1">Brunei Scam Trends</h2>
            <p class="text-slate-300 text-sm">Scans made by everyone on PhishCore. Counts only, so nobody's private scans are shown.</p>
        </div>

        <div class="c-cards">
            @foreach ($communityCards as $card)
                <div class="a-card a-stat a-in c-pad" style="--c: {{ $card['rgb'] }}; --d: {{ 0.04 + $loop->index * 0.05 }}s">
                    <div class="c-stat-top">
                        <p class="c-stat-lbl">{{ $card['label'] }}</p>
                        <span class="a-tile" style="width:2.2rem;height:2.2rem">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                        </span>
                    </div>
                    <p class="c-stat-val" data-community-total>{{ number_format($card['value']) }}</p>
                    @if ($card['share'])
                        @php $pct = (int) round($card['value'] / $communityTotal * 100); @endphp
                        <div class="c-share">
                            <div class="c-share-bar"><span class="a-grow" style="width: {{ $pct }}%; --d: {{ 0.2 + $loop->index * 0.07 }}s"></span></div>
                            <p class="c-share-txt">{{ $pct }}% of all scans</p>
                            <p class="c-share-txt">{{ number_format($card['week']) }} in the last 7 days</p>
                        </div>
                    @else
                        <svg class="c-spark" viewBox="0 0 100 34" preserveAspectRatio="none" aria-hidden="true">
                            <polygon class="c-spark-area" points="{{ $sparkArea }}" />
                            <polyline class="c-spark-line" points="{{ $sparkLine }}" />
                        </svg>
                        <p class="c-share-txt" style="margin-top:.4rem">{{ number_format($communityWeek) }} in the last 7 days</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="c-panels" x-data="{ topAll: false }" @keydown.escape.window="topAll = false">
            <div class="a-card a-in c-pad c-panel c-sticky" style="--d:.1s">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="c-h3">Scan activity this week</h3>
                        <p class="c-sub">All users, last 7 days</p>
                    </div>
                    <span class="c-pill">{{ number_format($communityWeek) }} {{ $communityWeek === 1 ? 'scan' : 'scans' }}</span>
                </div>
                <div class="c-chartbox"><canvas id="communityChart" role="img" aria-label="Stacked bar chart of safe, medium risk and high risk scans per day for the last 7 days"></canvas></div>
                <div class="c-facts">
                    <div class="c-fact"><b>{{ $busiestAt === null ? '—' : $community['days'][$busiestAt]['label'] }}</b><span>Busiest day</span></div>
                    <div class="c-fact"><b>{{ number_format($weekHigh) }}</b><span>High risk this week</span></div>
                    <div class="c-fact"><b>{{ $weekAvg }}</b><span>Scans per day</span></div>
                </div>
            </div>

            <div class="a-card a-in c-pad c-panel" style="--d:.14s">
                <div class="min-w-0">
                    <h3 class="c-h3">Top 10 most reported scams</h3>
                    <p class="c-sub">Links and phone numbers flagged by 2 or more different people</p>
                </div>
                <div class="mt-3 c-list-gap">
                    @forelse (array_slice($community['top'], 0, $communityShow) as $row)
                        @include('analytics._community-row', ['row' => $row, 'rank' => $loop->iteration, 'max' => $communityMax])
                    @empty
                        <div class="c-empty" data-community-empty>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286z" /></svg>
                            <p>Nothing to list yet. A link or number appears here once 2 different people have scanned or reported it.</p>
                        </div>
                    @endforelse
                    @if (count($community['top']) > $communityShow)
                        <button type="button" class="a-more" @click="topAll = true" data-community-viewall>
                            <span>View all {{ count($community['top']) }}</span>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg>
                        </button>
                    @endif
                </div>
                @if (count($community['top']) < 6)
                    <a href="{{ route('scan.index') }}" class="c-cta" data-community-cta>
                        <span class="c-cta-ico" aria-hidden="true">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                        </span>
                        <span class="c-cta-txt"><b>Help this list grow</b><span>Scan a link, or scan a phone number and tap Report as scam. When 2 people flag the same one, it appears here.</span></span>
                        <span class="c-cta-go">Scan now →</span>
                    </a>
                @endif
                <p class="c-note">Only items flagged by 2 or more different people are listed, which keeps one person's private scan and safe websites out of this list. Updated every few minutes.</p>
            </div>

            @if (count($community['top']) > $communityShow)
                <div class="a-modal" x-show="topAll" x-cloak x-transition.opacity.duration.150ms @click.self="topAll = false" role="dialog" aria-modal="true" aria-labelledby="community-top-title" data-community-modal x-effect="if (topAll) $nextTick(() => $refs.closeTop && $refs.closeTop.focus())">
                    <div class="a-modal-panel" x-show="topAll" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="a-modal-head">
                            <div class="min-w-0">
                                <h3 id="community-top-title" class="text-white font-semibold text-lg">Top {{ count($community['top']) }} most reported scams</h3>
                                <p class="text-sm text-slate-300 mt-0.5">Links and phone numbers flagged by 2 or more different people</p>
                            </div>
                            <button type="button" class="a-modal-x" x-ref="closeTop" @click="topAll = false" aria-label="Close">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="a-modal-body" style="padding-top:.25rem">
                            @foreach ($community['top'] as $row)
                                @include('analytics._community-row', ['row' => $row, 'rank' => $loop->iteration, 'max' => $communityMax])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <div id="panel-mine" role="tabpanel" aria-labelledby="tab-mine" x-show="tab === 'mine'" x-transition:enter="a-pan-enter" x-transition:enter-start="a-pan-enter-start" x-transition:enter-end="a-pan-enter-end" x-transition:leave="a-pan-leave" x-transition:leave-start="a-pan-leave-start" x-transition:leave-end="a-pan-leave-end" data-mine>
    <div class="a-in mb-4">
        <p class="text-slate-300 text-sm">Only your own scans, for the period you pick below.</p>
    </div>

    {{-- PERIOD --}}
    <div class="a-scroll a-in flex gap-2 mb-5 sm:mb-6 md:max-xl:landscape:mb-4 overflow-x-auto sm:overflow-visible sm:flex-wrap -mx-4 px-4 sm:mx-0 sm:px-0" style="--d:.04s">
        @foreach ($periods as $days => $label)
            <a href="{{ route('analytics', ['period' => $days]) }}" class="a-chip {{ $period == $days ? 'a-chip-on' : '' }}">{{ $label }}</a>
        @endforeach
        <span class="a-chip opacity-50 cursor-not-allowed" title="Coming soon">Custom Range</span>
    </div>

    {{-- TOP STAT CARDS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6 md:max-xl:landscape:mb-4">
        @foreach ($statCards as $card)
            <div class="a-card a-stat a-in p-3.5 sm:p-5 md:max-xl:p-4" style="--c: {{ $card['rgb'] }}; --d: {{ 0.06 + $loop->index * 0.06 }}s">
                <div class="flex items-start justify-between gap-2 sm:gap-3 md:max-xl:gap-2 mb-3 sm:mb-4 md:max-xl:min-h-[2.5rem]">
                    <p class="text-[11px] sm:text-xs md:max-xl:text-[11px] font-medium tracking-wide md:max-xl:tracking-normal text-slate-300 leading-snug min-w-0">{{ $card['label'] }}</p>
                    <span class="a-tile shrink-0 md:max-xl:!w-9 md:max-xl:!h-9">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-white mb-1">{{ $card['value'] }}{{ $card['suffix'] }}</p>
                <p class="text-xs font-medium {{ trendColor($card['change'], $card['goodUp']) }}">
                    {{ trendArrow($card['change']) }} {{ $card['change'] > 0 ? '+' : '' }}{{ $card['change'] }}% <span class="hidden sm:inline md:max-xl:hidden text-slate-400 font-normal">vs prev. period</span><span class="hidden md:max-xl:inline text-slate-400 font-normal">vs prev.</span>
                </p>
            </div>
        @endforeach
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 a-card a-in p-4 sm:p-6" style="--d:.1s">
            <div class="flex items-center justify-between gap-3 mb-1">
                <h2 class="text-white font-semibold">Report Activity Over Time</h2>
                <span class="text-xs font-medium text-slate-300">{{ $periods[$period] ?? '' }}</span>
            </div>
            <p class="text-sm text-slate-300 mb-4">Daily report totals for the selected period</p>
            <div class="relative h-48 sm:h-56 md:max-xl:h-64 xl:h-auto"><canvas id="activityChart" height="90"></canvas></div>
        </div>

        <div class="a-card a-in p-4 sm:p-6 md:max-xl:p-4 md:max-lg:portrait:grid md:max-lg:portrait:grid-cols-[auto_1fr] md:max-lg:portrait:items-center md:max-lg:portrait:gap-x-10" style="--d:.16s">
            <h2 class="text-white font-semibold mb-1 md:max-lg:portrait:col-span-2">Detection Results</h2>
            <p class="text-sm text-slate-300 mb-4 md:max-lg:portrait:col-span-2">Result breakdown for {{ $periods[$period] ?? '' }}</p>
            <div class="relative w-40 h-40 mx-auto mb-5 md:max-lg:portrait:mb-0 md:max-lg:portrait:ml-4">
                <canvas id="breakdownChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="text-2xl font-bold text-white">{{ $breakdown['total'] }}</span>
                    <span class="text-[10px] font-medium tracking-wide text-slate-300">TOTAL</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                @foreach ($breakdownRows as $row)
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-100"><span class="w-2 h-2 rounded-full {{ $tones[$row['tone']]['bg'] }}"></span> {{ $row['label'] }}</span>
                        <span class="text-slate-100 font-medium">{{ $breakdown[$row['key']] }} <span class="text-slate-400 font-normal">({{ $breakdown['total'] > 0 ? round($breakdown[$row['key']] / $breakdown['total'] * 100, 1) : 0 }}%)</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- RISK DISTRIBUTION + INDICATORS --}}
    @php $indicatorLimit = 6; @endphp
    <div class="grid md:grid-cols-2 gap-4 mb-4 md:max-xl:landscape:mb-3" x-data="{ showAll: false }" @keydown.escape.window="showAll = false">
        <div class="a-card a-in p-4 sm:p-6 flex flex-col" style="--d:.1s">
            <h2 class="text-white font-semibold mb-1">Risk-Level Distribution</h2>
            <p class="text-sm text-slate-300 mb-5">Number of reports in each risk bracket</p>

            {{-- one stacked bar = the whole picture at a glance --}}
            <div class="flex h-3 rounded-full overflow-hidden bg-white/10 mb-6 gap-0.5">
                @foreach ($riskBuckets as $bucket)
                    @php $tone = $tones[$bucket['color']] ?? $tones['sky']; @endphp
                    @if ($bucket['pct'] > 0)
                        <span class="a-grow block h-full {{ $tone['bg'] }}" style="width: {{ $bucket['pct'] }}%; --d: {{ 0.2 + $loop->index * 0.08 }}s" title="{{ $bucket['label'] }}: {{ $bucket['count'] }}"></span>
                    @endif
                @endforeach
            </div>

            {{-- tiles stretch to fill the card, so it matches the height of the card beside it --}}
            <div class="grid grid-cols-2 gap-3 flex-1 auto-rows-fr">
                @foreach ($riskBuckets as $bucket)
                    @php
                        $tone = $tones[$bucket['color']] ?? $tones['sky'];
                        $bucketName = trim(preg_replace('/\s*\(.*\)$/', '', $bucket['label']));
                        $bucketRange = preg_match('/\((.*)\)/', $bucket['label'], $mm) ? $mm[1] : '';
                    @endphp
                    <div class="a-well p-3.5 sm:p-4 flex flex-col justify-between min-h-[7.25rem] sm:min-h-[8.5rem]">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-100">{{ $bucketName }}</p>
                                <p class="text-xs text-slate-400 a-mono mt-0.5">{{ $bucketRange }}</p>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full mt-1 {{ $tone['bg'] }}"></span>
                        </div>
                        <div>
                            <p class="flex items-baseline gap-2">
                                <span class="text-3xl sm:text-4xl font-bold {{ $tone['text'] }}">{{ $bucket['count'] }}</span>
                                <span class="hidden sm:inline text-xs text-slate-300">{{ $bucket['count'] == 1 ? 'report' : 'reports' }}</span>
                                <span class="ml-auto text-sm font-semibold text-slate-200">{{ $bucket['pct'] }}%</span>
                            </p>
                            <span class="block h-1.5 rounded-full bg-white/10 overflow-hidden mt-3">
                                <span class="a-grow block h-full rounded-full {{ $tone['bg'] }}" style="width: {{ $bucket['pct'] }}%; --d: {{ 0.3 + $loop->index * 0.08 }}s"></span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="a-card a-in p-4 sm:p-6" style="--d:.16s">
            <h2 class="text-white font-semibold mb-1">Most Common Phishing Indicators</h2>
            <p class="text-sm text-slate-300 mb-5">Top triggers across your flagged reports, across all detection types (URL, email, phone, screenshot)</p>
            @if (count($indicatorCounts) > 0)
                <div class="space-y-4" data-indicators>
                    @foreach (array_slice($indicatorCounts, 0, $indicatorLimit, true) as $name => $count)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm mb-1.5">
                                <span class="text-slate-100 min-w-0"><span class="text-slate-400">{{ $loop->iteration }}.</span> {{ $name }}</span>
                                <span class="text-sky-300 font-semibold">{{ $count }}</span>
                            </div>
                            <span class="block h-2 rounded-full bg-white/10 overflow-hidden">
                                <span class="a-grow block h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($count / $maxIndicatorCount * 100) }}%; --d: {{ 0.2 + $loop->index * 0.07 }}s"></span>
                            </span>
                        </div>
                    @endforeach
                </div>
                @if (count($indicatorCounts) > $indicatorLimit)
                    <button type="button" class="a-more" @click="showAll = true" data-indicators-toggle>
                        <span>View all {{ count($indicatorCounts) }} indicators</span>
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg>
                    </button>
                @endif
            @else
                <p class="text-sm text-slate-300">No flagged indicators in this period yet.</p>
            @endif
        </div>
        @if (count($indicatorCounts) > $indicatorLimit)
            <div class="a-modal" x-show="showAll" x-cloak x-transition.opacity.duration.150ms @click.self="showAll = false" role="dialog" aria-modal="true" aria-labelledby="indicators-title" data-indicators-modal x-effect="if (showAll) $nextTick(() => $refs.closeIndicators && $refs.closeIndicators.focus())">
                <div class="a-modal-panel" x-show="showAll" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="a-modal-head">
                        <div class="min-w-0">
                            <h3 id="indicators-title" class="text-white font-semibold text-lg">All phishing indicators</h3>
                            <p class="text-sm text-slate-300 mt-0.5">{{ count($indicatorCounts) }} triggers across your flagged reports in this period</p>
                        </div>
                        <button type="button" class="a-modal-x" x-ref="closeIndicators" @click="showAll = false" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="a-modal-body space-y-4">
                        @foreach ($indicatorCounts as $name => $count)
                            <div data-indicator-row>
                                <div class="flex items-center justify-between gap-3 text-sm mb-1.5">
                                    <span class="text-slate-100 min-w-0"><span class="text-slate-400">{{ $loop->iteration }}.</span> {{ $name }}</span>
                                    <span class="text-sky-300 font-semibold">{{ $count }}</span>
                                </div>
                                <span class="block h-2 rounded-full bg-white/10 overflow-hidden">
                                    <span class="block h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($count / $maxIndicatorCount * 100) }}%"></span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- PERIOD COMPARISON + SCANNING PERFORMANCE --}}
    <div class="grid md:grid-cols-2 gap-4 mb-4 md:max-xl:landscape:mb-3">
        <div class="a-card a-in p-4 sm:p-6" style="--d:.1s">
            <h2 class="text-white font-semibold mb-1">Period Comparison</h2>
            <p class="text-sm text-slate-300 mb-5">Current vs previous {{ strtolower($periods[$period] ?? '') }}</p>
            <div class="space-y-4">
                @foreach ($breakdownRows as $row)
                    @php
                        $pc = $periodComparison[$row['key']];
                        $tone = $tones[$row['tone']];
                        $peak = max($pc['current'], $pc['previous'], 1);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="flex items-center gap-2 text-slate-100"><span class="w-2 h-2 rounded-full {{ $tone['bg'] }}"></span> {{ $row['label'] }}</span>
                            <span class="flex items-center gap-2.5">
                                <span class="text-white font-semibold">{{ $pc['current'] }}</span>
                                <span class="text-xs font-semibold {{ $pc['change'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $pc['change'] > 0 ? '+' : '' }}{{ $pc['change'] }}%</span>
                            </span>
                        </div>
                        <div class="flex gap-1.5">
                            <span class="flex-1 h-2 rounded-full bg-white/10 overflow-hidden">
                                <span class="a-grow block h-full rounded-full bg-slate-400/60" style="width: {{ $pc['previous'] > 0 ? min(100, round($pc['previous'] / $peak * 100)) : 0 }}%"></span>
                            </span>
                            <span class="flex-1 h-2 rounded-full bg-white/10 overflow-hidden">
                                <span class="a-grow block h-full rounded-full {{ $tone['bg'] }}" style="width: {{ $pc['current'] > 0 ? min(100, round($pc['current'] / $peak * 100)) : 0 }}%; --d:.3s"></span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 mt-5 text-xs text-slate-300">
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-slate-400/60"></span> Previous</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 rounded-full bg-sky-400"></span> Current</span>
            </div>
        </div>

        <div class="a-card a-in p-4 sm:p-6" style="--d:.16s">
            <h2 class="text-white font-semibold mb-1">Scanning Performance</h2>
            <p class="text-sm text-slate-300 mb-5">Engine response times across your reports</p>
            @if ($performance)
                <div class="grid grid-cols-2 gap-3">
                    <div class="a-well p-4">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">AVERAGE</p>
                        <p class="text-2xl font-bold text-white">{{ msLabel($performance['avg_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1">Across {{ $performance['count'] }} timed reports</p>
                    </div>
                    <div class="a-well p-4">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">MEDIAN</p>
                        <p class="text-2xl font-bold text-white">{{ msLabel($performance['median_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1">P50 percentile</p>
                    </div>
                    <div class="a-well p-4 min-w-0">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">FASTEST</p>
                        <p class="text-2xl font-bold text-emerald-400">{{ msLabel($performance['fastest_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1 truncate a-mono" title="{{ $performance['fastest_label'] }}">{{ $performance['fastest_label'] }}</p>
                    </div>
                    <div class="a-well p-4 min-w-0">
                        <p class="text-xs font-medium tracking-wide text-slate-300 mb-1">SLOWEST</p>
                        <p class="text-2xl font-bold text-orange-400">{{ msLabel($performance['slowest_ms']) }}</p>
                        <p class="text-xs text-slate-400 mt-1 truncate a-mono" title="{{ $performance['slowest_label'] }}">{{ $performance['slowest_label'] }}</p>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-300">No timing data yet — this started being recorded with your most recent reports. Submit a few more scans to populate this.</p>
            @endif
        </div>
    </div>

    {{-- TOP SOURCE COUNTRIES --}}
    <div class="a-card a-in p-4 sm:p-6 mb-4" style="--d:.1s">
        <h2 class="text-white font-semibold mb-1">Top Source Countries</h2>
        <p class="text-sm text-slate-300 mb-5">Countries your scanned URLs were hosted in, based on IP geolocation</p>
        @if ($topCountries->count() > 0)
            <div class="space-y-4">
                @foreach ($topCountries as $c)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="text-slate-100"><span class="text-slate-400">{{ $loop->iteration }}.</span> {{ $c['country'] }}</span>
                            <span class="flex items-center gap-3">
                                @if ($c['phishing_count'] > 0)
                                    <span class="text-xs font-semibold text-red-400">{{ $c['phishing_count'] }} phishing</span>
                                @endif
                                <span class="text-sky-300 font-semibold">{{ $c['count'] }}</span>
                            </span>
                        </div>
                        <span class="block h-2 rounded-full bg-white/10 overflow-hidden">
                            <span class="a-grow block h-full rounded-full bg-gradient-to-r from-sky-400 to-blue-500" style="width: {{ round($c['count'] / $maxCountryCount * 100) }}%; --d: {{ 0.2 + $loop->index * 0.07 }}s"></span>
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-300">No country data available yet — this is captured for URL scans going forward.</p>
        @endif
    </div>

    {{-- TOP PHISHING SOURCES --}}
    <div class="a-card a-in p-4 sm:p-6 mb-4" style="--d:.14s">
        <h2 class="text-white font-semibold mb-1">Top Phishing Sources</h2>
        <p class="text-sm text-slate-300 mb-5">Your most frequently detected phishing sources in this period — URLs, sender domains, phone numbers, or screenshots</p>

        @if ($topDomains->count() > 0)
            <div class="md:overflow-x-auto md:-mx-2">
                <table class="block md:table w-full text-sm md:min-w-[600px]">
                    <thead class="hidden md:table-header-group">
                        <tr class="text-left text-xs font-medium tracking-wide text-slate-300 border-b border-slate-500/25">
                            <th class="pb-3 px-2 w-10">#</th>
                            <th class="pb-3 px-2">SOURCE</th>
                            <th class="pb-3 px-2">DETECTIONS</th>
                            <th class="pb-3 px-2">AVG RISK SCORE</th>
                            <th class="pb-3 px-2">LATEST DETECTION</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group divide-y divide-slate-500/20">
                        @foreach ($topDomains as $i => $d)
                            <tr class="a-row a-src-row flex flex-wrap items-center gap-x-3 gap-y-1.5 py-3 md:py-0 md:table-row">
                                <td class="w-6 md:w-auto md:py-3 md:px-2 text-slate-400">{{ $i + 1 }}</td>
                                <td class="a-src-name flex-1 min-w-0 md:py-3 md:px-2 md:w-full md:max-w-0">
                                    <span class="flex items-center gap-2 text-slate-100 min-w-0" title="{{ $d['domain'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                        <span class="truncate a-src-text a-mono text-[13px]">{{ $d['domain'] }}</span>
                                    </span>
                                </td>
                                <td class="pl-9 md:pl-2 md:py-3 md:px-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-red-400 font-semibold w-5">{{ $d['detections'] }}</span>
                                        <span class="w-20 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                            <span class="block h-full rounded-full bg-red-400" style="width: {{ round($d['detections'] / $maxDetections * 100) }}%"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-xs md:text-sm md:py-3 md:px-2 whitespace-nowrap text-orange-400 font-semibold">{{ $d['avg_score'] }} <span class="text-slate-400 font-normal">/ 100</span></td>
                                <td class="ml-auto md:ml-0 text-xs md:text-sm md:py-3 md:px-2 whitespace-nowrap text-slate-300">{{ \Carbon\Carbon::parse($d['latest'])->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-300">No phishing detections in this period yet.</p>
        @endif
    </div>

    @if ($breakdown['total'] === 0)
        <p class="text-center text-sm text-slate-300 mt-6">No reports found in this period yet — try a wider date range or submit a few scans first.</p>
    @endif

    {{-- room at the bottom so the floating chat button never covers the last row --}}
    </div>{{-- /panel-mine --}}
    </div>{{-- /tabs --}}

    <div class="a-bottom-spacer" aria-hidden="true"></div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = "'Manrope', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = '#94a3b8';

        const tooltip = {
            backgroundColor: 'rgba(8, 15, 32, .95)',
            borderColor: 'rgba(148, 163, 184, .3)',
            borderWidth: 1,
            titleColor: '#f1f5f9',
            bodyColor: '#cbd5e1',
            padding: 10,
            cornerRadius: 10,
            displayColors: false,
        };

        const communityCtx = document.getElementById('communityChart');
        if (communityCtx) {
            const days = @json($community['days']);
            new Chart(communityCtx, {
                type: 'bar',
                data: {
                    labels: days.map((d, i) => i === days.length - 1 ? 'Today' : d.label),
                    datasets: [
                        { label: 'Safe', data: days.map(d => d.clean), backgroundColor: '#34d399', borderRadius: 4 },
                        { label: 'Medium risk', data: days.map(d => d.suspicious), backgroundColor: '#fb923c', borderRadius: 4 },
                        { label: 'High risk', data: days.map(d => d.phishing), backgroundColor: '#f87171', borderRadius: 4 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: true, labels: { boxWidth: 10, color: '#94a3b8' } }, tooltip: { ...tooltip, displayColors: true } },
                    scales: {
                        x: { stacked: true, ticks: { color: '#94a3b8' }, grid: { display: false }, border: { display: false } },
                        y: { stacked: true, beginAtZero: true, ticks: { color: '#94a3b8', precision: 0 }, grid: { color: 'rgba(148,163,184,0.12)' }, border: { display: false } }
                    }
                }
            });
        }

        const activityCtx = document.getElementById('activityChart');
        const fill = activityCtx.getContext('2d').createLinearGradient(0, 0, 0, 260);
        fill.addColorStop(0, 'rgba(56, 189, 248, 0.30)');
        fill.addColorStop(1, 'rgba(56, 189, 248, 0)');

        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Reports',
                    data: @json($chartCounts),
                    borderColor: '#38bdf8',
                    backgroundColor: fill,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#38bdf8',
                    pointHoverBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: window.matchMedia('(min-width: 1280px)').matches,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip },
                scales: {
                    x: { ticks: { color: '#94a3b8', maxTicksLimit: 8 }, grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, ticks: { color: '#94a3b8', precision: 0 }, grid: { color: 'rgba(148,163,184,0.12)' }, border: { display: false } }
                }
            }
        });

        const breakdownCtx = document.getElementById('breakdownChart');
        new Chart(breakdownCtx, {
            type: 'doughnut',
            data: {
                labels: ['Safe', 'Suspicious', 'Phishing'],
                datasets: [{
                    data: [{{ $breakdown['safe'] }}, {{ $breakdown['suspicious'] }}, {{ $breakdown['phishing'] }}],
                    backgroundColor: ['#34d399', '#fb923c', '#f87171'],
                    borderWidth: 0,
                    spacing: 3,
                    borderRadius: 6,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                cutout: '72%',
                plugins: { legend: { display: false }, tooltip: { ...tooltip, displayColors: true } }
            }
        });
    </script>
    @endpush

</x-layouts.dashboard>