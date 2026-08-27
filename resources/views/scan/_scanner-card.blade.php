@php
    $verdictBadge = [
        'clean' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'border' => 'border-emerald-500/20', 'label' => 'SAFE'],
        'suspicious' => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'border' => 'border-orange-500/20', 'label' => 'SUSPICIOUS'],
        'phishing' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'border' => 'border-red-500/20', 'label' => 'PHISHING'],
        'review' => ['bg' => 'bg-sky-500/10', 'text' => 'text-sky-400', 'border' => 'border-sky-500/20', 'label' => 'NEEDS REVIEW'],
    ];
@endphp

<div class="relative mb-8" x-data="{
        url: @js(old('url', '')),
        email: @js(old('email', '')),
        phone: @js(old('phone', '')),
        subject: @js(old('subject', '')),
        body: @js(old('body', '')),
        fileName: '',
        scanning: false,
        clearOthers(keep) {
            if (keep !== 'url') this.url = '';
            if (keep !== 'email') { this.email = ''; this.subject = ''; this.body = ''; }
            if (keep !== 'phone') this.phone = '';
            if (keep !== 'screenshot') this.fileName = '';
        }
    }" x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) scanning = false; })">

    {{-- rotating light sweep, sits behind the card --}}
    <div class="scanner-border-glow" :class="{ 'is-scanning': scanning }"></div>

    <div class="scanner-box relative z-10 bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden shadow-[0_0_60px_-15px_rgba(56,189,248,0.25)]"
         :class="{ 'is-scanning': scanning }">

        <div class="absolute -left-20 -top-20 w-64 h-64 rounded-full bg-sky-500/15 blur-3xl pointer-events-none"></div>
        <div class="absolute -right-20 -bottom-20 w-64 h-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shrink-0 shadow-[0_0_16px_0_rgba(56,189,248,0.4)]">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
                </span>
                <div>
                    <p class="text-white font-semibold text-sm">PhishCore Scanner</p>
                    <p class="text-[11px] text-slate-500 tracking-wide mt-0.5">AI-POWERED &middot; 7 DETECTION CHECKS &middot; ~2S RESULTS</p>
                </div>
            </div>
            <span class="flex items-center gap-2 text-xs" :class="scanning ? 'text-sky-400' : 'text-emerald-400'">
                <span class="w-1.5 h-1.5 rounded-full" :class="scanning ? 'bg-sky-400 animate-pulse' : 'bg-emerald-400'"></span>
                <span x-text="scanning ? 'Scanning...' : 'Engine online'"></span>
            </span>
        </div>

        <div class="relative p-6">
            <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="space-y-5"
                  @submit="scanning = true">
                @csrf

                <div>
                    <label class="flex items-center gap-1.5 text-xs tracking-wide text-slate-400 mb-2">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                        WEBSITE URL
                    </label>
                                      <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                            </svg>
                            <input type="text" name="url" x-model="url" :readonly="scanning"
                                   @input="clearOthers('url')"
                                   :class="{ 'opacity-50 pointer-events-none': scanning }"
                                   placeholder="Enter or paste a website URL, e.g. https://example.com"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-lg pl-11 pr-4 py-3.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500/60 focus:ring-2 focus:ring-sky-500/10 transition">
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="button" :disabled="scanning"
                                    @click="navigator.clipboard.readText().then(text => { url = text; clearOthers('url') })"
                                    class="flex items-center justify-center gap-1.5 px-4 py-3 sm:py-0 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition whitespace-nowrap disabled:opacity-50 w-full sm:w-auto">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6" /></svg>
                                Paste
                            </button>
                            <button type="submit" :disabled="scanning"
                                    class="flex items-center justify-center gap-2 px-6 py-3 sm:py-0 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold hover:opacity-90 transition whitespace-nowrap shadow-[0_0_18px_-2px_rgba(56,189,248,0.5)] disabled:opacity-60 disabled:cursor-not-allowed w-full sm:w-auto">
                                <svg x-show="!scanning" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                                <svg x-show="scanning" class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="scanning ? 'Scanning...' : 'Scan Now'"></span>
                            </button>
                        </div>
                    </div>
                    <p class="flex items-center gap-1.5 text-xs text-slate-600 mt-2">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                        Include the complete URL starting with <code class="text-slate-400">http://</code> or <code class="text-slate-400">https://</code> for accurate results.
                    </p>
                    @error('url')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <span class="flex-1 h-px bg-slate-800"></span>
                    <span class="text-[10px] tracking-wide text-slate-600">OR REPORT ONE OF THESE INSTEAD</span>
                    <span class="flex-1 h-px bg-slate-800"></span>
                </div>

                <div class="flex flex-col md:grid md:grid-cols-2 gap-5">
                    <div class="order-1">
                        <label class="flex items-center gap-1.5 text-xs tracking-wide text-slate-400 mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                            SENDER EMAIL
                        </label>
                        <input type="text" name="email" x-model="email" :readonly="scanning"
                               @input="clearOthers('email')"
                               :class="{ 'opacity-50 pointer-events-none': scanning }"
                               placeholder="scammer@suspicious-domain.com"
                               class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500/60 focus:ring-2 focus:ring-sky-500/10 transition">
                        @error('email')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="order-3 md:order-2">
                        <label class="flex items-center gap-1.5 text-xs tracking-wide text-slate-400 mb-2">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                            PHONE NUMBER
                        </label>
                        <input type="text" name="phone" x-model="phone" :readonly="scanning"
                               @input="clearOthers('phone')"
                               :class="{ 'opacity-50 pointer-events-none': scanning }"
                               placeholder="+673 XXX XXXX"
                               class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500/60 focus:ring-2 focus:ring-sky-500/10 transition">
                        @error('phone')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="order-2 md:order-3 md:col-span-2 rounded-lg border border-slate-800/70 bg-slate-950/40 px-4 py-4">
                        <p class="flex items-center gap-1.5 text-xs tracking-wide text-slate-500 mb-3">
                            <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.751.43.992l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.49l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.751-.43-.992l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.49l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /></svg>
                            OPTIONAL — SUBJECT &amp; BODY (IMPROVES EMAIL ACCURACY)
                        </p>
                        <div class="grid md:grid-cols-2 gap-3">
                            <div>
                                <input type="text" name="subject" x-model="subject" :readonly="scanning"
                                       @input="clearOthers('email')"
                                       :class="{ 'opacity-50 pointer-events-none': scanning }"
                                       placeholder="Email subject, e.g. Urgent Transfer of Funds Required"
                                       class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500/60 focus:ring-2 focus:ring-sky-500/10 transition">
                                @error('subject')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <textarea name="body" x-model="body" :readonly="scanning" rows="1"
                                          @input="clearOthers('email')"
                                          :class="{ 'opacity-50 pointer-events-none': scanning }"
                                          placeholder="Paste the email body text here"
                                          class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500/60 focus:ring-2 focus:ring-sky-500/10 transition resize-y min-h-[46px]"></textarea>
                                @error('body')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-1.5 text-xs tracking-wide text-slate-400 mb-2">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                        UPLOAD SCREENSHOT
                    </label>
                                       <label class="relative flex items-center justify-center gap-3 w-full bg-slate-950 border border-dashed border-slate-700 rounded-lg px-4 py-5 text-sm text-slate-500 cursor-pointer hover:border-sky-500/60 transition"
                           :class="{ 'opacity-50 pointer-events-none': scanning }">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                        <span x-text="fileName || 'Drop an image or click to upload (PNG/JPG, max 5MB)'"></span>
                        <input type="file" name="screenshot" accept="image/png, image/jpeg, image/jpg, .png, .jpg, .jpeg"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               @change="fileName = $event.target.files[0]?.name ?? ''; if (fileName) clearOthers('screenshot')">
                    </label>
                    @error('screenshot')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <p class="text-xs text-slate-500">
                    Fill in <span class="text-slate-400">one</span> of the options above &mdash; a website URL, a sender's email address, a reported phone number, or an uploaded screenshot &mdash; then run the scan.
                </p>
            </form>

            <div class="flex flex-wrap gap-x-5 gap-y-2.5 pt-5 mt-5 border-t border-slate-800/70">
                @foreach ([
                    'URL structure', 'SSL certificate', 'Domain age', 'Blacklists',
                    'Sender domain analysis', 'Phone number heuristics', 'OCR text extraction',
                ] as $capability)
                    <span class="flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        {{ $capability }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>

<h2 class="text-xl font-bold text-white mb-1">How PhishCore Checks Reports</h2>
<p class="text-sm text-slate-500 mb-6">Every scan runs through the detection layers relevant to what you submitted.</p>

<div class="grid md:grid-cols-2 gap-4 mb-10">
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">SSL Certificate Validation</h3>
        <p class="text-sm text-slate-400">Verifies whether the site uses a valid, trusted HTTPS certificate and checks for a name mismatch or expiry.</p>
    </div>
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">Domain Age &amp; Registration</h3>
        <p class="text-sm text-slate-400">New domains registered within 30 days are a strong phishing signal &mdash; used for URLs, email domains, and URLs found in screenshots.</p>
    </div>
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">URL Structure &amp; Sender Domain Analysis</h3>
        <p class="text-sm text-slate-400">Detects IP-based addresses, lookalike brand names, excessive hyphens, and other suspicious patterns in URLs and email domains.</p>
    </div>
    <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-5">
        <div class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" /></svg>
        </div>
        <h3 class="text-white font-semibold mb-1">Blacklist, Phone &amp; Screenshot OCR</h3>
        <p class="text-sm text-slate-400">Cross-references URLs against Google Safe Browsing, flags mismatched phone country codes, and extracts text from uploaded screenshots to detect hidden links or sender addresses.</p>
    </div>
</div>

@if (count($recentScans ?? []) > 0)
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-white">Recently Scanned</h2>
            <p class="text-sm text-slate-500">Your three most recent reports</p>
        </div>
        <a href="{{ route('scan.history') }}" class="flex items-center gap-1 text-sky-400 text-sm font-medium hover:text-sky-300 transition">
            View all
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        </a>
    </div>
    <div class="space-y-3">
        @php
            $verdictAccent = [
                'clean' => 'bg-emerald-400',
                'suspicious' => 'bg-orange-400',
                'phishing' => 'bg-red-400',
                'review' => 'bg-sky-400',
            ];
            $verdictScoreColor = [
                'clean' => 'text-emerald-400',
                'suspicious' => 'text-orange-400',
                'phishing' => 'text-red-400',
                'review' => 'text-sky-400',
            ];
            $typeIconPaths = [
                'url' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18M12 3a13.5 13.5 0 000 18',
                'email' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
                'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
                'screenshot' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 22.5H6a2.25 2.25 0 01-2.25-2.25V3.75A2.25 2.25 0 016 1.5h12a2.25 2.25 0 012.25 2.25v16.5A2.25 2.25 0 0118 22.5zM10.5 8.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
            ];
        @endphp
        @foreach ($recentScans as $scan)
            @php
                $verdict = $scan->analyses->first()->verdict ?? 'clean';
                $score = $scan->analyses->first()->risk_score ?? 0;
                $badge = $verdictBadge[$verdict] ?? $verdictBadge['clean'];
                $accent = $verdictAccent[$verdict] ?? $verdictAccent['clean'];
                $scoreColor = $verdictScoreColor[$verdict] ?? $verdictScoreColor['clean'];
                $iconPath = $typeIconPaths[$scan->type] ?? $typeIconPaths['url'];
                $label = match ($scan->type) {
                    'email' => $scan->sender_email,
                    'phone' => $scan->phone_number,
                    'screenshot' => 'Uploaded screenshot',
                    default => $scan->url,
                };
            @endphp
            <div class="relative flex items-center justify-between bg-slate-900/50 border border-slate-800 rounded-xl pl-5 pr-5 py-4 overflow-hidden">
                <span class="absolute left-0 top-0 bottom-0 w-1 {{ $accent }}"></span>
                <div class="flex items-center gap-3 min-w-0">
                    <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                    </svg>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-200 truncate">{{ $label }}</p>
                        <p class="text-xs text-slate-500">{{ $scan->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-5 shrink-0">
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} border {{ $badge['border'] }}">{{ $badge['label'] }}</span>
                    <div class="text-right">
                        <p class="text-lg font-bold {{ $scoreColor }} leading-none">{{ $score }}</p>
                        <p class="text-[10px] text-slate-600 tracking-wide">SCORE</p>
                    </div>
                    <a href="{{ route('scan.show', $scan) }}"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-sky-500/30 bg-sky-500/10 text-sky-400 text-xs font-medium hover:bg-sky-500/20 transition whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        Details
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

@push('styles')
<style>
    /* rotating light sweep around the scanner box border */
    .scanner-border-glow {
        position: absolute;
        inset: -2px;
        border-radius: 1.05rem; /* matches rounded-2xl (1rem) + 2px */
        padding: 2px;
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
                mask-composite: exclude;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        z-index: 0;
    }

    .scanner-border-glow.is-scanning {
        opacity: 1;
        background: conic-gradient(
            from 0deg,
            transparent 0%,
            #38bdf8 10%,
            #93c5fd 16%,
            transparent 30%,
            transparent 100%
        );
        animation: scanner-rotate 2s linear infinite;
    }

    @keyframes scanner-rotate {
        to { transform: rotate(360deg); }
    }

    /* pulsing / breathing glow on the box itself */
    .scanner-box {
        transition: box-shadow 0.6s ease;
    }

    .scanner-box.is-scanning {
        animation: scanner-pulse 1.8s ease-in-out infinite;
    }

    @keyframes scanner-pulse {
        0%, 100% { box-shadow: 0 0 60px -15px rgba(56,189,248,0.25), 0 0 0px 0px rgba(56,189,248,0); }
        50%      { box-shadow: 0 0 60px -15px rgba(56,189,248,0.25), 0 0 40px 8px rgba(56,189,248,0.35); }
    }
</style>
@endpush