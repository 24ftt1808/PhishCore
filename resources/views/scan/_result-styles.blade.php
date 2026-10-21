<style>
    /* verdict headline uses the same monospace face as the scanned URL */
    .r-headline { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: -.03em; }

    /* result page panels: gradient + hairline border, no blur filters, transform/opacity animation only */
    .r-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
    .r-hero { background: linear-gradient(135deg, rgba(var(--c), .16), rgba(14, 25, 50, .66) 58%); border-color: rgba(var(--c), .32); }
    /* the verdict is the loudest thing on this page: a stronger tint and glow in the verdict colour, a larger headline, calmer background behind it */
    .r-hero { border-color: rgba(var(--c), .5); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06), 0 0 0 1px rgba(var(--c), .1), 0 18px 50px -24px rgba(var(--c), .55); }
    .r-hero .r-tile { width: 3.4rem !important; height: 3.4rem !important; box-shadow: 0 0 22px -4px rgba(var(--c), .6); }
    .r-hero .r-headline { font-size: clamp(1.5rem, 1rem + 2.4vw, 2.5rem); line-height: 1.1; }
    .r-hero .text-slate-400 { color: #b0bccf; }
    .dash-aurora { opacity: .28 !important; }
    .r-tile { display: grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: .8rem; background: rgba(var(--c), .13); border: 1px solid rgba(var(--c), .32); flex-shrink: 0; }
    .r-well { background: rgba(8, 15, 32, .5); border: 1px solid rgba(148, 163, 184, .16); border-radius: .8rem; }
    .r-lift { transition: transform .28s cubic-bezier(.34, 1.4, .64, 1), border-color .25s; }
    .r-lift:hover { transform: translateY(-2px); border-color: rgba(148, 163, 184, .34); }
    .r-row { transition: background-color .2s; }
    .r-row:hover { background-color: rgba(125, 211, 252, .06); }

    .r-in { animation: r-in .6s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
    @keyframes r-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .r-grow { transform-origin: left; animation: r-grow .9s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, .3s); }
    @keyframes r-grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }
    .r-pin { animation: r-pin .9s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, .3s); }
    @keyframes r-pin { from { transform: translateX(-100%); } to { transform: translateX(var(--to)); } }
    .r-arc { animation: r-arc 1.2s cubic-bezier(.2, .9, .3, 1) .25s both; }
    @keyframes r-arc { from { stroke-dashoffset: var(--from); } to { stroke-dashoffset: var(--to); } }

    .r-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem 1.2rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), background-color .2s, border-color .2s, opacity .2s; }
    .r-btn:hover { transform: translateY(-1px); }
    .r-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
    .r-btn-main:hover { opacity: .92; }
    .r-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); background: rgba(148, 163, 184, .06); }
    .r-btn-ghost:hover { background: rgba(148, 163, 184, .13); border-color: rgba(148, 163, 184, .5); }

    /* verdict card: facts strip (2 columns on phones, one per fact from sm up) and a soft live-dot ping */
    .r-facts > div:nth-child(even) { border-left: 1px solid rgba(255, 255, 255, .1); }
    .r-facts > div:nth-child(n+3) { border-top: 1px solid rgba(255, 255, 255, .1); }
    .r-facts > div:last-child:nth-child(odd) { grid-column: 1 / -1; }
    @media (min-width: 640px) {
        .r-facts > div:last-child:nth-child(odd) { grid-column: auto; }
        .r-facts { grid-template-columns: repeat(var(--n, 4), minmax(0, 1fr)); }
        .r-facts > div { border-top: 0 !important; }
        .r-facts > div + div { border-left: 1px solid rgba(255, 255, 255, .1); }
    }
    .r-ping { animation: r-ping 2.4s cubic-bezier(0, 0, .2, 1) infinite; }
    @keyframes r-ping { 0% { transform: scale(1); opacity: .5; } 70%, 100% { transform: scale(2.4); opacity: 0; } }

    /* phones held upright: tighter panels, full-width buttons, smaller URL text */
    @media (max-width: 639px) {
        .r-card.p-6 { padding: 1.1rem; }
        .r-hero > .p-6 { padding: 1.15rem; }
        .r-hero .r-facts > div { padding-left: 1.15rem; padding-right: 1.15rem; }
        .r-hero > .flex-wrap { padding: 1rem 1.15rem; gap: .6rem; }
        .r-hero .r-btn { width: 100%; justify-content: center; }
        .r-hero .r-well p { font-size: .75rem; }
    }

    /* VirusTotal card. Sized by the card's own width (container queries), not the screen, so it fits phones, tablets and wide screens in either orientation */
    .vt { container-type: inline-size; }
    .vt-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
    .vt-id { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .vt-pills { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .vt-score { padding: .375rem .75rem; border-radius: 999px; border: 1px solid rgba(148, 163, 184, .25); background: rgba(148, 163, 184, .08); font-size: .75rem; font-weight: 600; color: #cbd5e1; white-space: nowrap; }
    .vt-hero { display: grid; justify-items: center; gap: 1.4rem; margin-top: 1.5rem; }
    .vt-donut { position: relative; width: 9.5rem; height: 9.5rem; flex: none; }
    .vt-donut svg { display: block; width: 100%; height: 100%; transform: rotate(-90deg); }
    .vt-donut-mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .vt-donut-num { font-size: 2.6rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
    .vt-donut-cap { margin-top: .45rem; font-size: .66rem; font-weight: 700; line-height: 1.3; letter-spacing: .08em; color: #94a3b8; }
    .vt-donut-cap span { display: block; }
    .vt-legend { list-style: none; margin: 0; padding: 0; width: 100%; display: flex; flex-direction: column; gap: .9rem; }
    .vt-row { display: grid; grid-template-columns: .65rem 5.2rem minmax(0, 1fr) 2.2rem; align-items: center; column-gap: .75rem; }
    .vt-dot { width: .65rem; height: .65rem; border-radius: .2rem; }
    .vt-label { font-size: .875rem; font-weight: 600; color: #e2e8f0; }
    .vt-track { height: .5rem; border-radius: 999px; background: rgba(148, 163, 184, .14); overflow: hidden; }
    .vt-fill { display: block; height: 100%; border-radius: 999px; }
    .vt-count { text-align: right; font-size: 1.1rem; font-weight: 800; color: #fff; font-variant-numeric: tabular-nums; }
    .vt-flagged { margin-top: 1.5rem; padding: 1rem 1.1rem; }
    .vt-flagged-top { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .25rem .75rem; }
    .vt-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .1em; color: #94a3b8; }
    .vt-note { font-size: .75rem; color: #94a3b8; }
    .vt-empty { margin-top: 1.25rem; }
    .vt-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
    .vt-chip { display: inline-flex; align-items: center; gap: .5rem; max-width: 100%; padding: .4rem .7rem; border-radius: .65rem; border: 1px solid; font-size: .8rem; font-weight: 700; }
    .vt-chip-name, .vt-chip-res { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .vt-chip-res { font-weight: 600; opacity: .85; }
    .vt-chip-malicious { background: rgba(248, 113, 113, .1); border-color: rgba(248, 113, 113, .3); color: #fecaca; }
    .vt-chip-suspicious { background: rgba(251, 146, 60, .1); border-color: rgba(251, 146, 60, .3); color: #fed7aa; }
    .vt-chip-more { background: rgba(148, 163, 184, .1); border-color: rgba(148, 163, 184, .25); color: #cbd5e1; }
    .vt-clear { display: flex; align-items: center; gap: .6rem; margin-top: 1.5rem; padding: .9rem 1.1rem; font-size: .875rem; font-weight: 600; color: #a7f3d0; }
    .vt-clear svg { flex: none; }
    .vt-foot-wrap { margin-top: 1.4rem; padding-top: 1.1rem; border-top: 1px solid rgba(148, 163, 184, .16); }
    .vt-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem; }
    .vt-btn { display: inline-flex; align-items: center; gap: .65rem; min-height: 2.75rem; padding: .6rem 1.1rem; border-radius: .75rem; border: 1px solid rgba(125, 211, 252, .35); background: rgba(56, 189, 248, .1); color: #e0f2fe; font-size: .875rem; font-weight: 700; text-align: left; transition: background-color .2s, border-color .2s; }
    .vt-btn:hover { background: rgba(56, 189, 248, .17); border-color: rgba(125, 211, 252, .55); }
    .vt-btn:focus-visible { outline: 2px solid rgba(125, 211, 252, .8); outline-offset: 2px; }
    .vt-btn-ic { width: 1rem; height: 1rem; flex: none; color: #7dd3fc; transition: transform .2s; }
    .vt-src { font-size: .75rem; color: #94a3b8; }
    /* room to spare (tablets, landscape phones, desktop): the chart sits beside the legend */
    @container (min-width: 34rem) {
        .vt-hero { grid-template-columns: auto minmax(0, 1fr); justify-items: stretch; align-items: center; gap: 2.75rem; }
        .vt-donut { width: 10.5rem; height: 10.5rem; }
        .vt-flagged { padding: 1.1rem 1.25rem; }
    }
    /* tight (phones held upright): shorter label column, full-width button, source line under it */
    @container (max-width: 26rem) {
        .vt-head { gap: .9rem; }
        .vt-head h2 { font-size: 1.05rem; }
        .vt-hero { margin-top: 1.75rem; gap: 1.75rem; }
        .vt-row { grid-template-columns: .65rem 4.8rem minmax(0, 1fr) 2rem; column-gap: .6rem; }
        .vt-flagged { padding: .9rem; }
        .vt-btn { width: 100%; justify-content: space-between; }
        .vt-src { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) { .vt-btn, .vt-btn-ic { transition: none; } }

    /* Page preview: grey placeholder shapes shown while the picture is being taken */
    .pv-skel { overflow: hidden; border-radius: .6rem; border: 1px solid rgba(255, 255, 255, .1); background: rgba(0, 0, 0, .3); }
    .pv-skel-bar { display: flex; align-items: center; gap: .4rem; padding: .6rem .8rem; border-bottom: 1px solid rgba(255, 255, 255, .08); }
    .pv-skel-dot { width: .55rem; height: .55rem; border-radius: 999px; background: rgba(148, 163, 184, .25); }
    .pv-skel-url { flex: 1; max-width: 22rem; height: .9rem; margin-left: .5rem; border-radius: 999px; background: rgba(148, 163, 184, .14); }
    .pv-skel-body { display: grid; gap: .8rem; align-content: start; min-height: 17rem; padding: 1.1rem 1.25rem 1.4rem; }
    .pv-skel-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .pv-skel-nav { display: flex; gap: .75rem; }
    .pv-skel-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .pv-sk { position: relative; display: block; overflow: hidden; border-radius: .45rem; background: rgba(148, 163, 184, .14); }
    .pv-sk::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .09), transparent); animation: pv-shimmer 1.5s ease-in-out infinite; }
    @keyframes pv-shimmer { to { transform: translateX(100%); } }
    .pv-tip { display: flex; flex-wrap: wrap; align-items: baseline; gap: .35rem .6rem; margin-top: .75rem; padding: .65rem .85rem; border-radius: .5rem; border-left: 3px solid #38bdf8; background: rgba(56, 189, 248, .08); color: #cbd5e1; font-size: .85rem; line-height: 1.45; min-height: 3.2rem; }
    .pv-tip-label { font-weight: 700; color: #7dd3fc; }
    @media (max-width: 639px) {
        .pv-skel-body { min-height: 14rem; padding: .9rem 1rem 1.1rem; }
        .pv-skel-nav { display: none; }
        .pv-skel-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pv-skel-cards .pv-sk:nth-child(3) { display: none; }
    }
    @media (prefers-reduced-motion: reduce) { .pv-sk::after { animation: none; } }

    @media (prefers-reduced-motion: reduce) { .r-in, .r-grow, .r-arc, .r-ping { animation: none; } .r-pin { transform: translateX(var(--to)); } .r-lift:hover, .r-btn:hover { transform: none; } }
</style>