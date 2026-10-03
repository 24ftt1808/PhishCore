@php
    $wide = $wide ?? false;

    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-400/10', 'text' => 'text-emerald-300', 'border' => 'border-emerald-400/25', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-400/10', 'text' => 'text-orange-300', 'border' => 'border-orange-400/25', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-400/10', 'text' => 'text-red-300', 'border' => 'border-red-400/25', 'label' => 'PHISHING'],
        'review' => ['bg' => 'bg-sky-400/10', 'text' => 'text-sky-300', 'border' => 'border-sky-400/25', 'label' => 'NEEDS REVIEW'],
    ];

    /* open on the tab that matches the last submission or the field that has an error */
    $startTab = 'url';
    if (old('email') || old('subject') || old('body') || $errors->has('email') || $errors->has('subject') || $errors->has('body')) $startTab = 'email';
    elseif (old('phone') || $errors->has('phone')) $startTab = 'phone';
    elseif ($errors->has('screenshot')) $startTab = 'screenshot';

    $tabIcons = [
        'url' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
        'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5zM10.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
    ];

    /* which scan tabs each check runs for, so the side panel and cards can light up the active ones */
    $checks = [
        ['URL structure', ['url', 'email']],
        ['SSL certificate', ['url', 'screenshot']],
        ['Domain age', ['url', 'email', 'screenshot']],
        ['Blacklists', ['url', 'screenshot']],
        ['Sender domain analysis', ['email']],
        ['Phone number heuristics', ['phone']],
        ['OCR text extraction', ['screenshot']],
    ];
    $checkCounts = [];
    foreach (['url', 'email', 'phone', 'screenshot'] as $t) {
        $checkCounts[$t] = collect($checks)->filter(fn ($c) => in_array($t, $c[1]))->count();
    }
    $tabLabels = ['url' => 'URL', 'email' => 'Email', 'phone' => 'Phone', 'screenshot' => 'Screenshot'];
@endphp

<style>
    .sc-card { background: linear-gradient(180deg, rgba(24, 40, 76, .62), rgba(14, 25, 50, .62)); border: 1px solid rgba(148, 163, 184, .18); border-radius: 1.5rem; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05); }
    .sc-card-sm { border-radius: 1.1rem; }
    .sc-lift { transition: transform .3s cubic-bezier(.34, 1.4, .64, 1), border-color .25s, opacity .3s, box-shadow .3s; }
    .sc-lift:hover { transform: translateY(-2px); border-color: rgba(56, 189, 248, .4); }

    .sc-input { width: 100%; border-radius: .85rem; padding: .9rem 1rem; font-size: .875rem; color: #fff; background: rgba(10, 22, 48, .8); border: 1px solid rgba(186, 230, 253, .15); transition: border-color .2s, box-shadow .2s; }
    .sc-input::placeholder { color: #8b9ab1; }
    .sc-input:focus { outline: none; border-color: rgba(56, 189, 248, .7); box-shadow: 0 0 0 3px rgba(56, 189, 248, .15); }

    /* hover glow for the four info cards: a soft light that follows the cursor, plus a faint halo */
    .sc-glow { position: relative; overflow: hidden; }
    .sc-glow::before { content: ""; position: absolute; inset: 0; pointer-events: none; opacity: 0; transition: opacity .3s;
                       background: radial-gradient(260px circle at var(--mx, 50%) var(--my, 50%), rgba(56, 189, 248, .2), transparent 65%); }
    .sc-glow > * { position: relative; z-index: 1; }
    .sc-glow:hover { box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05), 0 0 32px -8px rgba(56, 189, 248, .55); }
    .sc-glow:hover::before { opacity: 1; }

    /* tab slider: the outer pill travels with a soft overshoot, the inner one stretches toward where it is going */
    .sc-pill { transition: transform .5s cubic-bezier(.3, 1.25, .5, 1); will-change: transform; }
    .sc-pill-in { position: relative; width: 100%; height: 100%; border-radius: .8rem; will-change: transform;
                  background: linear-gradient(180deg, rgba(125, 211, 252, .24), rgba(56, 189, 248, .09));
                  border: 1px solid rgba(125, 211, 252, .36);
                  box-shadow: 0 0 18px -4px rgba(56, 189, 248, .45), inset 0 1px 0 rgba(255, 255, 255, .2); }
    .sc-pill-in::after { content: ""; position: absolute; left: 12%; right: 12%; top: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), transparent); }
    .sc-tile { display: grid; place-items: center; width: 2.25rem; height: 2.25rem; border-radius: .75rem; background: rgba(56, 189, 248, .12); border: 1px solid rgba(125, 211, 252, .28); color: #7dd3fc; }

    .sc-in { animation: sc-in .6s cubic-bezier(.2, .9, .3, 1) both; animation-delay: var(--d, 0s); }
    @keyframes sc-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

    /* rotating light sweep around the scanner box border while a scan runs */
    .scanner-border-glow { position: absolute; inset: -2px; border-radius: 1.65rem; padding: 2px; -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; opacity: 0; pointer-events: none; transition: opacity .3s ease; z-index: 0; }
    /* the light travels around the border: only the gradient angle animates, the box itself never moves */
    @property --scan-angle { syntax: '<angle>'; initial-value: 0deg; inherits: false; }
    .scanner-border-glow.is-scanning { opacity: 1; background: conic-gradient(from var(--scan-angle), transparent 0%, #38bdf8 10%, #93c5fd 16%, transparent 30%, transparent 100%); animation: scanner-rotate 2s linear infinite; }
    @keyframes scanner-rotate { to { --scan-angle: 360deg; } }
    .scanner-box.is-scanning { animation: scanner-pulse 1.8s ease-in-out infinite; }
    @keyframes scanner-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); } 50% { box-shadow: 0 0 36px 4px rgba(56, 189, 248, .3); } }
    .sc-sweep { animation: sc-sweep 1.1s ease-in-out infinite; }
    @keyframes sc-sweep { from { transform: translateX(-100%); } to { transform: translateX(300%); } }

    @media (prefers-reduced-motion: reduce) { .sc-in, .sc-pill, .sc-sweep, .scanner-border-glow.is-scanning, .scanner-box.is-scanning { animation: none; transition: none; } .sc-lift:hover { transform: none; } .sc-glow::before { display: none; } }
</style>

<div class="mb-10"
     x-data="{
        tab: '{{ $startTab }}',
        url: @js(old('url', '')),
        email: @js(old('email', '')),
        phone: @js(old('phone', '')),
        subject: @js(old('subject', '')),
        body: @js(old('body', '')),
        fileName: '', drag: false, more: {{ old('subject') || old('body') ? 'true' : 'false' }}, scanning: false,
        tabs: [
            { key: 'url', label: 'URL' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'screenshot', label: 'Screenshot' }
        ],
        icons: @js($tabIcons),
        counts: @js($checkCounts),
        get canScan() { return ({ url: this.url, email: this.email, phone: this.phone, screenshot: this.fileName }[this.tab] || '').trim() !== ''; },
        get needsProto() { return this.tab === 'url' && this.url.trim() !== '' && !/^https?:\/\//i.test(this.url.trim()); },
        get scanLabel() { return { url: 'Scan URL', email: 'Scan Email', phone: 'Scan Phone Number', screenshot: 'Scan Screenshot' }[this.tab]; },
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
            if (keep !== 'email') { this.email = ''; this.subject = ''; this.body = ''; }
            if (keep !== 'phone') this.phone = '';
            if (keep !== 'screenshot') this.removeFile();
        },
        removeFile() { this.fileName = ''; if (this.$refs.file) this.$refs.file.value = ''; }
     }"
     x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) scanning = false; })">

    <div class="grid {{ $wide ? 'xl:grid-cols-3' : '' }} gap-4">

        {{-- SCANNER --}}
        <div class="relative {{ $wide ? 'xl:col-span-2' : '' }}">
            <div class="scanner-border-glow" :class="{ 'is-scanning': scanning }"></div>

            <div class="scanner-box sc-card sc-in relative z-10 overflow-hidden h-full flex flex-col">
                <div class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 border-b border-white/10">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="sc-tile shrink-0"><img src="{{ asset('phishcore-logo-icon.png') }}" alt="" class="w-5 h-5 object-contain"></span>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm">PhishCore Scanner</p>
                            <p class="hidden sm:block text-[11px] text-slate-300 tracking-wide mt-0.5">AI-POWERED &middot; 7 DETECTION CHECKS &middot; ~2S RESULTS</p>
                        </div>
                    </div>
                    <span class="shrink-0 flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border"
                          :class="scanning ? 'text-sky-200 border-sky-300/30 bg-sky-400/10' : 'text-emerald-300 border-emerald-300/25 bg-emerald-400/10'">
                        <span class="relative flex w-1.5 h-1.5"><span class="absolute inset-0 rounded-full animate-ping opacity-60" :class="scanning ? 'bg-sky-300' : 'bg-emerald-400'"></span><span class="relative w-1.5 h-1.5 rounded-full" :class="scanning ? 'bg-sky-300' : 'bg-emerald-400'"></span></span>
                        <span x-text="scanning ? 'Scanning...' : 'Engine online'"></span>
                    </span>
                </div>
                <div x-show="scanning" x-cloak class="h-0.5 bg-sky-300/10 relative overflow-hidden"><span class="sc-sweep absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-sky-300 to-transparent"></span></div>

                <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="p-5 sm:p-6 flex-1 flex flex-col" @submit="if (!canScan) { $event.preventDefault() } else { scanning = true }">
                    @csrf

                    {{-- type selector --}}
                    <div class="relative grid grid-cols-4 p-1 rounded-2xl bg-[#07122b]/60 border border-sky-200/10" role="tablist">
                        <span class="sc-pill absolute top-1 bottom-1 left-1 pointer-events-none"
                              :style="`width:calc((100% - 0.5rem) / 4);transform:translateX(${idx * 100}%)`">
                            <span x-ref="blob" class="sc-pill-in block"></span>
                        </span>
                        <template x-for="t in tabs" :key="t.key">
                            <button type="button" role="tab" @click="pick(t.key)" :aria-selected="tab === t.key"
                                    :class="tab === t.key ? 'text-white' : 'text-slate-300 hover:text-white'"
                                    class="relative z-10 flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 py-2.5 px-1 rounded-xl text-[11px] sm:text-sm font-medium transition-colors duration-200 active:scale-95">
                                <svg class="w-4 h-4 shrink-0 transition-colors duration-200" :class="tab === t.key ? 'text-sky-300' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" :d="icons[t.key]" /></svg>
                                <span x-text="t.label"></span>
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
                                       class="sc-input !pr-24 !py-4">
                                <button type="button" :disabled="scanning"
                                        @click="navigator.clipboard.readText().then(text => { url = text; clearOthers('url') })"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10 hover:text-white transition disabled:opacity-50">Paste</button>
                            </div>
                            <p x-show="!needsProto" class="text-xs text-slate-300 mt-2">Include the complete URL starting with <code class="text-white">http://</code> or <code class="text-white">https://</code> for accurate results.</p>
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
                                   placeholder="scammer@suspicious-domain.com" class="sc-input !py-4">
                            @error('email')
                                <p class="mt-2 text-xs text-red-300">{{ $message }}</p>
                            @enderror

                            <button type="button" @click="more = !more" class="mt-3 flex items-center gap-1.5 text-xs text-sky-300 hover:text-sky-200 transition">
                                <svg class="w-3.5 h-3.5 transition-transform" :class="more ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                Add subject &amp; body <span class="text-slate-300">(optional, improves accuracy)</span>
                            </button>
                            <div x-show="more || subject || body" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-3 space-y-3">
                                <input type="text" name="subject" x-model="subject" :readonly="scanning" @input="clearOthers('email')"
                                       :class="{ 'opacity-50 pointer-events-none': scanning }"
                                       placeholder="Email subject, e.g. Urgent Transfer of Funds Required" class="sc-input">
                                @error('subject')
                                    <p class="text-xs text-red-300">{{ $message }}</p>
                                @enderror
                                <textarea name="body" x-model="body" :readonly="scanning" rows="4" @input="clearOthers('email')"
                                          :class="{ 'opacity-50 pointer-events-none': scanning }"
                                          placeholder="Paste the email body text here" class="sc-input resize-y min-h-[96px]"></textarea>
                                @error('body')
                                    <p class="text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div x-show="tab === 'phone'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                            <label class="block text-xs tracking-wide text-slate-300 mb-2">PHONE NUMBER</label>
                            <input type="text" name="phone" x-model="phone" :readonly="scanning" @input="clearOthers('phone')"
                                   :class="{ 'opacity-50 pointer-events-none': scanning }"
                                   placeholder="+673 XXX XXXX" class="sc-input !py-4">
                            @error('phone')
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
                                <span class="max-w-full truncate px-6" x-text="drag ? 'Drop it here' : (fileName || 'Drop an image or click to upload')"></span>
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
                    </div>

                    <div class="mt-auto pt-5">
                        <button type="submit" :disabled="scanning || !canScan" :class="!canScan && !scanning ? 'opacity-50 cursor-not-allowed saturate-50 shadow-none' : ''"
                                class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold shadow-[0_0_20px_-2px_rgba(56,189,248,0.45)] hover:brightness-110 active:scale-[0.98] transition disabled:opacity-70 disabled:cursor-not-allowed">
                            <svg x-show="!scanning" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                            <svg x-show="scanning" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="scanning ? 'Scanning...' : scanLabel"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- WHAT EACH SCAN CHECKS: the checks for the selected tab light up --}}
        <div class="sc-card sc-card-sm sc-in p-5 sm:p-6 flex flex-col" style="--d:.08s">
            <div class="flex items-center justify-between gap-3 mb-1">
                <p class="text-[11px] tracking-[0.14em] text-slate-300">WHAT THIS SCAN CHECKS</p>
                <span class="text-[11px] font-medium text-sky-200 bg-sky-400/10 border border-sky-300/25 px-2 py-0.5 rounded-full tabular-nums" x-text="counts[tab] + (counts[tab] === 1 ? ' check' : ' checks')"></span>
            </div>
            <p class="text-sm text-slate-300 mb-3">Highlighted checks run for the option you picked.</p>

            <ul class="space-y-1">
                @foreach ($checks as [$name, $for])
                    @php $forJs = json_encode($for); @endphp
                    <li class="flex items-center gap-3 rounded-xl px-3 py-2 border transition-colors duration-300"
                        :class="{{ $forJs }}.includes(tab) ? 'border-sky-300/30 bg-sky-400/10 text-white' : 'border-transparent text-slate-400'">
                        <span class="w-5 h-5 grid place-items-center rounded-full shrink-0 transition-colors duration-300"
                              :class="{{ $forJs }}.includes(tab) ? 'bg-emerald-400/20 text-emerald-300' : 'bg-white/5 text-slate-500'">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm">{{ $name }}</span>
                    </li>
                @endforeach
            </ul>

            <p class="text-xs text-slate-300 mt-auto pt-4 leading-relaxed">Fill in <span class="text-white font-medium">one</span> option, then run the scan. Results appear in a few seconds.</p>
        </div>
    </div>

<h2 class="text-xl font-bold text-white mt-10 mb-1">How PhishCore Checks Reports</h2>
<p class="text-sm text-slate-300 mb-5">Every scan runs through the detection layers relevant to what you submitted.</p>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
    @foreach ([
        ['SSL Certificate', 'Checks for a valid, trusted HTTPS certificate, and flags name mismatches or expiry.', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z', ['url', 'screenshot']],
        ['Domain Age', 'Domains registered in the last 30 days are a strong phishing signal.', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', ['url', 'email', 'screenshot']],
        ['URL & Sender Analysis', 'Spots IP-based addresses, lookalike brand names and odd patterns in links and email domains.', 'M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5', ['url', 'email']],
        ['Blacklist, Phone & OCR', 'Checks Google Safe Browsing, phone country codes, and text read from screenshots.', 'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z', ['url', 'phone', 'screenshot']],
    ] as $i => [$title, $text, $path, $applies])
        @php $appliesJs = json_encode($applies); @endphp
        <div class="sc-card sc-card-sm sc-lift sc-glow sc-in p-6 flex flex-col transition-opacity duration-300"
             @mousemove="const r = $el.getBoundingClientRect(); $el.style.setProperty('--mx', ($event.clientX - r.left) + 'px'); $el.style.setProperty('--my', ($event.clientY - r.top) + 'px')"
             :class="{{ $appliesJs }}.includes(tab) ? '' : 'opacity-70'"
             :style="{{ $appliesJs }}.includes(tab) ? 'border-color: rgba(125, 211, 252, .38)' : ''"
             style="--d: {{ 0.12 + $i * 0.06 }}s">
            <span class="sc-tile mb-4">
                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
            </span>
            <h3 class="text-white font-semibold mb-2">{{ $title }}</h3>
            <p class="text-sm text-slate-300 leading-relaxed">{{ $text }}</p>
            <p class="mt-auto pt-5 text-[10px] tracking-[0.12em] text-slate-500">
                @foreach ($applies as $t)
                    <span class="transition-colors duration-300" :class="tab === '{{ $t }}' ? 'text-sky-300 font-semibold' : ''">{{ strtoupper($tabLabels[$t]) }}</span>@if (! $loop->last)<span class="mx-1.5 text-slate-600">&middot;</span>@endif
                @endforeach
            </p>
        </div>
    @endforeach
</div>
</div>

@if (count($recentScans ?? []) > 0)
    <div class="flex items-center justify-between mb-4">
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
                    default => $scan->url,
                };
            @endphp
            <div class="sc-card sc-card-sm sc-lift relative flex items-center justify-between gap-3 pl-5 pr-4 sm:pr-5 py-4 overflow-hidden">
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
                    <a href="{{ route('scan.show', $scan) }}"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-sky-300/30 bg-sky-400/10 text-sky-200 text-xs font-medium hover:bg-sky-400/20 hover:border-sky-300/50 transition whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-7.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        Details
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif