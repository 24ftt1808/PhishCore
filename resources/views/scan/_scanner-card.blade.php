@php

    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-400/10', 'text' => 'text-emerald-300', 'border' => 'border-emerald-400/25', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-400/10', 'text' => 'text-orange-300', 'border' => 'border-orange-400/25', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-400/10', 'text' => 'text-red-300', 'border' => 'border-red-400/25', 'label' => 'PHISHING'],
        'review' => ['bg' => 'bg-sky-400/10', 'text' => 'text-sky-300', 'border' => 'border-sky-400/25', 'label' => 'NEEDS REVIEW'],
    ];

    /* open on the tab that matches the last submission or the field that has an error */
    $startTab = auth()->user()?->defaultScanTab() ?? 'url';
    if (old('email') || old('subject') || old('body') || old('email_headers') || $errors->has('eml_file') || $errors->has('email') || $errors->has('subject') || $errors->has('body') || $errors->has('email_headers')) $startTab = 'email';
    elseif (old('phone') || $errors->has('phone') || $errors->has('phone_country')) $startTab = 'phone';
    elseif ($errors->has('screenshot')) $startTab = 'screenshot';
    elseif ($errors->has('file')) $startTab = 'file';

    $tabIcons = [
        'url' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5zM10.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
        'file' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    ];

    /* which scan tabs each check runs for, so the side panel and cards can light up the active ones */
    $checks = [
        ['URL structure', ['url', 'email'], 'Spots IP-based addresses, lookalike brand names and odd patterns in web links.'],
        ['SSL certificate', ['url', 'screenshot'], 'Checks for a valid, trusted HTTPS certificate, and flags name mismatches or expiry.'],
        ['Domain age', ['url', 'email', 'screenshot'], 'Domains registered in the last 30 days are a strong phishing signal.'],
        ['Blacklists', ['url', 'screenshot'], 'Checks Google Safe Browsing and other threat databases for known phishing pages.'],
        ['Sender domain analysis', ['email'], 'Checks whether the sender\'s email domain copies a known brand or looks unusual.'],
        ['Phone number heuristics', ['phone'], 'Checks the number is valid and what kind of line it is, whether other PhishCore users have reported it, and a reputation lookup when available.'],
        ['File type', ['file'], 'Checks that the file really is what its name says, because scam apps often pretend to be documents or pictures.'],
        ['File fingerprint', ['file'], 'Looks up the file\'s fingerprint, not the file itself, in VirusTotal\'s database of known bad files.'],
        ['App permissions', ['file'], 'For Android apps (APK), lists what the app asks to do, such as read your text messages or control your screen.'],
        ['PDF links and actions', ['file'], 'Finds links inside a PDF and checks them, and flags PDFs that try to start programs or run scripts.'],
        ['PDF wording', ['file'], 'Reads the words in a PDF and looks for scam wording, such as urgent demands and requests for codes or bank details. Nothing is sent to an AI service.'],
        ['Previous reports for this file', ['file'], 'Checks whether PhishCore has already found this exact file to be phishing in recent scans by other people.'],
        ['OCR text extraction', ['screenshot'], 'Reads the text in your screenshot to find links, phone numbers and scam wording, then checks them.'],
    ];
    $checkCounts = [];
    foreach (['url', 'email', 'phone', 'screenshot', 'file'] as $t) {
        $checkCounts[$t] = collect($checks)->filter(fn ($c) => in_array($t, $c[1]))->count();
    }
@endphp

<style>
    .sc-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.5rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
    .sc-card-sm { border-radius: 1.1rem; }
    .sc-card, .sc-card * { min-width: 0; }

    /* five tabs on a phone: tighter padding and a slightly smaller label so "Screenshot" always fits */
    .sc-tab-label { white-space: nowrap; }
    @media (max-width: 639px) {
        .sc-tab { padding-left: 0 !important; padding-right: 0 !important; }
        .sc-tab-label { font-size: 10.5px; letter-spacing: -0.01em; }
    }
    @media (max-width: 359px) {
        .sc-tab-label { font-size: 9.5px; letter-spacing: -0.02em; }
    }

    /* phones: the country list sits above the number, so both have the full width */
    @media (max-width: 639px) {
        .sc-phone-row { flex-direction: column; }
        .sc-phone-row .sc-phone-country { width: 100% !important; }
    }

    /* phones: shorter wording */
    .sc-narrow, .sc-narrow-p { display: none; }
    @media (max-width: 639px) {
        .sc-wide { display: none; }
        .sc-narrow { display: inline; }
        .sc-narrow-p { display: block; }
    }

    /* the list of checks stays folded until it is wanted: it slides open, and each check that runs fades in one after the other */
    .sc-checks-toggle { cursor: pointer; }
    .sc-checks-body { display: grid; grid-template-rows: 0fr; opacity: 0; visibility: hidden; transition: grid-template-rows .5s cubic-bezier(.2, .9, .3, 1), opacity .35s ease, visibility 0s linear .5s; }
    .sc-checks-body.is-open { grid-template-rows: 1fr; opacity: 1; visibility: visible; transition-delay: 0s; }
    .sc-checks-inner { overflow: hidden; min-height: 0; }
    .sc-check-off { display: none; }
    .sc-checks-body.is-open .sc-check-item { animation: sc-check-in .5s cubic-bezier(.2, .9, .3, 1) backwards; animation-delay: calc(var(--i, 0) * 55ms + 80ms); }
    .sc-checks-body.is-open .sc-check-tick { animation: sc-check-pop .45s cubic-bezier(.3, 1.6, .5, 1) backwards; animation-delay: calc(var(--i, 0) * 55ms + 260ms); }
    .sc-check-item:hover { transform: translateY(-2px); border-color: rgba(56, 189, 248, .45); box-shadow: 0 10px 24px -14px rgba(56, 189, 248, .6); }
    @keyframes sc-check-in { from { opacity: 0; transform: translateY(12px) scale(.97); } to { opacity: 1; transform: none; } }
    @keyframes sc-check-pop { from { opacity: 0; transform: scale(.2) rotate(-25deg); } to { opacity: 1; transform: none; } }
    .sc-bump { animation: sc-bump .45s cubic-bezier(.3, 1.6, .5, 1); }
    @keyframes sc-bump { 0% { transform: scale(1); } 40% { transform: scale(1.22); } 100% { transform: scale(1); } }

    /* the text inside the upload boxes wraps instead of pushing the card wider than the screen */
    .sc-dz-text { max-width: 100%; padding: 0 .5rem; text-align: center; overflow-wrap: anywhere; }
    @media (min-width: 640px) { .sc-dz-text { padding: 0 1.5rem; } }
    .sc-lift { transition: transform .3s cubic-bezier(.34, 1.4, .64, 1), border-color .25s, opacity .3s, box-shadow .3s; }
    .sc-lift:hover { transform: translateY(-2px); border-color: rgba(56, 189, 248, .4); }

    .sc-input { width: 100%; border-radius: .85rem; padding: .9rem 1rem; font-size: .875rem; color: #fff; background: rgba(10, 22, 48, .8); border: 1px solid rgba(186, 230, 253, .15); transition: border-color .2s, box-shadow .2s; }
    .sc-input::placeholder { color: #8b9ab1; }
    .sc-input:focus { outline: none; border-color: rgba(56, 189, 248, .7); box-shadow: 0 0 0 3px rgba(56, 189, 248, .15); }


    /* tab slider: the outer pill travels with a soft overshoot, the inner one stretches toward where it is going */
    .sc-pill { transition: transform .5s cubic-bezier(.3, 1.25, .5, 1); will-change: transform; }
    .sc-pill-in { position: relative; width: 100%; height: 100%; border-radius: .8rem; will-change: transform;
                  background: linear-gradient(180deg, rgba(125, 211, 252, .24), rgba(56, 189, 248, .09));
                  border: 1px solid rgba(125, 211, 252, .36);
                  box-shadow: 0 0 18px -4px rgba(56, 189, 248, .45), inset 0 1px 0 rgba(255, 255, 255, .2); }
    .sc-pill-in::after { content: ""; position: absolute; left: 12%; right: 12%; top: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), transparent); }
    .sc-tile { display: grid; place-items: center; width: 2.25rem; height: 2.25rem; border-radius: .75rem; background: rgba(56, 189, 248, .12); border: 1px solid rgba(125, 211, 252, .28); color: #7dd3fc; }

    .sc-in { animation: sc-in .6s cubic-bezier(.2, .9, .3, 1) backwards; animation-delay: var(--d, 0s); }
    @keyframes sc-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

    /* rotating light sweep around the scanner box border while a scan runs */
    .scanner-border-glow { position: absolute; inset: -2px; border-radius: 1.65rem; padding: 2px; -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; opacity: 0; pointer-events: none; transition: opacity .3s ease; z-index: 0; }
    /* the light travels around the border: only the gradient angle animates, the box itself never moves */
    @property --scan-angle { syntax: '<angle>'; initial-value: 0deg; inherits: false; }
    .scanner-border-glow.is-scanning { opacity: 1; background: conic-gradient(from var(--scan-angle), transparent 0%, #38bdf8 10%, #93c5fd 16%, transparent 30%, transparent 100%); filter: drop-shadow(0 0 6px rgba(56, 189, 248, .6)); animation: scanner-rotate 2s linear infinite; }
    @keyframes scanner-rotate { to { --scan-angle: 360deg; } }
    .scanner-box.is-scanning { animation: scanner-pulse 1.8s ease-in-out infinite; }
    @keyframes scanner-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); } 50% { box-shadow: 0 0 30px 2px rgba(56, 189, 248, .22); } }
    .sc-sweep { animation: sc-sweep 1.1s ease-in-out infinite; }
    @keyframes sc-sweep { from { transform: translateX(-100%); } to { transform: translateX(300%); } }

    @media (prefers-reduced-motion: reduce) { .sc-in, .sc-pill, .sc-sweep, .scanner-border-glow.is-scanning, .scanner-box.is-scanning { animation: none; transition: none; } .sc-lift:hover, .sc-check-item:hover { transform: none; } .sc-checks-body, .sc-checks-body.is-open { transition: none; } .sc-checks-body.is-open .sc-check-item, .sc-checks-body.is-open .sc-check-tick, .sc-bump { animation: none; } }
</style>

<div class="mb-10"
     x-data="{
        tab: '{{ $startTab }}',
        url: @js(old('url', '')),
        email: @js(old('email', '')),
        phone: @js(old('phone', '')),
        phoneCountry: @js(old('phone_country', \App\Support\PhoneCountries::DEFAULT_REGION)),
        subject: @js(old('subject', '')),
        body: @js(old('body', '')),
        headers: @js(old('email_headers', '')),
        emlName: '',
        checksOpen: false, fileName: '', docName: '', docDrag: false, drag: false, more: {{ old('subject') || old('body') || old('email_headers') ? 'true' : 'false' }}, scanning: false,
        tabs: [
            { key: 'url', label: 'URL' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'screenshot', label: 'Screenshot' },
            { key: 'file', label: 'File' }
        ],
        icons: @js($tabIcons),
        counts: @js($checkCounts),
        checkFor: @js(collect($checks)->map(fn ($c) => $c[1])->values()),
        rank(i) { return this.checkFor.slice(0, i).filter(f => f.includes(this.tab)).length; },
        get canScan() { return ({ url: this.url, email: this.email || this.emlName || (this.looksRaw ? 'raw' : ''), phone: this.phone, screenshot: this.fileName, file: this.docName }[this.tab] || '').trim() !== ''; },
        get looksRaw() { return /^\s*[!-9;-~]+:[ \t]*\S/.test(this.body) && /^(received|received-spf|authentication-results|return-path|message-id|mime-version|dkim-signature|delivered-to):/im.test(this.body) && /^from:/im.test(this.body); },
        get needsProto() { return this.tab === 'url' && this.url.trim() !== '' && !/^https?:\/\//i.test(this.url.trim()); },
        get scanLabel() { return { url: 'Scan URL', email: 'Scan Email', phone: 'Scan Phone Number', screenshot: 'Scan Screenshot', file: 'Scan File' }[this.tab]; },
        get idx() { return this.tabs.findIndex(t => t.key === this.tab); },
        pick(k) {
            if (this.scanning || k === this.tab) return;
            const from = this.idx;
            this.tab = k;
            this.clearOthers(k);
            this.stretch(from, this.idx);
        },
        stretch(from, to) {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const d = Math.abs(to - from), right = to > from;
            const o = right ? 'left center' : 'right center';
            this.$refs.blob?.animate([
                { transform: 'scale(1, 1)', transformOrigin: o },
                { transform: `scale(${1 + 0.2 * d}, .93)`, transformOrigin: o, offset: .35 },
                { transform: `scale(${1 - 0.03 * d}, 1.04)`, transformOrigin: o, offset: .7 },
                { transform: 'scale(1, 1)', transformOrigin: o }
            ], { duration: 560, easing: 'cubic-bezier(.3, 1.2, .5, 1)' });
        },
        clearOthers(keep) {
            if (keep !== 'url') this.url = '';
            if (keep !== 'email') { this.email = ''; this.subject = ''; this.body = ''; this.headers = ''; this.removeEml(); }
            if (keep !== 'phone') this.phone = '';
            if (keep !== 'screenshot') this.removeFile();
            if (keep !== 'file') this.removeDoc();
        },
        removeFile() { this.fileName = ''; if (this.$refs.file) this.$refs.file.value = ''; },
        removeDoc() { this.docName = ''; if (this.$refs.doc) this.$refs.doc.value = ''; },
        removeEml() { this.emlName = ''; if (this.$refs.eml) this.$refs.eml.value = ''; }
     }"
     x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) scanning = false; })">

    {{-- one column that can never grow wider than the screen, whatever is inside it --}}
    <div class="grid gap-4" style="grid-template-columns: minmax(0, 1fr)">

        {{-- SCANNER --}}
        <div class="relative">
            <div class="scanner-border-glow" :class="{ 'is-scanning': scanning }"></div>

            <div class="scanner-box sc-card sc-in relative z-10 overflow-hidden h-full flex flex-col" :class="{ 'is-scanning': scanning }">
                <div class="flex items-center justify-between gap-3 px-5 sm:px-6 py-3 border-b border-white/10">
                    <p class="min-w-0 truncate text-white font-semibold text-sm">PhishCore Scanner</p>
                    <span class="shrink-0 inline-flex items-center gap-2 text-xs font-medium pl-2.5 pr-3 py-1.5 rounded-full border transition-colors duration-300"
                          :class="scanning ? 'text-sky-200 border-sky-300/30 bg-sky-400/10' : 'text-emerald-200 border-emerald-300/25 bg-emerald-400/10'"
                          role="status" aria-live="polite">
                        <span class="relative flex w-2 h-2">
                            <span class="absolute inset-0 rounded-full animate-ping opacity-60" :class="scanning ? 'bg-sky-300' : 'bg-emerald-400'"></span>
                            <span class="relative w-2 h-2 rounded-full" :class="scanning ? 'bg-sky-300' : 'bg-emerald-400'"></span>
                        </span>
                        <span x-text="scanning ? 'Scanning...' : 'Ready to scan'"></span>
                    </span>
                </div>
                <div x-show="scanning" x-cloak class="h-0.5 bg-sky-300/10 relative overflow-hidden"><span class="sc-sweep absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-sky-300 to-transparent"></span></div>

                <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="p-5 sm:p-6 flex-1 flex flex-col" @submit="if (!canScan) { $event.preventDefault() } else { scanning = true }">
                    @csrf
                    <x-scan-guard part="trap" />

                    {{-- type selector --}}
                    <div class="relative grid grid-cols-5 p-1 rounded-2xl bg-[#07122b]/60 border border-sky-200/10" role="tablist">
                        <span class="sc-pill absolute top-1 bottom-1 left-1 pointer-events-none"
                              :style="`width:calc((100% - 0.5rem) / 5);transform:translateX(${idx * 100}%)`">
                            <span x-ref="blob" class="sc-pill-in block"></span>
                        </span>
                        <template x-for="t in tabs" :key="t.key">
                            <button type="button" role="tab" @click="pick(t.key)" :aria-selected="tab === t.key"
                                    :class="tab === t.key ? 'text-white' : 'text-slate-300 hover:text-white'"
                                    class="sc-tab relative z-10 flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 py-2.5 px-1 rounded-xl text-[11px] sm:text-sm font-medium transition-colors duration-200 active:scale-95">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200" :class="tab === t.key ? 'text-sky-300' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" :d="icons[t.key]" /></svg>
                                <span class="sc-tab-label" x-text="t.label"></span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-5">
                        {{-- URL --}}
                        <div x-show="tab === 'url'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="block text-xs tracking-wide text-slate-300 mb-2">WEBSITE URL</label>
                            <div class="relative">
                                <input type="text" name="url" x-model="url" :readonly="scanning" @input="clearOthers('url')"
                                       :class="{ 'opacity-50 pointer-events-none': scanning }"
                                       placeholder="Enter or paste a website URL, e.g. https://example.com"
                                       :placeholder="window.innerWidth < 640 ? 'Paste a website URL' : 'Enter or paste a website URL, e.g. https://example.com'"
                                       class="sc-input !pr-24 !py-4">
                                <button type="button" :disabled="scanning"
                                        @click="navigator.clipboard.readText().then(text => { url = text; clearOthers('url') })"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10 hover:text-white transition disabled:opacity-50">Paste</button>
                            </div>
                            <p x-show="!needsProto" class="text-xs text-slate-300 mt-2"><span class="sc-wide">Include the complete URL starting with <code class="text-white">http://</code> or <code class="text-white">https://</code> for accurate results.</span><span class="sc-narrow">Start with <code class="text-white">https://</code> for accurate results.</span></p>
                            <p x-show="needsProto" x-cloak class="flex items-center gap-1.5 text-xs text-amber-300 mt-2">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                This link has no <code class="text-white">http://</code> or <code class="text-white">https://</code> at the start. Add it for more accurate results.
                            </p>
                            @error('url')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div x-show="tab === 'email'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="block text-xs tracking-wide text-slate-300 mb-2">SENDER EMAIL</label>
                            <input type="text" name="email" x-model="email" :readonly="scanning" @input="clearOthers('email')"
                                   :class="{ 'opacity-50 pointer-events-none': scanning }"
                                   placeholder="scammer@suspicious-domain.com (not needed if you upload or paste the whole email)" class="sc-input !py-4">
                            @error('email')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs">
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10 hover:text-white transition cursor-pointer"
                                       :class="{ 'opacity-50 pointer-events-none': scanning }">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                                    <span class="truncate max-w-[16rem]" x-text="emlName || 'Upload a saved email (.eml)'"></span>
                                    <input type="file" name="eml_file" x-ref="eml" accept=".eml,.txt,message/rfc822" class="sr-only"
                                           @change="emlName = $event.target.files[0]?.name ?? ''; if (emlName) clearOthers('email')">
                                </label>
                                <button type="button" x-show="emlName" x-cloak @click="removeEml()" class="text-sky-300 hover:text-sky-200 transition">Remove</button>
                                <span class="text-slate-400">or paste the whole email into the message box below. Either way the sender, subject and checks are filled in for you.</span>
                            </div>
                            @error('eml_file')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror


                            <button type="button" @click="more = !more" class="mt-3 flex items-center gap-1.5 text-xs text-sky-300 hover:text-sky-200 transition">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="more ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                Add subject &amp; body <span class="text-slate-300">(optional, improves accuracy)</span>
                            </button>
                            <div x-show="more || subject || body || headers" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-3 space-y-3">
                                <input type="text" name="subject" x-model="subject" :readonly="scanning" @input="clearOthers('email')"
                                       :class="{ 'opacity-50 pointer-events-none': scanning }"
                                       placeholder="Email subject, e.g. Urgent Transfer of Funds Required" class="sc-input">
                                @error('subject')
                                    <p class="text-xs text-red-300">{{ $message }}</p>
                                @enderror
                                <textarea name="body" x-model="body" :readonly="scanning" rows="4" @input="clearOthers('email')"
                                          :class="{ 'opacity-50 pointer-events-none': scanning }"
                                          placeholder="Paste the email body text, or the whole email from Show original" class="sc-input resize-y min-h-[96px]"></textarea>
                                @error('body')
                                    <p class="text-xs text-red-300">{{ $message }}</p>
                                @enderror
                                <textarea name="email_headers" x-model="headers" :readonly="scanning" rows="3" @input="clearOthers('email')"
                                          :class="{ 'opacity-50 pointer-events-none': scanning }"
                                          placeholder="Full email headers (optional): checks the email really came from its sender" class="sc-input resize-y min-h-[72px] font-mono text-xs"></textarea>
                                <p class="text-xs text-slate-400">In Gmail, open the email, choose <span class="text-slate-200">&#8942; &rarr; Show original</span> and copy the top part. In Outlook, open <span class="text-slate-200">File &rarr; Properties</span> and copy the "Internet headers". They are only used for this check and are not saved.</p>
                                @error('email_headers')
                                    <p class="text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div x-show="tab === 'phone'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="block text-xs tracking-wide text-slate-300 mb-2">PHONE NUMBER</label>
                            <div class="sc-phone-row flex gap-3">
                                <select name="phone_country" x-model="phoneCountry" aria-label="Country of the phone number"
                                        :class="{ 'opacity-50 pointer-events-none': scanning }"
                                        class="sc-phone-country sc-input !py-4 !w-40 sm:!w-60 shrink-0 truncate" style="color-scheme: dark">
                                    @foreach (\App\Support\PhoneCountries::all() as $country)
                                        <option value="{{ $country['code'] }}">{{ $country['code'] }} {{ $country['name'] }} (+{{ $country['dial'] }})</option>
                                    @endforeach
                                </select>
                                <input type="text" name="phone" x-model="phone" :readonly="scanning" @input="clearOthers('phone')"
                                       :class="{ 'opacity-50 pointer-events-none': scanning }"
                                       placeholder="Phone number" class="sc-input !py-4 min-w-0 flex-1">
                            </div>
                            <p class="mt-2 text-xs text-slate-400"><span class="sc-wide">Numbers typed with a leading + and country code keep that country, whatever is selected.</span><span class="sc-narrow">A number starting with + and a country code keeps that country.</span></p>
                            @error('phone')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                            @error('phone_country')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Screenshot --}}
                        <div x-show="tab === 'screenshot'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="block text-xs tracking-wide text-slate-300 mb-2">UPLOAD SCREENSHOT</label>
                            <label class="relative flex flex-col items-center justify-center gap-2 w-full rounded-2xl border border-dashed px-4 py-9 text-sm cursor-pointer transition"
                                   :class="[
                                       drag ? 'border-sky-300/70 bg-sky-400/[0.08] text-sky-100' : (fileName ? 'border-emerald-300/40 bg-emerald-400/[0.05] text-slate-100' : 'border-white/20 bg-black/20 text-slate-300 hover:border-sky-300/50 hover:bg-black/30'),
                                       scanning ? 'opacity-50 pointer-events-none' : ''
                                   ]"
                                   @dragenter="drag = true" @dragleave="drag = false" @drop="drag = false">
                                <svg x-show="!fileName" class="w-7 h-7 text-sky-300/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                <svg x-show="fileName" x-cloak class="w-7 h-7 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                <span class="sc-dz-text" x-text="drag ? 'Drop it here' : (fileName || 'Drop an image or click to upload')"></span>
                                <span x-show="!fileName" class="text-xs text-slate-400">PNG or JPG, max 5MB</span>
                                <input type="file" name="screenshot" x-ref="file" accept="image/png, image/jpeg, image/jpg, .png, .jpg, .jpeg"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       @change="fileName = $event.target.files[0]?.name ?? ''; if (fileName) clearOthers('screenshot')">
                                <button type="button" x-show="fileName" x-cloak @click.prevent.stop="removeFile()"
                                        class="absolute right-3 top-3 z-10 text-xs px-2.5 py-1 rounded-lg border border-white/10 bg-black/30 text-slate-200 hover:text-white hover:bg-black/50 transition">Remove</button>
                            </label>
                            @error('screenshot')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- File (APK or PDF) --}}
                        <div x-show="tab === 'file'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="block text-xs tracking-wide text-slate-300 mb-2">UPLOAD AN APP OR PDF</label>
                            <label class="relative flex flex-col items-center justify-center gap-2 w-full rounded-2xl border border-dashed px-4 py-9 text-sm cursor-pointer transition"
                                   :class="[
                                       docDrag ? 'border-sky-300/70 bg-sky-400/[0.08] text-sky-100' : (docName ? 'border-emerald-300/40 bg-emerald-400/[0.05] text-slate-100' : 'border-white/20 bg-black/20 text-slate-300 hover:border-sky-300/50 hover:bg-black/30'),
                                       scanning ? 'opacity-50 pointer-events-none' : ''
                                   ]"
                                   @dragenter="docDrag = true" @dragleave="docDrag = false" @drop="docDrag = false">
                                <svg x-show="!docName" class="w-7 h-7 text-sky-300/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                <svg x-show="docName" x-cloak class="w-7 h-7 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                <span class="sc-dz-text" x-text="docDrag ? 'Drop it here' : (docName || 'Drop an APK or PDF, or click to upload')"></span>
                                <span x-show="!docName" class="text-xs text-slate-400">APK or PDF, max 20MB</span>
                                <input type="file" name="file" x-ref="doc" accept=".apk,.pdf,application/pdf,application/vnd.android.package-archive"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                       @change="docName = $event.target.files[0]?.name ?? ''; if (docName) clearOthers('file')">
                                <button type="button" x-show="docName" x-cloak @click.prevent.stop="removeDoc()"
                                        class="absolute right-3 top-3 z-10 text-xs px-2.5 py-1 rounded-lg border border-white/10 bg-black/30 text-slate-200 hover:text-white hover:bg-black/50 transition">Remove</button>
                            </label>
                            <p class="mt-2 text-xs text-slate-400">The file is never opened or run. Only its fingerprint is checked with VirusTotal, and the file is deleted as soon as the scan finishes.</p>
                            @error('file')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-auto pt-5">
                        <x-scan-guard part="check" />

                        <button type="submit" :disabled="scanning || !canScan" :class="!canScan && !scanning ? 'opacity-50 cursor-not-allowed saturate-50 shadow-none' : ''"
                                class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold shadow-[0_0_20px_-2px_rgba(56,189,248,0.45)] hover:brightness-110 active:scale-[0.98] transition disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg x-show="!scanning" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                            <svg x-show="scanning" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="scanning ? 'Scanning...' : scanLabel"></span>
                        </button>
                        @if (app(\App\Services\AiTextCheck::class)->enabled())
                            <p data-ai-privacy x-show="['url', 'email', 'screenshot'].includes(tab)" class="mt-3 text-center text-[11px] leading-relaxed text-slate-400">
                                <span class="sc-wide">For emails, messages and web pages, the text may be sent to Google's Gemini AI for a second opinion. Please don't paste anything private, like passwords or bank details.</span>
                                <span class="sc-narrow">The text may go to Google's Gemini AI for a second opinion. Don't paste passwords or bank details.</span>
                            </p>
                        @endif
                        <p data-phone-reports-note x-show="tab === 'phone'" x-cloak class="mt-3 text-center text-[11px] leading-relaxed text-slate-400">This check relies on reports from other PhishCore users. A number with no reports isn't necessarily safe.</p>
                    </div>
                </form>
            </div>
        </div>

        {{-- WHAT EACH SCAN CHECKS: tiles for the selected tab light up --}}
        <div class="sc-card sc-card-sm sc-in px-5 py-4 sm:px-6" style="--d:.08s">
            <button type="button" class="sc-checks-toggle flex w-full items-center justify-between gap-3 text-left" @click="checksOpen = !checksOpen" :aria-expanded="checksOpen.toString()">
                <p class="text-[11px] tracking-[0.14em] text-slate-300">WHAT THIS SCAN CHECKS</p>
                <span class="flex items-center gap-2">
                    <span class="inline-block text-[11px] font-medium text-sky-200 bg-sky-400/10 border border-sky-300/25 px-2 py-0.5 rounded-full tabular-nums" x-effect="counts[tab]; $el.classList.remove('sc-bump'); void $el.offsetWidth; $el.classList.add('sc-bump')" x-text="counts[tab] + (counts[tab] === 1 ? ' check' : ' checks')"></span>
                    <svg class="w-4 h-4 text-slate-300 transition-transform duration-200" :class="checksOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </span>
            </button>

            <div class="sc-checks-body" :class="checksOpen ? 'is-open' : ''">
            <div class="sc-checks-inner">
            <p class="text-xs text-slate-400 mt-3 mb-3">The checks that run for the option you picked.</p>

            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($checks as [$name, $for, $text])
                    @php $forJs = json_encode($for); @endphp
                    <li class="sc-check-item rounded-xl px-3 py-3 border transition duration-300"
                        :style="'--i:' + rank({{ $loop->index }})"
                        :class="{{ $forJs }}.includes(tab) ? 'border-sky-300/30 bg-sky-400/10 text-white' : 'sc-check-off border-white/5 text-slate-400'">
                        <div class="flex items-center gap-2.5">
                            <span class="sc-check-tick w-5 h-5 grid place-items-center rounded-full shrink-0 transition-colors duration-300"
                                  :class="{{ $forJs }}.includes(tab) ? 'bg-emerald-400/20 text-emerald-300' : 'bg-white/5 text-slate-500'">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </span>
                            <span class="text-sm font-medium leading-tight">{{ $name }}</span>
                        </div>
                        <p class="mt-1.5 pl-[30px] text-xs leading-relaxed transition-colors duration-300"
                           :class="{{ $forJs }}.includes(tab) ? 'text-slate-300' : 'text-slate-500'">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
            </div>
            </div>
        </div>
    </div>
</div>

@if (count($recentScans ?? []) > 0)
    <div class="sc-in flex items-center justify-between mb-4" style="--d:.16s">
        <div>
            <h2 class="text-lg font-bold text-white">Recently Scanned</h2>
            <p class="text-sm text-slate-300">Your three most recent reports</p>
        </div>
        <a href="{{ route('scan.history') }}" class="flex items-center gap-1 text-sky-300 text-sm font-medium hover:text-sky-200 transition">
            View all
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        </a>
    </div>
    <div class="space-y-3">
        @php
            $verdictAccent = ['clean' => 'bg-emerald-400', 'suspicious' => 'bg-orange-400', 'phishing' => 'bg-red-400', 'review' => 'bg-sky-400'];
            $verdictScoreColor = ['clean' => 'text-emerald-300', 'suspicious' => 'text-orange-300', 'phishing' => 'text-red-300', 'review' => 'text-sky-300'];
        @endphp
        @foreach ($recentScans as $scan)
            @php
                $verdict = $scan->analyses->first()->verdict ?? 'clean';
                $score = $scan->analyses->first()->risk_score ?? 0;
                $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                $accent = $verdictAccent[$verdict] ?? $verdictAccent['clean'];
                $scoreColor = $verdictScoreColor[$verdict] ?? $verdictScoreColor['clean'];
                $iconPath = $tabIcons[$scan->type] ?? $tabIcons['url'];
                $label = match ($scan->type) {
                    'email' => $scan->sender_email,
                    'phone' => $scan->phone_number,
                    'screenshot' => 'Uploaded screenshot',
                    'file' => $scan->file_name ?: 'Uploaded file',
                    default => $scan->url,
                };
            @endphp
            <div class="sc-card sc-card-sm sc-lift sc-in relative flex items-center justify-between gap-3 pl-5 pr-4 sm:pr-5 py-4 overflow-hidden" style="--d:{{ number_format(0.22 + $loop->index * 0.07, 2) }}s">
                <span class="absolute left-0 top-0 bottom-0 w-1 {{ $accent }}"></span>
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-9 h-9 shrink-0 grid place-items-center rounded-lg bg-white/5 border border-white/10 text-sky-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-white truncate">{{ $label }}</p>
                        <p class="text-xs text-slate-300"><span class="uppercase tracking-wide text-slate-400">{{ $scan->type }}</span> &middot; {{ $scan->created_at->format('Y-m-d H:i') }} &middot; {{ $scan->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 sm:gap-5 shrink-0">
                    <span class="hidden sm:inline text-xs font-medium px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} border {{ $badge['border'] }}">{{ $badge['label'] }}</span>
                    <div class="text-right">
                        <p class="text-lg font-bold {{ $scoreColor }} leading-none tabular-nums">{{ $score }}</p>
                        <span class="hidden sm:block w-14 h-1 rounded-full bg-white/10 overflow-hidden mt-1.5 ml-auto"><span class="block h-full rounded-full {{ $accent }}" style="width: {{ min(max((int) $score, 0), 100) }}%"></span></span>
                        <p class="text-[10px] text-slate-400 tracking-wide mt-1">SCORE</p>
                    </div>
                    <a href="{{ route('scan.show', $scan) }}" aria-label="Details"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-sky-300/30 bg-sky-400/10 text-sky-200 text-xs font-medium hover:bg-sky-400/20 hover:border-sky-300/50 transition whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-7.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        <span class="hidden sm:inline">Details</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif