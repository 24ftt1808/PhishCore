<x-layouts.dashboard>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@500;700;800&display=swap');

        /* Same look as the rest of PhishCore: dark glass, thin borders, sky blue. The pixel font is only for the page title, the level badge and the big scores. */
        .pl-wrap { --pl-display: 'Press Start 2P', 'Courier New', ui-monospace, monospace; --pl-body: 'Nunito', system-ui, -apple-system, 'Segoe UI', sans-serif;
            width: 100%; max-width: 68rem; font-family: var(--pl-body); padding-bottom: calc(1rem + env(safe-area-inset-bottom)); }
        .pl-wrap button { font-family: inherit; -webkit-tap-highlight-color: transparent; touch-action: manipulation; }

        /* ---------- header + level ---------- */
        .pl-hero { position: relative; isolation: isolate; display: grid; --hx: 1.2rem; --hy: 1.1rem; --r: 1.5rem; gap: 1rem; padding: var(--hy) var(--hx); margin-bottom: 1rem; z-index: 5; border-radius: var(--r); background: linear-gradient(160deg, rgba(22, 38, 74, .93), rgba(10, 20, 44, .95)); border: 1px solid rgba(255, 255, 255, .1); box-shadow: 0 24px 50px -30px rgba(0, 0, 0, .85); }
        .pl-hero::before { content: ""; position: absolute; inset: 0; z-index: -1; border-radius: inherit; pointer-events: none; background: radial-gradient(60% 80% at 8% 0%, rgba(125, 211, 252, .16), transparent 70%), radial-gradient(50% 70% at 100% 100%, rgba(167, 139, 250, .14), transparent 70%); }
        .pl-hero h1 { font-family: var(--pl-display); font-size: 1rem; line-height: 1.5; color: #fff; text-shadow: 0 0 22px rgba(56, 189, 248, .45); }
        .pl-hero p { margin-top: .5rem; font-size: .92rem; font-weight: 600; color: #cbd5e1; max-width: 34rem; line-height: 1.55; }
        .pl-hud { position: relative; z-index: 3; display: flex; align-items: center; gap: .8rem; padding: .6rem .9rem .6rem .6rem; border-radius: 1.1rem; background: rgba(2, 8, 23, .45); border: 1px solid rgba(255, 255, 255, .1); }
        .pl-lvl { flex-shrink: 0; display: grid; place-items: center; width: 2.9rem; height: 2.9rem; border-radius: .85rem; font-family: var(--pl-display); font-size: .9rem; color: #fff; background: linear-gradient(135deg, #38bdf8, #2563eb); border: 1px solid rgba(186, 230, 253, .6); box-shadow: 0 8px 20px -8px rgba(56, 189, 248, .85); }
        .pl-hud-main { min-width: 0; flex: 1; }
        .pl-rankbtn { display: inline-flex; align-items: center; gap: .3rem; padding: .1rem .2rem .1rem 0; font-weight: 800; color: #fff; border-radius: .5rem; }
        .pl-rankbtn svg { width: .9rem; height: .9rem; color: #7dd3fc; transition: transform .2s; }
        .pl-rankbtn svg.pl-rk { width: 1.15rem; height: 1.15rem; margin-right: .1rem; }
        .pl-rankbtn[aria-expanded="true"] svg:last-child { transform: rotate(180deg); }
        .pl-rankbtn:hover { color: #bae6fd; }
        .pl-rankbtn:focus-visible { outline: 2px solid #7dd3fc; outline-offset: 2px; }
        .pl-ranks-pop { position: absolute; z-index: 20; top: calc(100% + .45rem); left: 0; right: 0; padding: .5rem; border-radius: 1.2rem; transform-origin: 18% 0; background: linear-gradient(160deg, rgba(48, 84, 150, .66), rgba(14, 26, 56, .8)); -webkit-backdrop-filter: blur(18px) saturate(1.5); backdrop-filter: blur(18px) saturate(1.5); border: 1px solid rgba(186, 230, 253, .4); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5), inset 0 -14px 28px -12px rgba(56, 189, 248, .3), 0 28px 50px -16px rgba(0, 0, 0, .85), 0 0 40px -10px rgba(56, 189, 248, .45); overflow: hidden; }
        .pl-ranks-pop::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 42%; border-radius: inherit; background: linear-gradient(180deg, rgba(255, 255, 255, .2), rgba(255, 255, 255, 0)); pointer-events: none; }
        .pl-ranks-pop::after { content: ""; position: absolute; right: -3rem; bottom: -4rem; width: 9rem; height: 9rem; border-radius: 9999px; background: radial-gradient(circle, rgba(56, 189, 248, .4), transparent 70%); pointer-events: none; animation: pl-blob 5s ease-in-out infinite alternate; }
        @keyframes pl-blob { to { transform: translate(-3rem, -1rem) scale(1.25); } }
        .pl-liq-in { transition: transform .6s cubic-bezier(.34, 1.56, .64, 1), opacity .3s ease, border-radius .6s cubic-bezier(.34, 1.56, .64, 1); }
        .pl-liq-from { opacity: 0; transform: scale(.55, .3) translateY(-10px); border-radius: 2.6rem; }
        .pl-liq-to { opacity: 1; transform: none; border-radius: 1.2rem; }
        .pl-liq-out { transition: transform .2s ease-in, opacity .18s ease-in; }
        .pl-liq-gone { opacity: 0; transform: scale(.9, .6) translateY(-6px); }
        .pl-ranks-pop .pl-rank { position: relative; color: #94a3b8; animation: pl-liq-item .55s cubic-bezier(.34, 1.56, .64, 1) backwards; animation-delay: calc(var(--i, 0) * 45ms + 120ms); }
        .pl-ranks-pop .pl-rank.done { color: #e2e8f0; }
        .pl-ranks-pop .pl-rank.now { color: #fff; background: rgba(56, 189, 248, .24); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .4); }
        @keyframes pl-liq-item { from { opacity: 0; transform: translateY(-10px) scale(.85); } }
        .pl-ranks-pop .pl-ranks { gap: .15rem; }
        .pl-ranks-pop .pl-rank { padding: .25rem .5rem; font-size: .85rem; }
        .pl-ranks-pop .pl-rank i { width: 1.6rem; height: 1.6rem; }
        .pl-rank i svg { width: 1rem; height: 1rem; }
        .pl-rank .lv { font-size: .66rem; font-weight: 800; color: #64748b; }
        .pl-ranks-pop .pl-ranks { max-height: min(27rem, 62dvh); overflow-y: auto; scrollbar-width: none; }
        .pl-ranks-pop .pl-ranks::-webkit-scrollbar { display: none; }
        .pl-hud-title { display: flex; justify-content: space-between; align-items: center; gap: .5rem; font-size: .9rem; font-weight: 800; line-height: 1.4; color: #fff; }
        .pl-hud-title span:last-child { color: #7dd3fc; font-size: .8rem; white-space: nowrap; }
        .pl-xpbar { position: relative; height: .6rem; margin-top: .4rem; overflow: hidden; border-radius: 9999px; background: rgba(148, 163, 184, .22); box-shadow: inset 0 1px 2px rgba(0, 0, 0, .35); }
        .pl-xpbar i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #38bdf8, #6366f1); box-shadow: 0 0 12px rgba(56, 189, 248, .6); transition: width .6s ease; }
        @container (min-width: 52rem) { .pl-hero { --hx: 1.6rem; --hy: 1.3rem; grid-template-columns: 1fr minmax(18rem, 22rem); align-items: center; } .pl-hero h1 { font-size: 1.3rem; } }

        /* ---------- game picker tabs ---------- */
        .pl-tabs { display: grid; grid-template-columns: repeat(3, 1fr) auto; gap: .6rem; margin-bottom: 1rem; }
        .pl-tabs.nobtn { grid-template-columns: repeat(3, 1fr); }
        .pl-layout.solo .pl-aside { display: none !important; }
        /* Phones and tablets, in any orientation: no button, and the three boxes always sit below the games. */
        @media (max-width: 1279px), (pointer: coarse) {
            .pl-sidebtn { display: none !important; }
            .pl-tabs, .pl-tabs.nobtn { grid-template-columns: repeat(3, 1fr); }
            .pl-layout:not(.solo) .pl-aside { display: grid !important; opacity: 1 !important; transform: none !important; }
        }
        @media (min-width: 1280px) and (pointer: coarse) {
            .pl-layout.noside:not(.solo) { grid-template-columns: minmax(0, 1fr) 17rem; column-gap: 1.25rem; }
        }
        @media (min-width: 1600px) and (pointer: coarse) {
            .pl-layout.noside:not(.solo) { grid-template-columns: minmax(0, 1fr) 19rem; }
        }
        @media (max-height: 520px) and (orientation: landscape) { .pl-layout:not(.solo) .pl-aside { display: none !important; } }
        .pl-sidebtn { display: grid; place-items: center; width: 3.2rem; min-height: 3.4rem; padding: 0; border-radius: 1rem; color: #cbd5e1; background: rgba(15, 28, 58, .7); border: 1px solid rgba(255, 255, 255, .1); transition: transform .2s ease, background .2s, border-color .2s, color .2s; }
        .pl-sidebtn svg { width: 1.45rem; height: 1.45rem; }
        .pl-sidebtn:hover { transform: translateY(-2px); color: #fff; border-color: rgba(125, 211, 252, .45); }
        .pl-sidebtn[aria-pressed="true"] { color: #7dd3fc; background: rgba(56, 189, 248, .16); border-color: rgba(125, 211, 252, .6); }
        @media (max-width: 639px) { .pl-sidebtn { width: 2.4rem; border-radius: .8rem; } .pl-sidebtn svg { width: 1.2rem; height: 1.2rem; } .pl-tabs .pl-tab { padding-left: .15rem; padding-right: .15rem; } .pl-tabs .pl-tab b { font-size: .74rem; } }
        @container (min-width: 40rem) { .pl-sidebtn { width: 4rem; min-height: 4rem; } .pl-sidebtn svg { width: 1.6rem; height: 1.6rem; } }
        .pl-tab { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; min-height: 3.4rem; padding: .55rem .4rem; border-radius: 1rem; text-align: center; color: #cbd5e1; background: rgba(15, 28, 58, .7); border: 1px solid rgba(255, 255, 255, .1); transition: transform .2s ease, background-color .2s, border-color .2s, color .2s; }
        .pl-tab svg { width: 1.35rem; height: 1.35rem; }
        .pl-tab b { font-weight: 800; font-size: .8rem; line-height: 1.3; }
        .pl-tab small { display: none; margin-top: .1rem; font-size: .74rem; font-weight: 600; opacity: .75; }
        .pl-tab:hover { transform: translateY(-2px); color: #fff; border-color: rgba(125, 211, 252, .45); }
        .pl-tab:active { transform: translateY(0); }
        .pl-tab[aria-selected="true"] { color: #fff; background: rgba(56, 189, 248, .16); border-color: rgba(125, 211, 252, .6); box-shadow: 0 10px 24px -14px rgba(56, 189, 248, .8); }
        .pl-tab[aria-selected="true"] svg { color: #7dd3fc; }
        @container (min-width: 40rem) { .pl-tab { flex-direction: row; gap: .7rem; min-height: 4rem; padding: .7rem 1rem; text-align: left; justify-content: flex-start; } .pl-tab svg { width: 1.6rem; height: 1.6rem; flex-shrink: 0; } .pl-tab b { font-size: .95rem; } .pl-tab small { display: block; } .pl-tab span { display: block; } }

        /* ---------- cards ---------- */
        .pl-card { position: relative; overflow: hidden; padding: 1.1rem; border-radius: 1.5rem; background: linear-gradient(160deg, rgba(22, 38, 74, .93), rgba(10, 20, 44, .95)); border: 1px solid rgba(255, 255, 255, .1); box-shadow: 0 24px 50px -30px rgba(0, 0, 0, .85); }
        @container (min-width: 40rem) { .pl-card { padding: 1.6rem; } }
        .pl-in { animation: pl-in .5s cubic-bezier(.2, .9, .3, 1) backwards; }
        @keyframes pl-in { from { opacity: 0; transform: translateY(14px) scale(.985); } }
        .pl-slide { animation: pl-slide .4s cubic-bezier(.2, .9, .3, 1) backwards; }
        @keyframes pl-slide { from { opacity: 0; transform: translateX(36px) rotate(1.5deg); } }
        .pl-title { font-size: 1.3rem; font-weight: 800; color: #fff; line-height: 1.35; }
        .pl-sub { margin-top: .4rem; font-size: 1rem; font-weight: 600; color: #cbd5e1; line-height: 1.6; }
        .pl-big { font-family: var(--pl-display); font-size: 2.2rem; color: #fff; line-height: 1.3; text-shadow: 0 0 28px rgba(56, 189, 248, .55); }
        .pl-center { text-align: center; }
        .pl-meta { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .5rem; font-size: .88rem; font-weight: 700; color: #94a3b8; }
        .pl-chipbox { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .65rem; border-radius: 9999px; background: rgba(251, 191, 36, .14); border: 1px solid rgba(251, 191, 36, .45); color: #fcd34d; }
        .pl-progress { height: .5rem; margin-bottom: .9rem; border-radius: 9999px; background: rgba(148, 163, 184, .22); overflow: hidden; box-shadow: inset 0 1px 2px rgba(0, 0, 0, .35); }
        .pl-progress i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #38bdf8, #6366f1); transition: width .4s ease; }

        .pl-msg { padding: .95rem 1.05rem; border-radius: 1.1rem; background: rgba(2, 8, 23, .5); border: 1px solid rgba(255, 255, 255, .1); }
        .pl-from { font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #7dd3fc; word-break: break-word; }
        .pl-text { margin-top: .45rem; font-size: 1.06rem; font-weight: 700; line-height: 1.6; color: #f1f5f9; overflow-wrap: anywhere; }
        .pl-ctx { margin-top: .6rem; font-size: .85rem; font-weight: 600; font-style: italic; color: #94a3b8; }
        .pl-split { display: grid; gap: .9rem; }

        .pl-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
        .pl-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 3rem; padding: .7rem 1.2rem; border-radius: .9rem; font-size: 1rem; font-weight: 800; line-height: 1.3; color: #fff; border: 1px solid rgba(255, 255, 255, .22); transition: transform .15s, filter .2s, box-shadow .2s; }
        .pl-btn:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
        .pl-btn:active:not(:disabled) { transform: translateY(1px); }
        .pl-btn:disabled { opacity: .55; cursor: default; }
        .pl-btn small { font-weight: 700; font-size: .78rem; opacity: .85; }
        .pl-scam { background: linear-gradient(180deg, #f87171, #dc2626); box-shadow: 0 10px 22px -14px rgba(220, 38, 38, .95), inset 0 1px 0 rgba(255, 255, 255, .4), inset 0 -3px 0 rgba(0, 0, 0, .14); }
        .pl-safe { background: linear-gradient(180deg, #34d399, #059669); box-shadow: 0 10px 22px -14px rgba(5, 150, 105, .95), inset 0 1px 0 rgba(255, 255, 255, .4), inset 0 -3px 0 rgba(0, 0, 0, .14); }
        .pl-main { background: linear-gradient(180deg, #38bdf8, #2563eb); box-shadow: 0 10px 22px -14px rgba(37, 99, 235, .95), inset 0 1px 0 rgba(255, 255, 255, .4), inset 0 -3px 0 rgba(0, 0, 0, .14); }
        .pl-ghost { background: rgba(255, 255, 255, .08); border-color: rgba(255, 255, 255, .2); box-shadow: none; }
        .pl-start { margin-top: 1.1rem; padding-left: 2rem; padding-right: 2rem; }
        .pl-lengths { display: flex; justify-content: center; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .pl-len { min-width: 4.8rem; padding: .55rem .9rem; border-radius: .85rem; font-weight: 800; font-size: .9rem; color: #cbd5e1; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .15); transition: transform .2s, background-color .2s, border-color .2s; }
        .pl-len:hover { transform: translateY(-1px); }
        .pl-len[aria-pressed="true"] { color: #fff; background: rgba(56, 189, 248, .22); border-color: rgba(125, 211, 252, .7); }
        .pl-len small { display: block; font-weight: 600; opacity: .75; font-size: .72rem; }
        .pl-len.info { cursor: default; text-align: center; }
        .pl-len.info:hover { transform: none; }

        .pl-result { padding: .95rem 1.05rem; border-radius: 1.1rem; font-size: .96rem; font-weight: 600; line-height: 1.55; color: #e2e8f0; animation: pl-in .35s cubic-bezier(.2, .9, .3, 1) backwards; }
        .pl-result.ok { background: rgba(16, 185, 129, .14); border: 1px solid rgba(52, 211, 153, .5); }
        .pl-result.bad { background: rgba(239, 68, 68, .14); border: 1px solid rgba(248, 113, 113, .5); animation: pl-in .35s cubic-bezier(.2, .9, .3, 1) backwards, pl-shake .4s .1s; }
        @keyframes pl-shake { 20% { transform: translateX(-6px); } 40% { transform: translateX(5px); } 60% { transform: translateX(-3px); } 80% { transform: translateX(2px); } }
        .pl-res-title { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: 1.1rem; font-weight: 800; line-height: 1.4; margin-bottom: .35rem; }
        .pl-res-title em { font-style: normal; font-size: .85rem; color: #fcd34d; animation: pl-pop .5s cubic-bezier(.34, 1.56, .64, 1) .15s backwards; }
        .pl-result.ok .pl-res-title { color: #6ee7b7; }
        .pl-result.bad .pl-res-title { color: #fca5a5; }
        .pl-result .pl-btn { margin-top: .8rem; width: 100%; }
        @keyframes pl-pop { from { opacity: 0; transform: scale(.4); } }

        .pl-lives { display: flex; gap: .3rem; }
        .pl-life { width: .9rem; height: .9rem; border-radius: 9999px; background: radial-gradient(circle at 35% 30%, #fecaca, #ef4444); box-shadow: 0 0 10px rgba(248, 113, 113, .7); transition: transform .3s, opacity .3s; }
        .pl-life.off { opacity: .2; transform: scale(.6); box-shadow: none; }
        .pl-timer { height: .55rem; margin-bottom: .9rem; border-radius: 9999px; background: rgba(148, 163, 184, .22); overflow: hidden; box-shadow: inset 0 1px 2px rgba(0, 0, 0, .35); }
        .pl-timer i { display: block; height: 100%; border-radius: inherit; transform-origin: left; background: linear-gradient(90deg, #f87171, #fbbf24 55%, #34d399); background-size: 300% 100%; animation: pl-shrink var(--t, 8s) linear forwards; }
        .pl-timer.paused i { animation-play-state: paused; }
        @keyframes pl-shrink { from { transform: scaleX(1); background-position: 0 0; } to { transform: scaleX(0); background-position: 100% 0; } }
        .pl-flash { position: absolute; inset: 0; z-index: 2; display: grid; place-items: center; text-align: center; padding: 1rem; font-size: 1.5rem; font-weight: 800; line-height: 1.4; backdrop-filter: blur(3px); animation: pl-in .25s ease-out backwards; }
        .pl-flash.ok { background: rgba(16, 185, 129, .4); color: #d1fae5; }
        .pl-flash.bad { background: rgba(239, 68, 68, .45); color: #fee2e2; }
        .pl-hint { margin-top: .6rem; text-align: center; font-size: .78rem; font-weight: 600; color: #94a3b8; }

        .pl-review { margin-top: 1.2rem; text-align: left; }
        .pl-review ul { margin: 0; padding: 0; }
        .pl-review h3 { margin-bottom: .6rem; font-weight: 800; font-size: .75rem; letter-spacing: .12em; line-height: 1.6; color: #7dd3fc; }
        .pl-review li { margin-bottom: .55rem; padding: .7rem .9rem; border-radius: .9rem; background: rgba(2, 8, 23, .5); border: 1px solid rgba(255, 255, 255, .08); font-size: .9rem; font-weight: 600; color: #e2e8f0; line-height: 1.5; list-style: none; }
        .pl-review li b { display: block; margin-bottom: .2rem; color: #fff; overflow-wrap: anywhere; }
        .pl-best { margin-top: .5rem; font-size: .92rem; font-weight: 800; color: #7dd3fc; }
        .pl-tips { margin-top: 1.1rem; font-size: .9rem; font-weight: 600; color: #cbd5e1; line-height: 1.6; }

        /* ---------- Scam Survivor ---------- */
        /* ---------- story picker: preview on top, compact tiles, played stories folded away ---------- */
        .pl-pick-head { margin-bottom: .7rem; }
        .pl-pick-head .pl-title { margin: 0; }
        .pl-pick-head .pl-sub { margin: .15rem 0 0; }
        .pl-feature { --ac: #7dd3fc; position: relative; padding: 1.1rem 1.2rem; border-radius: 1.4rem; background: linear-gradient(160deg, rgba(22, 38, 74, .93), rgba(10, 20, 44, .95)); border: 1px solid rgba(255, 255, 255, .12); box-shadow: 0 22px 44px -28px rgba(0, 0, 0, .9); }
        @supports (background: color-mix(in srgb, red 10%, blue)) { .pl-feature { background: radial-gradient(110% 140% at 0% 0%, color-mix(in srgb, var(--ac) 24%, transparent), transparent 62%), linear-gradient(160deg, rgba(22, 38, 74, .93), rgba(10, 20, 44, .95)); border-color: color-mix(in srgb, var(--ac) 40%, rgba(255, 255, 255, .1)); } }
        .pl-feat-in { display: grid; grid-template-columns: auto 1fr; gap: .9rem 1.1rem; align-items: center; }
        .pl-feat-in.swap { animation: pl-feat-swap .45s cubic-bezier(.34, 1.4, .64, 1); }
        @keyframes pl-feat-swap { from { opacity: 0; transform: translateY(10px) scale(.97); } }
        .pl-ic-big { display: grid; place-items: center; width: 5.2rem; height: 5.2rem; border-radius: 1.6rem; color: var(--ac); background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .16); box-shadow: 0 0 34px -8px var(--ac); animation: pl-ic-float 3.4s ease-in-out infinite; }
        @keyframes pl-ic-float { 50% { transform: translateY(-4px); } }
        .pl-ic-big .pl-ic { width: 3.4rem; height: 3.4rem; --play: running; }
        .pl-feat-name { font-size: 1.3rem; font-weight: 800; line-height: 1.25; color: #fff; }
        .pl-feat-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .3rem; font-size: .78rem; font-weight: 800; }
        .pl-feat-blurb { margin-top: .45rem; font-size: .95rem; font-weight: 600; line-height: 1.5; color: #cbd5e1; }
        .pl-feat-chat { grid-column: 1 / -1; display: grid; gap: .35rem; justify-items: start; padding: .7rem .8rem; border-radius: 1rem; background: rgba(2, 8, 23, .45); border: 1px solid rgba(255, 255, 255, .08); }
        .pl-feat-chat small { font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
        .pl-feat-chat .pl-b { max-width: 100%; animation: none; font-size: .92rem; }
        .pl-feat-chat .pl-typing { padding: .5rem .7rem; }
        .pl-feat-actions { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .6rem; }
        .pl-feat-actions .pl-btn { min-height: 2.8rem; padding: .5rem 1.5rem; }
        @container (min-width: 46rem) {
            .pl-feat-in { grid-template-columns: auto 1fr 20rem; }
            .pl-feat-chat { grid-column: 3; grid-row: 1 / span 2; }
            .pl-feat-actions { grid-column: 1 / 3; }
        }
        .pl-tiles-h { margin: 1rem 0 .5rem; font-size: .75rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #7dd3fc; }
        .pl-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
        @container (min-width: 36rem) { .pl-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @container (min-width: 56rem) { .pl-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .pl-tile { --ac: #7dd3fc; display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .5rem .7rem; border-radius: 1rem; text-align: left; color: #e2e8f0; background: rgba(8, 18, 42, .65); border: 1px solid rgba(255, 255, 255, .1); transition: transform .2s, border-color .2s, box-shadow .2s, background-color .2s; animation: pl-pop .35s calc(var(--i, 0) * .03s) cubic-bezier(.34, 1.4, .64, 1) backwards; }
        .pl-tile:hover { background: rgba(14, 30, 64, .8); }
        .pl-tile.on { transform: translateY(-2px); border-color: var(--ac); background: rgba(14, 30, 64, .85); box-shadow: 0 12px 26px -16px var(--ac); }
        .pl-tile .ic { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .8rem; color: var(--ac); background: rgba(255, 255, 255, .06); }
        .pl-ic { width: 1.7rem; height: 1.7rem; --play: paused; overflow: visible; }
        .pl-tile:hover .pl-ic, .pl-tile.on .pl-ic, .pl-tile:focus-visible .pl-ic { --play: running; }
        .pl-tile .tx { min-width: 0; }
        .pl-tile b { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .9rem; font-weight: 800; line-height: 1.25; color: #fff; }
        .pl-tile small { display: block; font-size: .72rem; font-weight: 700; color: #94a3b8; }
        .pl-tile .st { flex: none; margin-left: auto; width: .6rem; height: .6rem; border-radius: 9999px; background: #64748b; }
        .pl-tile .st.win { background: #34d399; box-shadow: 0 0 8px #34d399; }
        .pl-tile .st.meh { background: #fbbf24; box-shadow: 0 0 8px #fbbf24; }
        .pl-tile .st.lose { background: #f87171; box-shadow: 0 0 8px #f87171; }
        .pl-all-done { margin: .9rem 0 .2rem; font-size: .9rem; font-weight: 700; color: #94a3b8; }
        .pl-played-btn { display: flex; align-items: center; gap: .4rem; margin: .9rem 0 .5rem; padding: .35rem .2rem; font-size: .8rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; }
        .pl-played-btn:hover { color: #e2e8f0; }
        .pl-played-btn svg { width: 1.2rem; height: 1.2rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: transform .25s; }
        .pl-tiles-played .pl-tile { opacity: .85; }
        /* the little animations inside the icons, driven by --play so they only move on the chosen or hovered story */
        #pl-icons [class^="ic-"] { transform-box: fill-box; transform-origin: 50% 50%; animation-duration: 1.8s; animation-iteration-count: infinite; animation-timing-function: ease-in-out; animation-play-state: var(--play, paused); }
        #pl-icons .ic-bob { animation-name: pl-ic-bob; }
        #pl-icons .ic-pulse { animation-name: pl-ic-pulse; }
        #pl-icons .ic-twinkle { animation-name: pl-ic-twinkle; animation-duration: 1.4s; }
        #pl-icons .ic-coin { animation-name: pl-ic-bob; animation-duration: 1.3s; }
        #pl-icons .ic-beat { animation-name: pl-ic-beat; animation-duration: 1.2s; }
        #pl-icons .ic-draw { stroke-dasharray: 50; animation-name: pl-ic-draw; animation-duration: 2.4s; animation-delay: -1.6s; }
        #pl-icons .ic-arrowhead { animation-name: pl-ic-twinkle; animation-duration: 2.4s; }
        #pl-icons .ic-rise { animation-name: pl-ic-rise; animation-duration: 2s; animation-delay: -.8s; }
        #pl-icons .ic-blink { animation-name: pl-ic-blink; animation-duration: 1.1s; }
        #pl-icons .ic-swing { transform-origin: 80% 20%; animation-name: pl-ic-swing; }
        #pl-icons .ic-lid { transform-origin: 50% 100%; animation-name: pl-ic-lid; animation-duration: 1.6s; }
        #pl-icons .ic-flap { transform-origin: 50% 0; animation-name: pl-ic-flap; animation-duration: 2s; }
        #pl-icons .ic-shake { transform-origin: 50% 100%; animation-name: pl-ic-shake; animation-duration: 1.2s; }
        #pl-icons .ic-ring { transform-origin: 50% 0; animation-name: pl-ic-shake; animation-duration: 1.1s; }
        #pl-icons .ic-slide { animation-name: pl-ic-slide; animation-duration: 2s; }
        #pl-icons .ic-wig { animation-name: pl-ic-wig; animation-duration: 1.6s; }
        @keyframes pl-ic-bob { 50% { transform: translateY(-3px); } }
        @keyframes pl-ic-pulse { 50% { transform: scale(1.08); } }
        @keyframes pl-ic-twinkle { 0%, 100% { transform: scale(1) rotate(0); opacity: 1; } 50% { transform: scale(.65) rotate(25deg); opacity: .6; } }
        @keyframes pl-ic-beat { 0%, 40%, 100% { transform: scale(1); } 15% { transform: scale(1.18); } 30% { transform: scale(.96); } }
        @keyframes pl-ic-draw { 0% { stroke-dashoffset: 50; } 60%, 100% { stroke-dashoffset: 0; } }
        @keyframes pl-ic-rise { 0% { transform: translateY(4px); opacity: 0; } 40% { opacity: 1; } 100% { transform: translateY(-7px); opacity: 0; } }
        @keyframes pl-ic-blink { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .25; transform: scale(.8); } }
        @keyframes pl-ic-swing { 0%, 100% { transform: rotate(-14deg); } 50% { transform: rotate(14deg); } }
        @keyframes pl-ic-lid { 0%, 100% { transform: translateY(0) rotate(0); } 30% { transform: translateY(-5px) rotate(-8deg); } 60% { transform: translateY(0) rotate(0); } }
        @keyframes pl-ic-flap { 0%, 100% { transform: scaleY(1); } 50% { transform: scaleY(-.5); } }
        @keyframes pl-ic-shake { 0%, 100% { transform: rotate(0); } 20% { transform: rotate(-9deg); } 40% { transform: rotate(8deg); } 60% { transform: rotate(-5deg); } 80% { transform: rotate(3deg); } }
        @keyframes pl-ic-slide { 0%, 100% { transform: translateX(-3px); } 50% { transform: translateX(3px); } }
        @keyframes pl-ic-wig { 0%, 100% { transform: rotate(-6deg); } 50% { transform: rotate(6deg); } }
        .pl-picks { display: grid; gap: .6rem; }
        @container (min-width: 36rem) { .pl-picks { grid-template-columns: 1fr 1fr; } }
        @container (min-width: 52rem) { .pl-picks { grid-template-columns: repeat(3, 1fr); } }
        .pl-pick { display: flex; flex-direction: column; gap: .2rem; width: 100%; padding: .75rem .95rem; border-radius: 1rem; text-align: left; color: #fff; background: linear-gradient(160deg, rgba(22, 38, 74, .9), rgba(10, 20, 44, .95)); border: 1px solid rgba(255, 255, 255, .1); transition: transform .35s cubic-bezier(.34, 1.56, .64, 1), border-color .2s, box-shadow .2s; animation: pl-in .45s cubic-bezier(.2, .9, .3, 1) backwards; animation-delay: calc(var(--i, 0) * .04s); }
        .pl-pick:hover { transform: translateY(-4px) rotate(-.5deg); border-color: rgba(125, 211, 252, .55); box-shadow: 0 18px 34px -22px rgba(56, 189, 248, .8); }
        .pl-pick:active { transform: translateY(0); }
        .pl-pick-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .pl-pick-name { font-size: 1rem; font-weight: 800; line-height: 1.35; }
        .pl-pick-blurb { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .82rem; font-weight: 600; line-height: 1.45; color: #cbd5e1; }
        .pl-pick-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: auto; padding-top: .3rem; font-size: .72rem; font-weight: 800; color: #94a3b8; }
        .pl-tag { padding: .12rem .55rem; border-radius: 9999px; background: rgba(99, 102, 241, .22); color: #c7d2fe; }
        .pl-dots { letter-spacing: .15em; color: #7dd3fc; }
        .pl-badge { flex-shrink: 0; padding: .15rem .6rem; border-radius: 9999px; font-size: .7rem; font-weight: 800; }
        .pl-badge.win { background: rgba(16, 185, 129, .24); color: #6ee7b7; }
        .pl-badge.meh { background: rgba(251, 191, 36, .2); color: #fcd34d; }
        .pl-badge.lose { background: rgba(239, 68, 68, .22); color: #fca5a5; }

        /* the story plays out on a phone */
        .pl-stage { --ph: min(37rem, calc(100dvh - 9rem)); display: flex; flex-direction: column; align-items: center; gap: 1.2rem; padding: .2rem 0 .6rem; }
        .pl-3d { position: relative; flex: none; width: calc(var(--ph) * .51); height: var(--ph); max-width: 100%; perspective: 1300px; }
        .pl-hold { position: relative; width: 100%; height: 100%; isolation: isolate; will-change: transform; transform: rotateX(4deg) rotateY(-4deg); animation: pl-holdsway 7s 1s ease-in-out infinite backwards; }
        @keyframes pl-holdsway { 0%, 100% { transform: rotateX(4deg) rotateY(-4deg) translateY(0); } 50% { transform: rotateX(2.5deg) rotateY(3deg) translateY(-5px); } }
        @media (pointer: coarse) { .pl-hold { animation: none; transform: none; will-change: auto; } }
                .pl-reply { display: none; width: min(100%, 22rem); padding: .75rem; border-radius: 1.2rem; background: rgba(8, 18, 42, .7); border: 1px solid rgba(56, 189, 248, .25); box-shadow: 0 14px 40px -20px rgba(0, 0, 0, .8); animation: pl-pop .45s .5s cubic-bezier(.34, 1.4, .64, 1) backwards, pl-hover 5s 1s ease-in-out infinite; transition: opacity .25s; }
        .pl-reply.idle { opacity: 0; visibility: hidden; pointer-events: none; }
        .pl-reply-in.bump { animation: pl-bump .6s cubic-bezier(.34, 1.56, .64, 1); }
        @keyframes pl-hover { 0%, 100% { transform: perspective(900px) rotateY(-6deg) rotateX(2deg) translateY(0); } 50% { transform: perspective(900px) rotateY(-3deg) rotateX(-1deg) translateY(-10px); } }
        .pl-reply .pl-choice { animation: pl-pop .3s cubic-bezier(.34, 1.56, .64, 1) backwards, pl-choicehop 3.6s 1.2s ease-in-out infinite; }
        @keyframes pl-choicehop { 0%, 72%, 100% { translate: 0 0; } 82% { translate: 0 -.3rem; } 92% { translate: 0 0; } }
        @keyframes pl-bump { 0% { opacity: 0; transform: scale(.7) translateY(26px); } 55% { opacity: 1; transform: scale(1.06) translateY(-7px); } 100% { transform: none; } }
        .pl-reply-h { margin-bottom: .45rem; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #7dd3fc; }
        .pl-reply .pl-choices { padding: 0; border: 0; background: none; min-height: 0; max-height: none; overflow: visible; gap: .45rem; }
        .pl-reply .pl-choice { min-height: 2.5rem; padding: .5rem .9rem; border-radius: .9rem; font-size: .9rem; }
        .pl-device { position: relative; flex: none; display: flex; width: 100%; height: 100%; padding: .4rem; border-radius: 2.9rem; background: linear-gradient(145deg, #4b5563 0%, #1f2937 30%, #0f172a 55%, #374151 100%); box-shadow: inset 0 0 0 1.5px rgba(255, 255, 255, .3), inset 0 0 0 3px #0b0f19, 0 40px 80px -30px rgba(0, 0, 0, .9), 0 0 70px -20px rgba(56, 189, 248, .5); animation: pl-devin .5s cubic-bezier(.2, .8, .3, 1) backwards; }
        @keyframes pl-devin { from { opacity: 0; transform: translateY(4.5rem); } }
        .pl-device::after { content: ""; position: absolute; inset: 0; z-index: 6; border-radius: inherit; pointer-events: none; background: linear-gradient(115deg, rgba(255, 255, 255, .1) 0, rgba(255, 255, 255, 0) 28%); }
        .pl-btnside { position: absolute; width: .2rem; border-radius: .15rem; background: linear-gradient(180deg, #6b7280, #1f2937); }
        .pl-btnside.a { left: -.16rem; top: 15%; height: 1.1rem; }
        .pl-btnside.v1 { left: -.16rem; top: 22%; height: 2rem; }
        .pl-btnside.v2 { left: -.16rem; top: 29%; height: 2rem; }
        .pl-btnside.p { right: -.16rem; top: 25%; height: 3.4rem; }
        .pl-screen { position: relative; display: flex; flex: 1; flex-direction: column; min-width: 0; min-height: 0; overflow: hidden; border: .18rem solid #02040a; border-radius: 2.5rem; background: linear-gradient(170deg, #12244a, #0a1530); }
        .pl-screen::after { content: ""; position: absolute; inset: 0; z-index: 4; background: #000; pointer-events: none; animation: pl-screen-on .9s .35s ease-out forwards; }
        @keyframes pl-screen-on { 0% { opacity: 1; } 25% { opacity: .75; background: #e0f2fe; } 100% { opacity: 0; } }
        .pl-notch { position: absolute; z-index: 5; top: .5rem; left: 50%; width: 5.2rem; height: 1.45rem; transform: translateX(-50%); border-radius: 9999px; background: #000; }
        .pl-notch::after { content: ""; position: absolute; right: .5rem; top: 50%; width: .6rem; height: .6rem; transform: translateY(-50%); border-radius: 9999px; background: radial-gradient(circle at 35% 35%, #3b4a8a 0, #0b1230 55%, #02040a 100%); box-shadow: 0 0 0 .1rem #0a0a0f; }
        .pl-status { display: flex; align-items: center; height: 2.5rem; padding: .3rem 1.4rem 0 1.5rem; color: #fff; }
        .pl-status > span { flex: 1; display: flex; align-items: center; }
        .pl-status .time { font-size: .9rem; font-weight: 800; letter-spacing: -.01em; }
        .pl-status .icons { justify-content: flex-end; gap: .25rem; }
        .pl-status svg { height: .66rem; width: auto; display: block; }
        /* reply panel: our own theme, replies shown as sent bubbles */
        .pl-reply-h { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; font-size: .8rem; letter-spacing: .02em; text-transform: none; color: #e0f2fe; }
        .pl-reply-h span { font-weight: 600; font-size: .72rem; color: #94a3b8; }
        .pl-reply .pl-choices { justify-items: end; }
        .pl-reply .pl-choice { width: fit-content; max-width: 92%; text-align: left; color: #fff; background: linear-gradient(135deg, rgba(56, 189, 248, .28), rgba(37, 99, 235, .4)); border: 1px solid rgba(56, 189, 248, .5); border-radius: .9rem .9rem .25rem .9rem; }
        .pl-reply .pl-choice:hover { background: linear-gradient(135deg, rgba(56, 189, 248, .4), rgba(37, 99, 235, .55)); }
        /* on phones and tablets the replies stay inside the phone */
        .pl-device .pl-choices { flex: none; display: grid; justify-items: end; align-content: start; gap: .45rem; min-height: 0; max-height: 46%; padding: .65rem .75rem 1.5rem; overflow-y: auto; border-top: 1px solid rgba(255, 255, 255, .08); background: rgba(2, 8, 23, .35); }
        .pl-device .pl-choice { width: fit-content; max-width: 94%; min-height: 2.5rem; padding: .45rem .9rem; border-radius: .9rem .9rem .25rem .9rem; font-size: .92rem; line-height: 1.35; text-align: left; color: #fff; background: linear-gradient(135deg, rgba(56, 189, 248, .28), rgba(37, 99, 235, .4)); border: 1px solid rgba(56, 189, 248, .5); animation: pl-pop .3s cubic-bezier(.34, 1.56, .64, 1) backwards, pl-choicehop 3.6s 1.2s ease-in-out infinite; }
        .pl-device .pl-choice:hover { background: linear-gradient(135deg, rgba(56, 189, 248, .4), rgba(37, 99, 235, .55)); }
        /* on a wide screen the replies float beside the phone, with the hands holding it */
        @container (min-width: 52rem) {
            .pl-stage { flex-direction: row; justify-content: center; align-items: center; gap: 6rem; }
            .pl-reply { display: block; width: 23rem; padding: .9rem; }
            .pl-reply-h { margin: 0 0 .7rem; font-size: .92rem; }
            .pl-reply-h span { font-size: .78rem; }
            .pl-reply .pl-choices { gap: .55rem; }
            .pl-reply .pl-choice { min-height: 2.9rem; padding: .6rem 1.05rem; font-size: 1rem; }
            .pl-device .pl-choices { display: none; }
            .pl-device .pl-chat { padding-bottom: 4.6rem; }
        }
        .pl-homebar { position: absolute; z-index: 5; bottom: .35rem; left: 50%; width: 5.4rem; height: .26rem; transform: translateX(-50%); border-radius: 9999px; background: rgba(255, 255, 255, .55); }
        .pl-phone-head { display: flex; align-items: center; gap: .7rem; padding: .7rem 1rem; background: rgba(2, 8, 23, .45); border-bottom: 1px solid rgba(255, 255, 255, .08); }
        .pl-phone-head .av { display: grid; place-items: center; flex-shrink: 0; width: 2.3rem; height: 2.3rem; border-radius: 9999px; font-size: .95rem; font-weight: 800; background: linear-gradient(135deg, #64748b, #334155); color: #fff; border: 1px solid rgba(255, 255, 255, .25); }
        .pl-phone-head p { min-width: 0; font-size: .95rem; font-weight: 800; color: #fff; }
        .pl-phone-head small { display: block; font-size: .72rem; font-weight: 600; color: #94a3b8; }
        .pl-phone-head .pl-btn { margin-left: auto; min-height: 2.4rem; padding: .3rem .9rem; font-size: .85rem; }
        .pl-chat { display: flex; flex-direction: column; gap: .5rem; flex: 1; min-height: 0; overflow-y: auto; padding: .75rem .8rem; overscroll-behavior: contain; touch-action: pan-y; }
        .pl-device .pl-chat { padding-bottom: 1rem; }
        .pl-device .pl-chat, .pl-device .pl-choices { scrollbar-width: none; -ms-overflow-style: none; }
        .pl-device .pl-chat::-webkit-scrollbar, .pl-device .pl-choices::-webkit-scrollbar { display: none; }
        .pl-device .pl-phone-head { padding: .5rem .8rem; gap: .55rem; }
        .pl-device .pl-phone-head .av { width: 1.9rem; height: 1.9rem; font-size: .8rem; }
        .pl-device .pl-phone-head p { font-size: .88rem; }
        .pl-device .pl-phone-head small { font-size: .66rem; }
        .pl-device .pl-phone-head .pl-btn { min-height: 1.9rem; padding: .15rem .75rem; font-size: .78rem; border-radius: .7rem; }
        .pl-device .pl-b { max-width: 86%; padding: .5rem .7rem; font-size: .88rem; line-height: 1.45; }
        .pl-device .pl-b.tip { font-size: .76rem; padding: .4rem .6rem; }
        .pl-b { max-width: 84%; padding: .6rem .85rem; border-radius: 1.1rem; font-size: .97rem; font-weight: 600; line-height: 1.5; overflow-wrap: anywhere; animation: pl-pop .3s cubic-bezier(.34, 1.56, .64, 1) backwards; }
        .pl-b.them { align-self: flex-start; border-bottom-left-radius: .3rem; background: rgba(51, 65, 85, .9); color: #f1f5f9; transform-origin: left bottom; }
        .pl-b.me { align-self: flex-end; border-bottom-right-radius: .3rem; background: linear-gradient(135deg, #38bdf8, #2563eb); color: #fff; transform-origin: right bottom; }
        .pl-b.tip { align-self: center; max-width: 96%; text-align: center; font-size: .82rem; border-radius: .8rem; }
        .pl-b.tip.flag { background: rgba(239, 68, 68, .16); border: 1px solid rgba(248, 113, 113, .5); color: #fecaca; }
        .pl-b.tip.good { background: rgba(16, 185, 129, .16); border: 1px solid rgba(52, 211, 153, .5); color: #a7f3d0; }
        .pl-typing { display: flex; gap: .3rem; align-self: flex-start; padding: .75rem .9rem; border-radius: 1.1rem; background: rgba(51, 65, 85, .9); }
        .pl-typing i { width: .42rem; height: .42rem; border-radius: 9999px; background: #cbd5e1; animation: pl-dot 1s infinite ease-in-out; }
        .pl-typing i:nth-child(2) { animation-delay: .15s; }
        .pl-typing i:nth-child(3) { animation-delay: .3s; }
        @keyframes pl-dot { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-.28rem); opacity: 1; } }
        .pl-choices { display: grid; align-content: start; gap: .55rem; padding: .85rem 1rem 1rem; border-top: 1px solid rgba(255, 255, 255, .08); background: rgba(2, 8, 23, .35); min-height: 4.5rem; max-height: 45dvh; overflow-y: auto; }
        .pl-choice { min-height: 2.9rem; padding: .65rem 1rem; border-radius: 1rem; text-align: left; font-size: .96rem; font-weight: 700; line-height: 1.4; color: #e0f2fe; background: rgba(56, 189, 248, .1); border: 1px solid rgba(56, 189, 248, .4); animation: pl-pop .3s cubic-bezier(.34, 1.56, .64, 1) backwards; transition: background-color .2s, transform .15s; }
        .pl-choice:hover { background: rgba(56, 189, 248, .22); transform: translateY(-1px); }
        .pl-choice:active { transform: translateY(1px); }
        .pl-end-badge { display: inline-grid; place-items: center; width: 4rem; height: 4rem; margin-bottom: .7rem; border-radius: 9999px; font-size: 1.7rem; font-weight: 800; color: #fff; border: 2px solid rgba(255, 255, 255, .7); animation: pl-pop .6s cubic-bezier(.34, 1.56, .64, 1) backwards; }
        .pl-end-badge.win { background: linear-gradient(135deg, #34d399, #059669); box-shadow: 0 0 36px -6px rgba(16, 185, 129, .9); }
        .pl-end-badge.meh { background: linear-gradient(135deg, #fbbf24, #d97706); box-shadow: 0 0 36px -6px rgba(251, 191, 36, .9); }
        .pl-end-badge.lose { background: linear-gradient(135deg, #f87171, #dc2626); box-shadow: 0 0 36px -6px rgba(239, 68, 68, .9); }
        .pl-end-row { display: flex; flex-wrap: wrap; gap: .7rem; justify-content: center; margin-top: 1.1rem; }
        /* result cards: smaller and tidier */
        .pl-result-card { padding: 1rem 1.1rem; }
        .pl-result-card .pl-end-badge { width: 3rem; height: 3rem; margin-bottom: .4rem; font-size: 1.3rem; }
        .pl-result-card .pl-big { margin: .2rem 0; font-size: 1.7rem; line-height: 1.2; }
        .pl-result-card .pl-title { font-size: 1.15rem; }
        .pl-result-card .pl-sub { margin-top: .2rem; font-size: .92rem; }
        .pl-result-card .pl-best { margin-top: .3rem; font-size: .85rem; }
        .pl-result-card .pl-review { margin-top: .8rem; }
        .pl-result-card .pl-review h3 { margin-bottom: .4rem; font-size: .7rem; }
        .pl-result-card .pl-review ul { display: grid; gap: .35rem; }
        .pl-result-card .pl-review li { margin: 0; padding: .45rem .7rem; border-radius: .7rem; font-size: .82rem; line-height: 1.4; }
        .pl-result-card .pl-review li b { margin-bottom: .1rem; }
        @container (min-width: 44rem) { .pl-result-card .pl-review ul { grid-template-columns: 1fr 1fr; } }
        .pl-result-card .pl-tips { margin-top: .8rem; font-size: .8rem; line-height: 1.5; color: #94a3b8; }
        .pl-result-card .pl-end-row { margin-top: .8rem; }
        .pl-end-row { align-items: center; }
        .pl-end-row .pl-btn { margin-top: 0; }
        .pl-result-card .pl-end-row .pl-btn { min-height: 2.6rem; padding: .45rem 1.4rem; min-width: 9.5rem; }

        /* ---------- Phil on the result cards ---------- */
        .pl-phil { display: flex; align-items: center; justify-content: center; gap: .8rem; max-width: 27rem; margin: 0 auto .8rem; text-align: left; }
        .pl-phil-fish svg, .pl-guide-phil svg { --pa: var(--phil-a, #0ea5e9); --pb: var(--phil-b, #38bdf8); --pc: var(--phil-c, #bae6fd); }
        .pl-phil-fish { flex: none; width: 3.6rem; animation: pl-philbob 2.4s ease-in-out infinite; }
        .pl-phil-fish svg { display: block; width: 100%; height: auto; image-rendering: pixelated; filter: drop-shadow(0 0 8px rgba(56, 189, 248, .55)); }
        .pl-phil.sad .pl-phil-fish { animation-duration: 4.2s; }
        .pl-phil.sad .pl-phil-fish svg { filter: saturate(.55) brightness(.9) drop-shadow(0 0 6px rgba(148, 163, 184, .4)); }
        .pl-phil.cheer .pl-phil-fish { animation: pl-philcheer .7s ease-in-out infinite; }
        .pl-phil-say { position: relative; padding: .55rem .85rem; border-radius: 1rem; font-size: .92rem; font-weight: 800; line-height: 1.4; color: #0b1220; background: #e0f2fe; border: 2px solid #7dd3fc; box-shadow: 3px 3px 0 rgba(0, 0, 0, .3); animation: pl-philpop .5s cubic-bezier(.34, 1.5, .64, 1) both; }
        .pl-phil-say::before { content: ""; position: absolute; left: -.42rem; top: 50%; width: .7rem; height: .7rem; transform: translateY(-50%) rotate(45deg); background: #e0f2fe; border: 0 solid #7dd3fc; border-width: 0 0 2px 2px; }
        @keyframes pl-philbob { 0%, 100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-5px) rotate(3deg); } }
        @keyframes pl-philcheer { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-9px); } }
        @keyframes pl-philpop { from { opacity: 0; transform: scale(.6) translateX(-8px); } }

        /* ---------- Phil the guide: a tip and a short how-to-play on each start screen ---------- */
        .pl-guide { display: flex; align-items: flex-start; gap: .8rem; margin-bottom: 1rem; padding: .7rem .8rem; border-radius: 1.1rem; background: rgba(56, 189, 248, .08); border: 1px solid rgba(125, 211, 252, .22); text-align: left; }
        .pl-guide-phil { flex: none; width: 3.2rem; padding: 0; background: none; border: 0; cursor: pointer; animation: pl-philbob 2.6s ease-in-out infinite; }
        .pl-guide-phil svg { display: block; width: 100%; height: auto; image-rendering: pixelated; filter: drop-shadow(0 0 8px rgba(56, 189, 248, .55)); }
        .pl-guide-body { flex: 1; min-width: 0; }
        .pl-guide-say { position: relative; display: inline-block; max-width: 100%; padding: .45rem .8rem; border-radius: .9rem; font-size: .9rem; font-weight: 800; line-height: 1.4; color: #0b1220; background: #e0f2fe; border: 2px solid #7dd3fc; box-shadow: 3px 3px 0 rgba(0, 0, 0, .3); }
        .pl-guide-say.swap { animation: pl-philpop .4s cubic-bezier(.34, 1.5, .64, 1); }
        .pl-guide-say::before { content: ""; position: absolute; left: -.42rem; top: .8rem; width: .65rem; height: .65rem; transform: rotate(45deg); background: #e0f2fe; border: 0 solid #7dd3fc; border-width: 0 0 2px 2px; }
        .pl-guide-row { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .5rem; }
        .pl-guide-btn { padding: .3rem .8rem; border-radius: .7rem; font-size: .8rem; font-weight: 800; color: #e0f2fe; background: rgba(125, 211, 252, .14); border: 1px solid rgba(125, 211, 252, .35); cursor: pointer; transition: background .2s; }
        .pl-guide-btn:hover { background: rgba(125, 211, 252, .26); }
        .pl-guide-btn.go { color: #0b1220; background: #7dd3fc; border-color: #7dd3fc; }
        .pl-guide-btn:disabled { opacity: .4; cursor: default; }
        .pl-guide-step { display: grid; gap: .3rem; padding: .6rem .8rem; border-radius: .9rem; background: rgba(2, 8, 23, .45); border: 1px solid rgba(255, 255, 255, .1); animation: pl-philpop .35s cubic-bezier(.34, 1.4, .64, 1); }
        .pl-guide-step small { font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #7dd3fc; }
        .pl-guide-step p { font-size: .92rem; font-weight: 700; line-height: 1.5; color: #e2e8f0; }
        .pl-guide-dots { display: flex; gap: .3rem; margin-left: auto; }
        .pl-guide-dots i { width: .45rem; height: .45rem; border-radius: 9999px; background: rgba(255, 255, 255, .22); }
        .pl-guide-dots i.on { background: #7dd3fc; }
        @container (min-width: 40rem) {
            .pl-guide { align-items: center; gap: 1rem; padding: .6rem 1rem; }
            .pl-guide-phil { width: 3.6rem; }
            .pl-guide-body { display: flex; align-items: center; flex-wrap: wrap; gap: .6rem 1rem; }
            .pl-guide-say { flex: 0 1 auto; font-size: .95rem; }
            .pl-guide-step { flex: 1 1 24rem; }
            .pl-guide-row { margin: 0 0 0 auto; }
        }
        @media (max-width: 639px) { .pl-guide { padding: .6rem .7rem; } .pl-guide-phil { width: 2.6rem; } }
        @media (max-height: 520px) and (orientation: landscape) { .pl-guide-say { font-size: .8rem; } }

        /* ---------- the cheat sheet box on the right ---------- */

        /* ---------- confetti and XP toast ---------- */
        .pl-confetti { position: absolute; inset: 0; z-index: 3; overflow: hidden; pointer-events: none; }
        .pl-confetti i { position: absolute; top: -14px; left: var(--x); width: 9px; height: 15px; border-radius: 2px; background: var(--c); animation: pl-fall 1.9s ease-in forwards; animation-delay: var(--d); }
        @keyframes pl-fall { to { transform: translateY(26rem) rotate(560deg); opacity: 0; } }
        .pl-toast { position: fixed; z-index: 60; right: 1rem; bottom: 6.2rem; padding: .6rem 1rem; border-radius: 9999px; font-size: .95rem; font-weight: 800; line-height: 1.4; color: #fcd34d; background: rgba(10, 20, 44, .96); border: 1px solid rgba(251, 191, 36, .55); box-shadow: 0 12px 28px -10px rgba(0, 0, 0, .8); animation: pl-toast 2.2s cubic-bezier(.34, 1.4, .64, 1) forwards; pointer-events: none; }
        @keyframes pl-toast { 0% { opacity: 0; transform: translateY(14px) scale(.7); } 12% { opacity: 1; transform: none; } 80% { opacity: 1; } 100% { opacity: 0; transform: translateY(-14px); } }

        /* ---------- start screens: text on the left, a little sample of the game on the right ---------- */
        .pl-quit { min-height: 2rem; padding: .15rem .8rem; border-radius: .7rem; font-size: .8rem; flex: none; white-space: nowrap; }
        .pl-intro { display: grid; gap: 1.2rem; text-align: center; }
        .pl-demo { display: block; width: 100%; max-width: 24rem; margin: 0 auto; text-align: left; position: relative; padding: 1rem 1.1rem 1.1rem; border-radius: 1.25rem; background: rgba(2, 8, 23, .5); border: 1px solid rgba(255, 255, 255, .1); pointer-events: none; user-select: none; }
        @keyframes pl-float { to { transform: rotate(1.5deg) translateY(-6px); } }
        .pl-demo-tag { margin-bottom: .6rem; font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #64748b; }
        .pl-demo .pl-text { font-size: .98rem; }
        .pl-demo .pl-actions { margin-top: .9rem; }
        .pl-demo .pl-btn { min-height: 2.6rem; padding: .5rem .8rem; font-size: .95rem; }
        .pl-demo .pl-scam, .pl-demo .pl-safe { animation: pl-nudge 3s ease-in-out infinite; }
        .pl-demo .pl-safe { animation-delay: 1.5s; }
        @keyframes pl-nudge { 0%, 70%, 100% { transform: none; filter: none; } 80% { transform: translateY(2px) scale(.97); filter: brightness(1.2); } }
        .pl-demo .pl-b { max-width: 100%; margin-bottom: .6rem; animation: none; }
        .pl-demo .pl-choice { display: block; min-height: 0; margin-top: .45rem; padding: .5rem .8rem; font-size: .88rem; animation: none; }
        .pl-demo .pl-choice + .pl-choice { animation: pl-nudge 3s ease-in-out 1.5s infinite; }
        .pl-rush { background: radial-gradient(110% 90% at 100% 0%, rgba(251, 146, 60, .2), transparent 55%), radial-gradient(90% 80% at 0% 100%, rgba(56, 189, 248, .15), transparent 55%), linear-gradient(160deg, rgba(22, 38, 74, .96), rgba(10, 20, 44, .98)); border-color: rgba(251, 146, 60, .3); box-shadow: 0 24px 50px -30px rgba(0, 0, 0, .85), 0 0 0 1px rgba(251, 146, 60, .06) inset; }
        .pl-rush::before { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 30%; background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .06), transparent); transform: translateX(-130%) skewX(-18deg); animation: pl-rush-sweep 6s ease-in-out infinite; pointer-events: none; }
        @keyframes pl-rush-sweep { 0% { transform: translateX(-130%) skewX(-18deg); } 55%, 100% { transform: translateX(420%) skewX(-18deg); } }
        .pl-rush > * { position: relative; }
        .pl-rush-eyebrow { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .7rem; border-radius: 9999px; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #fdba74; background: rgba(251, 146, 60, .14); border: 1px solid rgba(251, 146, 60, .35); }
        .pl-rush-title { margin: .8rem 0 .4rem; font-size: 1.45rem; line-height: 1.2; font-weight: 900; color: #f8fafc; }
        .pl-rush .pl-sub { margin: 0; }
        .pl-rush-tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem; margin-top: 1.1rem; box-sizing: border-box; }
        .pl-rush-tile { box-sizing: border-box; display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: .7rem .4rem .65rem; border-radius: 1rem; text-align: center; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .12); }
        .pl-rush-tile strong { font-size: .88rem; font-weight: 800; color: #f1f5f9; }
        .pl-rush-tile small { font-size: .7rem; font-weight: 600; line-height: 1.25; color: #94a3b8; }
        .pl-rush-ic { display: inline-flex; align-items: center; justify-content: center; gap: .15rem; height: 1.9rem; margin-bottom: .2rem; font-size: 1.15rem; }
        .pl-rush-ic.hearts { color: #fb7185; }
        .pl-rush-ic.bolt { color: #fbbf24; font-size: 1.5rem; }
        .pl-rush-ic kbd { display: inline-block; min-width: 1.6rem; padding: .1rem .35rem; border-radius: .4rem; font: 800 .9rem/1.3 inherit; color: #e2e8f0; background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .25); border-bottom-width: 3px; }
        .pl-rush-go { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: .8rem; margin-top: 1.1rem; }
        .pl-rush-go .pl-start { margin-top: 0; }
        .pl-rush-start { display: inline-flex; align-items: center; gap: .45rem; font-size: 1.05rem; }
        .pl-rush-best { padding: .35rem .8rem; border-radius: 9999px; font-size: .85rem; font-weight: 800; color: #7dd3fc; background: rgba(56, 189, 248, .12); border: 1px solid rgba(56, 189, 248, .3); }
        .pl-rush-hud { display: flex; align-items: center; justify-content: space-between; margin-bottom: .6rem; }
        .pl-rush-hud .pl-demo-tag { margin: 0; }
        .pl-rush-lives { display: inline-flex; gap: .15rem; color: #fb7185; font-size: .95rem; }
        .pl-rush-lives svg:last-child { opacity: .3; }
        @container (min-width: 40rem) { .pl-rush-title { font-size: 1.75rem; } .pl-rush .pl-rush-go { justify-content: flex-start; } .pl-rush-eyebrow { margin-top: .1rem; } }
        .pl-quizc { background: radial-gradient(110% 90% at 100% 0%, rgba(52, 211, 153, .17), transparent 55%), radial-gradient(90% 80% at 0% 100%, rgba(56, 189, 248, .16), transparent 55%), linear-gradient(160deg, rgba(22, 38, 74, .96), rgba(10, 20, 44, .98)); border-color: rgba(52, 211, 153, .3); }
        .pl-quizc .pl-rush-eyebrow { color: #6ee7b7; background: rgba(52, 211, 153, .13); border-color: rgba(52, 211, 153, .35); }
        .pl-rush-tile.pick { cursor: pointer; font-family: inherit; transition: transform .15s, background .15s, border-color .15s; }
        .pl-rush-tile.pick:hover { transform: translateY(-2px); }
        .pl-rush-tile.pick strong { font-size: 1.7rem; line-height: 1.1; font-weight: 900; color: #f8fafc; }
        .pl-rush-tile.pick span { font-size: .8rem; font-weight: 800; color: #cbd5e1; }
        .pl-rush-tile.pick[aria-pressed="true"] { background: rgba(56, 189, 248, .2); border-color: rgba(125, 211, 252, .75); box-shadow: 0 0 0 3px rgba(56, 189, 248, .14); }
        .pl-feature { overflow: hidden; }
        .pl-feature::before { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 30%; background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .06), transparent); transform: translateX(-130%) skewX(-18deg); animation: pl-rush-sweep 6s ease-in-out infinite; pointer-events: none; }
        .pl-feat-in { position: relative; }
        .pl-feat-eye { margin-bottom: .45rem; color: var(--ac); border-color: rgba(255, 255, 255, .2); background: rgba(255, 255, 255, .07); }
        @supports (background: color-mix(in srgb, red 10%, blue)) { .pl-feat-eye { border-color: color-mix(in srgb, var(--ac) 45%, transparent); background: color-mix(in srgb, var(--ac) 14%, transparent); } }
        .pl-feat-name { font-size: 1.5rem; font-weight: 900; }
        .pl-rp-hud { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .7rem; padding: .45rem .65rem; border-radius: 1.1rem; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .1); }
        .pl-rp-mid { display: flex; align-items: center; gap: .7rem; }
        .pl-rp-score { display: flex; align-items: baseline; gap: .45rem; }
        .pl-rp-score small { font-size: .65rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #94a3b8; }
        .pl-rp-score b { font-size: 1.6rem; line-height: 1; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums; }
        .pl-hearts { display: flex; gap: .25rem; font-size: 1.35rem; color: #fb7185; }
        .pl-heart { width: 1em; height: 1em; filter: drop-shadow(0 0 6px rgba(251, 113, 133, .6)); transition: transform .35s cubic-bezier(.34, 1.56, .64, 1), opacity .3s, filter .3s; }
        .pl-heart.off { opacity: .22; transform: scale(.72); filter: grayscale(1); }
        .pl-rp-timer { height: .8rem; margin-bottom: .75rem; box-shadow: inset 0 1px 2px rgba(0, 0, 0, .4), 0 0 18px -6px rgba(251, 146, 60, .6); }
        .pl-rp { background: radial-gradient(110% 90% at 100% 0%, rgba(251, 146, 60, .16), transparent 55%), radial-gradient(90% 80% at 0% 100%, rgba(56, 189, 248, .12), transparent 55%), linear-gradient(160deg, rgba(22, 38, 74, .96), rgba(10, 20, 44, .98)); border-color: rgba(251, 146, 60, .28); }
        .pl-rp .pl-from { display: inline-block; max-width: 100%; padding: .2rem .75rem; border-radius: 1rem; line-height: 1.5; background: rgba(56, 189, 248, .12); border: 1px solid rgba(56, 189, 248, .3); }
        .pl-rp .pl-text { font-size: 1.12rem; }
        .pl-rp .pl-actions .pl-btn { min-height: 3.5rem; font-size: 1.1rem; }
        .pl-rp-kbd { display: inline-grid; place-items: center; min-width: 1.8rem; height: 1.7rem; padding: 0 .3rem; border-radius: .45rem; font-size: .95rem; font-weight: 900; line-height: 1; background: rgba(0, 0, 0, .22); border: 1px solid rgba(255, 255, 255, .35); border-bottom-width: 3px; }
        @media (pointer: coarse) { .pl-rp-kbd { display: none; } }
        .pl-qz-progress { height: .8rem; margin-bottom: .75rem; box-shadow: inset 0 1px 2px rgba(0, 0, 0, .4), 0 0 18px -6px rgba(52, 211, 153, .55); }
        .pl-qz-progress i { background: linear-gradient(90deg, #34d399, #38bdf8); }
        .pl-qz.pl-rp { background: radial-gradient(110% 90% at 100% 0%, rgba(52, 211, 153, .15), transparent 55%), radial-gradient(90% 80% at 0% 100%, rgba(56, 189, 248, .13), transparent 55%), linear-gradient(160deg, rgba(22, 38, 74, .96), rgba(10, 20, 44, .98)); border-color: rgba(52, 211, 153, .28); }
        .pl-sv-hud { margin-bottom: .8rem; }
        .pl-sv-mid { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .pl-sv-ic { display: grid; place-items: center; flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; color: var(--ac, #7dd3fc); background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .16); box-shadow: 0 0 18px -6px var(--ac, #7dd3fc); }
        .pl-sv-ic .pl-ic { width: 1.6rem; height: 1.6rem; }
        .pl-sv-name { display: flex; align-items: center; gap: .6rem; min-width: 0; }
        .pl-sv-name b { font-size: 1.05rem; font-weight: 900; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pl-sv-stage { --ph: min(37rem, calc(100dvh - 13rem)); position: relative; padding: 1.4rem 1rem 1.2rem; border-radius: 1.5rem; background: radial-gradient(70% 90% at 50% 0%, rgba(125, 211, 252, .12), transparent 70%), linear-gradient(160deg, rgba(22, 38, 74, .6), rgba(10, 20, 44, .75)); border: 1px solid rgba(255, 255, 255, .1); }
        @supports (background: color-mix(in srgb, red 10%, blue)) { .pl-sv-stage { background: radial-gradient(70% 90% at 50% 0%, color-mix(in srgb, var(--ac) 22%, transparent), transparent 70%), linear-gradient(160deg, rgba(22, 38, 74, .6), rgba(10, 20, 44, .75)); border-color: color-mix(in srgb, var(--ac) 35%, rgba(255, 255, 255, .1)); } }
        .pl-rside { display: contents; }
        @container (min-width: 52rem) {
            .pl-rside { display: flex; flex-direction: column; gap: 1rem; width: 23rem; }
            .pl-rside .pl-reply { width: 100%; }
            .pl-sv-stage { padding: 1.8rem 2rem; overflow: hidden; }
            .pl-sv .pl-reply { border-color: rgba(56, 189, 248, .35); box-shadow: 0 14px 40px -20px rgba(0, 0, 0, .8), 0 0 28px -14px var(--ac); }
            .pl-sv .pl-reply .pl-choice { min-height: 3.2rem; box-shadow: 0 8px 18px -12px rgba(37, 99, 235, .9), inset 0 1px 0 rgba(255, 255, 255, .25); }
        }
        .pl-phone-head { background: linear-gradient(180deg, rgba(15, 23, 42, .92), rgba(15, 23, 42, .6)); border-bottom-color: rgba(255, 255, 255, .07); }
        .pl-phone-head .av { border-radius: .85rem; box-shadow: 0 0 14px -4px var(--ac, #38bdf8); }
        .pl-date { align-self: center; padding: .15rem .7rem; border-radius: 9999px; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; background: rgba(255, 255, 255, .06); }
        .pl-b.them { background: rgba(30, 41, 59, .96); border: 1px solid rgba(255, 255, 255, .08); border-radius: 1.15rem 1.15rem 1.15rem .35rem; }
        .pl-b.me { background: linear-gradient(135deg, #0ea5e9, #6366f1); border-radius: 1.15rem 1.15rem .35rem 1.15rem; box-shadow: 0 6px 14px -8px rgba(99, 102, 241, .9); }
        .pl-typing { border: 1px solid rgba(255, 255, 255, .08); background: rgba(30, 41, 59, .96); }
        .pl-reply .pl-choice { background: linear-gradient(135deg, rgba(14, 165, 233, .3), rgba(99, 102, 241, .42)); border-color: rgba(125, 211, 252, .5); }
        .pl-reply .pl-choice:hover { background: linear-gradient(135deg, rgba(14, 165, 233, .45), rgba(99, 102, 241, .58)); }
        .pl-device .pl-choices { justify-items: stretch; gap: .4rem; padding: .55rem .7rem .45rem; background: rgba(2, 8, 23, .5); }
        .pl-device .pl-choice { width: 100%; max-width: 100%; border-radius: .9rem; color: #e0f2fe; background: rgba(34, 211, 238, .1); border: 1px solid rgba(34, 211, 238, .45); }
        .pl-device .pl-choice:hover { background: rgba(34, 211, 238, .22); }
        .pl-compose { flex: none; display: flex; align-items: center; gap: .5rem; padding: .45rem .7rem 1.35rem; background: rgba(2, 8, 23, .6); border-top: 1px solid rgba(255, 255, 255, .07); }
        .pl-compose-in { flex: 1; padding: .45rem .9rem; border-radius: 9999px; font-size: .78rem; font-weight: 600; color: #64748b; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .12); }
        .pl-compose-send { display: grid; place-items: center; flex: none; width: 1.9rem; height: 1.9rem; border-radius: 9999px; font-size: 1rem; color: #fff; background: linear-gradient(135deg, #0ea5e9, #6366f1); opacity: .45; }
        @media (min-width: 1024px) and (pointer: fine) and (min-height: 521px) { .pl-sv .pl-phone-head .pl-btn { display: none; } }
        @media (max-width: 1023px) and (orientation: portrait) {
            .pl-wrap.in-story .pl-hero, .pl-wrap.in-story .pl-tabs { display: none; }
            .pl-sv-stage .pl-3d { --h: min(calc(100dvh - 10.5rem), 46rem); height: var(--h); width: min(100%, 24rem, calc(var(--h) / 1.85)); }
            .pl-sv-stage { margin-bottom: 4.5rem; }
        }
        .pl-rp-timer i { animation-delay: calc(var(--d, 0s) * -1); }
        .pl-tab:disabled { cursor: not-allowed; }
        .pl-tab:disabled:not([aria-selected="true"]) { opacity: .4; filter: saturate(.5); }
        .pl-tab:disabled:hover { transform: none; border-color: rgba(255, 255, 255, .1); }
        .pl-tab:disabled:hover svg { transform: none; }
        .pl-rush-tile.mode { cursor: pointer; font-family: inherit; color: inherit; transition: transform .15s, background .15s, border-color .15s, box-shadow .15s; }
        .pl-rush-tile.mode:hover { transform: translateY(-2px); }
        .pl-rush-tile.mode[aria-pressed="true"] { background: rgba(251, 146, 60, .16); border-color: rgba(253, 186, 116, .75); box-shadow: 0 0 0 3px rgba(251, 146, 60, .14); }
        .pl-rush-ic.flame { color: #fb923c; font-size: 1.5rem; }
        .pl-rush-ic.cal { color: #7dd3fc; font-size: 1.4rem; }
        .pl-rush-note { margin-top: .7rem; min-height: 2.6em; font-size: .85rem; font-weight: 600; line-height: 1.45; color: #94a3b8; }
        .pl-intro-main .pl-surprise-btn { margin-top: .9rem; }
        .pl-demo .pl-timer { margin: 0 0 .8rem; }
        .pl-demo .pl-timer i { animation: pl-shrink 6s linear infinite; }
        .pl-demo .pl-meta { margin-bottom: .6rem; }
        @container (min-width: 40rem) {
            .pl-intro { grid-template-columns: 1.1fr 1fr; gap: 2rem; align-items: center; text-align: left; }
            .pl-intro .pl-lengths { justify-content: flex-start; }
            .pl-demo { transform: rotate(1.5deg); animation: pl-float 4s ease-in-out infinite alternate; }
        }

        /* ---------- game feel: bounce and shine (movement only, no new colours) ---------- */
        .pl-lvl { position: relative; animation: pl-bob 3.2s ease-in-out infinite; }
        .pl-lvl::after { content: ""; position: absolute; inset: -2px; border-radius: inherit; border: 2px solid rgba(125, 211, 252, .7); opacity: 0; animation: pl-ring 2.8s ease-out infinite; }
        @keyframes pl-bob { 50% { transform: translateY(-3px); } }
        @keyframes pl-ring { 0% { transform: scale(1); opacity: .7; } 70%, 100% { transform: scale(1.55); opacity: 0; } }
        .pl-lvl.up { animation: pl-lvlup .9s cubic-bezier(.34, 1.56, .64, 1); }
        @keyframes pl-lvlup { 0% { transform: scale(1); } 30% { transform: scale(1.5) rotate(-8deg); } 60% { transform: scale(.92) rotate(4deg); } 100% { transform: scale(1); } }
        .pl-xpbar i { position: relative; overflow: hidden; }
        .pl-xpbar i::after { content: ""; position: absolute; inset: 0; background: linear-gradient(100deg, transparent 30%, rgba(255, 255, 255, .5) 50%, transparent 70%); transform: translateX(-100%); animation: pl-shine 2.6s ease-in-out infinite; }
        @keyframes pl-shine { 60%, 100% { transform: translateX(100%); } }
        .pl-hero > .pl-confetti { border-radius: inherit; }
        .pl-tab svg { transition: transform .35s cubic-bezier(.34, 1.56, .64, 1); }
        .pl-tab:hover svg { transform: rotate(-10deg) scale(1.15); }
        .pl-tab[aria-selected="true"] { animation: pl-tabpop .45s cubic-bezier(.34, 1.56, .64, 1); }
        .pl-tab[aria-selected="true"] svg { animation: pl-hop .6s cubic-bezier(.34, 1.56, .64, 1); }
        @keyframes pl-tabpop { from { transform: scale(.94); } }
        @keyframes pl-hop { 0% { transform: scale(.6) rotate(-20deg); } 60% { transform: scale(1.3) rotate(8deg); } 100% { transform: none; } }
        .pl-btn { position: relative; overflow: hidden; }
        .pl-btn::after { content: ""; position: absolute; top: 0; bottom: 0; left: -60%; width: 40%; background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .4), transparent); transform: translateX(0) skewX(-18deg); transition: transform .6s ease; pointer-events: none; }
        .pl-btn:hover:not(:disabled)::after { transform: translateX(450%) skewX(-18deg); }
        .pl-start { animation: pl-breathe 2.4s ease-in-out infinite; }
        .pl-start:hover { animation: none; }
        @keyframes pl-breathe { 50% { transform: scale(1.05); } }
        .pl-chipbox { animation: pl-beat 1.1s ease-in-out infinite; }
        @keyframes pl-beat { 50% { transform: scale(1.1); } }
        .pl-big { animation: pl-bigpop .7s cubic-bezier(.34, 1.56, .64, 1) backwards; }
        @keyframes pl-bigpop { from { opacity: 0; transform: scale(.4) rotate(-6deg); } }

        /* ---------- Phil the Phish, a retro fish who wanders the header ---------- */
        .pl-water { position: absolute; inset: 0; z-index: 0; clip-path: inset(0 round 1.5rem); pointer-events: none; }
        @keyframes pl-sway { from { transform: rotate(-5deg); } to { transform: rotate(5deg); } }
        .pl-hook { position: absolute; top: -4rem; left: 82%; height: calc(100% + 2.5rem); display: flex; flex-direction: column; align-items: center; transform: translateY(-110%); animation: pl-cast 22s ease-in-out infinite; }
        @container (min-width: 52rem) { .pl-hook { left: 56%; } }
        .pl-line { flex: 1; width: 1px; background: rgba(186, 230, 253, .55); }
        .pl-bait { padding: .3rem .4rem; font-family: var(--pl-display); font-size: .4rem; line-height: 1; color: #0b1220; background: #e0f2fe; border: 2px solid #7dd3fc; box-shadow: 2px 2px 0 rgba(0, 0, 0, .35); transform-origin: 50% 0; animation: pl-swing 2.6s ease-in-out infinite alternate; }
        @keyframes pl-swing { from { transform: rotate(-7deg); } to { transform: rotate(7deg); } }
        @keyframes pl-cast { 0%, 6% { transform: translateY(-110%); } 14% { transform: translateY(6%); } 17%, 55% { transform: translateY(0); } 62%, 100% { transform: translateY(-110%); } }
        /* the sea: its own strip at the bottom of the header, so the fish never sit on the text */
        .pl-sea { position: relative; grid-column: 1 / -1; height: 3.5rem; margin: -.3rem calc(var(--hx) * -1) calc(var(--hy) * -1); border-radius: 0 0 var(--r) var(--r); background: linear-gradient(180deg, rgba(125, 211, 252, .36) 0%, rgba(56, 189, 248, .32) 22%, rgba(37, 99, 235, .44) 62%, rgba(12, 30, 90, .78) 100%); box-shadow: inset 0 1px 0 rgba(224, 242, 254, .5), inset 0 -10px 18px -10px rgba(2, 8, 30, .6); }
        .pl-sea-clip { position: absolute; inset: 0; overflow: hidden; border-radius: inherit; pointer-events: none; }
        .pl-wave { position: absolute; left: 0; top: -1px; width: calc(100% + 80px); height: 10px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 10'%3E%3Cpath d='M0 5 Q10 0 20 5 T40 5 T60 5 T80 5 V10 H0Z' fill='%237dd3fc'/%3E%3C/svg%3E") repeat-x; opacity: .55; animation: pl-wave 6s linear infinite; }
        .pl-wave.w2 { top: 3px; opacity: .28; background-position: 40px 0; animation-duration: 9s; animation-direction: reverse; }
        @keyframes pl-wave { to { transform: translateX(-80px); } }
        .pl-bub { position: absolute; left: var(--x); bottom: .3rem; width: var(--s); height: var(--s); border-radius: 9999px; border: 1px solid rgba(224, 242, 254, .55); background: radial-gradient(circle at 32% 30%, rgba(255, 255, 255, .75) 0 14%, rgba(186, 230, 253, .16) 42%, rgba(186, 230, 253, .04) 72%); opacity: 0; animation: pl-rise var(--t) ease-in infinite; animation-delay: var(--d); }
        @keyframes pl-rise { 0% { transform: translate(0, 0) scale(.7); opacity: 0; } 12% { opacity: .9; } 55% { transform: translate(var(--dx, .3rem), -1.5rem) scale(1); opacity: .8; } 100% { transform: translate(calc(var(--dx, .3rem) * -.5), -3.1rem) scale(1.1); opacity: 0; } }
        .pl-rays { position: absolute; top: 0; bottom: 0; left: -20%; width: 140%; pointer-events: none; background: linear-gradient(105deg, transparent 0 8%, rgba(255, 255, 255, .11) 10% 15%, transparent 19% 33%, rgba(255, 255, 255, .07) 35% 39%, transparent 43% 60%, rgba(255, 255, 255, .09) 62% 67%, transparent 71% 85%, rgba(255, 255, 255, .06) 87% 90%, transparent 94%); -webkit-mask-image: linear-gradient(#000, transparent 92%); mask-image: linear-gradient(#000, transparent 92%); animation: pl-rays 14s ease-in-out infinite alternate; }
        @keyframes pl-rays { from { transform: translateX(-4%); opacity: .75; } to { transform: translateX(4%); opacity: 1; } }
        .pl-wave.w3 { top: -2px; opacity: .2; background-position: 20px 0; animation-duration: 4.6s; }
        .pl-sand { position: absolute; left: 0; bottom: 0; width: 100%; height: .8rem; pointer-events: none; }
        .pl-fbub { position: absolute; left: var(--fx, 38%); top: .1rem; width: .26rem; height: .26rem; border-radius: 9999px; border: 1px solid rgba(224, 242, 254, .7); background: radial-gradient(circle at 32% 30%, rgba(255, 255, 255, .8) 0 16%, rgba(186, 230, 253, .1) 60%); opacity: 0; animation: pl-fbub 3.4s ease-out infinite; animation-delay: var(--d, 0s); pointer-events: none; }
        @keyframes pl-fbub { 0% { transform: translate(0, 0) scale(.6); opacity: 0; } 15% { opacity: .9; } 100% { transform: translate(.25rem, -1.7rem) scale(1.15); opacity: 0; } }
        .pl-weed { position: absolute; bottom: 0; width: .9rem; height: 1.8rem; opacity: .55; transform-origin: 50% 100%; animation: pl-sway 4s ease-in-out infinite alternate; }
        .pl-weed.l { left: 1.2%; }
        .pl-weed.r { right: 1.4%; animation-delay: -2s; }
        @keyframes pl-sway { from { transform: rotate(-5deg); } to { transform: rotate(5deg); } }
        .pl-fishlane { position: absolute; inset: 0; z-index: 2; container-type: inline-size; pointer-events: none; }
        .pl-fish { position: absolute; left: 0; bottom: .55rem; padding: 0; background: none; border: 0; animation: pl-swim var(--dur, 26s) ease-in-out infinite alternate; animation-delay: var(--dl, 0s); }
        .pl-fish.main { pointer-events: auto; cursor: pointer; }
        .pl-fish.main:focus-visible { outline: 2px solid #7dd3fc; outline-offset: 3px; border-radius: .4rem; }
        .pl-fish.mini { bottom: 1.1rem; animation-name: pl-swim-mini; --dur: 31s; }
        .pl-fish.mini .pl-fish-px { width: var(--w, 2.1rem); opacity: .85; }
        @keyframes pl-swim { from { transform: translateX(0); } to { transform: translateX(calc(100cqw - 15.5rem)); } }
        @keyframes pl-swim-mini { from { transform: translateX(0); } to { transform: translateX(calc(100cqw - 3.6rem)); } }
        .pl-fish-y { position: relative; display: block; animation: pl-fishbob 1.4s steps(1) infinite; }
        @keyframes pl-fishbob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }
        .pl-fish-px { display: block; width: 3.4rem; height: auto; image-rendering: pixelated; animation: pl-turn calc(var(--dur, 26s) * 2) steps(1) infinite; animation-delay: var(--dl, 0s); filter: hue-rotate(var(--h, 0deg)) drop-shadow(0 0 8px rgba(56, 189, 248, .55)); }
                @keyframes pl-turn { 0% { transform: scaleX(1); } 50% { transform: scaleX(-1); } }
        .pl-fish-y.hop { animation: pl-hopfish .6s cubic-bezier(.34, 1.56, .64, 1); }
        @keyframes pl-hopfish { 0% { transform: translateY(0); } 35% { transform: translateY(-1rem) scale(1.15); } 70% { transform: translateY(0); } 100% { transform: none; } }
        .pl-say { position: absolute; top: 50%; left: calc(100% + .55rem); width: max-content; max-width: 11rem; padding: .3rem .5rem; transform: translateY(-50%); font-family: var(--pl-display); font-size: .5rem; line-height: 1.55; text-align: left; color: #0b1220; background: #e0f2fe; border: 2px solid #7dd3fc; box-shadow: 3px 3px 0 rgba(0, 0, 0, .35); pointer-events: none; }
        .pl-say::after { content: ""; position: absolute; left: -.3rem; top: 50%; width: .5rem; height: .5rem; transform: translateY(-50%) rotate(45deg); background: #e0f2fe; border: 0 solid #7dd3fc; border-width: 0 0 2px 2px; }
        @media (max-width: 639px) { .pl-fish-px { width: 2.6rem; } .pl-fish.mini .pl-fish-px { width: calc(var(--w, 2.1rem) * .75); } .pl-say { max-width: 9rem; font-size: .45rem; } @keyframes pl-swim { from { transform: translateX(0); } to { transform: translateX(calc(100cqw - 12.5rem)); } } @keyframes pl-swim-mini { from { transform: translateX(0); } to { transform: translateX(calc(100cqw - 2.8rem)); } } }
        @media (max-height: 520px) and (orientation: landscape) { .pl-water, .pl-sea { display: none; } }

        /* ---------- side panel: stats, ranks, tip, help ---------- */
        .pl-layout { display: block; }
        .pl-col { min-width: 0; container-type: inline-size; }
        .pl-aside { display: grid; gap: .9rem; margin-top: 1rem; }
        @media (min-width: 640px) and (max-width: 1279px) { .pl-aside { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
        .pl-side-card { padding: 1rem 1.1rem; border-radius: 1.25rem; background: linear-gradient(160deg, rgba(22, 38, 74, .93), rgba(10, 20, 44, .95)); border: 1px solid rgba(255, 255, 255, .1); box-shadow: 0 20px 40px -30px rgba(0, 0, 0, .85); }
        .pl-side-h { margin-bottom: .7rem; font-size: .75rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #7dd3fc; }
        .pl-stat { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .5rem 0; font-size: .92rem; font-weight: 600; color: #cbd5e1; border-bottom: 1px solid rgba(255, 255, 255, .07); }
        .pl-stat:last-child { border-bottom: 0; padding-bottom: 0; }
        .pl-stat b { font-weight: 800; color: #fff; white-space: nowrap; }
        .pl-ranks { margin: 0; padding: 0; list-style: none; display: grid; gap: .35rem; }
        .pl-rank { display: flex; align-items: center; gap: .6rem; padding: .35rem .5rem; border-radius: .7rem; font-size: .9rem; font-weight: 700; color: #64748b; border: 1px solid transparent; }
        .pl-rank i { display: grid; place-items: center; flex-shrink: 0; width: 1.6rem; height: 1.6rem; border-radius: 9999px; font-style: normal; font-size: .72rem; font-weight: 800; background: rgba(148, 163, 184, .16); }
        .pl-rank.done { color: #cbd5e1; }
        .pl-rank.done i { background: rgba(56, 189, 248, .22); color: #7dd3fc; }
        .pl-rank.now { color: #fff; background: rgba(56, 189, 248, .14); border-color: rgba(125, 211, 252, .5); }
        .pl-rank.now i { background: linear-gradient(135deg, #38bdf8, #2563eb); color: #fff; }
        .pl-rank em { margin-left: auto; font-style: normal; font-size: .68rem; font-weight: 800; color: #7dd3fc; }
        .pl-tip { font-size: .95rem; font-weight: 600; line-height: 1.6; color: #e2e8f0; }
        /* From laptop width up the four cards sit on the right and the games get a little narrower. */
        @media (min-width: 1280px) {
            .pl-wrap { max-width: 88rem; }
            .pl-layout { display: grid; grid-template-columns: minmax(0, 1fr) 17rem; column-gap: 1.25rem; align-items: start; transition: grid-template-columns .4s cubic-bezier(.4, 0, .2, 1), column-gap .4s cubic-bezier(.4, 0, .2, 1); }
            .pl-layout.noside { grid-template-columns: minmax(0, 1fr) 0rem; column-gap: 0; }
            .pl-aside { min-width: 0; overflow: hidden; }
            .pl-aside > * { box-sizing: border-box; min-width: 15rem; }
            .pl-aside { grid-template-columns: minmax(0, 1fr); margin-top: 0; position: sticky; top: 0; }
        }
        @media (min-width: 1600px) { .pl-layout { grid-template-columns: minmax(0, 1fr) 19rem; } .pl-layout.noside { grid-template-columns: minmax(0, 1fr) 0rem; } }
        /* While a game is running the cards go away and the game has the room. */
        .pl-layout.solo { display: block; }
        .pl-t-ease { transition: opacity .3s ease, transform .35s cubic-bezier(.34, 1.2, .64, 1); }
        .pl-t-off { opacity: 0; transform: translateY(12px) scale(.98); }
        .pl-t-on { opacity: 1; transform: none; }
        @media (min-width: 1280px) { .pl-t-off { transform: translateX(26px); } }
        .pl-layout.solo .pl-col { max-width: 68rem; }
        @media (max-width: 1023px), (pointer: coarse) { .pl-sv-hud { display: none; } .pl-sv-stage { padding: .2rem 0 .6rem; background: none; border: 0; } }
        @media (max-height: 520px) and (orientation: landscape) { .pl-aside, .pl-demo { display: none !important; } }

        /* ---------- tablets and phones, both orientations ---------- */
        @media (max-width: 639px) {
            .pl-hero { --hx: .9rem; --hy: .9rem; --r: 1.25rem; }
            .pl-hero h1 { font-size: .9rem; }
            .pl-hero p { font-size: .86rem; }
            .pl-title { font-size: 1.15rem; }
            .pl-big { font-size: 1.8rem; }
            .pl-text { font-size: 1rem; }
            .pl-btn { font-size: .95rem; padding: .65rem .8rem; }
            .pl-toast { right: .75rem; bottom: 5.4rem; }
        }
        @media (min-width: 640px) and (max-width: 1023px) {
            .pl-text { font-size: 1.12rem; }
        }
        /* landscape phones and small tablets: little height, so use the width */
        @media (max-height: 520px) and (orientation: landscape) {
            .pl-hero { grid-template-columns: 1fr auto; align-items: center; gap: .8rem; padding: .55rem .9rem; margin-bottom: .6rem; border-radius: 1rem; }
            .pl-hero h1 { font-size: .85rem; }
            .pl-hero p { display: none; }
            .pl-hud { padding: .35rem .7rem .35rem .4rem; }
            .pl-lvl { width: 2.2rem; height: 2.2rem; font-size: .75rem; }
            .pl-tabs { gap: .4rem; margin-bottom: .6rem; }
            .pl-tab { flex-direction: row; min-height: 2.6rem; padding: .35rem .6rem; gap: .45rem; justify-content: center; }
            .pl-tab small { display: none; }
            .pl-tab b { font-size: .8rem; }
            .pl-card { padding: .8rem 1rem; border-radius: 1.1rem; }
            .pl-split { grid-template-columns: 1.15fr 1fr; align-items: start; }
            .pl-title { font-size: 1.05rem; }
            .pl-big { font-size: 1.6rem; }
            .pl-progress, .pl-timer { margin-bottom: .5rem; }
            .pl-result { padding: .6rem .8rem; font-size: .88rem; }
            .pl-hint { display: none; }
            .pl-picks { grid-template-columns: repeat(3, 1fr); gap: .6rem; }
            .pl-feat-chat, .pl-feat-blurb, .pl-pick-head { display: none; }
            .pl-feature { padding: .7rem .9rem; }
            .pl-ic-big { width: 3.6rem; height: 3.6rem; border-radius: 1.1rem; }
            .pl-ic-big .pl-ic { width: 2.4rem; height: 2.4rem; }
            .pl-feat-name { font-size: 1.05rem; }
            .pl-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .pl-pick { padding: .7rem .8rem; }
            .pl-pick-blurb { font-size: .8rem; }
            .pl-stage { flex-direction: column; align-items: center; gap: 0; overflow: visible; }
            .pl-3d { --ph: calc(100dvh - 7rem); width: min(100%, 46rem); height: calc(100dvh - 7rem); min-height: 12rem; perspective: none; }
            .pl-hold { animation: none; transform: none; }
            .pl-reply, .pl-sv-hud, .pl-compose { display: none !important; }
            .pl-sv-stage { padding: 0; background: none; border: 0; }
            .pl-device { padding: .35rem; border-radius: 1.4rem; }
            .pl-device .pl-chat { padding-bottom: 1rem; }
            .pl-btnside, .pl-device::after { display: none; }
            .pl-screen { display: grid; grid-template-columns: 1.2fr 1fr; grid-template-rows: auto 1fr; border-radius: 1.1rem; border-width: 0; }
            .pl-device .pl-phone-head { grid-column: 1 / -1; }
            .pl-device .pl-choices { display: grid !important; max-height: none; border-top: 0; border-left: 1px solid rgba(255, 255, 255, .08); padding-bottom: .6rem; overflow-y: auto; }
            .pl-notch, .pl-status, .pl-homebar { display: none; }
                .pl-toast { bottom: 1rem; }
        }

        /* score screens: Play again / Back sit right under the result, the review is folded away until tapped */
        .pl-result-card details.pl-review { margin-top: .8rem; }
        .pl-result-card .pl-review summary { display: flex; align-items: center; justify-content: center; gap: .4rem; min-height: 2.5rem; padding: .4rem .9rem; border-radius: .9rem; cursor: pointer; list-style: none; font-size: .85rem; font-weight: 800; color: #7dd3fc; background: rgba(56, 189, 248, .08); border: 1px solid rgba(125, 211, 252, .3); }
        .pl-result-card .pl-review summary::-webkit-details-marker { display: none; }
        .pl-result-card .pl-review summary::after { content: ""; width: .5rem; height: .5rem; margin-top: -.2rem; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: rotate(45deg); transition: transform .2s; }
        .pl-result-card .pl-review[open] summary::after { margin-top: .2rem; transform: rotate(-135deg); }
        .pl-result-card .pl-review[open] summary { margin-bottom: .6rem; }
        .pl-result-card .pl-review summary:focus-visible { outline: 2px solid #7dd3fc; outline-offset: 2px; }
        @media (max-width: 639px) { .pl-result-card .pl-vtext { display: none; } .pl-result-card .pl-phil { margin-bottom: .2rem; } }
        /* rank box: keep it on one tidy line */
        .pl-rankbtn { min-width: 0; text-align: left; }
        .pl-rankbtn span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pl-hud-title span:last-child { flex: none; }
        @media (max-width: 639px) {
            .pl-hud { gap: .6rem; padding: .45rem .7rem .45rem .45rem; }
            .pl-lvl { width: 2.4rem; height: 2.4rem; font-size: .8rem; border-radius: .7rem; }
            .pl-hud-title { font-size: .82rem; gap: .35rem; }
            .pl-hud-title span:last-child { font-size: .72rem; }
            .pl-rankbtn { gap: .2rem; }
            .pl-rankbtn svg.pl-rk { width: 1rem; height: 1rem; }
            .pl-rankbtn svg { width: .75rem; height: .75rem; }
            .pl-xpbar { height: .5rem; margin-top: .3rem; }
        }
        /* ---- story screens dressed like the channel the scam arrives on (all dark, so the page stays one theme) ---- */
        .pl-sv .pl-screen { background: var(--cb, linear-gradient(170deg, #12244a, #0a1530)); }
        .pl-sv .pl-phone-head { background: var(--hb, linear-gradient(180deg, rgba(15, 23, 42, .92), rgba(15, 23, 42, .6))); }
        .pl-sv .pl-b.them { background: var(--tb, rgba(30, 41, 59, .96)); border-color: var(--tbd, rgba(255, 255, 255, .08)); border-radius: var(--tr, 1.15rem 1.15rem 1.15rem .35rem); }
        .pl-sv .pl-b.me { background: var(--mb, linear-gradient(135deg, #0ea5e9, #6366f1)); border-radius: var(--mr, 1.15rem 1.15rem .35rem 1.15rem); box-shadow: none; }
        .pl-sv .pl-device .pl-choice, .pl-sv .pl-reply .pl-choice { background: var(--cc, linear-gradient(135deg, rgba(14, 165, 233, .3), rgba(99, 102, 241, .42))); border-color: var(--cbd, rgba(125, 211, 252, .5)); border-radius: var(--mr, .9rem .9rem .25rem .9rem); }
        .pl-sv .pl-device .pl-choices { background: var(--chb, rgba(2, 8, 23, .35)); }
        .pl-sv .pl-b, .pl-sv .pl-choice { font-family: var(--ff, inherit); }
        .pl-sv[data-ch="sms"] { --cb: linear-gradient(180deg, #0b1220, #0d1526); --hb: rgba(17, 24, 39, .96); --tb: #1f2937; --mb: linear-gradient(135deg, #22c55e, #16a34a); --cc: linear-gradient(135deg, rgba(34, 197, 94, .26), rgba(22, 163, 74, .4)); --cbd: rgba(74, 222, 128, .55); }
        .pl-sv[data-ch="chat"] { --cb: #0b141a; --hb: #1f2c34; --tb: #202c33; --tbd: transparent; --mb: #005c4b; --cc: rgba(0, 92, 75, .6); --cbd: rgba(0, 168, 132, .6); --chb: rgba(11, 20, 26, .85); --tr: .7rem .7rem .7rem .15rem; --mr: .7rem .7rem .15rem .7rem; }
        .pl-sv[data-ch="chat"] .pl-chat { background-image: radial-gradient(rgba(255, 255, 255, .045) 1px, transparent 1.5px); background-size: 16px 16px; }
        .pl-sv[data-ch="email"] { --cb: #0f172a; --hb: #111827; --tb: #162033; --tbd: rgba(148, 163, 184, .35); --mb: linear-gradient(135deg, #4f46e5, #3730a3); --cc: rgba(79, 70, 229, .3); --cbd: rgba(129, 140, 248, .55); --tr: .5rem; --mr: .5rem; --chb: #0b1222; }
        .pl-sv[data-ch="email"] .pl-b.them, .pl-sv[data-ch="email"] .pl-b.me { max-width: 96%; border-left: 3px solid var(--ac, #34d399); }
        .pl-sv[data-ch="email"] .pl-b.me { align-self: stretch; max-width: 96%; margin-left: auto; }
        .pl-sv[data-ch="market"] { --cb: #120f22; --hb: #1b1533; --tb: #231b3d; --tbd: rgba(167, 139, 250, .35); --mb: linear-gradient(135deg, #8b5cf6, #6d28d9); --cc: rgba(139, 92, 246, .3); --cbd: rgba(167, 139, 250, .6); --chb: rgba(18, 15, 34, .9); }
        .pl-sv[data-ch="call"] { --cb: #06070d; --hb: linear-gradient(180deg, #2a0d12, #0a0709); --tb: #1c0f13; --tbd: rgba(248, 113, 113, .4); --mb: linear-gradient(135deg, #15803d, #14532d); --cc: rgba(22, 163, 74, .3); --cbd: rgba(74, 222, 128, .55); --chb: #05060a; --tr: 1rem; --mr: 1rem; }
        .pl-sv[data-ch="call"] .pl-phone-head .av { animation: plf-ring 1.8s ease-out infinite; }
        @keyframes plf-ring { 0% { box-shadow: 0 0 0 0 rgba(248, 113, 113, .6); } 100% { box-shadow: 0 0 0 .8rem rgba(248, 113, 113, 0); } }
        .pl-sv[data-ch="qr"] { --cb: #0a0a0f; --hb: #14110c; --tb: #1a1209; --tbd: rgba(251, 146, 60, .4); --mb: linear-gradient(135deg, #f97316, #c2410c); --cc: rgba(249, 115, 22, .26); --cbd: rgba(251, 146, 60, .6); --chb: #08080c; --tr: .3rem; --mr: .3rem; --ff: ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace; }
        .pl-sv[data-ch="qr"] .pl-chat { background-image: linear-gradient(rgba(251, 146, 60, .05) 1px, transparent 1px), linear-gradient(90deg, rgba(251, 146, 60, .05) 1px, transparent 1px); background-size: 18px 18px; }
        .pl-sv[data-ch="social"] { --cb: #0f0a1a; --hb: linear-gradient(90deg, rgba(244, 114, 182, .22), rgba(168, 85, 247, .22), rgba(99, 102, 241, .2)); --tb: #1f1630; --mb: linear-gradient(135deg, #f472b6, #a855f7 55%, #6366f1); --cc: linear-gradient(135deg, rgba(244, 114, 182, .3), rgba(99, 102, 241, .4)); --cbd: rgba(244, 114, 182, .55); --chb: rgba(15, 10, 26, .9); --tr: 1.3rem 1.3rem 1.3rem .4rem; --mr: 1.3rem 1.3rem .4rem 1.3rem; }
        .pl-sv[data-ch="real"] { --cb: #07130e; --hb: #0d2218; --tb: #0f2a1e; --tbd: rgba(52, 211, 153, .35); --mb: linear-gradient(135deg, #059669, #047857); --cc: rgba(16, 185, 129, .28); --cbd: rgba(52, 211, 153, .6); --chb: #06100b; }
        /* Story on a small phone held upright: keep the phone, but trim the fake bits (clock bar, "Choose a reply" bar)
           and tighten the spacing so the chat itself gets the room. */
        @media (max-width: 639px) and (orientation: portrait) {
            .pl-sv-stage .pl-3d { width: min(100%, 24rem, calc(var(--h) / 1.6)); }
            /* the real phone already shows a clock and a notch, so the fake ones go; messages sit just above the replies like a real chat */
            .pl-sv-stage .pl-status, .pl-sv-stage .pl-notch { display: none; }
            .pl-sv-stage { padding-left: .5rem; padding-right: .5rem; }
            .pl-sv-stage .pl-device .pl-chat > :first-child { margin-top: auto; }
            .pl-sv-stage .pl-compose { display: none; }
            .pl-sv-stage .pl-device .pl-choices { padding: .5rem .6rem 1.2rem; gap: .35rem; }
            .pl-sv-stage .pl-device .pl-choice { min-height: 2.1rem; padding: .35rem .75rem; font-size: .86rem; line-height: 1.3; }
            .pl-sv-stage .pl-device .pl-chat { padding: .5rem .6rem .6rem; gap: .4rem; }
            .pl-sv-stage .pl-device .pl-b { font-size: .84rem; padding: .4rem .6rem; }
            .pl-sv-stage .pl-device .pl-phone-head { padding: .35rem .7rem; }
        }
        /* Story on a touchscreen tablet, in either direction: the same layout as a phone, only bigger. The replies stay inside the phone
           (the floating reply box and the hands are for mouse screens), and the Phish Lab box and tabs step aside while the story runs. */
        @media (pointer: coarse) and (min-width: 640px) {
            .pl-wrap.in-story .pl-hero, .pl-wrap.in-story .pl-tabs { display: none; }
            .pl-stage.pl-sv-stage { flex-direction: column; align-items: center; gap: 0; }
            .pl-sv-stage .pl-reply { display: none !important; }
            .pl-sv-stage .pl-device .pl-choices { display: grid !important; }
            .pl-sv-stage .pl-3d { --h: min(calc(100dvh - 7.5rem), 50rem); height: var(--h); width: min(100%, 24rem, calc(var(--h) / 2.05)); }
            .pl-sv-stage { margin-bottom: 4.5rem; }
            .pl-sv-stage .pl-device .pl-chat { padding: .8rem 1rem 1rem; gap: .5rem; }
            .pl-sv-stage .pl-device .pl-choices { padding: .7rem .9rem 1.4rem; gap: .45rem; }
            .pl-sv-stage .pl-status, .pl-sv-stage .pl-notch, .pl-sv-stage .pl-compose { display: none; }
            .pl-sv-stage .pl-device .pl-chat > :first-child { margin-top: auto; }
        }
        /* While a quiz or rush is running on a phone held upright, hide the whole Phish Lab header and the tabs so the game gets the screen,
           and leave room at the bottom so the chat button never sits on top of the answer buttons. */
        @media (max-width: 639px) and (orientation: portrait) {
            .pl-wrap.in-game:not(.in-story) { padding-bottom: 4.75rem; }
            .pl-wrap.in-game .pl-hero, .pl-wrap.in-game .pl-tabs { display: none; }
        }
        /* ---- game feel: sound button, score pop, answer flashes, combo banner, count-in ---- */
        .pl-sndbtn { position: absolute; top: 0; right: 0; z-index: 6; display: grid; place-items: center; width: 2.2rem; height: 2.2rem; padding: 0; border-radius: 9999px; color: #94a3b8; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .14); transition: color .2s, background .2s, border-color .2s, transform .2s; }
        .pl-sndbtn svg { width: 1.1rem; height: 1.1rem; }
        .pl-sndbtn:hover { color: #fff; transform: translateY(-1px); }
        .pl-sndbtn[aria-pressed="true"] { color: #7dd3fc; background: rgba(56, 189, 248, .16); border-color: rgba(125, 211, 252, .55); }
        /* The button sits in the same row as the title, so the two always line up. */
        .pl-hero h1 { display: flex; align-items: center; min-height: 2.2rem; padding-right: 2.8rem; }
        @media (max-width: 639px) { .pl-sndbtn { width: 1.8rem; height: 1.8rem; } .pl-sndbtn svg { width: .95rem; height: .95rem; } .pl-hero h1 { min-height: 1.8rem; padding-right: 2.4rem; } }
        /* Wide header and short landscape phones: the title is only part of the row, so the button goes to the header corner. */
        @container (min-width: 52rem) { .pl-hero-text { position: static !important; } .pl-sndbtn { top: .7rem; right: .7rem; } .pl-hero h1 { padding-right: 0; } }
        @media (max-height: 520px) and (orientation: landscape) { .pl-hero-text { position: static !important; } .pl-sndbtn { top: .3rem; right: .3rem; width: 1.8rem; height: 1.8rem; } .pl-hero h1 { padding-right: 0; min-height: 0; } .pl-hud { margin-right: 2rem; } }
        .pl-snd { display: inline-flex; align-items: center; gap: .4rem; margin-top: .65rem; padding: .3rem .8rem; border-radius: 9999px; font-size: .74rem; font-weight: 800; color: #94a3b8; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .12); transition: color .2s, background .2s, border-color .2s; }
        .pl-snd svg { width: 1rem; height: 1rem; }
        .pl-snd:hover { color: #fff; }
        .pl-snd[aria-pressed="true"] { color: #7dd3fc; background: rgba(56, 189, 248, .16); border-color: rgba(125, 211, 252, .55); }
        .pl-bump { display: inline-block; animation: plf-bump .4s cubic-bezier(.2, 1.6, .4, 1); }
        @keyframes plf-bump { 0% { transform: scale(1); } 40% { transform: scale(1.45); color: #fde68a; text-shadow: 0 0 14px rgba(253, 230, 138, .7); } 100% { transform: scale(1); } }
        .pl-gainhost { position: relative; }
        .pl-gain { position: absolute; right: 0; top: -.2rem; font-style: normal; font-weight: 900; font-size: .95rem; color: #6ee7b7; pointer-events: none; animation: plf-gain .95s ease-out forwards; }
        @keyframes plf-gain { from { opacity: 0; transform: translateY(.4rem) scale(.7); } 20% { opacity: 1; transform: translateY(-.3rem) scale(1.1); } to { opacity: 0; transform: translateY(-1.7rem) scale(1); } }
        .pl-card.fx-ok { animation: plf-ok .55s ease-out; }
        .pl-card.fx-bad { animation: plf-shake .45s ease-in-out; }
        @keyframes plf-ok { 0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, .7); } 100% { box-shadow: 0 0 0 22px rgba(52, 211, 153, 0); } }
        @keyframes plf-shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-9px); } 40% { transform: translateX(8px); } 60% { transform: translateX(-6px); } 80% { transform: translateX(4px); } }
        .pl-chipbox.tier1 { box-shadow: 0 0 12px rgba(251, 191, 36, .35); }
        .pl-chipbox.tier2 { color: #fdba74; background: rgba(249, 115, 22, .2); border-color: rgba(251, 146, 60, .7); box-shadow: 0 0 16px rgba(249, 115, 22, .5); }
        .pl-chipbox.tier3 { color: #fecdd3; background: rgba(244, 63, 94, .22); border-color: rgba(251, 113, 133, .8); box-shadow: 0 0 20px rgba(244, 63, 94, .6); }
        .pl-combo-wrap { position: relative; height: 0; z-index: 6; pointer-events: none; }
        .pl-combo { position: absolute; left: 0; right: 0; top: .6rem; margin: 0 auto; width: max-content; max-width: 92%; padding: .45rem 1.1rem; border-radius: 9999px; font-family: var(--pl-display); font-size: .85rem; color: #1c1917; background: linear-gradient(135deg, #fde68a, #fbbf24); box-shadow: 0 6px 24px rgba(251, 191, 36, .5); animation: plf-combo 1.3s ease-out forwards; }
        .pl-combo.t2 { background: linear-gradient(135deg, #fdba74, #f97316); box-shadow: 0 6px 26px rgba(249, 115, 22, .55); }
        .pl-combo.t3 { color: #fff; background: linear-gradient(135deg, #fb7185, #e11d48); box-shadow: 0 6px 28px rgba(244, 63, 94, .6); }
        @keyframes plf-combo { 0% { opacity: 0; transform: translateY(.6rem) scale(.5); } 15% { opacity: 1; transform: translateY(0) scale(1.15); } 25% { transform: scale(1); } 80% { opacity: 1; transform: translateY(-.2rem) scale(1); } 100% { opacity: 0; transform: translateY(-.9rem) scale(.95); } }
        .pl-combo .sp { position: absolute; top: 50%; left: 50%; width: 6px; height: 6px; border-radius: 50%; background: #fde68a; animation: plf-spark .8s ease-out forwards; }
        @keyframes plf-spark { from { opacity: 1; transform: translate(-50%, -50%); } to { opacity: 0; transform: translate(calc(-50% + var(--x)), calc(-50% + var(--y))) scale(.4); } }
        .pl-count { display: grid; place-items: center; align-content: center; gap: .5rem; min-height: 16rem; text-align: center; cursor: pointer; }
        .pl-count-n { font-family: var(--pl-display); font-size: 5rem; line-height: 1; color: #fff; text-shadow: 0 0 30px rgba(56, 189, 248, .7); animation: plf-count .7s ease-out both; }
        .pl-count-n.go { color: #6ee7b7; text-shadow: 0 0 30px rgba(52, 211, 153, .8); }
        @keyframes plf-count { from { opacity: 0; transform: scale(2.2); } 35% { opacity: 1; transform: scale(1); } to { opacity: .9; transform: scale(.92); } }
        @media (max-width: 480px) { .pl-rp-hud { gap: .4rem; padding: .4rem .5rem; } .pl-rp-mid { gap: .4rem; } .pl-rp-score { gap: .3rem; } .pl-rp-score small { font-size: .55rem; letter-spacing: .08em; } .pl-rp-score b { white-space: nowrap; font-size: 1.35rem; } .pl-rp-hud .pl-chipbox { white-space: nowrap; font-size: .7rem; padding: .1rem .5rem; } }
        .pl-count p { font-size: .95rem; font-weight: 700; color: #cbd5e1; }
        .pl-count small { font-size: .72rem; font-weight: 700; color: #64748b; }

        /* ---- streak flame, badges, stars, new-best ribbon, level-up card ---- */
        .pl-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .65rem; }
        .pl-chips .pl-snd { margin-top: 0; }
        .pl-flame { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .8rem; border-radius: 9999px; font-size: .74rem; font-weight: 800; color: #fdba74; background: rgba(249, 115, 22, .14); border: 1px solid rgba(251, 146, 60, .5); }
        .pl-flame svg { width: 1rem; height: 1rem; fill: currentColor; transform-origin: 50% 90%; }
        .pl-flame:not(.cold) svg { animation: plf-flick 1.4s ease-in-out infinite; }
        .pl-flame.cold { color: #94a3b8; background: rgba(255, 255, 255, .05); border-color: rgba(255, 255, 255, .12); }
        @keyframes plf-flick { 0%, 100% { transform: scale(1) rotate(-3deg); } 50% { transform: scale(1.18) rotate(3deg); } }
        .pl-stars { display: flex; justify-content: center; gap: .5rem; margin: .1rem 0 .5rem; }
        .pl-star { width: 2.3rem; height: 2.3rem; fill: currentColor; color: rgba(255, 255, 255, .13); }
        .pl-star.on { color: #fbbf24; filter: drop-shadow(0 0 10px rgba(251, 191, 36, .7)); animation: plf-star .5s cubic-bezier(.2, 1.6, .4, 1) both; animation-delay: calc(var(--i) * .22s + .15s); }
        @keyframes plf-star { from { opacity: 0; transform: scale(.2) rotate(-50deg); } to { opacity: 1; transform: none; } }
        .pl-newbest { display: inline-block; margin: .3rem 0 .1rem; padding: .3rem .9rem; border-radius: 9999px; font-family: var(--pl-display); font-size: .75rem; color: #1c1917; background: linear-gradient(135deg, #fde68a, #fbbf24); box-shadow: 0 0 22px rgba(251, 191, 36, .55); animation: plf-newbest .6s cubic-bezier(.2, 1.6, .4, 1) .6s both; }
        @keyframes plf-newbest { from { opacity: 0; transform: scale(.3) rotate(-8deg); } to { opacity: 1; transform: none; } }
        .pl-bdgs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem .3rem; }
        .pl-bdg { display: grid; justify-items: center; align-content: start; gap: .25rem; padding: .15rem 0; background: none; border: 0; font-size: .64rem; font-weight: 800; line-height: 1.15; text-align: center; color: #64748b; }
        .pl-bdg i { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 9999px; background: rgba(255, 255, 255, .05); border: 1px dashed rgba(255, 255, 255, .2); transition: transform .2s ease; }
        .pl-bdg:hover i { transform: translateY(-2px); }
        .pl-bdg svg { width: 1.15rem; height: 1.15rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; opacity: .55; }
        .pl-bdg.on { color: #fde68a; }
        .pl-bdg.on svg { opacity: 1; }
        .pl-bdg.on i { background: radial-gradient(circle at 30% 25%, rgba(253, 230, 138, .35), rgba(251, 191, 36, .12)); border: 1px solid rgba(251, 191, 36, .7); box-shadow: 0 0 14px rgba(251, 191, 36, .3); }
        .pl-bdg-how { margin-top: .7rem; min-height: 3em; font-size: .78rem; font-weight: 600; line-height: 1.5; color: #94a3b8; }
        .pl-side-h em { float: right; font-style: normal; letter-spacing: 0; color: #94a3b8; }
        .pl-toast.badge { bottom: 9.6rem; color: #fde68a; border-color: rgba(253, 230, 138, .6); }
        @media (max-width: 639px) { .pl-toast.badge { bottom: 8.6rem; } }
        @media (max-height: 520px) and (orientation: landscape) { .pl-toast.badge { bottom: 3.6rem; } }
        .pl-lvlfx { position: fixed; inset: 0; z-index: 70; pointer-events: none; overflow: hidden; }
        .pl-lvlfx .pl-confetti i { animation-name: plf-confall; animation-duration: 2.6s; }
        @keyframes plf-confall { to { transform: translateY(105vh) rotate(720deg); opacity: .9; } }
        .pl-lvlcard { position: absolute; left: 0; right: 0; top: 16%; margin: 0 auto; width: min(17rem, 86%); display: grid; justify-items: center; gap: .2rem; padding: 1.1rem 1rem; border-radius: 1.4rem; text-align: center; background: linear-gradient(160deg, rgba(30, 58, 138, .97), rgba(15, 23, 42, .98)); border: 1px solid rgba(125, 211, 252, .6); box-shadow: 0 20px 60px rgba(14, 165, 233, .35), 0 0 0 6px rgba(56, 189, 248, .12); animation: plf-lvlcard 3.2s ease-out forwards; }
        .pl-lvlcard small { font-size: .7rem; font-weight: 900; letter-spacing: .22em; color: #7dd3fc; }
        .pl-lvlcard b { font-family: var(--pl-display); font-size: 1.6rem; color: #fff; text-shadow: 0 0 24px rgba(56, 189, 248, .7); }
        .pl-lvlcard em { font-style: normal; font-size: .9rem; font-weight: 800; color: #fde68a; }
        .pl-lvlcard-ic { display: grid; place-items: center; width: 3.6rem; height: 3.6rem; margin-bottom: .2rem; border-radius: 9999px; color: #fde68a; background: rgba(251, 191, 36, .14); border: 2px solid rgba(251, 191, 36, .7); box-shadow: 0 0 28px rgba(251, 191, 36, .45); }
        .pl-lvlcard-ic svg { width: 1.9rem; height: 1.9rem; }
        @keyframes plf-lvlcard { 0% { opacity: 0; transform: translateY(-2rem) scale(.6); } 10% { opacity: 1; transform: translateY(0) scale(1.06); } 16% { transform: scale(1); } 85% { opacity: 1; transform: none; } 100% { opacity: 0; transform: translateY(-1rem) scale(.96); } }

        /* ---- Phil reactions, wardrobe and water themes ---- */
        .pl-fish-y.cheer { animation: plf-cheer .9s ease-out; }
        .pl-fish-y.ouch { animation: plf-ouch .55s ease-in-out; }
        @keyframes plf-cheer { 0%, 100% { transform: none; } 25% { transform: translateY(-.9rem) scale(1.15); } 50% { transform: translateY(0) scale(1); } 75% { transform: translateY(-.5rem) scale(1.1); } }
        @keyframes plf-ouch { 0%, 100% { transform: none; } 15% { transform: translateX(-.35rem); } 30% { transform: translateX(.35rem); } 45% { transform: translateX(-.25rem); } 60% { transform: translateX(.25rem); } 80% { transform: translateX(-.1rem); } }
        .pl-ex { position: absolute; top: -.9rem; left: 55%; font-family: var(--pl-display); font-size: .8rem; line-height: 1; color: #f87171; text-shadow: 0 0 8px rgba(248, 113, 113, .8); pointer-events: none; animation: plf-ex .55s ease-out both; }
        @keyframes plf-ex { from { opacity: 0; transform: translateY(.4rem) scale(.4); } 40% { opacity: 1; transform: scale(1.3); } to { opacity: 1; transform: none; } }
        .pl-fish-y.show { animation: plf-show .6s ease-out; }
        @keyframes plf-show { 0%, 100% { transform: none; } 40% { transform: translateY(-.5rem) scale(1.12); } }
        .pl-lvlcard .unl { margin-top: .35rem; font-size: .78rem; font-weight: 700; line-height: 1.4; color: #a7f3d0; }
        .pl-wd-modal { position: fixed; inset: 0; z-index: 80; display: grid; grid-template-columns: minmax(0, 1fr); place-items: center; padding: 1rem; background: rgba(2, 6, 23, .86); }
        .pl-wd-card { box-sizing: border-box; min-width: 0; width: min(26rem, 100%); max-height: calc(100dvh - 2rem); overflow-y: auto; padding: 1rem 1.1rem 1.1rem; border-radius: 1.4rem; background: linear-gradient(160deg, rgba(24, 42, 82, .98), rgba(9, 18, 40, .99)); border: 1px solid rgba(125, 211, 252, .35); box-shadow: 0 18px 40px rgba(0, 0, 0, .55), inset 0 1px 0 rgba(255, 255, 255, .08); }
        .pl-wd-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .75rem; }
        .pl-wd-head h2 { font-size: .8rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #7dd3fc; }
        .pl-wd-x { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 9999px; font-size: 1.3rem; line-height: 1; color: #cbd5e1; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .12); }
        .pl-wd-x:hover { color: #fff; border-color: rgba(125, 211, 252, .5); }
        .pl-wd-view { position: relative; isolation: isolate; overflow: hidden; display: grid; place-items: center; height: 9.5rem; border-radius: 1.1rem; border: 1px solid rgba(125, 211, 252, .3); background: linear-gradient(180deg, rgba(125, 211, 252, .36) 0%, rgba(56, 189, 248, .32) 22%, rgba(37, 99, 235, .44) 62%, rgba(12, 30, 90, .78) 100%); box-shadow: inset 0 -14px 24px -12px rgba(2, 8, 30, .7); }
        .pl-wd-view .pl-wave { z-index: 0; }
        .pl-wd-view .pl-bub { z-index: 0; }
        .pl-wd-sand { position: absolute; left: 0; right: 0; bottom: 0; z-index: 0; height: 1.2rem; background: linear-gradient(180deg, rgba(160, 138, 92, 0), rgba(160, 138, 92, .55) 55%, rgba(120, 98, 62, .9)); }
        .pl-wd-shadow { position: absolute; bottom: .95rem; left: 50%; z-index: 1; width: 5.5rem; height: .6rem; margin-left: -2.75rem; border-radius: 50%; background: radial-gradient(ellipse, rgba(2, 8, 30, .55), transparent 70%); }
        .pl-wd-phil { position: relative; z-index: 2; width: 7.6rem; height: auto; margin-bottom: .5rem; image-rendering: pixelated; filter: drop-shadow(0 0 12px rgba(56, 189, 248, .55)); animation: pl-fishbob 1.4s steps(1) infinite; }
        .pl-wd-cap { margin: .7rem 0 .85rem; text-align: center; font-size: .9rem; font-weight: 800; color: #f1f5f9; }
        .pl-wd-cap small { display: block; margin-top: .2rem; font-size: .72rem; font-weight: 700; color: #94a3b8; }
        .pl-wd-cap small b { color: #fde68a; }
        .pl-wd-tabs { display: grid; grid-template-columns: repeat(3, 1fr); gap: .25rem; padding: .25rem; border-radius: 9999px; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .08); }
        .pl-wd-tab { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .42rem .3rem; border-radius: 9999px; font-size: .76rem; font-weight: 800; color: #94a3b8; transition: color .2s, background .2s; }
        .pl-wd-tab small { font-size: .64rem; font-weight: 800; opacity: .75; }
        .pl-wd-tab:hover { color: #fff; }
        .pl-wd-tab[aria-selected="true"] { color: #0b1220; background: linear-gradient(135deg, #7dd3fc, #38bdf8); box-shadow: 0 4px 14px -4px rgba(56, 189, 248, .7); }
        .pl-wd-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(5.4rem, 1fr)); gap: .55rem; margin-top: .8rem; }
        .pl-wd-tile { position: relative; display: grid; gap: .35rem; justify-items: center; padding: .45rem .35rem .55rem; border-radius: .95rem; font-size: .72rem; font-weight: 800; color: #cbd5e1; background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .1); transition: transform .2s ease, border-color .2s, background .2s, color .2s, box-shadow .2s; }
        .pl-wd-tile:hover:not(:disabled) { transform: translateY(-2px); color: #fff; border-color: rgba(125, 211, 252, .5); }
        .pl-wd-tile[aria-pressed="true"] { color: #e0f2fe; background: rgba(56, 189, 248, .14); border-color: rgba(125, 211, 252, .75); box-shadow: 0 0 0 1px rgba(125, 211, 252, .3), 0 8px 22px -10px rgba(56, 189, 248, .6); }
        .pl-wd-tile[aria-pressed="true"]::after { content: "\2713"; position: absolute; top: .3rem; right: .3rem; display: grid; place-items: center; width: 1.1rem; height: 1.1rem; border-radius: 9999px; font-size: .65rem; font-weight: 900; color: #0b1220; background: #7dd3fc; }
        .pl-wd-tile:disabled { cursor: not-allowed; color: #64748b; }
        .pl-wd-tile:disabled .pl-wd-th, .pl-wd-tile:disabled .pl-wd-sw { opacity: .35; filter: grayscale(.6); }
        .pl-wd-th, .pl-wd-sw { width: 100%; height: 3.4rem; border-radius: .65rem; }
        .pl-wd-th { display: grid; place-items: center; background: rgba(8, 16, 36, .75); }
        .pl-wd-th svg { width: 3.6rem; height: auto; image-rendering: pixelated; }
        .pl-wd-sw { position: relative; overflow: hidden; border: 1px solid rgba(255, 255, 255, .1); background: linear-gradient(180deg, rgba(125, 211, 252, .36) 0%, rgba(56, 189, 248, .32) 22%, rgba(37, 99, 235, .44) 62%, rgba(12, 30, 90, .78) 100%); }
        .pl-wd-sw::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: .45rem; background: linear-gradient(180deg, rgba(160, 138, 92, .2), rgba(120, 98, 62, .85)); }
        .pl-wd-lock { position: absolute; left: 50%; top: 2.15rem; z-index: 1; display: inline-flex; align-items: center; gap: .2rem; padding: .15rem .5rem; border-radius: 9999px; font-size: .62rem; font-weight: 800; color: #cbd5e1; background: rgba(2, 6, 23, .88); border: 1px solid rgba(255, 255, 255, .15); transform: translate(-50%, -50%); white-space: nowrap; }
        .pl-wd-lock svg { width: .7rem; height: .7rem; }
        .pl-wd-foot { display: grid; grid-template-columns: auto 1fr; gap: .6rem; margin-top: 1rem; }
        .pl-sea[data-water="sunset"], .pl-wd-view[data-water="sunset"], .pl-wd-sw[data-water="sunset"] { background: linear-gradient(180deg, rgba(253, 186, 116, .5) 0%, rgba(251, 146, 60, .42) 24%, rgba(190, 60, 90, .5) 62%, rgba(60, 20, 70, .82) 100%); }
        .pl-sea[data-water="sunset"] .pl-wave, .pl-wd-view[data-water="sunset"] .pl-wave { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 10'%3E%3Cpath d='M0 5 Q10 0 20 5 T40 5 T60 5 T80 5 V10 H0Z' fill='%23fdba74'/%3E%3C/svg%3E"); }
        .pl-sea[data-water="night"], .pl-wd-view[data-water="night"], .pl-wd-sw[data-water="night"] { background: linear-gradient(180deg, rgba(99, 102, 241, .32) 0%, rgba(67, 56, 202, .38) 25%, rgba(30, 27, 75, .66) 62%, rgba(8, 6, 32, .92) 100%); }
        .pl-sea[data-water="night"] .pl-wave, .pl-wd-view[data-water="night"] .pl-wave { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 10'%3E%3Cpath d='M0 5 Q10 0 20 5 T40 5 T60 5 T80 5 V10 H0Z' fill='%23a5b4fc'/%3E%3C/svg%3E"); }
        .pl-sea[data-water="coral"], .pl-wd-view[data-water="coral"], .pl-wd-sw[data-water="coral"] { background: linear-gradient(180deg, rgba(94, 234, 212, .42) 0%, rgba(45, 212, 191, .34) 24%, rgba(13, 148, 136, .48) 62%, rgba(6, 50, 70, .84) 100%); }
        .pl-sea[data-water="coral"] .pl-wave, .pl-wd-view[data-water="coral"] .pl-wave { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 10'%3E%3Cpath d='M0 5 Q10 0 20 5 T40 5 T60 5 T80 5 V10 H0Z' fill='%235eead4'/%3E%3C/svg%3E"); }
        .pl-coral { position: absolute; bottom: 0; left: 58%; width: 3.2rem; height: auto; opacity: .9; pointer-events: none; }
        @media (prefers-reduced-motion: reduce) {
            .pl-lvl, .pl-lvl::after, .pl-lvl.up, .pl-start, .pl-chipbox, .pl-big, .pl-xpbar i::after, .pl-ranks-pop::after, .pl-ranks-pop .pl-rank, .pl-tab[aria-selected="true"], .pl-tab[aria-selected="true"] svg { animation: none !important; }
            .pl-wave, .pl-bub, .pl-fbub, .pl-rays, .pl-weed, .pl-hook, .pl-bait, .pl-fish, .pl-fish-y, .pl-fish-px { animation: none !important; }
            .pl-hook { display: none; }
            .pl-fish.main { transform: translateX(8rem); }
            .pl-fish.mini { transform: translateX(var(--rx, 16rem)); }
            .pl-liq-in, .pl-liq-out, .pl-btn::after, .pl-tab svg { transition: none !important; }
            .pl-device, .pl-screen::after, .pl-hold, .pl-reply, .pl-reply-in, .pl-reply .pl-choice { animation: none !important; }
            .pl-screen::after { display: none; }
            .pl-layout, .pl-t-ease { transition: none !important; }
            .pl-phil-fish, .pl-phil-say, .pl-guide-phil, .pl-guide-say, .pl-guide-step { animation: none !important; }
            .pl-rush::before, .pl-feature::before { animation: none !important; }
            .pl-demo, .pl-demo .pl-scam, .pl-demo .pl-safe, .pl-demo .pl-timer i, .pl-demo .pl-choice { animation: none !important; }
            .pl-ic-big, .pl-feat-in.swap, .pl-tile, #pl-icons [class^="ic-"] { animation: none !important; }
            .pl-in, .pl-slide, .pl-result, .pl-flash, .pl-b, .pl-choice, .pl-typing i, .pl-pick, .pl-confetti i, .pl-end-badge, .pl-res-title em, .pl-bump, .pl-gain, .pl-card.fx-ok, .pl-card.fx-bad, .pl-combo, .pl-combo .sp, .pl-count-n, .pl-flame svg, .pl-star, .pl-newbest, .pl-lvlcard, .pl-ex, .pl-wd-phil, .pl-wd-view .pl-bub { animation: none !important; }
            .pl-toast { animation-duration: 3s; }
            .pl-timer i { animation-duration: 0.001s !important; }
            .pl-btn, .pl-tab, .pl-len, .pl-pick, .pl-xpbar i { transition: none; }
        }
    </style>

    <div class="pl-wrap" :class="{ 'is-playing': playing, 'in-game': inGame, 'in-story': playing && tab === 'story' }" :style="'--phil-a:' + phil.a + ';--phil-b:' + phil.b + ';--phil-c:' + phil.c" @pl-mood.window="react($event.detail)" x-data="playHub(@js($messages), @js($stories))" @pl-xp.window="addXp($event.detail)" @pl-playing="active[$event.detail.game] = $event.detail.on; held[$event.detail.game] = !!$event.detail.hold">
        <div class="pl-layout noside" :class="{ solo: playing, noside: !side }">
        <div class="pl-col">
        <header class="pl-hero pl-in">
            <svg width="0" height="0" style="position: absolute" aria-hidden="true" focusable="false"><symbol id="pl-fish-px" viewBox="0 0 17 9"><rect x="8" y="0" width="2" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="7" y="1" width="2" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="9" y="1" width="3" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="0" y="2" width="1" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="6" y="2" width="8" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="0" y="3" width="2" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="5" y="3" width="3" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="8" y="3" width="6" height="1" fill="#0b1220"/><rect x="14" y="3" width="1" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="0" y="4" width="3" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="4" y="4" width="4" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="8" y="4" width="1" height="1" fill="#0b1220"/><rect x="9" y="4" width="1" height="1" fill="#e0f2fe"/><rect x="10" y="4" width="4" height="1" fill="#0b1220"/><rect x="14" y="4" width="2" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="0" y="5" width="2" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="4" y="5" width="12" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="16" y="5" width="1" height="1" fill="#0b1220"/><rect x="0" y="6" width="1" height="1" style="fill:var(--pa,#0ea5e9)"/><rect x="4" y="6" width="1" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="5" y="6" width="10" height="1" style="fill:var(--pc,#bae6fd)"/><rect x="15" y="6" width="1" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="4" y="7" width="2" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="6" y="7" width="7" height="1" style="fill:var(--pc,#bae6fd)"/><rect x="13" y="7" width="2" height="1" style="fill:var(--pb,#38bdf8)"/><rect x="6" y="8" width="6" height="1" style="fill:var(--pb,#38bdf8)"/></symbol></svg>
            <svg width="0" height="0" style="position: absolute" aria-hidden="true" focusable="false"><defs>
                <g id="pl-hat-none"></g>
                <g id="pl-hat-party"><rect x="11" y="-3" width="1" height="1" fill="#fde68a"/><rect x="11" y="-2" width="1" height="1" fill="#f43f5e"/><rect x="10" y="-1" width="3" height="1" fill="#fde68a"/><rect x="10" y="0" width="3" height="1" fill="#f43f5e"/><rect x="9" y="1" width="5" height="1" fill="#fde68a"/></g>
                <g id="pl-hat-cap"><rect x="9" y="0" width="4" height="1" fill="#2563eb"/><rect x="8" y="1" width="6" height="1" fill="#2563eb"/><rect x="13" y="1" width="3" height="1" fill="#1d4ed8"/><rect x="10" y="0" width="1" height="1" fill="#fde68a"/></g>
                <g id="pl-hat-crown"><rect x="9" y="-1" width="1" height="1" fill="#fbbf24"/><rect x="11" y="-1" width="1" height="1" fill="#fbbf24"/><rect x="13" y="-1" width="1" height="1" fill="#fbbf24"/><rect x="9" y="0" width="5" height="1" fill="#fbbf24"/><rect x="9" y="1" width="5" height="1" fill="#f59e0b"/><rect x="11" y="0" width="1" height="1" fill="#ef4444"/></g>
                <g id="pl-hat-wizard"><rect x="12" y="-3" width="1" height="1" fill="#a78bfa"/><rect x="11" y="-2" width="2" height="1" fill="#7c3aed"/><rect x="10" y="-1" width="3" height="1" fill="#7c3aed"/><rect x="11" y="-1" width="1" height="1" fill="#fde68a"/><rect x="10" y="0" width="3" height="1" fill="#6d28d9"/><rect x="8" y="1" width="7" height="1" fill="#6d28d9"/></g>
            </defs></svg>
            <svg id="pl-icons" width="0" height="0" style="position: absolute" aria-hidden="true" focusable="false"><symbol id="pl-ic-parcel" viewBox="0 0 48 48"><g class="ic-bob"><path d="M8 18l16-8 16 8v18l-16 8-16-8z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M8 18l16 8 16-8M24 26v18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 14l16 8" stroke="#fde68a" stroke-width="3" stroke-linecap="round"/></g></symbol><symbol id="pl-ic-police" viewBox="0 0 48 48"><g class="ic-pulse"><path d="M24 6l15 5v11c0 10-6 17-15 20C15 39 9 32 9 22V11z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path class="ic-twinkle" d="M24 15l2.500 5 5.500.8-4 3.900.9 5.500L24 27.500l-4.900 2.700.9-5.500-4-3.900 5.500-.8z" fill="#fde68a"/></g></symbol><symbol id="pl-ic-job" viewBox="0 0 48 48"><rect x="7" y="17" width="34" height="23" rx="4" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M17 17v-3a3 3 0 013-3h8a3 3 0 013 3v3M7 27h34" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><g class="ic-coin"><circle cx="24" cy="27" r="5.500" fill="#fde68a"/><path d="M24 24v6" stroke="#92400e" stroke-width="2" stroke-linecap="round"/></g></symbol><symbol id="pl-ic-mum" viewBox="0 0 48 48"><path d="M8 11h32a3 3 0 013 3v17a3 3 0 01-3 3H23l-8 7v-7H8a3 3 0 01-3-3V14a3 3 0 013-3z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path class="ic-beat" d="M24 29c-5-3.500-8-6-8-9a4.500 4.500 0 018-2.500A4.500 4.500 0 0132 20c0 3-3 5.500-8 9z" fill="#fb7185"/></symbol><symbol id="pl-ic-invest" viewBox="0 0 48 48"><path d="M7 7v34h34" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path class="ic-draw" d="M12 33l9-9 6 6 11-13" fill="none" stroke="#34d399" stroke-width="3.500" stroke-linecap="round" stroke-linejoin="round" pathLength="50"/><path class="ic-arrowhead" d="M31 16h8v8" fill="none" stroke="#34d399" stroke-width="3.500" stroke-linecap="round" stroke-linejoin="round"/></symbol><symbol id="pl-ic-romance" viewBox="0 0 48 48"><path class="ic-beat" d="M24 41C10 31 6 24 6 17a9 9 0 0118-4 9 9 0 0118 4c0 7-4 14-18 24z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path class="ic-rise" d="M36 9c-1.500-1.500-4 0-2.500 2 .8 1 2.500 2 2.500 2s1.700-1 2.500-2c1.500-2-1-3.500-2.500-2z" fill="#f9a8d4"/></symbol><symbol id="pl-ic-bank" viewBox="0 0 48 48"><path d="M6 19L24 8l18 11z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M10 23v13M19 23v13M29 23v13M38 23v13M6 41h36" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><g class="ic-blink"><circle cx="39" cy="10" r="5.500" fill="#ef4444"/><path d="M39 7.500v3" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="39" cy="13" r=".9" fill="#fff"/></g></symbol><symbol id="pl-ic-shop" viewBox="0 0 48 48"><g class="ic-swing"><path d="M7 24L24 7h17v17L24 41z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><circle cx="33" cy="15" r="3" fill="#fde68a"/><path d="M19 28l9-9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g></symbol><symbol id="pl-ic-prize" viewBox="0 0 48 48"><rect x="7" y="22" width="34" height="19" rx="3" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M24 22v19M7 30h34" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><g class="ic-lid"><rect x="5" y="14" width="38" height="8" rx="3" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M24 14c-6-8-13-3-8 0M24 14c6-8 13-3 8 0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><path class="ic-twinkle" d="M40 5l1.500 3.500L45 10l-3.500 1.500L40 15l-1.500-3.500L35 10l3.500-1.500z" fill="#fde68a"/></symbol><symbol id="pl-ic-invoice" viewBox="0 0 48 48"><rect x="6" y="12" width="36" height="26" rx="4" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path class="ic-flap" d="M6 17l18 13 18-13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><g class="ic-blink"><circle cx="39" cy="13" r="5.500" fill="#fbbf24"/><path d="M39 10.500v3" stroke="#78350f" stroke-width="2" stroke-linecap="round"/><circle cx="39" cy="16" r=".9" fill="#78350f"/></g></symbol><symbol id="pl-ic-hacked" viewBox="0 0 48 48"><g class="ic-shake"><rect x="10" y="22" width="28" height="20" rx="4" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M16 22v-6a8 8 0 0116 0v6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="24" cy="31" r="3" fill="currentColor"/><path d="M24 33v4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g></symbol><symbol id="pl-ic-realalert" viewBox="0 0 48 48"><g class="ic-ring"><path d="M12 34V22a12 12 0 0124 0v12l4 4H8z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M20 42a4 4 0 008 0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><circle class="ic-blink" cx="37" cy="9" r="4.500" fill="#22c55e"/></symbol><symbol id="pl-ic-sell" viewBox="0 0 48 48"><g class="ic-slide"><rect x="5" y="14" width="38" height="22" rx="3" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><circle cx="24" cy="25" r="5.500" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 21h1M36 29h1" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><path d="M30 42h12m0 0l-4-4m4 4l-4 4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></symbol><symbol id="pl-ic-ticket" viewBox="0 0 48 48"><g class="ic-wig"><path d="M5 17a3 3 0 013-3h32a3 3 0 013 3v4a4 4 0 000 8v4a3 3 0 01-3 3H8a3 3 0 01-3-3v-4a4 4 0 000-8z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M31 15v19" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="3 3.500"/><path d="M12 22h11M12 28h8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g></symbol><symbol id="pl-ic-rent" viewBox="0 0 48 48"><g class="ic-bob"><path d="M6 24L24 8l18 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 22v20h28V22" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="20" y="30" width="8" height="12" rx="1.500" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><g class="ic-swing"><circle cx="39" cy="12" r="3.500" fill="none" stroke="#fde68a" stroke-width="2.5"/><path d="M39 15.500V24m0 0h-3m3 3.500h-3" fill="none" stroke="#fde68a" stroke-width="2.5" stroke-linecap="round"/></g></symbol><symbol id="pl-ic-remote" viewBox="0 0 48 48"><g class="ic-pulse"><rect x="5" y="9" width="38" height="25" rx="3" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 41h14M24 34v7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><path class="ic-blink" d="M20 14l11 8-5 1.500 3.500 5.500-3 1.800-3.500-5.500-3.500 3.500z" fill="#fde68a" stroke="#fde68a" stroke-width="1.500" stroke-linejoin="round"/></symbol><symbol id="pl-ic-scholar" viewBox="0 0 48 48"><g class="ic-bob"><path d="M24 9L4 19l20 10 20-10z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 24v9c0 3 5.500 6 12 6s12-3 12-6v-9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><g class="ic-swing"><path d="M42 20v12" fill="none" stroke="#fde68a" stroke-width="2.5" stroke-linecap="round"/><circle cx="42" cy="35" r="2.200" fill="#fde68a"/></g></symbol><symbol id="pl-ic-charity" viewBox="0 0 48 48"><g class="ic-beat"><path d="M24 33S8 25 8 15c0-4.500 3.500-7.500 7.500-7.500 3.500 0 6.500 2 8.500 5 2-3 5-5 8.500-5 4 0 7.500 3 7.500 7.500C40 25 24 33 24 33z" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><path d="M5 40h10l6-3h9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle class="ic-coin" cx="39" cy="38" r="5" fill="none" stroke="#fde68a" stroke-width="2.5"/></symbol><symbol id="pl-ic-onboard" viewBox="0 0 48 48"><g class="ic-bob"><rect x="9" y="9" width="30" height="21" rx="2.500" fill="currentColor" fill-opacity=".18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 36h40l-3.500 4h-33z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g><path class="ic-draw" d="M17 20l5 5 9-9" fill="none" stroke="#fde68a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></symbol><symbol id="pl-ic-qr" viewBox="0 0 48 48"><g class="ic-pulse"><rect x="6" y="6" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="28" y="6" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="6" y="28" width="14" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 10h6v6h-6zM32 10h6v6h-6zM10 32h6v6h-6z" fill="currentColor"/></g><path class="ic-blink" d="M28 28h5v5h-5zM37 28h5v5h-5zM28 37h5v5h-5zM37 37h5v5h-5z" fill="#fde68a"/></symbol></svg>
            <svg id="pl-rks" width="0" height="0" style="position: absolute" aria-hidden="true" focusable="false"><symbol id="pl-rush-1" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21v-9"/><path d="M12 12c0-4-3-6-7-6 0 4 3 6 7 6z"/><path d="M12 14c0-3 2-5 6-5 0 3-2 5-6 5z"/></g></symbol><symbol id="pl-rush-2" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.500" cy="10.500" r="6"/><path d="M15 15l6 6"/></g></symbol><symbol id="pl-rush-3" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a4 4 0 005.700 0l3-3a4 4 0 00-5.700-5.700l-1 1"/><path d="M14 10a4 4 0 00-5.700 0l-3 3a4 4 0 005.700 5.700l1-1"/></g></symbol><symbol id="pl-rush-4" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 8l9 6 9-6"/></g></symbol><symbol id="pl-rush-5" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3v12a4 4 0 11-8 0v-2"/><circle cx="13" cy="3" r="1"/></g></symbol><symbol id="pl-rush-6" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18L19 5"/><path d="M8 11l5 5"/><path d="M6 18l-3 3"/></g></symbol><symbol id="pl-rush-7" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.500 8-8 9-4.500-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></g></symbol><symbol id="pl-rush-8" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L5 14h6l-1 8 9-12h-6z"/></g></symbol><symbol id="pl-rush-9" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></g></symbol><symbol id="pl-rush-10" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12c3-5 9-6 14-2l4-3v10l-4-3c-5 4-11 3-14-2z"/><circle cx="8" cy="11" r=".8"/></g></symbol><symbol id="pl-rush-11" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8v5a4 4 0 01-8 0z"/><path d="M8 6H4v1a4 4 0 004 4M16 6h4v1a4 4 0 01-4 4"/><path d="M12 13v4M8 20h8"/></g></symbol><symbol id="pl-rush-12" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l4 4 5-7 5 7 4-4-2 11H5z"/></g></symbol></svg>
            <div class="pl-water" aria-hidden="true">
                <div class="pl-hook"><span class="pl-line"></span><span class="pl-bait">FREE!</span></div>
            </div>
            <div class="pl-confetti" x-show="lvlUp" style="display: none;" aria-hidden="true"><template x-for="k in 22" :key="k"><i :style="'--x:' + (k * 4.5 % 100) + '%;--d:' + (k % 7 * .08) + 's;--c:' + ['#7dd3fc', '#38bdf8', '#a78bfa', '#fcd34d', '#34d399'][k % 5]"></i></template></div>
            <div class="pl-hero-text" style="position: relative; z-index: 1">
                <h1>Phish Lab</h1>
                <button type="button" class="pl-sndbtn" :aria-pressed="sound" @click="toggleSound()" :title="sound ? 'Sound is on. Click to mute.' : 'Sound is off. Click to turn on.'" :aria-label="sound ? 'Mute game sounds' : 'Turn game sounds on'" title="Sound is off. Click to turn on.">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5L6 9H3v6h3l5 4V5z"/><path x-show="sound" d="M15.500 8.500a5 5 0 010 7M18.500 5.500a9 9 0 010 13"/><path x-show="!sound" style="display: none;" d="M16 9l5 6M21 9l-5 6"/></svg>
                </button>
                <p>Games that train you to spot scams. Win XP, level up and become a scam-spotting pro. All examples are made up. Your progress stays in this browser.</p>
                <div class="pl-chips">
                    <button type="button" class="pl-snd" @click="wardOpen = true" aria-haspopup="dialog" title="Dress up Phil">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8l4 4 5-7 5 7 4-4-2 11H5z"/></svg>
                        <span>Wardrobe</span>
                    </button>
                    <span class="pl-flame" :class="streakDays > 0 && playedToday ? '' : 'cold'" :title="streakDays > 0 && !playedToday ? 'Play a game today to keep your streak alive' : 'Days in a row you have played'">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2c1 4.500 5.500 6 5.500 11a5.500 5.500 0 01-11 0c0-2.200 1-3.500 2.200-4.500.1 2 1 3 2.300 3.200C10.200 8.500 10.500 5 12 2z"/></svg>
                        <span x-text="streakDays > 0 ? streakDays + (streakDays === 1 ? ' day streak' : ' day streak') : 'Start a streak'"></span>
                    </span>
                </div>
            </div>
            <div class="pl-hud" aria-label="Your level">
                <span class="pl-lvl" :class="lvlUp ? 'up' : ''" x-text="lv.n"></span>
                <div class="pl-hud-main">
                    <p class="pl-hud-title">
                        <button type="button" class="pl-rankbtn" @click="ranksOpen = !ranksOpen" :aria-expanded="ranksOpen" aria-haspopup="true" title="See all ranks"><svg class="pl-rk" viewBox="0 0 24 24" aria-hidden="true"><use :href="'#pl-rush-' + rankNo"></use></svg><span x-text="rank"></span><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg></button>
                        <span x-text="lv.cur + ' / ' + lv.need + ' XP'"></span>
                    </p>
                    <div class="pl-xpbar"><i :style="'width:' + (lv.cur / lv.need * 100) + '%'"></i></div>
                </div>
                <div class="pl-ranks-pop" x-show="ranksOpen" x-transition:enter="pl-liq-in" x-transition:enter-start="pl-liq-from" x-transition:enter-end="pl-liq-to" x-transition:leave="pl-liq-out" x-transition:leave-start="pl-liq-to" x-transition:leave-end="pl-liq-gone" @click.outside="ranksOpen = false" @keydown.escape.window="ranksOpen = false" style="display: none;">
                    <ul class="pl-ranks">
                        <template x-for="(r, ri) in ranks" :key="r">
                            <li class="pl-rank" :style="'--i:' + ri" :class="ri + 1 < lv.n ? 'done' : (ri + 1 === Math.min(lv.n, ranks.length) ? 'now' : '')">
                                <i><svg viewBox="0 0 24 24" aria-hidden="true"><use :href="'#pl-rush-' + (ri + 1)"></use></svg></i><span x-text="r"></span><small class="lv" x-text="'Lv ' + (ri + 1)"></small><em x-show="ri + 1 === Math.min(lv.n, ranks.length)" x-text="(lv.need - lv.cur) + ' XP to go'"></em>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
            <div class="pl-sea" :data-water="phil.water">
                <div class="pl-sea-clip" aria-hidden="true">
                    <span class="pl-rays"></span>
                    <span class="pl-wave w1"></span><span class="pl-wave w2"></span><span class="pl-wave w3"></span>
                    <i class="pl-bub" style="--x: 4%; --s: 0.3rem; --d: 0s; --t: 7s; --dx: 0.3rem"></i>
                    <i class="pl-bub" style="--x: 9%; --s: 0.2rem; --d: 3.1s; --t: 5.5s; --dx: -0.2rem"></i>
                    <i class="pl-bub" style="--x: 15%; --s: 0.5rem; --d: 1.4s; --t: 8.5s; --dx: 0.4rem"></i>
                    <i class="pl-bub" style="--x: 22%; --s: 0.25rem; --d: 5.2s; --t: 6s; --dx: -0.3rem"></i>
                    <i class="pl-bub" style="--x: 30%; --s: 0.35rem; --d: 2.2s; --t: 7.5s; --dx: 0.2rem"></i>
                    <i class="pl-bub" style="--x: 37%; --s: 0.2rem; --d: 6.4s; --t: 5s; --dx: 0.3rem"></i>
                    <i class="pl-bub" style="--x: 44%; --s: 0.45rem; --d: 0.8s; --t: 9s; --dx: -0.4rem"></i>
                    <i class="pl-bub" style="--x: 52%; --s: 0.28rem; --d: 4.3s; --t: 6.5s; --dx: 0.25rem"></i>
                    <i class="pl-bub" style="--x: 59%; --s: 0.2rem; --d: 1.9s; --t: 5.2s; --dx: -0.2rem"></i>
                    <i class="pl-bub" style="--x: 66%; --s: 0.5rem; --d: 3.6s; --t: 8s; --dx: 0.35rem"></i>
                    <i class="pl-bub" style="--x: 73%; --s: 0.3rem; --d: 0.3s; --t: 6.8s; --dx: -0.3rem"></i>
                    <i class="pl-bub" style="--x: 81%; --s: 0.22rem; --d: 5.6s; --t: 5.6s; --dx: 0.2rem"></i>
                    <i class="pl-bub" style="--x: 88%; --s: 0.4rem; --d: 2.7s; --t: 8.2s; --dx: -0.35rem"></i>
                    <i class="pl-bub" style="--x: 95%; --s: 0.28rem; --d: 4.9s; --t: 6.2s; --dx: 0.25rem"></i>
                    <svg class="pl-sand" viewBox="0 0 200 14" preserveAspectRatio="none"><defs><linearGradient id="plsand" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#a08a5c" stop-opacity=".7"/><stop offset="1" stop-color="#4b3d27" stop-opacity=".95"/></linearGradient></defs><path d="M0 8 C20 3 40 10 62 6 C86 2 104 10 130 6 C154 3 176 9 200 5 V14 H0Z" fill="url(#plsand)"/><g fill="#d9c79b" fill-opacity=".35"><ellipse cx="38" cy="9.500" rx="2.200" ry="1.100"/><ellipse cx="97" cy="9" rx="1.600" ry=".9"/><ellipse cx="151" cy="10" rx="2.400" ry="1.200"/></g></svg>
                    <svg class="pl-weed l" viewBox="0 0 8 16" shape-rendering="crispEdges"><g fill="#34d399"><rect x="3" y="12" width="2" height="4"/><rect x="2" y="9" width="2" height="3"/><rect x="4" y="6" width="2" height="3"/><rect x="3" y="3" width="2" height="3"/><rect x="2" y="0" width="2" height="3"/></g></svg><svg class="pl-weed r" viewBox="0 0 8 16" shape-rendering="crispEdges"><g fill="#34d399"><rect x="3" y="12" width="2" height="4"/><rect x="2" y="9" width="2" height="3"/><rect x="4" y="6" width="2" height="3"/><rect x="3" y="3" width="2" height="3"/><rect x="2" y="0" width="2" height="3"/></g></svg>
                    <svg class="pl-coral" x-show="phil.water === 'coral'" style="display: none;" viewBox="0 0 40 16" shape-rendering="crispEdges" aria-hidden="true"><g fill="#fb7185"><rect x="4" y="8" width="2" height="8"/><rect x="2" y="5" width="2" height="5"/><rect x="6" y="3" width="2" height="6"/><rect x="8" y="6" width="2" height="3"/></g><g fill="#fdba74"><rect x="22" y="6" width="2" height="10"/><rect x="19" y="3" width="2" height="6"/><rect x="24" y="2" width="2" height="6"/><rect x="26" y="7" width="3" height="2"/></g><g fill="#f9a8d4"><rect x="32" y="10" width="6" height="6"/><rect x="33" y="8" width="4" height="2"/></g></svg>
                </div>
                <div class="pl-fishlane">
                <div class="pl-fish mini" style="--dl: -11s; --rx: 16rem"><div class="pl-fish-y"><svg class="pl-fish-px" viewBox="0 0 17 9"><use href="#pl-fish-px"/></svg></div></div>
                <div class="pl-fish mini" style="--dl: -17s; --dur: 37s; --w: 2.5rem; --h: -45deg; bottom: 1.35rem; --rx: 24rem"><div class="pl-fish-y"><svg class="pl-fish-px" viewBox="0 0 17 9"><use href="#pl-fish-px"/></svg></div></div>
                <div class="pl-fish mini" style="--dl: -21s; --dur: 34s; --w: 1.8rem; --h: 200deg; bottom: 1.6rem; --rx: 30rem"><div class="pl-fish-y"><svg class="pl-fish-px" viewBox="0 0 17 9"><use href="#pl-fish-px"/></svg></div></div>

                <button type="button" class="pl-fish main" @click="poke()" title="Poke Phil the Phish" aria-label="Phil the Phish. Press for a safety joke.">
                    <span class="pl-fish-y" :class="fishHop ? 'hop' : mood">
                        <i class="pl-fbub" style="--d: 0s"></i><i class="pl-fbub" style="--d: 1.7s; --fx: 62%"></i>
                        <span class="pl-say" x-show="fishSay" x-transition.opacity.duration.200ms style="display: none;" x-text="fishSay"></span>
                        <b class="pl-ex" x-show="mood === 'ouch'" style="display: none;" aria-hidden="true">!</b>
                        <svg class="pl-fish-px" viewBox="0 -3 17 12" :style="'--pa:' + phil.a + ';--pb:' + phil.b + ';--pc:' + phil.c"><use href="#pl-fish-px" width="17" height="9"/><use :href="'#pl-hat-' + phil.hat"/></svg>
                    </span>
                </button>
            </div>
            </div>
        </header>

        <div class="pl-tabs pl-in" :class="{ 'nobtn': playing }" role="tablist" style="animation-delay: .08s">
            <button type="button" class="pl-tab" role="tab" :aria-selected="tab === 'quiz'" @click="tab = 'quiz'" :disabled="playing">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75L11.25 15 15 9.75M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
                <span><b>Scam or Safe?</b><small>Quick quiz</small></span>
            </button>
            <button type="button" class="pl-tab" role="tab" :aria-selected="tab === 'rush'" @click="tab = 'rush'" :disabled="playing">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 2 4.5 13.5H11L10 22l9-11.5h-6.5z" /></svg>
                <span><b>Inbox Rush</b><small>Beat the clock</small></span>
            </button>
            <button type="button" class="pl-tab" role="tab" :aria-selected="tab === 'story'" @click="tab = 'story'" :disabled="playing">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10h8M8 14h5M21 12c0 4.4-4 8-9 8-1.1 0-2.2-.2-3.2-.5L4 21l1.4-3.8C4.2 15.8 3 14 3 12c0-4.4 4-8 9-8s9 3.6 9 8z" /></svg>
                <span><b>Scam Survivor</b><small>Choose your path</small></span>
            </button>
                    <button type="button" class="pl-sidebtn" x-show="!playing" :aria-pressed="side" @click="toggleSide()" :title="side ? 'Hide stats and tips' : 'Show stats and tips'" :aria-label="side ? 'Hide stats and tips' : 'Show stats and tips'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.500"/><path d="M15 4v16"/><path d="M18 8h.01M18 12h.01"/></svg>
            </button>
        </div>

        {{-- 1. SCAM OR SAFE? QUIZ --}}
        <section x-show="tab === 'quiz'" x-cloak x-data="quizGame(messages)" x-effect="$dispatch('pl-playing', { game: 'quiz', on: counting || (started && !done), hold: counting || started })">
            <template x-if="counting">
                <div class="pl-card pl-in pl-count" @click="skip()">
                    <template x-for="n in [count]" :key="n"><b class="pl-count-n" :class="n === 0 ? 'go' : ''" x-text="n === 0 ? 'GO!' : n"></b></template>
                    <p>Scam or safe? <span x-text="size"></span> messages coming up.</p>
                    <small>Tap to skip</small>
                    <button type="button" class="pl-btn pl-ghost pl-quit" @click.stop="quit()">Quit</button>
                </div>
            </template>

            <template x-if="!started && !counting">
                <div class="pl-in">
                    <div class="pl-pick-head">
                        <p class="pl-title">Scam or Safe?</p>
                        <p class="pl-sub">Spot the scam, or call it safe.</p>
                    </div>
                    <div class="pl-guide" x-data="plGuide('quiz')">
                        <button type="button" class="pl-guide-phil" @click="nextTip()" aria-label="Phil the Phish. Tap for another tip." title="Tap Phil for another tip"><svg viewBox="0 0 17 9" aria-hidden="true"><use href="#pl-fish-px"/></svg></button>
                        <div class="pl-guide-body">
                            <p class="pl-guide-say" x-show="!open" x-effect="tip; $el.classList.remove('swap'); void $el.offsetWidth; $el.classList.add('swap')" x-text="tip"></p>
                            <div class="pl-guide-step" x-show="open" style="display: none;">
                                <small x-text="'How to play  |  ' + (step + 1) + ' of ' + steps.length"></small>
                                <p x-text="steps[step]"></p>
                            </div>
                            <div class="pl-guide-row">
                                <button type="button" class="pl-guide-btn go" x-show="!open" @click="start()">How to play</button>
                                <button type="button" class="pl-guide-btn" x-show="open" style="display: none;" @click="back()" :disabled="step === 0">Back</button>
                                <button type="button" class="pl-guide-btn go" x-show="open" style="display: none;" @click="next()" x-text="step === steps.length - 1 ? 'Got it!' : 'Next'"></button>
                                <button type="button" class="pl-guide-btn" x-show="open" style="display: none;" @click="open = false">Close</button>
                                <span class="pl-guide-dots" x-show="open" style="display: none;" aria-hidden="true"><template x-for="n in steps.length" :key="n"><i :class="n - 1 === step ? 'on' : ''"></i></template></span>
                            </div>
                        </div>
                    </div>
                <div class="pl-card pl-rush pl-quizc">
                  <div class="pl-intro">
                    <div class="pl-intro-main">
                    <span class="pl-rush-eyebrow"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.500 6v5.500c0 4.500 3.100 8 7.500 9.500 4.400-1.500 7.500-5 7.500-9.500V6zM8.800 12l2.300 2.300 4.300-4.600"/></svg> Quick quiz</span>
                    <h3 class="pl-rush-title">Can you spot the scam?</h3>
                    <p class="pl-sub">Messages, calls and websites flash by. Decide if each one is a scam or safe, then learn why. Every correct answer earns XP.</p>
                    <div class="pl-rush-tiles" role="group" aria-label="Number of questions">
                        <template x-for="opt in lengths" :key="opt.n">
                            <button type="button" class="pl-rush-tile pick" :aria-pressed="size === opt.n" @click="size = opt.n"><strong x-text="opt.n"></strong><span>questions</span><small x-text="opt.label"></small></button>
                        </template>
                    </div>
                    <div class="pl-rush-go">
                        <button type="button" class="pl-btn pl-main pl-start pl-rush-start" @click="start()"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.500 6v5.500c0 4.500 3.100 8 7.500 9.500 4.400-1.500 7.500-5 7.500-9.500V6zM8.800 12l2.300 2.300 4.300-4.600"/></svg> Start quiz</button>
                        <span class="pl-rush-best" x-show="best > 0" x-text="'Best streak: ' + best"></span>
                    </div>
                    </div>
                    <div class="pl-demo" aria-hidden="true">
                        <p class="pl-demo-tag">Sample question</p>
                        <div class="pl-msg">
                            <p class="pl-from">SMS &middot; BIBD-Alert</p>
                            <p class="pl-text">Your account will be blocked today. Verify now: bibd-secure-login.com/verify</p>
                        </div>
                        <div class="pl-actions"><span class="pl-btn pl-scam">Scam!</span><span class="pl-btn pl-safe">Safe</span></div>
                    </div>
                  </div>
                </div>
                </div>
            </template>

            <template x-if="started && !done">
                <div>
                    <div class="pl-rp-hud">
                        <button type="button" class="pl-btn pl-ghost pl-quit" @click="quit()">Quit</button>
                        <div class="pl-rp-mid">
                            <span class="pl-rp-score"><small>Question</small><b x-text="(i + 1) + ' / ' + round.length"></b></span>
                            <span class="pl-chipbox" :class="'tier' + plTier(streak)" x-show="streak > 1" x-text="'Streak x' + streak"></span>
                        </div>
                        <span class="pl-rp-score right pl-gainhost"><small>Score</small><b :class="pop ? 'pl-bump' : ''" x-text="score"></b><template x-for="g in gains" :key="g.id"><em class="pl-gain" x-text="g.txt"></em></template></span>
                    </div>
                    <div class="pl-combo-wrap" aria-live="polite">
                        <template x-if="combo"><div class="pl-combo" :class="'t' + combo.tier"><span x-text="combo.text"></span><template x-for="k in 8" :key="k"><i class="sp" :style="'--x:' + Math.round(Math.cos(k * 0.785) * 70) + 'px;--y:' + Math.round(Math.sin(k * 0.785) * 38) + 'px'"></i></template></div></template>
                    </div>
                    <div class="pl-progress pl-qz-progress"><i :style="'width:' + (i / round.length * 100) + '%'"></i></div>
                    <template x-for="n in [i]" :key="n">
                        <div class="pl-card pl-slide pl-split pl-rp pl-qz" :class="fx">
                            <div class="pl-msg">
                                <p class="pl-from"><span x-text="q.kind"></span> &middot; <span x-text="q.from"></span></p>
                                <p class="pl-text" x-text="q.text"></p>
                                <p class="pl-ctx" x-show="q.context" x-text="q.context"></p>
                            </div>
                            <div class="pl-side">
                                <div class="pl-actions" x-show="picked === null">
                                    <button type="button" class="pl-btn pl-scam" @click="answer(true)">Scam!</button>
                                    <button type="button" class="pl-btn pl-safe" @click="answer(false)">Safe</button>
                                </div>
                                <div class="pl-result" :class="correct ? 'ok' : 'bad'" x-show="picked !== null">
                                    <p class="pl-res-title"><span x-text="correct ? 'Correct!' : 'Not quite'"></span><em x-show="correct">+10 XP</em></p>
                                    <p><b x-text="q.scam ? 'This is a scam. ' : 'This one is safe. '"></b><span x-text="q.why"></span></p>
                                    <button type="button" class="pl-btn pl-main" @click="next()" x-text="i >= round.length - 1 ? 'See my score' : 'Next'"></button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="done">
                <div class="pl-card pl-in pl-center pl-result-card">
                    <div class="pl-confetti" x-show="score / round.length >= 0.8"><template x-for="k in 18" :key="k"><i :style="'--x:' + (k * 5.3 % 100) + '%;--d:' + (k % 6 * .12) + 's;--c:' + ['#fbbf24', '#38bdf8', '#34d399', '#f87171', '#a78bfa'][k % 5]"></i></template></div>
                    <div class="pl-phil" x-data="{ phil: plPhil('quiz', score, round.length) }" :class="phil.mood">
                        <span class="pl-phil-fish" aria-hidden="true"><svg viewBox="0 0 17 9"><use href="#pl-fish-px"/></svg></span>
                        <p class="pl-phil-say" x-text="phil.line"></p>
                    </div>
                    <div class="pl-stars" role="img" :aria-label="stars + ' out of 3 stars'"><template x-for="k in 3" :key="k"><svg class="pl-star" :class="k <= stars ? 'on' : ''" :style="'--i:' + k" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.500l2.900 6 6.600.9-4.800 4.600 1.200 6.500L12 17.400l-5.900 3.100 1.200-6.500L2.500 9.400l6.600-.9z"/></svg></template></div>
                    <p class="pl-sub" style="margin-top: 0">Your score</p>
                    <p class="pl-big" x-data="plCounter(score)" x-text="n + ' / ' + round.length"></p>
                    <p class="pl-newbest" x-show="newBest">New best streak!</p>
                    <p class="pl-title" style="margin-top: .7rem" x-text="verdict.title"></p>
                    <p class="pl-sub pl-vtext" x-text="verdict.text"></p>
                    <p class="pl-best" x-text="'Best streak this round: ' + bestStreak + (best > 0 ? '   |   Record: ' + best : '')"></p>
                    <div class="pl-end-row">
                        <button type="button" class="pl-btn pl-main pl-start" @click="start()">Play again</button>
                        <button type="button" class="pl-btn pl-ghost" @click="quit()">Back to menu</button>
                    </div>
                    <details class="pl-review" x-show="missed.length > 0" :open="innerWidth >= 1024">
                        <summary x-text="'Review ' + missed.length + (missed.length === 1 ? ' missed message' : ' missed messages')"></summary>
                        <ul>
                            <template x-for="m in missed" :key="m.text">
                                <li><b x-text="m.text"></b><span x-text="m.why"></span></li>
                            </template>
                        </ul>
                        <p class="pl-tips">Remember: slow down, never share codes or passwords, and check by calling the real number or opening the real app yourself.</p>
                    </details>
                </div>
            </template>
        </section>

        {{-- 2. INBOX RUSH --}}
        <section x-show="tab === 'rush'" x-cloak x-data="rushGame(messages)" x-effect="$dispatch('pl-playing', { game: 'rush', on: state === 'play' || state === 'count', hold: state !== 'idle' })" @keydown.window="tab === 'rush' && key($event)">
            <template x-if="state === 'idle'">
                <div class="pl-in">
                    <div class="pl-pick-head">
                        <p class="pl-title">Inbox Rush</p>
                        <p class="pl-sub">Beat the clock and sort the inbox.</p>
                    </div>
                    <div class="pl-guide" x-data="plGuide('rush')">
                        <button type="button" class="pl-guide-phil" @click="nextTip()" aria-label="Phil the Phish. Tap for another tip." title="Tap Phil for another tip"><svg viewBox="0 0 17 9" aria-hidden="true"><use href="#pl-fish-px"/></svg></button>
                        <div class="pl-guide-body">
                            <p class="pl-guide-say" x-show="!open" x-effect="tip; $el.classList.remove('swap'); void $el.offsetWidth; $el.classList.add('swap')" x-text="tip"></p>
                            <div class="pl-guide-step" x-show="open" style="display: none;">
                                <small x-text="'How to play  |  ' + (step + 1) + ' of ' + steps.length"></small>
                                <p x-text="steps[step]"></p>
                            </div>
                            <div class="pl-guide-row">
                                <button type="button" class="pl-guide-btn go" x-show="!open" @click="start()">How to play</button>
                                <button type="button" class="pl-guide-btn" x-show="open" style="display: none;" @click="back()" :disabled="step === 0">Back</button>
                                <button type="button" class="pl-guide-btn go" x-show="open" style="display: none;" @click="next()" x-text="step === steps.length - 1 ? 'Got it!' : 'Next'"></button>
                                <button type="button" class="pl-guide-btn" x-show="open" style="display: none;" @click="open = false">Close</button>
                                <span class="pl-guide-dots" x-show="open" style="display: none;" aria-hidden="true"><template x-for="n in steps.length" :key="n"><i :class="n - 1 === step ? 'on' : ''"></i></template></span>
                            </div>
                        </div>
                    </div>
                <div class="pl-card pl-rush">
                  <div class="pl-intro">
                    <div class="pl-intro-main">
                    <span class="pl-rush-eyebrow"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M13.500 2 4 13.500h6.200L9 22l10-12.200h-6.400z"/></svg> Fast round</span>
                    <h3 class="pl-rush-title">How fast can you sort the inbox?</h3>
                    <p class="pl-sub">Messages arrive one at a time. Block the scams and keep the safe ones before the timer runs out.</p>
                    <div class="pl-rush-tiles" role="group" aria-label="Choose a mode">
                        <button type="button" class="pl-rush-tile mode" :aria-pressed="mode === 'normal'" @click="mode = 'normal'"><span class="pl-rush-ic hearts"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.500 1 5.400 3 1.900-2 3.400-3 5.400-3 3.600 0 5.700 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.500 1 5.400 3 1.900-2 3.400-3 5.400-3 3.600 0 5.700 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.500 1 5.400 3 1.900-2 3.400-3 5.400-3 3.600 0 5.700 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg></span><strong>Normal</strong><small>3 lives</small></button>
                        <button type="button" class="pl-rush-tile mode" :aria-pressed="mode === 'hard'" @click="mode = 'hard'"><span class="pl-rush-ic flame"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 2c.8 3.600-3.200 5.200-3.200 9.200a3.200 3.200 0 0 0 6.400 0c0-1-.4-1.900-.9-2.700 2.900 1.800 4.700 4.300 4.700 7.300A7 7 0 0 1 5 15.500C5 9.500 11 7.500 12 2z"/></svg></span><strong>Hard</strong><small>Double points</small></button>
                        <button type="button" class="pl-rush-tile mode" :aria-pressed="mode === 'daily'" @click="mode = 'daily'"><span class="pl-rush-ic cal"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 3v4M17 3v4M3 10h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="15" r="1.600" fill="currentColor"/></svg></span><strong>Daily</strong><small>Same for everyone</small></button>
                    </div>
                    <p class="pl-rush-note" x-text="cfg.note"></p>
                    <div class="pl-rush-go">
                        <button type="button" class="pl-btn pl-main pl-start pl-rush-start" @click="start()"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M13.500 2 4 13.500h6.200L9 22l10-12.200h-6.400z"/></svg> <span x-text="mode === 'daily' ? 'Start daily' : 'Start rush'"></span></button>
                        <span class="pl-rush-best" x-show="best > 0" x-text="(mode === 'daily' ? 'Best today: ' : 'Best: ') + best + ' pts'"></span>
                    </div>
                    </div>
                    <div class="pl-demo" aria-hidden="true">
                        <div class="pl-rush-hud"><span class="pl-demo-tag">Sample round</span><span class="pl-rush-lives"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.5 1 5.4 3 1.9-2 3.4-3 5.4-3 3.6 0 5.7 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.5 1 5.4 3 1.9-2 3.4-3 5.4-3 3.6 0 5.7 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.5 1 5.4 3 1.9-2 3.4-3 5.4-3 3.6 0 5.7 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg></span></div>
                        
                        <div class="pl-timer"><i></i></div>
                        <div class="pl-msg">
                            <p class="pl-from">SMS &middot; BruneiPost</p>
                            <p class="pl-text">Your parcel is held. Pay B$1.80 to release it: bruneipost-fee.com</p>
                        </div>
                        <div class="pl-actions"><span class="pl-btn pl-scam">Block</span><span class="pl-btn pl-safe">Keep</span></div>
                    </div>
                  </div>
                </div>
                </div>
            </template>

            <template x-if="state === 'count'">
                <div class="pl-card pl-in pl-count" @click="skip()">
                    <template x-for="n in [count]" :key="n"><b class="pl-count-n" :class="n === 0 ? 'go' : ''" x-text="n === 0 ? 'GO!' : n"></b></template>
                    <p><span x-text="cfg.label"></span> &middot; <span x-text="cfg.lives"></span> lives. Block the scams, keep the safe ones.</p>
                    <small>Tap to skip</small>
                    <button type="button" class="pl-btn pl-ghost pl-quit" @click.stop="quit()">Quit</button>
                </div>
            </template>

            <template x-if="state === 'play'">
                <div>
                    <div class="pl-rp-hud">
                        <button type="button" class="pl-btn pl-ghost pl-quit" @click="quit()">Quit</button>
                        <div class="pl-rp-mid">
                            <span class="pl-rp-score pl-gainhost"><small>Score</small><b :class="pop ? 'pl-bump' : ''" x-text="score"></b><template x-for="g in gains" :key="g.id"><em class="pl-gain" x-text="g.txt"></em></template></span>
                            <span class="pl-chipbox" :class="'tier' + plTier(streak)" x-show="streak > 1" x-text="'Streak x' + streak"></span>
                        </div>
                        <span class="pl-hearts" role="img" :aria-label="lives + ' lives left'">
                            <template x-for="l in cfg.lives" :key="l"><svg class="pl-heart" :class="l > lives ? 'off' : ''" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3 4.5 6.6 4.5c2 0 3.500 1 5.400 3 1.900-2 3.400-3 5.400-3 3.600 0 5.700 3.700 4.200 7.200C19.500 16.400 12 21 12 21z"/></svg></template>
                        </span>
                    </div>
                    <div class="pl-combo-wrap" aria-live="polite">
                        <template x-if="combo"><div class="pl-combo" :class="'t' + combo.tier"><span x-text="combo.text"></span><template x-for="k in 8" :key="k"><i class="sp" :style="'--x:' + Math.round(Math.cos(k * 0.785) * 70) + 'px;--y:' + Math.round(Math.sin(k * 0.785) * 38) + 'px'"></i></template></div></template>
                    </div>
                    <template x-for="n in [turn]" :key="n">
                        <div>
                            <div class="pl-timer pl-rp-timer" :class="locked ? 'paused' : ''"><i :style="'--t:' + seconds + 's; --d:' + elapsed + 's'" @animationend="timeout()"></i></div>
                            <div class="pl-card pl-slide pl-split pl-rp" :class="fx">
                                <div class="pl-msg">
                                    <p class="pl-from"><span x-text="cur.kind"></span> &middot; <span x-text="cur.from"></span></p>
                                    <p class="pl-text" x-text="cur.text"></p>
                                    <p class="pl-ctx" x-show="cur.context" x-text="cur.context"></p>
                                </div>
                                <div class="pl-side">
                                    <div class="pl-actions">
                                        <button type="button" class="pl-btn pl-scam" :disabled="locked" @click="answer(true)"><kbd class="pl-rp-kbd" aria-hidden="true">&larr;</kbd> Block <small>(scam)</small></button>
                                        <button type="button" class="pl-btn pl-safe" :disabled="locked" @click="answer(false)">Keep <small>(safe)</small> <kbd class="pl-rp-kbd" aria-hidden="true">&rarr;</kbd></button>
                                    </div>
                                </div>
                                <div class="pl-flash" :class="flash && flash.ok ? 'ok' : 'bad'" x-show="flash" x-text="flash && flash.text"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="state === 'over'">
                <div class="pl-card pl-in pl-center pl-result-card">
                    <div class="pl-confetti" x-show="newBest"><template x-for="k in 18" :key="k"><i :style="'--x:' + (k * 5.3 % 100) + '%;--d:' + (k % 6 * .12) + 's;--c:' + ['#fbbf24', '#38bdf8', '#34d399', '#f87171', '#a78bfa'][k % 5]"></i></template></div>
                    <div class="pl-phil" x-data="{ phil: plPhil('rush', Math.round(score / cfg.mult), newBest) }" :class="phil.mood">
                        <span class="pl-phil-fish" aria-hidden="true"><svg viewBox="0 0 17 9"><use href="#pl-fish-px"/></svg></span>
                        <p class="pl-phil-say" x-text="phil.line"></p>
                    </div>
                    <div class="pl-stars" role="img" :aria-label="stars + ' out of 3 stars'"><template x-for="k in 3" :key="k"><svg class="pl-star" :class="k <= stars ? 'on' : ''" :style="'--i:' + k" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.500l2.900 6 6.600.9-4.800 4.600 1.200 6.500L12 17.400l-5.900 3.100 1.200-6.500L2.500 9.400l6.600-.9z"/></svg></template></div>
                    <p class="pl-sub" style="margin-top: 0" x-text="'Game over  |  ' + cfg.label"></p>
                    <p class="pl-big" x-data="plCounter(score)" x-text="n"></p>
                    <p class="pl-newbest" x-show="newBest">New best score!</p>
                    <p class="pl-sub" x-text="answered + ' messages sorted'"></p>
                    <p class="pl-best" x-show="best > 0" x-text="'Your best: ' + best + ' points'"></p>
                    <div class="pl-end-row">
                        <button type="button" class="pl-btn pl-main pl-start" @click="start()">Play again</button>
                        <button type="button" class="pl-btn pl-ghost" @click="quit()">Back to menu</button>
                    </div>
                    <details class="pl-review" x-show="missed.length > 0" :open="innerWidth >= 1024">
                        <summary x-text="'See what tripped you up (' + Math.min(missed.length, 4) + ')'"></summary>
                        <ul>
                            <template x-for="m in missed.slice(0, 4)" :key="m.text">
                                <li><b x-text="m.text"></b><span x-text="(m.scam ? 'Scam. ' : 'Safe. ') + m.why"></span></li>
                            </template>
                        </ul>
                    </details>
                </div>
            </template>
        </section>

        {{-- 3. SCAM SURVIVOR --}}
        <section x-show="tab === 'story'" x-cloak x-data="survivorGame(stories)" x-effect="$dispatch('pl-playing', { game: 'story', on: view === 'play', hold: view !== 'pick' })">
            <template x-if="view === 'pick'">
                <div class="pl-in">
                    <div class="pl-pick-head">
                        <p class="pl-title">Scam Survivor</p>
                        <p class="pl-sub">Pick a story, then chat your way out.</p>
                    </div>
                    <div class="pl-guide" x-data="plGuide('story')">
                        <button type="button" class="pl-guide-phil" @click="nextTip()" aria-label="Phil the Phish. Tap for another tip." title="Tap Phil for another tip"><svg viewBox="0 0 17 9" aria-hidden="true"><use href="#pl-fish-px"/></svg></button>
                        <div class="pl-guide-body">
                            <p class="pl-guide-say" x-show="!open" x-effect="tip; $el.classList.remove('swap'); void $el.offsetWidth; $el.classList.add('swap')" x-text="tip"></p>
                            <div class="pl-guide-step" x-show="open" style="display: none;">
                                <small x-text="'How to play  |  ' + (step + 1) + ' of ' + steps.length"></small>
                                <p x-text="steps[step]"></p>
                            </div>
                            <div class="pl-guide-row">
                                <button type="button" class="pl-guide-btn go" x-show="!open" @click="start()">How to play</button>
                                <button type="button" class="pl-guide-btn" x-show="open" style="display: none;" @click="back()" :disabled="step === 0">Back</button>
                                <button type="button" class="pl-guide-btn go" x-show="open" style="display: none;" @click="next()" x-text="step === steps.length - 1 ? 'Got it!' : 'Next'"></button>
                                <button type="button" class="pl-guide-btn" x-show="open" style="display: none;" @click="open = false">Close</button>
                                <span class="pl-guide-dots" x-show="open" style="display: none;" aria-hidden="true"><template x-for="n in steps.length" :key="n"><i :class="n - 1 === step ? 'on' : ''"></i></template></span>
                            </div>
                        </div>
                    </div>
                    <div class="pl-feature" x-ref="feat" :style="'--ac:' + ac(cur.tag)">
                        <div class="pl-feat-in" x-effect="sel; $el.classList.remove('swap'); void $el.offsetWidth; $el.classList.add('swap')">
                            <div class="pl-ic-big"><svg class="pl-ic" viewBox="0 0 48 48" aria-hidden="true"><use :href="'#pl-ic-' + cur.id"></use></svg></div>
                            <div class="pl-feat-body">
                                <span class="pl-rush-eyebrow pl-feat-eye"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H11l-5 4v-4H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg> Featured story</span>
                                <p class="pl-feat-name" x-text="cur.title"></p>
                                <p class="pl-feat-meta">
                                    <span class="pl-tag" x-text="cur.tag"></span>
                                    <span class="pl-dots" :aria-label="'Difficulty ' + cur.hard + ' of 3'" x-text="'●'.repeat(cur.hard) + '○'.repeat(3 - cur.hard)"></span>
                                    <em class="pl-badge" style="font-style: normal" :class="done[cur.id]" x-show="done[cur.id]" x-text="done[cur.id] === 'win' ? 'Survived' : (done[cur.id] === 'meh' ? 'Close call' : 'Scammed')"></em>
                                </p>
                                <p class="pl-feat-blurb" x-text="cur.blurb"></p>
                            </div>
                            <div class="pl-feat-chat" aria-hidden="true">
                                <small x-text="cur.who"></small>
                                <div class="pl-b them" x-text="line(cur)"></div>
                                <div class="pl-typing"><i></i><i></i><i></i></div>
                            </div>
                            <div class="pl-feat-actions">
                                <button type="button" class="pl-btn pl-main pl-rush-start" @click="begin(cur, { currentTarget: $refs.feat })"><svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H11l-5 4v-4H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg> Start story</button>
                                <button type="button" class="pl-btn pl-ghost" @click="random($event)">Surprise me</button>
                            </div>
                        </div>
                    </div>
                    <p class="pl-tiles-h" x-show="fresh.length" x-text="'Stories to try (' + fresh.length + ')'"></p>
                    <div class="pl-tiles">
                        <template x-for="(s, si) in fresh" :key="s.id">
                            <button type="button" class="pl-tile" :class="sel === s.id ? 'on' : ''" :style="'--ac:' + ac(s.tag) + '; --i:' + si" @click="sel = s.id">
                                <span class="ic"><svg class="pl-ic" viewBox="0 0 48 48" aria-hidden="true"><use :href="'#pl-ic-' + s.id"></use></svg></span>
                                <span class="tx"><b x-text="s.title"></b><small x-text="s.tag"></small></span>
                            </button>
                        </template>
                    </div>
                    <p class="pl-all-done" x-show="!fresh.length">You have tried every story. Replay any of them below.</p>
                    <button type="button" class="pl-played-btn" x-show="played.length" @click="showPlayed = !showPlayed" :aria-expanded="showPlayed">
                        <span x-text="'Played (' + played.length + ')'"></span><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" :style="showPlayed ? 'transform: rotate(180deg)' : ''"><path d="M5.5 7.5 10 12l4.500-4.500"/></svg>
                    </button>
                    <div class="pl-tiles pl-tiles-played" x-show="showPlayed && played.length" x-transition.opacity.duration.200ms>
                        <template x-for="(s, si) in played" :key="s.id">
                            <button type="button" class="pl-tile" :class="sel === s.id ? 'on' : ''" :style="'--ac:' + ac(s.tag) + '; --i:' + si" @click="sel = s.id">
                                <span class="ic"><svg class="pl-ic" viewBox="0 0 48 48" aria-hidden="true"><use :href="'#pl-ic-' + s.id"></use></svg></span>
                                <span class="tx"><b x-text="s.title"></b><small x-text="s.tag"></small></span>
                                <i class="st" :class="done[s.id]" :title="done[s.id] === 'win' ? 'Survived' : (done[s.id] === 'meh' ? 'Close call' : 'Scammed')"></i>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="view === 'play'">
                <div class="pl-sv" :data-ch="chan(story.tag)" :style="'--ac:' + ac(story.tag)">
                <div class="pl-rp-hud pl-sv-hud">
                    <button type="button" class="pl-btn pl-ghost pl-quit" @click="leave()">Quit</button>
                    <div class="pl-sv-mid">
                        <span class="pl-sv-ic"><svg class="pl-ic" viewBox="0 0 48 48" aria-hidden="true"><use :href="'#pl-ic-' + story.id"></use></svg></span>
                        <span class="pl-sv-name"><b x-text="story.title"></b><span class="pl-dots" :aria-label="'Difficulty ' + story.hard + ' of 3'" x-text="'●'.repeat(story.hard) + '○'.repeat(3 - story.hard)"></span></span>
                    </div>
                    <span class="pl-tag" x-text="story.tag"></span>
                </div>
                <div class="pl-stage pl-sv-stage">
                    <div class="pl-3d">
                    <div class="pl-hold">
                        
                    <div class="pl-device" x-ref="device">
                        <i class="pl-btnside a" aria-hidden="true"></i><i class="pl-btnside v1" aria-hidden="true"></i><i class="pl-btnside v2" aria-hidden="true"></i><i class="pl-btnside p" aria-hidden="true"></i>
                        <div class="pl-screen">
                            <span class="pl-notch" aria-hidden="true"></span>
                            <div class="pl-status" aria-hidden="true">
                                <span class="time" x-text="clock.replace(/\s?[AP]M$/i, '')"></span>
                                <span class="icons">
                                    <svg viewBox="0 0 18 12" fill="currentColor"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="5" y="5.5" width="3" height="6.5" rx="1"/><rect x="10" y="3" width="3" height="9" rx="1"/><rect x="15" y="0" width="3" height="12" rx="1"/></svg>
                                    <svg viewBox="0 0 17 12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.9"><path d="M1.500 4.300a10 10 0 0 1 14 0"/><path d="M4 7a6.500 6.500 0 0 1 9 0"/><circle cx="8.500" cy="10" r="1" fill="currentColor" stroke="none"/></svg>
                                    <svg viewBox="0 0 27 12" fill="none"><rect x=".5" y=".5" width="22" height="11" rx="3.200" stroke="currentColor" opacity=".45"/><rect x="2" y="2" width="16" height="8" rx="1.800" fill="currentColor"/><path d="M24.200 4v4c.8-.3 1.400-1.100 1.400-2s-.6-1.700-1.400-2Z" fill="currentColor" opacity=".5"/></svg>
                                </span>
                            </div>
                            <div class="pl-phone-head">
                                <span class="av" x-text="story.who.charAt(0)"></span>
                                <p><span x-text="story.who"></span><small x-text="story.tag"></small></p>
                                <button type="button" class="pl-btn pl-ghost" @click="leave()">Quit</button>
                            </div>
                            <div class="pl-chat" x-ref="chat" x-init="watchChat($el)">
                                <div class="pl-date" x-text="'Today ' + clock.replace(/\s?[AP]M$/i, '')"></div>
                                <template x-for="(m, mi) in log" :key="mi">
                                    <div class="pl-b" :class="m.who === 'tip' ? 'tip ' + m.kind : m.who">
                                        <span x-text="(m.who === 'tip' ? (m.kind === 'flag' ? 'Red flag: ' : 'Good move: ') : '') + m.text"></span>
                                    </div>
                                </template>
                                <div class="pl-typing" x-show="typing" aria-label="Typing"><i></i><i></i><i></i></div>
                            </div>
                            <div class="pl-choices">
                                <template x-for="(c, ci) in choices" :key="c.text">
                                    <button type="button" class="pl-choice" :style="'animation-delay:' + (ci * 0.07) + 's'" @click="pick(c)" x-text="c.text"></button>
                                </template>
                            </div>
                            <div class="pl-compose" aria-hidden="true"><span class="pl-compose-in">Choose a reply</span><span class="pl-compose-send"><svg viewBox="0 0 24 24" width="1em" height="1em"><path fill="none" stroke="currentColor" stroke-width="2.600" stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M6 11l6-6 6 6"/></svg></span></div>
                            <span class="pl-homebar" aria-hidden="true"></span>
                        </div>
                    </div>
                        
                    </div>
                    </div>
                    <div class="pl-rside">
                    <div class="pl-reply" :class="choices.length ? '' : 'idle'">
                      <div class="pl-reply-in" x-effect="choices; choices.length; $el.classList.remove('bump'); void $el.offsetWidth; $el.classList.add('bump')">
                        <p class="pl-reply-h">Your reply <span>tap a message to send</span></p>
                        <div class="pl-choices">
                                <template x-for="(c, ci) in choices" :key="c.text">
                                    <button type="button" class="pl-choice" :style="'animation-delay:' + (ci * 0.07) + 's'" @click="pick(c)" x-text="c.text"></button>
                                </template>
                            </div>
                      </div>
                    </div>
                                        </div>
                </div>
                </div>
            </template>

            <template x-if="view === 'end'">
                <div class="pl-card pl-in pl-center pl-result-card">
                    <div class="pl-confetti" x-show="ending.kind === 'win'"><template x-for="k in 18" :key="k"><i :style="'--x:' + (k * 5.3 % 100) + '%;--d:' + (k % 6 * .12) + 's;--c:' + ['#fbbf24', '#38bdf8', '#34d399', '#f87171', '#a78bfa'][k % 5]"></i></template></div>
                    <div class="pl-phil" x-data="{ phil: plPhil('story', ending.kind) }" :class="phil.mood">
                        <span class="pl-phil-fish" aria-hidden="true"><svg viewBox="0 0 17 9"><use href="#pl-fish-px"/></svg></span>
                        <p class="pl-phil-say" x-text="phil.line"></p>
                    </div>
                    <span class="pl-end-badge" :class="ending.kind" x-text="ending.kind === 'win' ? '✓' : (ending.kind === 'meh' ? '!' : '✕')"></span>
                    <p class="pl-title" x-text="ending.title"></p>
                    <p class="pl-sub" x-text="ending.text"></p>
                    <p class="pl-best" x-text="'+' + ({ win: 60, meh: 30, lose: 10 })[ending.kind] + ' XP'"></p>
                    <div class="pl-end-row">
                        <button type="button" class="pl-btn pl-main" @click="begin(story)">Try again</button>
                        <button type="button" class="pl-btn pl-ghost" @click="leave()">Back to stories</button>
                    </div>
                    <details class="pl-review" :open="innerWidth >= 1024">
                        <summary x-text="'Red flags in this story (' + story.flags.length + ')'"></summary>
                        <ul>
                            <template x-for="f in story.flags" :key="f"><li x-text="f"></li></template>
                        </ul>
                        <p class="pl-tips">If you are ever scammed: call your bank first, then the anti-scam helpline 16993, and report to the police on 993.</p>
                    </details>
                </div>
            </template>
        </section>

        </div>

        <aside class="pl-aside" x-cloak x-show="side" x-transition:enter="pl-t-ease" x-transition:enter-start="pl-t-off" x-transition:enter-end="pl-t-on" x-transition:leave="pl-t-ease" x-transition:leave-start="pl-t-on" x-transition:leave-end="pl-t-off" aria-label="Your progress and safety tips">
            <section class="pl-side-card">
                <h2 class="pl-side-h">Your stats</h2>
                <p class="pl-stat"><span>Best Inbox Rush</span><b x-text="stats.rush + ' pts'"></b></p>
                <p class="pl-stat"><span>Best quiz streak</span><b x-text="stats.streak"></b></p>
                <p class="pl-stat"><span>Stories survived</span><b x-text="stats.survived + ' / ' + stories.length"></b></p>
            </section>
            <section class="pl-side-card">
                <h2 class="pl-side-h">Badges <em x-text="badgeCount + ' / ' + badges.length"></em></h2>
                <div class="pl-bdgs">
                    <template x-for="b in badges" :key="b.id">
                        <button type="button" class="pl-bdg" :class="b.on ? 'on' : ''" @click="badgeInfo = b" :title="b.how" :aria-label="b.name + (b.on ? ', earned. ' : ', not earned yet. ') + b.how">
                            <i><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="b.icon"/></svg></i><span x-text="b.name"></span>
                        </button>
                    </template>
                </div>
                <p class="pl-bdg-how" x-text="badgeInfo ? badgeInfo.name + (badgeInfo.on ? ' (earned): ' : ': ') + badgeInfo.how : 'Tap a badge to see how to earn it.'"></p>
            </section>
            <section class="pl-side-card">
                <h2 class="pl-side-h">Safety tip of the day</h2>
                <p class="pl-tip" x-text="tip"></p>
            </section>
        </aside>
        </div>

        <div class="pl-toast" x-show="toast" x-text="toast" style="display: none;"></div>
        <div class="pl-toast badge" x-show="badgeToast" x-text="badgeToast" style="display: none;" role="status"></div>
        <div class="pl-wd-modal" x-show="wardOpen" style="display: none;" role="dialog" aria-modal="true" aria-label="Phil's wardrobe" @click.self="wardOpen = false" @keydown.escape.window="wardOpen = false">
            <div class="pl-wd-card">
                <div class="pl-wd-head">
                    <h2>Phil's wardrobe</h2>
                    <button type="button" class="pl-wd-x" @click="wardOpen = false" aria-label="Close wardrobe">&times;</button>
                </div>
                <div class="pl-wd-view" :data-water="phil.water">
                    <span class="pl-wave w1"></span><span class="pl-wave w2"></span>
                    <i class="pl-bub" style="--x: 10%; --s: 0.4rem; --d: 0s; --t: 6s; --dx: 0.3rem"></i>
                    <i class="pl-bub" style="--x: 24%; --s: 0.25rem; --d: 2.2s; --t: 5s; --dx: -0.2rem"></i>
                    <i class="pl-bub" style="--x: 78%; --s: 0.5rem; --d: 1.1s; --t: 7s; --dx: 0.35rem"></i>
                    <i class="pl-bub" style="--x: 90%; --s: 0.3rem; --d: 3.4s; --t: 5.5s; --dx: -0.25rem"></i>
                    <span class="pl-wd-sand"></span><span class="pl-wd-shadow"></span>
                    <svg class="pl-wd-phil" viewBox="0 -3 17 12" :style="'--pa:' + phil.a + ';--pb:' + phil.b + ';--pc:' + phil.c" aria-hidden="true"><use href="#pl-fish-px" width="17" height="9"/><use :href="'#pl-hat-' + phil.hat"/></svg>
                </div>
                <p class="pl-wd-cap"><span x-text="look"></span><small x-html="nextUnlock ? 'Next unlock: <b>' + nextUnlock.label + '</b> at level ' + nextUnlock.lv : 'Everything is unlocked!'"></small></p>
                <div class="pl-wd-tabs" role="tablist">
                    <template x-for="grp in [['hat', 'Hat'], ['color', 'Colour'], ['water', 'Water']]" :key="grp[0]">
                        <button type="button" class="pl-wd-tab" role="tab" :aria-selected="wardTab === grp[0]" @click="wardTab = grp[0]"><span x-text="grp[1]"></span><small x-text="unlocked(grp[0]) + '/' + wardrobeList[grp[0]].length"></small></button>
                    </template>
                </div>
                <div class="pl-wd-grid" role="tabpanel">
                    <template x-for="it in wardrobeList[wardTab]" :key="wardTab + it.id">
                        <button type="button" class="pl-wd-tile" :aria-pressed="wardrobe[wardTab] === it.id && lv.n >= it.lv" :disabled="lv.n < it.lv" @click="pick(wardTab, it)" :aria-label="it.name + (lv.n < it.lv ? ', locked, reach level ' + it.lv : '')">
                            <template x-if="wardTab === 'water'"><span class="pl-wd-sw" :data-water="it.id"></span></template>
                            <template x-if="wardTab !== 'water'"><span class="pl-wd-th"><svg viewBox="0 -3 17 12" :style="tileStyle(it)" aria-hidden="true"><use href="#pl-fish-px" width="17" height="9"/><use :href="'#pl-hat-' + (wardTab === 'hat' ? it.id : phil.hat)"/></svg></span></template>
                            <span x-text="it.name"></span>
                            <span class="pl-wd-lock" x-show="lv.n < it.lv" style="display: none;"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg><span x-text="'Lv ' + it.lv"></span></span>
                        </button>
                    </template>
                </div>
                <div class="pl-wd-foot">
                    <button type="button" class="pl-btn pl-ghost" @click="resetLook()">Reset</button>
                    <button type="button" class="pl-btn pl-main" @click="wardOpen = false">Done</button>
                </div>
            </div>
        </div>
        <div class="pl-lvlfx" x-show="lvlCard" style="display: none;" aria-live="polite">
            <div class="pl-confetti" aria-hidden="true"><template x-for="k in 56" :key="k"><i :style="'--x:' + (k * 1.8 % 100) + '%;--d:' + (k % 9 * .07) + 's;--c:' + ['#fbbf24', '#38bdf8', '#34d399', '#f87171', '#a78bfa', '#fde68a'][k % 6]"></i></template></div>
            <template x-if="lvlCard"><div class="pl-lvlcard">
                <span class="pl-lvlcard-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><use :href="'#pl-rush-' + lvlCard.rankNo"></use></svg></span>
                <small>LEVEL UP!</small>
                <b x-text="'Level ' + lvlCard.n"></b>
                <em x-text="'New rank: ' + lvlCard.rank"></em>
                <span class="unl" x-show="lvlCard.unlock" x-text="'Unlocked: ' + lvlCard.unlock + '. Open Phil\'s wardrobe to wear it.'"></span>
            </div></template>
        </div>
    </div>

    <script>
        window.PL_SCOPE = @js($scope);
        window.PL_SAVED = @js($saved);
        window.PL_PUSH_URL = @js(route('play.progress'));
        window.PL_TOKEN = @js(csrf_token());
        function plShuffle(list) {
            const a = list.slice();
            for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; }
            return a;
        }
        // What Phil says on a result card. The tier depends on how the player did, and a line is never repeated twice in a row.
        const PHIL_LINES = {
            quiz: {
                perfect: ['Perfect score! I am doing a happy flip.', 'Not one slip. You could teach my whole school.', 'Flawless! The scammers are crying into their keyboards.', 'Every single one right. Blub, I am impressed.', 'A perfect catch. Not a single bad bait got through.', 'That was spotless. Tell your family how you did it!', 'Full marks! You swim faster than any scam.', '100 percent. Even the shark is nervous.'],
                great: ['Great job! Only a tiny nibble got through.', 'So close to perfect. Check the ones you missed below.', 'Sharp eyes! Just a few fishy ones to look at again.', 'Strong score. You are getting hard to hook.', 'Nice swimming! A quick look at the misses and you are set.', 'You spot most scams already. Keep it up!', 'Almost spotless. The missed ones are a good lesson.', 'Blub, well done! One more round for full marks?'],
                ok: ['Not bad! Half the scams did not fool you.', 'A decent catch. Read the tips below, then go again.', 'You are learning. The next round will be better.', 'Some scams slipped past. That is how we learn.', 'Middle of the pond. Slow down and check the sender.', 'Good start! Look at what tripped you up below.', 'You are getting there. Hover before you click!', 'A few bites got you. Try again, I believe in you.'],
                low: ['Ouch, they hooked a few. Check the tips below.', 'Do not worry, even I got hooked once. Try again!', 'Tricky ones! Read why, then give it another go.', 'The scammers had a good day. You will win the next one.', 'Slow down and look at the sender first. Blub.', 'Everyone starts here. The tips below will help.', 'That is how scams work, they are made to fool people.', 'Read the reasons below. Next round will go better.'],
                zero: ['Zero? Okay, that is bold. Read the tips and try again.', 'Tough round! Slow down, check the sender and link.', 'Even the unluckiest fish gets better. Go again!', 'Blub. Fresh start. The tips below will help a lot.', 'Nowhere to go but up. I will cheer for you.'],
            },
            rush: {
                zero: ['No points yet. The clock is quick, try again!', 'Rough start! Read the messages below, then go again.', 'The rush is hard at first. Breathe, then go again.', 'Zero is just the start. Blub.', 'Do not panic when the timer runs. Look at the sender first.'],
                low: ['A slow start. Check what tripped you up below.', 'You are warming up. Try again, a bit calmer.', 'Fast is good, careful is better. Find your pace.', 'A few messages sorted. The next run will go further.', 'Look at the sender first, then the link, then decide.', 'Not bad for a first dive! Go again.', 'The clock is tough. Learn the traps and beat it.', 'You are getting the hang of it. One more run?'],
                mid: ['Nice run! Your eyes are getting quick.', 'Solid score. You are sorting like a pro.', 'A good dive! A few traps got you, see below.', 'You kept your cool. Can you beat this?', 'Fast and mostly right. That is the way.', 'Good speed! Slow down a touch for the tricky ones.', 'Decent haul. The scammers are getting nervous.', 'You are in the zone. One more run!'],
                high: ['What a run! Your brain is faster than any scam.', 'Lightning fast. Blub, I could not keep up.', 'Huge score! You sort scams like a champion.', 'Wow. The inbox never stood a chance.', 'Super sharp! You should train the others.', 'That was amazing. Do not let it go to your head.', 'A top score! The scammers will find someone easier.', 'Incredible dive! I need a moment to catch my breath.'],
                record: ['A new best! Doing a happy flip!', 'New record! You beat yourself. Blub!', 'Personal best! Look at you go.', 'You topped your own score. Keep climbing!', 'Record broken! Time to tell someone.', 'New high score! My fins are clapping.', 'You did it, a new best. Proud of you.', 'Best run yet! The next one will be even better.'],
            },
            story: {
                win: ['You stayed safe! I am so proud.', 'You spotted every trick. Great decisions!', 'A clean escape. The scammer got nothing.', 'You win! Tell your family how you did it.', 'Well played. You did not take the bait.', 'Smart and calm. That is how you beat a scam.', 'You saved your money. Blub, hero!', 'You checked first and stayed safe. Perfect.', 'Safe and sound. Try the other stories too!', 'The scammer gave up. That was all you.'],
                meh: ['You got out, but it was close. Read the flags below.', 'Not a disaster, but not perfect. Try for a clean win.', 'A bit of a wobble. See what you can do differently.', 'You escaped, barely. Look at the red flags.', 'Half safe! Try again and choose the careful options.', 'Close call! A little more care and you win.', 'You stopped in time. Next time stop sooner.', 'Phew. Try again and see what changes.'],
                lose: ['Oh no, the scammer got you. That is okay, it is practice!', 'It is only a game. Now you know the tricks.', 'Do not feel bad. Real scams fool smart people too.', 'Ouch. Read the red flags and try another path.', 'That one hurt. Next time, stop and check first.', 'Blub. I got hooked once too. Try again!', 'Better to fail here than in real life. Try again.', 'The trick worked this time. Read why below.', 'Chin up! Choose a different path and see.', 'Scammers are good at this. Now you are better.'],
            },
        };
        const plPhilLast = {};
        function plPhil(game, a, b) {
            let tier, mood = '';
            if (game === 'quiz') {
                const r = a / b;
                tier = a === 0 ? 'zero' : (r === 1 ? 'perfect' : (r >= .8 ? 'great' : (r >= .5 ? 'ok' : 'low')));
                mood = r >= .8 ? 'cheer' : (r < .5 ? 'sad' : '');
            } else if (game === 'rush') {
                tier = a === 0 ? 'zero' : (a < 25 ? 'low' : (a < 70 ? 'mid' : 'high'));
                if (b && a > 0 && Math.random() < .5) tier = 'record';
                mood = tier === 'record' || tier === 'high' ? 'cheer' : (tier === 'zero' || tier === 'low' ? 'sad' : '');
            } else {
                tier = a;
                mood = a === 'win' ? 'cheer' : (a === 'lose' ? 'sad' : '');
            }
            const pool = PHIL_LINES[game][tier];
            const key = game + tier;
            let line = pool[Math.floor(Math.random() * pool.length)];
            if (pool.length > 1 && line === plPhilLast[key]) line = pool[(pool.indexOf(line) + 1) % pool.length];
            plPhilLast[key] = line;
            return { line, mood };
        }
        // Phil's how-to-play help on each start screen: a rotating tip, and a short step-by-step guide.
        const PHIL_GUIDE = {
            quiz: {
                tips: ['Hi! I am Phil. I will help you get started.', 'Not every message is a scam. Safe ones are mixed in, so do not tap Scam every time.', 'Check who sent it first, then look at the link.', 'A threat or a rush is a big clue. Real companies give you time.', 'After every answer I explain why. That is where the learning is.', 'Each correct answer earns XP, and your best streak is saved.', 'Not sure? Ask yourself: did I ask for this message?', 'Try the 20 question Marathon when you feel ready.', 'Tap the fish swimming in the header at the top. I tell safety jokes!'],
                steps: ['Press Start and a message, call, email or website will show up. It could be real or a scam.', 'Read it carefully. Look at who sent it, what it asks you to do, and whether it rushes you.', 'Tap Scam! if you think it is a trick, or Safe if you think it is genuine. Careful, many are safe!', 'I will tell you why you were right or wrong. Read it, that is how you get better at spotting scams.', 'Each correct answer gives XP and builds your streak. Choose 5, 10 or 20 questions before you start.'],
            },
            rush: {
                tips: ['Hi! I am Phil. I will help you get started.', 'On Normal you have 3 lives. A wrong answer or running out of time costs one.', 'Eight correct in a row wins a life back. Keep your streak!', 'Hard has 2 lives and a shorter timer, but double points.', 'The Daily challenge has the same messages for everyone today. Compare scores with friends!', 'On a computer, press the left arrow to block and the right arrow to keep.', 'On Normal the timer starts at 8 seconds and gets shorter as you go.', 'A streak of correct answers is worth more points each time.', 'Do not rush too much. Wrong answers cost lives.', 'Look at the sender first, it is the quickest clue.', 'When the game ends, I show you which ones tripped you up.', 'Tap the fish swimming in the header at the top. I tell safety jokes!'],
                steps: ['Pick Normal, Hard or Daily, then press Start. Messages arrive one at a time, and a timer at the top shrinks.', 'Block the scams and keep the safe messages before the timer runs out.', 'On a computer you can press the left arrow key to block and the right arrow key to keep.', 'Normal has 3 lives, Hard has 2. A wrong answer or a timeout costs one life, and the timer gets shorter as you go.', 'Correct answers in a row score more points, and 8 in a row wins a life back. Hard doubles your points, and the Daily challenge gives everyone the same messages today.', 'When the lives run out, I show what tripped you up.'],
            },
            story: {
                tips: ['Hi! I am Phil. I will help you get started.', 'Tap a story to see a preview, then press Start.', 'The dots show how hard a story is. Three dots is the toughest.', 'There is no timer here. Take your time and think.', 'Some choices end the story fast, so choose carefully.', 'Winning earns the most XP, but even a loss teaches you the red flags.', 'Played stories fold away. Open them again to try a different path.', 'Stuck? Ask what a real bank or friend would do.', 'Tap the fish swimming in the header at the top. I tell safety jokes!'],
                steps: ['Tap a story in the list to see a preview. Press Start when you are ready, or Surprise me to get a random one.', 'A scammer begins chatting with you. You choose how to reply from the options.', 'Each choice takes the story somewhere different. Some paths keep you safe, others are traps.', 'There is no timer. Think about who is writing, what they want and whether they are rushing you.', 'At the end you see the red flags in that story and earn XP: 60 for a win, 30 for a close call and 10 for a loss.'],
            },
        };
        function plGuide(game) {
            const g = PHIL_GUIDE[game];
            return {
                tips: g.tips, steps: g.steps, tip: g.tips[0], tipI: 0, open: false, step: 0,
                nextTip() { this.open = false; this.tipI = (this.tipI + 1) % this.tips.length; this.tip = this.tips[this.tipI]; },
                start() { this.step = 0; this.open = true; },
                next() { if (this.step >= this.steps.length - 1) { this.open = false; this.tipI = 0; this.tip = this.tips[0]; } else { this.step++; } },
                back() { if (this.step > 0) this.step--; },
                reset() { this.open = false; this.step = 0; this.tipI = 0; this.tip = this.tips[0]; },
                init() { this.$watch('tab', () => this.reset()); },
            };
        }
        // Same shuffle for everyone on the same day, used by the Daily challenge.
        function plDayKey() { const d = new Date(); return d.getFullYear() + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0'); }
        function plShuffleSeeded(list, seed) {
            let a = seed >>> 0;
            const rnd = () => { a = (a + 0x6D2B79F5) >>> 0; let t = a; t = Math.imul(t ^ (t >>> 15), t | 1); t ^= t + Math.imul(t ^ (t >>> 7), t | 61); return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
            const out = list.slice();
            for (let i = out.length - 1; i > 0; i--) { const j = Math.floor(rnd() * (i + 1)); [out[i], out[j]] = [out[j], out[i]]; }
            return out;
        }
        function plSleep(ms) { return new Promise(resolve => setTimeout(resolve, ms)); }

        // ---- game feel helpers: tiny synth sounds (off until the player turns them on), score pop, combo banners ----
        window.PL_SOUND = false;
        let plAudio = null;
        function plTone(f, t0, dur, type, vol, f2) {
            const o = plAudio.createOscillator(), g = plAudio.createGain(), t = plAudio.currentTime + t0;
            o.type = type; o.frequency.setValueAtTime(f, t);
            if (f2) o.frequency.exponentialRampToValueAtTime(f2, t + dur);
            g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(vol, t + 0.015); g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
            o.connect(g); g.connect(plAudio.destination); o.start(t); o.stop(t + dur + 0.05);
        }
        function plSfx(kind, n) {
            if (!window.PL_SOUND) return;
            try {
                if (!plAudio) plAudio = new (window.AudioContext || window.webkitAudioContext)();
                if (plAudio.state === 'suspended') plAudio.resume();
                if (kind === 'ok') { plTone(660, 0, .12, 'triangle', .16); plTone(880, .09, .16, 'triangle', .16); }
                else if (kind === 'bad') { plTone(190, 0, .28, 'sawtooth', .1, 90); }
                else if (kind === 'streak') { const b = 520 + Math.min(n || 3, 12) * 22; [0, 4, 7, 12].forEach((st, i) => plTone(b * Math.pow(2, st / 12), i * .07, .14, 'triangle', .15)); }
                else if (kind === 'life') { [784, 988, 1175, 1568].forEach((f, i) => plTone(f, i * .08, .2, 'sine', .15)); }
                else if (kind === 'tick') { plTone(440, 0, .09, 'square', .07); }
                else if (kind === 'go') { plTone(660, 0, .1, 'square', .08); plTone(990, .09, .3, 'square', .08); }
                else if (kind === 'lvl') { [523, 659, 784, 1047, 1319].forEach((f, i) => plTone(f, i * .1, .26, 'triangle', .17)); }
                else if (kind === 'win') { [523, 659, 784, 1047].forEach((f, i) => plTone(f, i * .12, .3, 'triangle', .16)); plTone(1047, .5, .5, 'sine', .12); }
                else if (kind === 'over') { [392, 330, 262].forEach((f, i) => plTone(f, i * .16, .3, 'triangle', .14)); }
            } catch (e) {}
        }
        function plTier(n) { return n >= 8 ? 3 : (n >= 5 ? 2 : (n >= 3 ? 1 : 0)); }
        function plCombo(n) { return n === 3 ? 'Heating up!' : (n === 5 ? 'On fire!' : (n === 8 ? 'Unstoppable!' : (n > 8 && n % 4 === 0 ? 'Legendary!' : null))); }
        function plFxMixin() {
            return {
                fx: '', pop: false, gains: [], combo: null, gid: 0, comboTimer: null, popTimer: null,
                bump() { this.pop = false; clearTimeout(this.popTimer); requestAnimationFrame(() => { this.pop = true; this.popTimer = setTimeout(() => { this.pop = false; }, 420); }); },
                gain(txt) { const id = ++this.gid; this.gains.push({ id, txt }); setTimeout(() => { this.gains = this.gains.filter(g => g.id !== id); }, 950); },
                hype(streak) {
                    const text = plCombo(streak);
                    if (!text) return false;
                    this.combo = { id: ++this.gid, text, tier: plTier(streak) };
                    clearTimeout(this.comboTimer); this.comboTimer = setTimeout(() => { this.combo = null; }, 1300);
                    return true;
                },
            };
        }


        function plPut(name, v) { plSave(name, v); }
        function plIncr(name) { plPut(name, plGetBest(name) + 1); }
        function plYesterdayKey() { const d = new Date(); d.setDate(d.getDate() - 1); return d.getFullYear() + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0'); }
        function plDayLast() { try { return localStorage.getItem(PL_KEY('day-last')) || ''; } catch (e) { return ''; } }
        // Days played in a row. It counts a day the first time any game earns XP, and it is only alive if you played today or yesterday.
        function plStreakDays() { const last = plDayLast(); return (last === plDayKey() || last === plYesterdayKey()) ? plGetBest('day-n') : 0; }
        function plTouchDay() {
            const today = plDayKey(), last = plDayLast();
            if (last === today) return;
            const n = last === plYesterdayKey() ? plGetBest('day-n') + 1 : 1;
            plPut('day-last', today); plPut('day-n', n);
            if (n > plGetBest('day-best')) plPut('day-best', n);
        }
        // Counts a number up on the results screen.
        function plCounter(target) {
            return {
                n: 0,
                init() {
                    const to = Number(target) || 0;
                    if (!to || (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) { this.n = to; return; }
                    const t0 = performance.now(), dur = Math.min(1100, 350 + to * 8);
                    const tick = now => { const k = Math.min(1, (now - t0) / dur); this.n = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(tick); };
                    requestAnimationFrame(tick);
                },
            };
        }

        function plMood(kind, line) { window.dispatchEvent(new CustomEvent('pl-mood', { detail: { kind, line } })); }

        function plXp(n) { if (n > 0) window.dispatchEvent(new CustomEvent('pl-xp', { detail: n })); }
        // Best scores and XP stay in this browser only.
        const PL_KEY = name => 'phishcore-play-' + (window.PL_SCOPE || 'x') + '-' + name;
        function plGetBest(name) { try { return parseInt(localStorage.getItem(PL_KEY(name)) || '0', 10) || 0; } catch (e) { return 0; } }
        function plSetBest(name, value) {
            const old = plGetBest(name);
            if (value > old) { plSave(name, value); return value; }
            return old;
        }

        // ---- saving progress to the server ----
        // The games read and write the browser copy as before. Every change is also sent to the server (a moment later, in one go),
        // and when the page opens the server copy is merged in, so progress follows the player to another device.
        const PL_NUM = ['xp', 'rush', 'rush-hard', 'quiz-streak', 'day-n', 'day-best', 'perfect', 'daily-runs'];
        const PL_PREFS = ['side', 'sound', 'wardrobe'];
        const PL_RANK = { lose: 1, meh: 2, win: 3 };
        const plSyncable = k => PL_NUM.includes(k) || /^rush-daily-\d{8}$/.test(k) || ['day-last', 'prefs-at', 'survivor', 'badges', ...PL_PREFS].includes(k);
        let plDirty = false, plPushTimer = null;
        function plLocalAll() {
            const out = {};
            try {
                const pre = PL_KEY('');
                for (let i = 0; i < localStorage.length; i++) {
                    const k = localStorage.key(i);
                    if (k && k.startsWith(pre) && plSyncable(k.slice(pre.length))) out[k.slice(pre.length)] = localStorage.getItem(k);
                }
            } catch (e) {}
            return out;
        }
        function plSave(name, v) {
            try { localStorage.setItem(PL_KEY(name), String(v)); } catch (e) {}
            if (PL_PREFS.includes(name)) { try { localStorage.setItem(PL_KEY('prefs-at'), String(Date.now())); } catch (e) {} }
            plDirty = true; clearTimeout(plPushTimer); plPushTimer = setTimeout(plPush, 2500);
        }
        function plPush() {
            if (!plDirty || !window.PL_PUSH_URL) return;
            clearTimeout(plPushTimer); plDirty = false;
            try {
                fetch(window.PL_PUSH_URL, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.PL_TOKEN }, body: JSON.stringify({ data: plLocalAll() }) })
                    .then(r => { if (!r.ok) plDirty = true; }).catch(() => { plDirty = true; });
            } catch (e) { plDirty = true; }
        }
        // The same rules as the server (PlayProgressSync::merge): scores only go up, badges add up, the newer look wins.
        function plMerge(a, b) {
            const pj = v => { try { return JSON.parse(v); } catch (e) { return null; } };
            const out = {};
            for (const k of new Set([...Object.keys(a), ...Object.keys(b)])) {
                if (['day-last', 'day-n', 'prefs-at', ...PL_PREFS].includes(k)) continue;
                const x = a[k], y = b[k];
                if (x === undefined || y === undefined) out[k] = x ?? y;
                else if (PL_NUM.includes(k) || k.startsWith('rush-daily-')) out[k] = String(Math.max(parseInt(x, 10) || 0, parseInt(y, 10) || 0));
                else if (k === 'survivor') { const s = { ...(pj(x) || {}) }; for (const [id, r] of Object.entries(pj(y) || {})) if (!s[id] || PL_RANK[r] > PL_RANK[s[id]]) s[id] = r; out[k] = JSON.stringify(s); }
                else if (k === 'badges') out[k] = JSON.stringify([...new Set([...(pj(x) || []), ...(pj(y) || [])])]);
            }
            const la = a['day-last'], lb = b['day-last'];
            if (la !== undefined || lb !== undefined) {
                const na = parseInt(a['day-n'], 10) || 0, nb = parseInt(b['day-n'], 10) || 0;
                if (la === undefined || (lb !== undefined && lb > la)) { out['day-last'] = lb; out['day-n'] = String(nb); }
                else if (lb === undefined || la > lb) { out['day-last'] = la; out['day-n'] = String(na); }
                else { out['day-last'] = la; out['day-n'] = String(Math.max(na, nb)); }
            }
            const newer = (parseInt(b['prefs-at'], 10) || 0) >= (parseInt(a['prefs-at'], 10) || 0) ? b : a, other = newer === b ? a : b;
            for (const k of [...PL_PREFS, 'prefs-at']) { const v = newer[k] ?? other[k]; if (v !== undefined) out[k] = v; }
            return out;
        }
        // Runs once, as the page loads and before the games read their saved values.
        function plSyncBoot() {
            const local = plLocalAll(), saved = window.PL_SAVED;
            if (!saved) { if (Object.keys(local).length) { plDirty = true; plPush(); } return; }
            const merged = plMerge(saved, local);
            for (const [k, v] of Object.entries(merged)) { try { if (local[k] !== v) localStorage.setItem(PL_KEY(k), v); } catch (e) {} }
            if (JSON.stringify(Object.keys(merged).sort().map(k => [k, merged[k]])) !== JSON.stringify(Object.keys(saved).sort().map(k => [k, saved[k]]))) { plDirty = true; plPush(); }
        }
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') plPush(); });
        window.addEventListener('pagehide', plPush);

        function playHub(messages, stories) {
            const RANKS = ['Newbie', 'Scam Spotter', 'Link Checker', 'Sender Sleuth', 'Phish Hunter', 'Scam Slayer', 'Cyber Guardian', 'Fraud Fighter', 'Security Sage', 'Phish Master', 'Cyber Champion', 'Legend'];
            const TIPS = [
                'Real banks never ask for your OTP or PIN, not by text, not by phone.',
                'A message that says "act now or lose your account" is trying to rush you. Slow down.',
                'Before you click, look at the link. A real company\'s website name will not have extra words or dashes.',
                'If a stranger offers easy money for simple tasks, the catch is that you pay first.',
                'Got a call from your "bank"? Hang up and call the number on the back of your card.',
                'A padlock in the browser only means the connection is private. It does not mean the site is honest.',
                'Never install an app because someone on a call or chat told you to.',
                'If someone says "do not tell anyone", that is your cue to tell someone.',
                'A deal that is far cheaper than everywhere else is usually a trap.',
                'Online friends who suddenly need money are a warning sign, however kind they seem.',
                'Parcel texts that ask for a small fee are after your card details, not the fee.',
                'Use a different password for each account, so one leak cannot open them all.',
                'Check the sender address, not just the name. Scammers copy names easily.',
                'When unsure, scan the link with PhishCore before you open it.',
            ];
            const FISH_LINES = [
                'Not clicking that link!',
                'Free prize? I only bite real bait.',
                'Blub. Check the sender first.',
                'Never share your OTP. Not even with a fish.',
                'That is a phish. I would know.',
                'Too good to be true? Blub, yes.',
                'URGENT! Act now! ...no thanks.',
                'I got hooked once. Never again.',
                'Hover before you click. Blub.',
                'Phishing is not just for fish!',
                'A bank will never DM you first. Blub.',
                'Short link? Do not trust it. Blub.',
                'Fake deadlines make fake fear.',
                'My cousin clicked a prize link. Now he is a sardine tin.',
                'Check the spelling in the web address.',
                'If it sounds too easy, it is bait.',
                'Call back on a number YOU trust.',
                'Gift card codes are cash. Never send them!',
                'Strangers do not give out free money. Blub.',
                'Two-step login is my favourite armour.',
                'Delete it, block it, tell someone.',
                'Parcel fee by link? Swim away.',
                'Even your boss can be faked. Check first.',
                'A friend asking for money? Call them.',
                'Updates are good. Random pop-ups are not.',
                'Your IC number is for you, not for texts.',
                'I never shop on a site with no padlock.',
                'A padlock is not a promise, though!',
                'Real police do not ask for transfers.',
                'Scammers love a good panic. Stay calm.',
                'Fresh passwords are tasty. Reuse is rotten.',
                'Slow down. Scammers hate slow.',
                'Not sure? Scan it with PhishCore!',
                'Do not scan a QR code from a stranger.',
                'Love online, then money? Red flag.',
                'The sender name can lie. The address cannot.',
                'You cannot win a contest you did not enter.',
                'Banks do not need your password. Ever.',
                'Strange attachment? Do not open. Blub.',
                'Report it, then block it. Be a hero.',
                'Tell your family about scams too.',
                'Public Wi-Fi? Skip the banking app.',
                'A job that asks you to pay first is a trap.',
                'I swim in circles, not into scams.',
                'Treat unexpected links like unknown fish.',
                'Hooks look shiny. That is the point.',
                'Verify first, tap later. Blub.',
                'If they rush you, they are hiding something.',
                'Wrong number? Maybe. Chat? No thanks.',
                'Helpline 16993 if you are unsure.',
            ];
            const WARDROBE = {
                hat: [
                    { id: 'none', name: 'No hat', lv: 1 },
                    { id: 'party', name: 'Party hat', lv: 2, sw: '#f43f5e' },
                    { id: 'cap', name: 'Cap', lv: 3, sw: '#2563eb' },
                    { id: 'crown', name: 'Crown', lv: 5, sw: '#fbbf24' },
                    { id: 'wizard', name: 'Wizard hat', lv: 7, sw: '#7c3aed' },
                ],
                color: [
                    { id: 'classic', name: 'Classic', lv: 1, c: ['#0ea5e9', '#38bdf8', '#bae6fd'], sw: '#38bdf8' },
                    { id: 'mint', name: 'Mint', lv: 3, c: ['#059669', '#34d399', '#a7f3d0'], sw: '#34d399' },
                    { id: 'pink', name: 'Pink', lv: 4, c: ['#db2777', '#f472b6', '#fbcfe8'], sw: '#f472b6' },
                    { id: 'gold', name: 'Gold', lv: 6, c: ['#d97706', '#fbbf24', '#fde68a'], sw: '#fbbf24' },
                    { id: 'violet', name: 'Violet', lv: 8, c: ['#7c3aed', '#a78bfa', '#ddd6fe'], sw: '#a78bfa' },
                ],
                water: [
                    { id: 'day', name: 'Day', lv: 1, sw: '#38bdf8' },
                    { id: 'sunset', name: 'Sunset', lv: 4, sw: '#fb923c' },
                    { id: 'night', name: 'Night', lv: 6, sw: '#6366f1' },
                    { id: 'coral', name: 'Coral reef', lv: 8, sw: '#2dd4bf' },
                ],
            };
            const WARDROBE_ALL = Object.entries(WARDROBE).flatMap(([kind, list]) => list.map(i => ({ ...i, kind })));
            const REACT = {
                cheer: ['Nice one!', 'You are on fire!', 'Phish-tastic!', 'Keep it up!', 'Scammers hate this one trick!'],
                ouch: ['Ouch! Shake it off.', 'Oops! The next one is yours.', 'That one was sneaky.'],
            };
            const BADGES = [
                { id: 'first', name: 'First steps', how: 'Earn your first XP.', icon: 'M5 12l5 5L20 7', test: (s, h) => h.xp > 0 },
                { id: 'perfect', name: 'Perfect round', how: 'Get every answer right in a quiz of 5 or more.', icon: 'M12 3l2.800 5.700 6.200.9-4.500 4.400 1.100 6.200L12 17.300 6.400 20.200l1.100-6.200L3 9.600l6.200-.9z', test: s => s.perfect >= 1 },
                { id: 'streak10', name: 'Hot streak', how: 'Get 10 right in a row in Scam or Safe?', icon: 'M12 3c1 4 5 5.500 5 10a5 5 0 01-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 1-9z', test: s => s.streak >= 10 },
                { id: 'rush200', name: 'Speed sorter', how: 'Score 200 points in Inbox Rush.', icon: 'M13 2L5 14h6l-1 8 9-12h-6z', test: s => s.rush >= 200 },
                { id: 'hard300', name: 'Hard hitter', how: 'Score 300 points on Hard mode.', icon: 'M12 3l8 3v6c0 5-3.500 8-8 9-4.500-1-8-4-8-9V6zM9 12l2 2 4-4', test: s => s.hard >= 300 },
                { id: 'daily3', name: 'Daily regular', how: 'Finish the Daily challenge on 3 different days.', icon: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', test: s => s.daily >= 3 },
                { id: 'surv3', name: 'Survivor', how: 'Survive 3 Scam Survivor stories.', icon: 'M12 21s-7.500-4.600-9.600-9.300C.9 8.200 3 4.500 6.600 4.500c2 0 3.500 1 5.400 3 1.900-2 3.400-3 5.400-3 3.600 0 5.700 3.700 4.200 7.200C19.500 16.400 12 21 12 21z', test: s => s.survived >= 3 },
                { id: 'survall', name: 'Story master', how: 'Survive every Scam Survivor story.', icon: 'M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zM4 19a2 2 0 012-2h13', test: (s, h) => s.survived >= h.stories.length },
                { id: 'days3', name: 'On a roll', how: 'Play 3 days in a row.', icon: 'M12 7v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z', test: s => s.dayBest >= 3 },
                { id: 'days7', name: 'Dedicated', how: 'Play 7 days in a row.', icon: 'M3 8l4 4 5-7 5 7 4-4-2 11H5z', test: s => s.dayBest >= 7 },
                { id: 'lv5', name: 'Rising star', how: 'Reach level 5.', icon: 'M12 19V5M5 12l7-7 7 7', test: (s, h) => h.lv.n >= 5 },
                { id: 'lv10', name: 'Veteran', how: 'Reach level 10.', icon: 'M8 4h8v5a4 4 0 01-8 0zM8 6H4v1a4 4 0 004 4M16 6h4v1a4 4 0 01-4 4M12 13v4M8 20h8', test: (s, h) => h.lv.n >= 10 },
            ];
            const readStats = () => {
                let done = {};
                try { done = JSON.parse(localStorage.getItem(PL_KEY('survivor'))) || {}; } catch (e) {}
                return { rush: plGetBest('rush'), streak: plGetBest('quiz-streak'), survived: Object.values(done).filter(v => v === 'win').length, hard: plGetBest('rush-hard'), perfect: plGetBest('perfect'), daily: plGetBest('daily-runs'), dayBest: plGetBest('day-best') };
            };
            return {
                tab: 'quiz', wardOpen: false, wardTab: 'hat', mood: '', moodTimer: null, lastReact: 0, wardrobeList: WARDROBE,
                wardrobe: (() => { const d = { hat: 'none', color: 'classic', water: 'day' }; try { return { ...d, ...(JSON.parse(localStorage.getItem(PL_KEY('wardrobe'))) || {}) }; } catch (e) { return d; } })(),
                streakDays: plStreakDays(), playedToday: plDayLast() === plDayKey(), badgeInfo: null, badgeToast: null, badgeTimer: null, lvlCard: null, lvlCardTimer: null, sound: (() => { try { return localStorage.getItem(PL_KEY('sound')) === '1'; } catch (e) { return false; } })(), side: (() => { try { return localStorage.getItem(PL_KEY('side')) === '1'; } catch (e) { return false; } })(), messages, stories, xp: plGetBest('xp'), toast: null, toastTimer: null,
                ranks: RANKS, stats: readStats(), active: { quiz: false, rush: false, story: false }, held: { quiz: false, rush: false, story: false }, ranksOpen: false, lvlUp: false, lvTimer: null, fishSay: '', fishTimer: null, fishHop: false,
                get playing() { return this.active[this.tab]; },
                get inGame() { return this.held[this.tab]; },
                get badges() { return BADGES.map(b => ({ ...b, on: b.test(this.stats, this) })); },
                get phil() {
                    const pick = k => { const it = WARDROBE[k].find(i => i.id === this.wardrobe[k]); return it && this.lv.n >= it.lv ? it : WARDROBE[k][0]; };
                    const c = pick('color').c || ['#0ea5e9', '#38bdf8', '#bae6fd'];
                    return { hat: pick('hat').id, colorId: pick('color').id, a: c[0], b: c[1], c: c[2], water: pick('water').id };
                },
                pick(kind, it) {
                    if (this.lv.n < it.lv) return;
                    this.wardrobe[kind] = it.id;
                    try { plSave('wardrobe', JSON.stringify(this.wardrobe)); } catch (e) {}
                    this.react({ kind: 'show', line: 'Looking good!' });
                },
                get look() {
                    const nm = k => (WARDROBE[k].find(i => i.id === this.phil[k === 'color' ? 'colorId' : k]) || WARDROBE[k][0]).name;
                    return nm('hat') + '  |  ' + nm('color') + '  |  ' + nm('water');
                },
                get nextUnlock() {
                    const locked = WARDROBE_ALL.filter(i => i.lv > this.lv.n).sort((a, b) => a.lv - b.lv)[0];
                    if (!locked) return null;
                    return { lv: locked.lv, label: locked.name + (locked.kind === 'color' ? ' Phil' : (locked.kind === 'water' ? ' water' : '')) };
                },
                unlocked(kind) { return WARDROBE[kind].filter(i => i.lv <= this.lv.n).length; },
                tileStyle(it) { const c = this.wardTab === 'color' ? it.c : [this.phil.a, this.phil.b, this.phil.c]; return '--pa:' + c[0] + ';--pb:' + c[1] + ';--pc:' + c[2]; },
                resetLook() {
                    this.wardrobe = { hat: 'none', color: 'classic', water: 'day' };
                    try { plSave('wardrobe', JSON.stringify(this.wardrobe)); } catch (e) {}
                    this.react({ kind: 'show', line: 'Back to my usual look!' });
                },
                // Phil reacts to what happens in the games: a flip and a cheer, or a flinch.
                react(d) {
                    const kind = d.kind, now = Date.now();
                    this.mood = ''; clearTimeout(this.moodTimer);
                    requestAnimationFrame(() => { this.mood = kind; this.moodTimer = setTimeout(() => { this.mood = ''; }, 1000); });
                    const lines = REACT[kind] || [];
                    if (d.line) { this.say(d.line); this.lastReact = now; }
                    else if (lines.length && now - this.lastReact > 6000 && (kind === 'cheer' || Math.random() < 0.5)) { this.say(lines[Math.floor(Math.random() * lines.length)]); this.lastReact = now; }
                },
                get badgeCount() { return this.badges.filter(b => b.on).length; },
                checkBadges(before) {
                    let have = null;
                    try { have = JSON.parse(localStorage.getItem(PL_KEY('badges'))); } catch (e) {}
                    const now = this.badges.filter(b => b.on);
                    const fresh = Array.isArray(have) ? now.filter(b => !have.includes(b.id)) : now;
                    try { plSave('badges', JSON.stringify(now.map(b => b.id))); } catch (e) {}
                    // A player who already had progress before badges existed gets theirs quietly the first time.
                    if (!fresh.length || (have === null && before > 0)) return;
                    this.badgeToast = 'Badge unlocked: ' + fresh.map(b => b.name).join(', ');
                    plSfx('life');
                    clearTimeout(this.badgeTimer);
                    this.badgeTimer = setTimeout(() => { this.badgeToast = null; }, 3200);
                },
                toggleSound() { this.sound = !this.sound; window.PL_SOUND = this.sound; try { plSave('sound', this.sound ? '1' : '0'); } catch (e) {} if (this.sound) plSfx('ok'); },
                toggleSide() { this.side = !this.side; try { plSave('side', this.side ? '1' : '0'); } catch (e) {} },
                tip: TIPS[Math.floor(Date.now() / 86400000) % TIPS.length],
                init() {
                    window.PL_SOUND = this.sound;
                    this.$watch('tab', () => { this.stats = readStats(); });
                    setTimeout(() => this.say('Psst! Tap me for a safety joke!'), 2500);
                    setInterval(() => { if (!document.hidden) { this.say(); } }, 13000);
                },
                fishBag: [], fishLast: '',
                say(greeting) {
                    // Draw lines out of a shuffled bag so Phil uses every line before repeating one.
                    if (greeting) {
                        this.fishSay = greeting;
                        clearTimeout(this.fishTimer);
                        this.fishTimer = setTimeout(() => { this.fishSay = ''; }, 4600);
                        return;
                    }
                    if (!this.fishBag.length) {
                        const last = this.fishLast;
                        this.fishBag = plShuffle(FISH_LINES);
                        if (this.fishBag.length > 1 && this.fishBag[this.fishBag.length - 1] === last) this.fishBag.unshift(this.fishBag.pop());
                    }
                    this.fishSay = this.fishLast = this.fishBag.pop();
                    clearTimeout(this.fishTimer);
                    this.fishTimer = setTimeout(() => { this.fishSay = ''; }, 3400);
                },
                poke() { this.fishHop = true; setTimeout(() => { this.fishHop = false; }, 600); this.say(); },
                need(level) { return 80 + (level - 1) * 40; },
                get lv() { let n = 1, left = this.xp; while (left >= this.need(n)) { left -= this.need(n); n++; } return { n, cur: left, need: this.need(n) }; },
                get rank() { return RANKS[Math.min(this.lv.n - 1, RANKS.length - 1)]; },
                get rankNo() { return Math.min(this.lv.n, RANKS.length); },
                addXp(n) {
                    const before = this.lv.n, xpBefore = this.xp;
                    this.xp += n;
                    plTouchDay(); this.streakDays = plStreakDays(); this.playedToday = true;
                    this.stats = readStats();
                    if (this.lv.n > before) {
                        plSfx('lvl'); this.lvlUp = true; clearTimeout(this.lvTimer); this.lvTimer = setTimeout(() => { this.lvlUp = false; }, 1800);
                        this.lvlCard = { n: this.lv.n, rank: this.rank, rankNo: this.rankNo, unlock: WARDROBE_ALL.filter(i => i.lv > before && i.lv <= this.lv.n).map(i => i.name).join(', ') };
                        this.react({ kind: 'cheer', line: 'Level up! I am so proud of you.' });
                        clearTimeout(this.lvlCardTimer); this.lvlCardTimer = setTimeout(() => { this.lvlCard = null; }, 3200);
                    }
                    this.checkBadges(xpBefore);
                    try { plSave('xp', String(this.xp)); } catch (e) {}
                    this.toast = '+' + n + ' XP' + (this.lv.n > before ? '  |  Level ' + this.lv.n + '!' : '');
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => { this.toast = null; }, 2200);
                },
            };
        }

        function quizGame(all) {
            return {
                ...plFxMixin(), counting: false, count: 0, run: 0,
                lengths: [{ n: 5, label: 'Quick' }, { n: 10, label: 'Standard' }, { n: 20, label: 'Marathon' }],
                size: 10, newBest: false, round: [], i: 0, score: 0, streak: 0, bestStreak: 0, picked: null, done: false, started: false, missed: [], best: plGetBest('quiz-streak'),
                quit() { this.run++; this.counting = false; this.started = false; this.done = false; this.picked = null; this.fx = ''; },
                // 3, 2, 1, Go! before the round. Tapping the number skips it.
                start() { this.run++; this.started = false; this.done = false; this.picked = null; this.counting = true; this.count = 3; plSfx('tick'); this.cnt(this.run); },
                cnt(r) { setTimeout(() => { if (r !== this.run || !this.counting) return; if (this.count > 1) { this.count--; plSfx('tick'); this.cnt(r); } else if (this.count === 1) { this.count = 0; plSfx('go'); this.cnt(r); } else { this.begin(); } }, this.count === 0 ? 450 : 700); },
                skip() { if (this.counting) { this.run++; this.begin(); } },
                begin() { this.counting = false; this.fx = ''; this.newBest = false; this.round = plShuffle(all).slice(0, this.size); this.i = 0; this.score = 0; this.streak = 0; this.bestStreak = 0; this.picked = null; this.done = false; this.missed = []; this.started = true; },
                get q() { return this.round[this.i]; },
                get stars() { const r = this.score / this.round.length; return r >= 0.9 ? 3 : (r >= 0.7 ? 2 : (r >= 0.4 ? 1 : 0)); },
                get correct() { return this.picked !== null && this.picked === this.q.scam; },
                answer(isScam) {
                    if (this.picked !== null) return;
                    this.picked = isScam;
                    if (isScam === this.q.scam) {
                        this.score++; this.streak++; this.bestStreak = Math.max(this.bestStreak, this.streak); plXp(10);
                        this.fx = 'fx-ok'; this.bump(); this.gain('+1');
                        if (this.hype(this.streak)) { plSfx('streak', this.streak); plMood('cheer'); } else plSfx('ok');
                    }
                    else { this.streak = 0; this.missed.push(this.q); this.fx = 'fx-bad'; plSfx('bad'); plMood('ouch'); }
                },
                next() {
                    if (this.i >= this.round.length - 1) { this.done = true; plSfx(this.score / this.round.length >= 0.8 ? 'win' : 'over'); if (this.score / this.round.length >= 0.8) plMood('cheer', 'What a round! I am so proud.'); const prev = this.best; this.best = plSetBest('quiz-streak', this.bestStreak); this.newBest = this.bestStreak > prev && this.bestStreak >= 3; if (this.score === this.round.length && this.round.length >= 5) plIncr('perfect'); plXp(Math.round(this.score / this.round.length * 20)); return; }
                    this.i++; this.picked = null; this.fx = '';
                },
                get verdict() {
                    const r = this.score / this.round.length;
                    if (r >= 0.9) return { title: 'Scam-proof!', text: 'You spotted almost everything. Share what you know with family and friends.' };
                    if (r >= 0.7) return { title: 'Sharp eyes', text: 'You caught most of them. Look again at the ones you missed below.' };
                    if (r >= 0.4) return { title: 'Getting there', text: 'Some tricks got past you, and that is how everyone learns. Read the reasons below and try again.' };
                    return { title: 'Now you know the tricks', text: 'Scammers are good at this. Read the reasons below and play again, your score will jump.' };
                },
            };
        }

        function rushGame(all) {
            return {
                ...plFxMixin(), count: 0,
                state: 'idle', run: 0, queue: [], cur: null, turn: 0, lives: 3, score: 0, streak: 0, answered: 0, locked: false, flash: null, missed: [], seconds: 8, elapsed: 0, t0: 0,
                mode: 'normal', newBest: false, dayKey: plDayKey(),
                modes: [
                    { id: 'normal', label: 'Normal', lives: 3, base: 8, floor: 3.4, mult: 1, note: '3 lives. The timer shrinks as you go. Every 8 in a row wins a life back.' },
                    { id: 'hard', label: 'Hard', lives: 2, base: 6, floor: 2.6, mult: 2, note: '2 lives, a shorter timer and double points. Every 8 in a row wins a life back.' },
                    { id: 'daily', label: 'Daily challenge', lives: 3, base: 8, floor: 3.4, mult: 1, note: 'Everyone gets the same messages today. Beat your best of the day, and earn bonus XP the first time.' },
                ],
                bests: { normal: plGetBest('rush'), hard: plGetBest('rush-hard'), daily: plGetBest('rush-daily-' + plDayKey()) },
                get cfg() { return this.modes.find(m => m.id === this.mode); },
                get stars() { const p = this.score / this.cfg.mult; return p >= 250 ? 3 : (p >= 120 ? 2 : (p >= 40 ? 1 : 0)); },
                get best() { return this.bests[this.mode]; },
                bestKey(m) { return m === 'normal' ? 'rush' : (m === 'hard' ? 'rush-hard' : 'rush-daily-' + this.dayKey); },
                // The bar restarts whenever the game is hidden, so remember how much time was used and carry on from there.
                init() {
                    this.$watch('tab', t => {
                        if (this.state !== 'play') return;
                        const now = performance.now();
                        if (t !== 'rush') { if (!this.locked) this.elapsed = Math.min(this.elapsed + (now - this.t0) / 1000, this.seconds - 0.4); }
                        else { this.t0 = now; }
                    });
                },
                // 3, 2, 1, Go! before the round. Tapping the number skips it.
                start() { this.run++; this.state = 'count'; this.count = 3; plSfx('tick'); this.cnt(this.run); },
                cnt(r) { setTimeout(() => { if (r !== this.run || this.state !== 'count') return; if (this.count > 1) { this.count--; plSfx('tick'); this.cnt(r); } else if (this.count === 1) { this.count = 0; plSfx('go'); this.cnt(r); } else { this.begin(); } }, this.count === 0 ? 450 : 700); },
                skip() { if (this.state === 'count') { this.run++; this.begin(); } },
                begin() { this.dayKey = plDayKey(); this.dealt = 0; this.queue = this.mode === 'daily' ? plShuffleSeeded(all, parseInt(this.dayKey, 10)) : plShuffle(all); this.lives = this.cfg.lives; this.score = 0; this.streak = 0; this.answered = 0; this.missed = []; this.newBest = false; this.run++; this.state = 'play'; this.deal(); },
                quit() { this.run++; this.locked = true; this.flash = null; this.state = 'idle'; },
                deal() {
                    if (!this.queue.length) this.queue = this.mode === 'daily' ? plShuffleSeeded(all, parseInt(this.dayKey, 10) + (++this.dealt)) : plShuffle(all);
                    this.cur = this.queue.pop();
                    this.seconds = Math.max(this.cfg.floor, this.cfg.base - this.answered * 0.15);
                    this.locked = false; this.flash = null; this.fx = ''; this.elapsed = 0; this.t0 = performance.now(); this.turn++;
                },
                answer(isScam) {
                    if (this.locked) return;
                    this.locked = true; this.answered++;
                    if (isScam === this.cur.scam) {
                        this.streak++;
                        const gain = (10 + Math.min(this.streak - 1, 5) * 2) * this.cfg.mult;
                        const bonus = this.streak % 8 === 0 && this.lives < this.cfg.lives;
                        if (bonus) this.lives++;
                        this.score += gain; this.flash = { ok: true, text: '+' + gain + (bonus ? '  and a life back!' : '') };
                        this.fx = 'fx-ok'; this.bump(); this.gain('+' + gain);
                        if (bonus) { this.hype(this.streak); plSfx('life'); plMood('cheer', 'A life back! Keep going!'); } else if (this.hype(this.streak)) { plSfx('streak', this.streak); plMood('cheer'); } else plSfx('ok');
                        this.after(bonus ? 1000 : 600);
                    } else {
                        this.streak = 0; this.lives--; this.missed.push(this.cur);
                        this.flash = { ok: false, text: this.cur.scam ? 'That was a scam!' : 'That one was safe' };
                        this.fx = 'fx-bad'; plSfx('bad'); plMood('ouch');
                        this.after(1300);
                    }
                },
                timeout() {
                    if (this.locked) return;
                    this.locked = true; this.answered++; this.streak = 0; this.lives--; this.missed.push(this.cur);
                    this.flash = { ok: false, text: 'Too slow!' };
                    this.fx = 'fx-bad'; plSfx('bad'); plMood('ouch');
                    this.after(1300);
                },
                after(ms) { const r = this.run; setTimeout(() => { if (r !== this.run || this.state !== 'play') return; if (this.lives <= 0) this.finish(); else this.deal(); }, ms); },
                finish() {
                    const key = this.bestKey(this.mode), before = plGetBest(key);
                    this.bests[this.mode] = plSetBest(key, this.score);
                    this.newBest = this.score > before && this.score > 0;
                    this.state = 'over';
                    plSfx(this.newBest ? 'win' : 'over'); if (this.newBest) plMood('cheer', 'New best! Phish-tastic!');
                    const firstDaily = this.mode === 'daily' && before === 0 && this.score > 0;
                    if (firstDaily) plIncr('daily-runs');
                    plXp(Math.round(this.score / 4));
                    if (firstDaily) plXp(10);
                },
                key(e) {
                    if (this.state !== 'play') return;
                    if (e.key === 'ArrowLeft') { e.preventDefault(); this.answer(true); }
                    if (e.key === 'ArrowRight') { e.preventDefault(); this.answer(false); }
                },
            };
        }

        function survivorGame(stories) {
            const RANK = { lose: 1, meh: 2, win: 3 };
            const XP = { win: 60, meh: 30, lose: 10 };
            const load = () => { try { return JSON.parse(localStorage.getItem(PL_KEY('survivor'))) || {}; } catch (e) { return {}; } };
            return {
                view: 'pick', story: null, vars: {}, log: [], choices: [], typing: false, ending: null, token: 0, done: load(),
                sel: null, showPlayed: false,
                init() { const f = stories.find(x => !this.done[x.id]); this.sel = (f || stories[0]).id; this.showPlayed = !f; },
                get fresh() { return stories.filter(x => !this.done[x.id]); },
                get played() { return stories.filter(x => this.done[x.id]); },
                get cur() { return stories.find(x => x.id === this.sel) || stories[0]; },
                // First line of the story with the first amount filled in, for the preview.
                line(st) {
                    const first = st.nodes[st.start].say[0];
                    return first.replace(/\{(\w+)\}/g, (m, k) => ((st.vars || {})[k] || [])[0] || m);
                },
                chan(tag) {
                    const t = tag.toLowerCase();
                    if (t.includes('real')) return 'real';
                    if (t.startsWith('sms')) return 'sms';
                    if (t.startsWith('phone')) return 'call';
                    if (t.startsWith('chat')) return 'chat';
                    if (t.startsWith('market')) return 'market';
                    if (t.startsWith('email')) return 'email';
                    if (t.startsWith('social')) return 'social';
                    if (t.startsWith('qr')) return 'qr';
                    return '';
                },
                ac(tag) {
                    const t = tag.toLowerCase();
                    if (t.includes('real')) return '#22c55e';
                    if (t.startsWith('sms')) return '#f59e0b';
                    if (t.startsWith('phone')) return '#f87171';
                    if (t.startsWith('chat')) return '#38bdf8';
                    if (t.startsWith('market')) return '#a78bfa';
                    if (t.startsWith('email')) return '#34d399';
                    if (t.startsWith('social')) return '#f472b6';
                    if (t.startsWith('qr')) return '#fb923c';
                    return '#7dd3fc';
                },
                clock: '',
                random(ev) {
                    const fresh = stories.filter(s => !this.done[s.id]);
                    const pool = fresh.length ? fresh : stories;
                    this.begin(pool[Math.floor(Math.random() * pool.length)], ev);
                },
                begin(story, ev) {
                    this.clock = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    // New amounts every play, so replays are not word for word the same.
                    this.vars = {};
                    Object.keys(story.vars || {}).forEach(k => { const o = story.vars[k]; this.vars[k] = o[Math.floor(Math.random() * o.length)]; });
                    this.story = story; this.log = []; this.choices = []; this.ending = null; this.view = 'play'; this.enter(story.start);
                },
                fill(text) { return text.replace(/\{(\w+)\}/g, (m, k) => this.vars[k] || m); },
                leave() { this.token++; this.typing = false; this.view = 'pick'; },
                scroll() { this.$nextTick(() => { const el = this.$refs.chat; if (el) el.scrollTop = el.scrollHeight; }); },
                // Always follow the newest message. Instant (not smooth) so it never fights the wheel or a finger.
                watchChat(el) {
                    let near = true;
                    const bottom = () => { el.scrollTop = el.scrollHeight; };
                    el.addEventListener('scroll', () => { near = el.scrollHeight - el.scrollTop - el.clientHeight < 60; }, { passive: true });
                    if (window.MutationObserver) { new MutationObserver(bottom).observe(el, { childList: true, subtree: true, attributes: true, attributeFilter: ['style'] }); }
                    if (window.ResizeObserver) { new ResizeObserver(() => { if (near) bottom(); }).observe(el); }
                    bottom();
                },
                async enter(key) {
                    const node = this.story.nodes[key];
                    const t = ++this.token;
                    this.choices = [];
                    if (node.end) { await plSleep(900); if (t === this.token) this.finish(node.end); return; }
                    for (const line of node.say) {
                        this.typing = true; this.scroll();
                        await plSleep(Math.min(1500, 500 + line.length * 14));
                        if (t !== this.token) return;
                        this.typing = false;
                        this.log.push({ who: 'them', text: this.fill(line) });
                        this.scroll();
                        await plSleep(250);
                        if (t !== this.token) return;
                    }
                    // Fill in the amounts and shuffle the order, so the safe answer is not always in the same place.
                    this.choices = plShuffle(node.choices.map(c => ({ ...c, text: this.fill(c.text), say: c.say ? this.fill(c.say) : null, tip: c.tip ? [c.tip[0], this.fill(c.tip[1])] : null })));
                    this.scroll();
                },
                async pick(choice) {
                    if (this.typing || !this.choices.length) return;
                    this.choices = [];
                    // The player says a real chat line. Some choices (ignore, walk away) are silence, so no bubble.
                    if (choice.say) { this.log.push({ who: 'me', text: choice.say }); }
                    this.scroll();
                    if (choice.tip) { await plSleep(350); this.log.push({ who: 'tip', kind: choice.tip[0], text: choice.tip[1] }); this.scroll(); }
                    await plSleep(450);
                    this.enter(choice.next);
                },
                finish(end) {
                    this.ending = { ...end, title: this.fill(end.title), text: this.fill(end.text) };
                    plSfx(end.kind === 'win' ? 'win' : 'over'); if (end.kind === 'win') plMood('cheer', 'You survived! Well played.'); else if (end.kind === 'lose') plMood('ouch', 'Phew. Try another path?');
                    const old = this.done[this.story.id];
                    if (!old || RANK[end.kind] > RANK[old]) {
                        this.done[this.story.id] = end.kind;
                        try { plSave('survivor', JSON.stringify(this.done)); } catch (e) {}
                    }
                    this.view = 'end';
                    plXp(XP[end.kind]);
                },
            };
        }
        plSyncBoot();
    </script>
</x-layouts.dashboard>