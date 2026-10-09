@php
    $chatReport = request()->route('report');
    $chatReportId = ($chatReport instanceof \App\Models\Report && $chatReport->canBeViewedBy(auth()->user())) ? $chatReport->id : null;
    // Ties the saved chat in the browser to this login, so another user (or a new login) never sees it.
    // Which part of the app the person is on, so Cora's suggested questions and tips fit the page.
    $chatPage = match (true) {
        $chatReportId !== null => 'report',
        request()->routeIs('dashboard') => 'dashboard',
        request()->routeIs('scan.index', 'scan.store') => 'scan',
        request()->routeIs('scan.history') => 'history',
        request()->routeIs('analytics') => 'analytics',
        request()->routeIs('play.*') => 'play',
        request()->routeIs('profile.*') => 'settings',
        default => 'other',
    };
    $chatScope = auth()->check() ? substr(hash('sha256', session()->getId().'|'.auth()->id()), 0, 16) : '';
@endphp

@auth
    @if (app(\App\Services\ChatAdvisor::class)->enabled())
        {{-- Shared "liquid glass" look for the floating chat and the AI Chat page. --}}
        <style>
            /* no `position` here: the floating panel is absolute and the page box is made relative in its own markup */
            .cg-glass {
                isolation: isolate; overflow: hidden;
                background: linear-gradient(160deg, rgba(22, 38, 74, .93), rgba(10, 20, 44, .95));
                -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
                border: 1px solid rgba(255, 255, 255, .2);
                box-shadow: 0 28px 60px -22px rgba(2, 8, 23, .8), inset 0 1px 0 rgba(255, 255, 255, .35), inset 0 -1px 0 rgba(255, 255, 255, .05), inset 0 0 44px rgba(125, 211, 252, .06);
            }
            .cg-glass::before { content: ""; position: absolute; inset: 0; z-index: -1; pointer-events: none;
                background: radial-gradient(60% 38% at 12% 0%, rgba(125, 211, 252, .2), transparent 70%), radial-gradient(50% 40% at 100% 100%, rgba(167, 139, 250, .18), transparent 70%); }
            .cg-orb { position: absolute; z-index: -1; border-radius: 9999px; filter: blur(40px); pointer-events: none; will-change: transform; }
            .cg-orb-a { top: -4rem; left: -3rem; width: 13rem; height: 13rem; background: rgba(56, 189, 248, .38); animation: cg-drift-a 16s ease-in-out infinite alternate; }
            .cg-orb-b { bottom: -5rem; right: -3rem; width: 15rem; height: 15rem; background: rgba(129, 140, 248, .34); animation: cg-drift-b 19s ease-in-out infinite alternate; }
            @keyframes cg-drift-a { to { transform: translate(5rem, 4rem) scale(1.15); } }
            @keyframes cg-drift-b { to { transform: translate(-6rem, -3rem) scale(1.1); } }

            /* sizes: page on desktop/tablet, then phones, then short landscape screens */
            .cg-page, .cg-page-head { max-width: 54rem; }
            .cg-page { height: calc(100vh - 9.5rem); height: min(calc(100dvh - 9.5rem), 48rem); min-height: 24rem; }
                        .cg-glass::before { opacity: .3; }
            .cg-orb { opacity: .35; }
            .cg-panel { height: min(33rem, calc(100vh - 7rem)); height: min(33rem, calc(100dvh - 7rem)); }
            @media (max-width: 1023px) { .cg-page { height: calc(100vh - 12rem); height: calc(100dvh - 12rem); } .cg-panel { height: min(33rem, calc(100vh - 9.5rem)); height: min(33rem, calc(100dvh - 9.5rem)); } } /* keeps the panel under the phone top bar */
            @media (max-width: 639px) { .cg-page { height: calc(100vh - 9.5rem); height: calc(100dvh - 9.5rem); min-height: 20rem; border-radius: 1.4rem; } .cg-hide-phone { display: none !important; } }
            @media (max-height: 520px) and (orientation: landscape) {
                .cg-page { height: calc(100vh - 6.5rem); height: calc(100dvh - 6.5rem); min-height: 12rem; }
                .cg-panel { height: calc(100vh - 6.5rem); height: calc(100dvh - 6.5rem); max-width: 24rem; }
                .cg-hide-short { display: none !important; }
                /* tighter rows so the messages get the room */
                .cg-head { padding-top: .5rem !important; padding-bottom: .5rem !important; }
                .cg-scroll { padding-top: .75rem !important; padding-bottom: .75rem !important; }
                .cg-form { padding-top: .5rem !important; padding-bottom: max(.4rem, env(safe-area-inset-bottom)) !important; }
                .cg-composer textarea, .cg-composer textarea:focus { min-height: 2.4rem; padding-top: .55rem; padding-bottom: .55rem; }
                .cg-send { width: 2.4rem; height: 2.4rem; }
            }

            .cg-scroll { scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, .22) transparent; overscroll-behavior: contain; scroll-behavior: smooth; }
            .cg-scroll::-webkit-scrollbar { width: 6px; }
            .cg-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .2); border-radius: 9999px; }

            /* messages */
            .cg-msg { animation: cg-rise .38s cubic-bezier(.2, .9, .3, 1) both; }
            @keyframes cg-rise { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
            .cg-bot { background: rgba(255, 255, 255, .07); border: 1px solid rgba(255, 255, 255, .14); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
            .cg-me { background: linear-gradient(135deg, rgba(56, 189, 248, .96), rgba(37, 99, 235, .96)); border: 1px solid rgba(255, 255, 255, .22); box-shadow: 0 10px 24px -10px rgba(37, 99, 235, .8), inset 0 1px 0 rgba(255, 255, 255, .4); }
            .cg-err { background: rgba(239, 68, 68, .14); border: 1px solid rgba(248, 113, 113, .4); }

            .cg-av { width: 1.9rem; height: 1.9rem; border-radius: 9999px; display: grid; place-items: center; flex-shrink: 0;
                background: linear-gradient(135deg, rgba(56, 189, 248, .35), rgba(129, 140, 248, .35)); border: 1px solid rgba(255, 255, 255, .25);
                box-shadow: 0 0 16px -2px rgba(56, 189, 248, .6), inset 0 1px 0 rgba(255, 255, 255, .35); }
            .cg-av img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
            .cg-av-me { overflow: hidden; font-size: .7rem; font-weight: 700; color: #fff; background: linear-gradient(135deg, #38bdf8, #2563eb); box-shadow: 0 0 14px -3px rgba(56, 189, 248, .6), inset 0 1px 0 rgba(255, 255, 255, .4); }
            .cg-av-lg { width: 3.5rem; height: 3.5rem; box-shadow: 0 0 34px -2px rgba(56, 189, 248, .6), inset 0 1px 0 rgba(255, 255, 255, .4); animation: cg-float 4s ease-in-out infinite; }
            @keyframes cg-float { 50% { transform: translateY(-4px); } }
            .cg-live { flex-shrink: 0; width: .42rem; height: .42rem; border-radius: 9999px; background: #34d399; box-shadow: 0 0 8px 1px rgba(52, 211, 153, .8); animation: cg-pulse 2.4s ease-in-out infinite; }
            @keyframes cg-pulse { 50% { opacity: .45; } }

            .cg-dot { display: block; width: .42rem; height: .42rem; border-radius: 9999px; background: #bae6fd; animation: cg-bounce 1.2s ease-in-out infinite; }
            .cg-dot:nth-child(2) { animation-delay: .15s; }
            .cg-dot:nth-child(3) { animation-delay: .3s; }
            @keyframes cg-bounce { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-.32rem); opacity: 1; } }

            .cg-copy { opacity: 0; transition: opacity .15s, background-color .15s, color .15s; }
            .group:hover .cg-copy, .cg-copy:focus-visible { opacity: 1; }
            @media (hover: none) { .cg-copy { opacity: .75; } }

            /* suggestions and buttons */
            .cg-chip { background: rgba(255, 255, 255, .06); border: 1px solid rgba(125, 211, 252, .3); color: #bae6fd; border-radius: 9999px; padding: .55rem .95rem; font-size: .8125rem; line-height: 1.25; text-align: left;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .14); transition: transform .2s cubic-bezier(.34, 1.4, .64, 1), background-color .2s, border-color .2s; }
            .cg-chip:hover { transform: translateY(-2px); background: rgba(125, 211, 252, .15); border-color: rgba(125, 211, 252, .6); }
            .cg-chip:active { transform: scale(.97); }
            .cg-ghost { flex-shrink: 0; white-space: nowrap; min-height: 2.25rem; padding: 0 .85rem; border-radius: 9999px; font-size: .75rem; color: #e2e8f0; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .16); transition: background-color .15s, color .15s; }
            .cg-ghost:hover { background: rgba(255, 255, 255, .14); color: #fff; }
            .cg-jump { width: 2.25rem; height: 2.25rem; display: grid; place-items: center; border-radius: 9999px; color: #e0f2fe; background: rgba(15, 23, 42, .65); border: 1px solid rgba(255, 255, 255, .25); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); box-shadow: 0 8px 20px -8px rgba(0, 0, 0, .6); }

            /* message box */
            .cg-form { padding-bottom: max(.75rem, env(safe-area-inset-bottom)); }
            .cg-composer { display: flex; align-items: flex-end; gap: .5rem; padding: .35rem .35rem .35rem 1.1rem; border-radius: 1.5rem; background: rgba(8, 15, 32, .45); border: 1px solid rgba(255, 255, 255, .16);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .12); transition: border-color .2s, box-shadow .2s; }
            .cg-composer:focus-within { border-color: rgba(125, 211, 252, .65); box-shadow: 0 0 0 4px rgba(56, 189, 248, .14), inset 0 1px 0 rgba(255, 255, 255, .15); }
            .cg-composer textarea, .cg-composer textarea:focus { flex: 1; min-width: 0; background: transparent; border: 0; outline: 0; box-shadow: none; resize: none; color: #f1f5f9; font-size: .9rem; line-height: 1.4; padding: .72rem 0; min-height: 2.75rem; max-height: 8rem; }
            .cg-composer textarea::placeholder { color: #94a3b8; }
            @media (max-width: 639px) { .cg-composer textarea, .cg-composer textarea:focus { font-size: 16px; } } /* stops iOS zooming in on focus */
            .cg-send { width: 2.75rem; height: 2.75rem; border-radius: 9999px; display: grid; place-items: center; flex-shrink: 0; color: #fff; background: linear-gradient(135deg, #38bdf8, #2563eb);
                box-shadow: 0 8px 20px -6px rgba(37, 99, 235, .8), inset 0 1px 0 rgba(255, 255, 255, .45); transition: transform .2s cubic-bezier(.34, 1.56, .64, 1), opacity .2s, filter .2s; }
            .cg-send:hover:not(:disabled) { transform: scale(1.08) rotate(-8deg); }
            .cg-send:active:not(:disabled) { transform: scale(.94); }
            .cg-send:disabled { opacity: .4; cursor: not-allowed; filter: saturate(.4); }

            .cg-fab { background: linear-gradient(135deg, rgba(56, 189, 248, .95), rgba(37, 99, 235, .95)); border: 1px solid rgba(255, 255, 255, .35); box-shadow: 0 12px 28px -8px rgba(37, 99, 235, .85), inset 0 1px 0 rgba(255, 255, 255, .5); }


            /* entrance, bouncy open, robot (transform and opacity only, so it stays smooth) */
            .cg-in { animation: cg-in .6s cubic-bezier(.2, .9, .3, 1) backwards; animation-delay: var(--d, 0s); }
            @keyframes cg-in { from { opacity: 0; transform: translateY(14px) scale(.985); } }
            .cg-chip { animation: cg-in .5s cubic-bezier(.2, .9, .3, 1) backwards; animation-delay: calc(.25s + var(--i, 0) * .08s); }
            .cg-pop-enter { transition: transform .45s cubic-bezier(.34, 1.56, .64, 1), opacity .2s ease-out; will-change: transform, opacity; }
            .cg-pop-leave { transition: transform .18s ease-in, opacity .15s ease-in; }
            .cg-pop-from { opacity: 0; transform: translateY(16px) scale(.78); }
            .cg-pop-to { opacity: 1; transform: none; }
            .cg-pop-out { opacity: 0; transform: translateY(8px) scale(.92); }
            .rb { overflow: visible; }
            .rb-eye, .rb-ant { transform-box: fill-box; transform-origin: center; }
            .rb-eye { animation: rb-blink 5s infinite; }
            .rb-ant { animation: rb-ant 2.2s ease-in-out infinite; }
            @keyframes rb-blink { 0%, 91%, 100% { transform: scaleY(1); } 94% { transform: scaleY(.1); } }
            @keyframes rb-ant { 50% { opacity: .35; transform: scale(1.5); } }
            .rb { transform-origin: 50% 90%; }
            .rb-mouth, .rb-smile, .rb-eye, .rb-eye-happy, .rb-hand { transition: opacity .15s; }
            .rb-smile, .rb-eye-happy, .rb-hand { opacity: 0; }
            .rb-hand { transform-origin: 19.7px 20.6px; transform: rotate(-12deg); }
            :is(.cg-fab, .side-link, .cg-av):hover .rb-mouth, :is(.cg-fab, .side-link, .cg-av):hover .rb-eye { opacity: 0; }
            :is(.cg-fab, .side-link, .cg-av):hover .rb-smile, :is(.cg-fab, .side-link, .cg-av):hover .rb-eye-happy { opacity: 1; }
            :is(.cg-fab, .side-link, .cg-av):hover .rb-hand { opacity: 1; animation: rb-wave .8s ease-in-out infinite; }
            @keyframes rb-wave { 0%, 100% { transform: rotate(-12deg); } 25% { transform: rotate(-38deg); } 50% { transform: rotate(8deg); } 75% { transform: rotate(-34deg); } }
            .cg-fab { position: relative; transition: filter .2s ease-out; }
            .cg-fab:hover { filter: brightness(1.12); }
            .cg-fab::after { content: ""; position: absolute; inset: 0; border-radius: inherit; border: 2px solid rgba(125, 211, 252, .7); opacity: 0; pointer-events: none; animation: cg-ring 4s ease-out infinite; }
            .cg-fab.is-open::after { animation: none; }
            @keyframes cg-ring { 0%, 70% { opacity: 0; transform: scale(1); } 75% { opacity: .8; } 100% { opacity: 0; transform: scale(1.6); } }
            /* little speech bubble that pops up next to the floating button now and then */
            .cg-tip { position: absolute; right: 0; bottom: 4.2rem; width: max-content; max-width: min(17rem, calc(100vw - 2rem)); display: flex; align-items: flex-start; gap: .4rem; padding: .65rem .5rem .65rem .85rem; border-radius: 1rem 1rem .3rem 1rem; text-align: left; font-size: .82rem; line-height: 1.35; color: #e8f1ff; cursor: pointer;
                background: linear-gradient(160deg, rgba(30, 52, 98, .96), rgba(14, 26, 54, .96)); border: 1px solid rgba(125, 211, 252, .35); box-shadow: 0 14px 30px -12px rgba(0, 0, 0, .7), 0 0 0 1px rgba(2, 6, 23, .4); }
            .cg-tip-x { flex: none; display: grid; place-items: center; width: 1.4rem; height: 1.4rem; margin-top: -.1rem; border-radius: 999px; color: #94a3b8; }
            .cg-tip-x:hover { color: #fff; background: rgba(148, 163, 184, .2); }
            .cg-tip-in { transition: opacity .35s ease-out, transform .35s ease-out; }
            .cg-tip-from { opacity: 0; transform: translateY(6px); }
            .cg-tip-to { opacity: 1; transform: none; }
            .cg-tip-out { opacity: 0; }
            @media (prefers-reduced-motion: reduce) {
                .cg-tip-in { transition: none; }
                .cg-orb, .cg-av-lg, .cg-live, .cg-dot, .cg-msg, .cg-in, .cg-chip, .rb-eye, .rb-ant, .cg-fab::after, .rb-hand { animation: none !important; }
                .cg-pop-enter, .cg-pop-leave { transition: none; }
                .cg-scroll { scroll-behavior: auto; }
                .cg-chip, .cg-send { transition: none; }
            }
        </style>

        {{-- The AI Chat page has its own big chat, so the floating one is hidden there. --}}
        @unless (request()->routeIs('chat.index'))
        <div
            x-data="safetyChat(@js(['url' => route('chat.send'), 'token' => csrf_token(), 'reportId' => $chatReportId, 'scope' => $chatScope, 'page' => $chatPage]))"
            @keydown.escape.window="open = false"
            class="fixed bottom-4 right-4 z-50 sm:bottom-6 sm:right-6"
            style="padding-right: env(safe-area-inset-right); padding-bottom: env(safe-area-inset-bottom);"
        >
            {{-- Chat panel --}}
            <div
                x-show="open"
                x-transition:enter="cg-pop-enter"
                x-transition:enter-start="cg-pop-from"
                x-transition:enter-end="cg-pop-to"
                x-transition:leave="cg-pop-leave"
                x-transition:leave-start="cg-pop-to"
                x-transition:leave-end="cg-pop-out"
                style="display: none;"
                role="dialog"
                aria-label="Chat with Cora"
                class="cg-glass cg-panel absolute bottom-16 right-0 flex w-[calc(100vw-2rem)] max-w-[26rem] origin-bottom-right flex-col rounded-3xl"
            >
                <span class="cg-orb cg-orb-a" aria-hidden="true"></span>
                <span class="cg-orb cg-orb-b" aria-hidden="true"></span>

                <div class="cg-head flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="cg-av" aria-hidden="true">
                            <x-robot-head class="rb h-[1.15rem] w-[1.15rem] text-sky-100" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">Cora</p>
                            <p class="cg-hide-short flex items-center gap-1.5 text-[11px] text-slate-400"><i class="cg-live"></i><span class="truncate">AI helper<span class="hidden min-[420px]:inline"> &middot; can make mistakes</span></span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="reset()" x-show="messages.length > 0" style="display: none;" class="cg-ghost">New<span class="hidden min-[420px]:inline">&nbsp;chat</span></button>
                        <button type="button" @click="open = false" aria-label="Close chat" class="grid h-9 w-9 place-items-center rounded-full text-slate-300 hover:bg-white/10 hover:text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <x-chat-thread :compact="true" />
            </div>

            {{-- Random pop-up message beside the floating button (only here, not on the other robot-face spots) --}}
            <div
                x-show="tip && !open"
                x-transition:enter="cg-tip-in"
                x-transition:enter-start="cg-tip-from"
                x-transition:enter-end="cg-tip-to"
                x-transition:leave="cg-tip-in"
                x-transition:leave-start="cg-tip-to"
                x-transition:leave-end="cg-tip-out"
                style="display: none;"
                class="cg-tip"
                role="status"
                aria-live="polite"
                @click="open = true"
            >
                <span x-text="tip"></span>
                <button type="button" class="cg-tip-x" aria-label="Hide Cora's tips" @click.stop="silence()">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Floating button --}}
            <button
                type="button"
                @click="toggle()"
                :aria-expanded="open.toString()"
                aria-label="Chat with Cora"
                :class="open ? 'is-open' : ''"
                class="cg-fab flex h-14 w-14 items-center justify-center rounded-full text-white focus:outline-none focus:ring-2 focus:ring-sky-300"
            >
                <x-robot-head x-show="!open" class="rb rb-fab h-8 w-8" :stroke="1.7" />
                <svg x-show="open" style="display: none;" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        @endunless

        <script>
            function safetyChat(config) {
                const KEY = 'phishcore-chat-' + (config.scope || 'x');
                // Remove chats saved by earlier logins or other users in this tab.
                try {
                    Object.keys(sessionStorage).filter(k => k.indexOf('phishcore-chat') === 0 && k !== KEY).forEach(k => sessionStorage.removeItem(k));
                } catch (e) {}
                const load = () => { try { return JSON.parse(sessionStorage.getItem(KEY)) || {}; } catch (e) { return {}; } };
                const saved = load();
                // Only pop the keyboard up on devices with a real pointer; on phones and tablets it would cover the chat.
                const canAutoFocus = () => { try { return window.matchMedia('(hover: hover) and (pointer: fine)').matches; } catch (e) { return false; } };

                // Three random questions from a bigger list, so the chat doesn't always open the same way.
                // Suggested questions. Cora cannot see a message or link until it is typed in, so none of these ask her to judge one.
                const GENERAL = ['What should I do if I clicked a scam link?', 'How can I spot a fake bank message?', 'How do I report a scam in Brunei?', 'Is a link with a padlock always safe?', 'What is phishing, in simple words?', 'Someone asked for my OTP. What now?', 'How do I know if a website is fake?', 'How do job offer scams work?', 'How do I protect my accounts from hackers?', 'What does a scam text look like?', 'How do parcel delivery scams work?', 'What is two-factor authentication?', 'Can scammers fake a phone number?', 'I sent money to a scammer. What now?'];
                const BY_PAGE = {
                    report: ['Explain this scan result in simple words', 'What should I do now?', 'How do I report a scam in Brunei?', 'Why was this flagged as risky?', 'Is it safe to open this link?', 'What if I already typed my password?', 'What does the risk score mean?', 'Should I warn my friends about this?'],
                    dashboard: ['What does the risk score mean?', 'How do I check a suspicious link safely?', 'What are the most common phishing tricks?', 'What should I do if I clicked a scam link?', 'How do I stay safe from phishing?', 'How do I report a scam in Brunei?'],
                    scan: ['How do I check a suspicious link safely?', 'Is a link with a padlock always safe?', 'How do I know if a website is fake?', 'What does a scam text look like?', 'What should I check before I click a link?', 'What does the risk score mean?'],
                    history: ['What should I do about a link that was flagged risky?', 'What does the risk score mean?', 'I opened a risky link. What now?', 'How do I report a scam in Brunei?', 'Why do scammers make messages sound urgent?'],
                    analytics: ['What are the most common phishing tricks?', 'How do phishing scams usually spread?', 'What does the risk score mean?', 'Why do scammers copy real brands?', 'How do I stay safe from phishing?'],
                    play: ['How can I spot a fake bank message?', 'What are the red flags in a scam text?', 'How do job offer scams work?', 'Can scammers fake a phone number?', 'Someone asked for my OTP. What now?', 'How do parcel delivery scams work?'],
                    settings: ['What is two-factor authentication?', 'How do I make a strong password?', 'How do I protect my accounts from hackers?', 'What if I typed my password on a fake site?', 'How often should I change my password?']
                };
                const POOL = (config.reportId ? BY_PAGE.report : BY_PAGE[config.page]) || GENERAL;
                const pick = () => {
                    const a = POOL.slice();
                    for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; }
                    return a.slice(0, 3);
                };
                const TIPS = [
                    'Hi, I\'m Cora. Ask me if a message looks like a scam.',
                    'Got a strange link? Ask me before you click it.',
                    'Tip: a real bank will never ask for your OTP or password in a message.',
                    'Tip: check the web address carefully before you log in.',
                    'Not sure about an SMS? Tell me what it says and I\'ll help.',
                    '"Urgent! Act now!" is a classic scam trick. Stay calm and check first.',
                    'Clicked something odd? Tell me what happened and I\'ll guide you.',
                    'A padlock in the address bar does not mean a site is safe.',
                    'Need to report a scam in Brunei? I can show you how.'
                ];
                const PAGE_TIPS = {
                    dashboard: ['Want to check something suspicious? Open the Scan page. I can explain the result.', 'Not sure what a number here means? Ask me.'],
                    scan: ['Paste the link here instead of tapping it. Safer that way.', 'Got a result you don\'t understand? I can explain it in simple words.'],
                    history: ['Spot a risky scan in your history? Ask me what to do next.'],
                    analytics: ['Curious why scammers keep using the same tricks? Ask me.'],
                    play: ['Stuck on a question? Ask me why a message is a scam.', 'Tip: scammers love urgency. Slow down and check first.'],
                    settings: ['Tip: turn on two-factor authentication on your important accounts.'],
                    report: ['Want this scan result explained in simple words? Just ask.']
                };

                return {
                    open: !!saved.open,
                    messages: Array.isArray(saved.messages) ? saved.messages.slice(-40) : [],
                    input: '',
                    loading: false,
                    copied: null,
                    showJump: false,
                    suggestions: pick(),
                    tip: '',
                    tipTimer: null,
                    tipHide: null,
                    lastTip: -1,

                    init() {
                        this.$watch('open', () => { this.save(); if (this.open) { this.hideTip(); this.focusAndScroll(); } });
                        if (this.open) this.focusAndScroll();
                        this.scheduleTip(true);
                    },

                    // Cora says something now and then (at most 8 times per visit, never while the chat is open, and "x" silences her).
                    scheduleTip(first) {
                        let quiet = false, shown = 0;
                        try { quiet = sessionStorage.getItem('phishcore-cora-quiet') === '1'; shown = parseInt(sessionStorage.getItem('phishcore-cora-tips') || '0', 10) || 0; } catch (e) {}
                        if (quiet || shown >= 8) return;
                        const wait = first ? 9000 + Math.random() * 7000 : 40000 + Math.random() * 40000;
                        clearTimeout(this.tipTimer);
                        this.tipTimer = setTimeout(() => this.showTip(), wait);
                    },
                    showTip() {
                        const LIST = (PAGE_TIPS[config.reportId ? 'report' : config.page] || []).concat(TIPS);
                        if (document.hidden || this.open || this.loading) { this.tipTimer = setTimeout(() => this.showTip(), 15000); return; }
                        let i;
                        do { i = Math.floor(Math.random() * LIST.length); } while (LIST.length > 1 && i === this.lastTip);
                        this.lastTip = i;
                        this.tip = LIST[i];
                        try { sessionStorage.setItem('phishcore-cora-tips', String((parseInt(sessionStorage.getItem('phishcore-cora-tips') || '0', 10) || 0) + 1)); } catch (e) {}
                        clearTimeout(this.tipHide);
                        this.tipHide = setTimeout(() => { this.hideTip(); this.scheduleTip(false); }, 7000);
                    },
                    hideTip() { this.tip = ''; clearTimeout(this.tipHide); },
                    silence() {
                        this.hideTip();
                        clearTimeout(this.tipTimer);
                        try { sessionStorage.setItem('phishcore-cora-quiet', '1'); } catch (e) {}
                    },
                    save() { try { sessionStorage.setItem(KEY, JSON.stringify({ open: this.open, messages: this.messages.slice(-40) })); } catch (e) {} },
                    toggle() { this.open = !this.open; },
                    reset() { this.messages = []; this.showJump = false; this.suggestions = pick(); this.save(); this.$nextTick(() => this.grow()); },
                    focusAndScroll() {
                        this.$nextTick(() => {
                            this.scrollDown(false);
                            if (canAutoFocus() && this.$refs.box) { this.$refs.box.focus({ preventScroll: true }); }
                        });
                    },
                    scrollDown(smooth = true) {
                        const el = this.$refs.scroll;
                        if (!el) return;
                        el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                    },
                    onScroll() {
                        const el = this.$refs.scroll;
                        if (!el) return;
                        this.showJump = el.scrollHeight - el.scrollTop - el.clientHeight > 140;
                    },
                    jump() { this.scrollDown(true); },
                    grow() {
                        const el = this.$refs.box;
                        if (!el) return;
                        el.style.height = 'auto';
                        el.style.height = Math.min(el.scrollHeight, 128) + 'px';
                    },
                    ask(q) { this.input = q; this.send(); },

                    copy(i) {
                        const text = (this.messages[i] || {}).content || '';
                        const done = () => { this.copied = i; setTimeout(() => { if (this.copied === i) this.copied = null; }, 1500); };
                        // The clipboard API needs https; on a plain http site (like phishcore.test) fall back to the old way.
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).then(done).catch(() => {});
                            return;
                        }
                        try {
                            const ta = document.createElement('textarea');
                            ta.value = text;
                            ta.setAttribute('readonly', '');
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            done();
                        } catch (e) {}
                    },

                    async send() {
                        const text = this.input.trim();
                        if (!text || this.loading) return;
                        this.messages.push({ role: 'user', content: text });
                        this.input = '';
                        this.loading = true;
                        this.save();
                        this.$nextTick(() => { this.grow(); this.scrollDown(); });

                        const history = this.messages.filter(m => m.role === 'user' || m.role === 'assistant').slice(-12)
                            .map(m => ({ role: m.role, content: m.content }));

                        try {
                            const res = await fetch(config.url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.token },
                                body: JSON.stringify({ messages: history, report_id: config.reportId, page: config.page || null }),
                            });
                            let data = {};
                            try { data = await res.json(); } catch (e) {}

                            if (res.redirected) {
                                this.messages.push({ role: 'error', content: 'Your session has expired. Please refresh the page and sign in again.' });
                            } else if (res.ok && data.ok) {
                                this.messages.push({ role: 'assistant', content: data.reply });
                            } else if (res.status === 419 || res.status === 401) {
                                this.messages.push({ role: 'error', content: 'Your session has expired. Please refresh the page and try again.' });
                            } else if (res.status === 422) {
                                this.messages.push({ role: 'error', content: data.reply || 'That message could not be sent. Please shorten it and try again.' });
                            } else {
                                this.messages.push({ role: 'error', content: data.reply || 'Something went wrong. Please try again.' });
                            }
                        } catch (e) {
                            this.messages.push({ role: 'error', content: 'Could not reach the server. Check your connection and try again.' });
                        }

                        this.loading = false;
                        this.save();
                        this.$nextTick(() => this.scrollDown());
                    },
                };
            }
        </script>
    @endif
@endauth