<style>
    /* result page panels: gradient + hairline border, no blur filters, transform/opacity animation only */
    .r-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.1rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
    .r-hero { background: linear-gradient(135deg, rgba(var(--c), .16), rgba(14, 25, 50, .66) 58%); border-color: rgba(var(--c), .32); }
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
    .r-arc { animation: r-arc 1.2s cubic-bezier(.2, .9, .3, 1) .25s both; }
    @keyframes r-arc { from { stroke-dashoffset: var(--from); } to { stroke-dashoffset: var(--to); } }

    .r-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem 1.2rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; transition: transform .25s cubic-bezier(.34, 1.4, .64, 1), background-color .2s, border-color .2s, opacity .2s; }
    .r-btn:hover { transform: translateY(-1px); }
    .r-btn-main { color: #fff; background: linear-gradient(90deg, #38bdf8, #2563eb); }
    .r-btn-main:hover { opacity: .92; }
    .r-btn-ghost { color: #e2e8f0; border: 1px solid rgba(148, 163, 184, .3); background: rgba(148, 163, 184, .06); }
    .r-btn-ghost:hover { background: rgba(148, 163, 184, .13); border-color: rgba(148, 163, 184, .5); }

    @media (prefers-reduced-motion: reduce) { .r-in, .r-grow, .r-arc { animation: none; } .r-lift:hover, .r-btn:hover { transform: none; } }
</style>