<x-layouts.guest-landing>

    {{-- HERO --}}
        <section id="home" class="scroll-mt-20 max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> AI-Powered Phishing Protection
            </span>
            <h1 class="text-5xl font-extrabold text-white leading-tight mb-2">Detect Phishing Threats</h1>
            <h2 class="text-5xl font-extrabold bg-gradient-to-r from-sky-400 to-blue-500 bg-clip-text text-transparent leading-tight mb-6">
                Before They Cause Harm.
            </h2>
            <p class="text-slate-400 mb-8 max-w-lg">
                PhishCore helps anyone analyse suspicious links, emails, phone numbers and screenshots &mdash; identifying phishing threats and protecting sensitive information.
            </p>
            <div class="flex gap-4">
                <a href="{{ route('register') }}" class="flex items-center gap-2 px-6 py-3 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white font-medium hover:opacity-90 transition">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                    Start Scanning
                </a>
                <a href="#how-it-works" class="flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-700 text-slate-200 hover:bg-slate-900 transition">
                    Learn How It Works
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>
            </div>
        </div>

        <div class="relative bg-slate-900/60 border border-slate-800 rounded-2xl p-8 overflow-hidden" style="box-shadow: 0 0 60px -15px rgba(56,189,248,0.3);">
            <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-sky-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -bottom-10 w-40 h-40 rounded-full bg-red-500/5 blur-3xl pointer-events-none"></div>

<div class="relative w-14 h-14 mx-auto rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-6" style="box-shadow: 0 0 20px -4px rgba(56,189,248,0.5);">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-8 h-8 object-contain">
</div>

            <div class="relative flex items-center gap-2.5 bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 mb-4 text-sm text-slate-400 font-mono">
                <svg width="16" height="16" class="text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
                https://suspicious-example.com
            </div>

            <div class="relative grid grid-cols-2 gap-3 mb-4 text-xs">
                <span class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span> SSL Invalid
                </span>
                <span class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span> Domain &lt; 7d
                </span>
                <span class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span> Blacklisted
                </span>
                <span class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span> Scan: 1.8s
                </span>
            </div>

            <div class="relative text-center border-t border-slate-800 pt-4">
                <p class="text-xs text-slate-500 mb-1 tracking-wide">RISK SCORE</p>
                <p class="text-4xl font-bold text-red-500">92<span class="text-lg text-slate-500">/100</span></p>
                <p class="text-xs text-red-400 font-medium mt-1">HIGH RISK &mdash; PHISHING DETECTED</p>
            </div>
        </div>
    </section>

    {{-- STATS --}}
    <section class="reveal max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 sm:grid-cols-3 gap-8 text-center border-y border-slate-800/60">
        <div><p class="text-3xl font-bold text-sky-400">{{ number_format($totalScans) }}</p><p class="text-sm text-slate-500">Scans Performed</p></div>
        <div><p class="text-3xl font-bold text-sky-400">{{ number_format($threatsDetected) }}</p><p class="text-sm text-slate-500">Threats Detected</p></div>
        <div><p class="text-3xl font-bold text-sky-400">{{ $avgScanSeconds }}s</p><p class="text-sm text-slate-500">Average Scan Time</p></div>
    </section>

    {{-- FEATURES --}}
        <section id="features" class="scroll-mt-20 reveal max-w-7xl mx-auto px-6 py-24 text-center">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Platform Features</span>
        <h2 class="text-3xl font-bold text-white mb-14">Everything You Need to Stay Protected</h2>

        <div class="grid md:grid-cols-3 gap-6 text-left">

            {{-- Multi-Format Threat Scanning --}}
            <div class="reveal bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition" style="transition-delay: 0s">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 8v5M8 10.5h5" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Multi-Format Threat Scanning</h3>
                <p class="text-sm text-slate-400">Submit a URL, email, phone number, or screenshot and receive instant threat analysis powered by multiple detection layers.</p>
            </div>

            {{-- AI-Powered Threat Detection --}}
            <div class="reveal bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition" style="transition-delay: 0.08s">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">AI-Powered Threat Detection</h3>
                <p class="text-sm text-slate-400">Machine learning models trained on phishing patterns classify URLs with high accuracy.</p>
            </div>

            {{-- SSL & Domain Analysis --}}
            <div class="reveal bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition" style="transition-delay: 0.16s">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">SSL &amp; Domain Analysis</h3>
                <p class="text-sm text-slate-400">Checks certificate validity, domain age, registrar reputation, and WHOIS data.</p>
            </div>

            {{-- Blacklist Database Checks --}}
            <div class="reveal bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition" style="transition-delay: 0.24s">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Blacklist Database Checks</h3>
                <p class="text-sm text-slate-400">Cross-references URLs against known phishing and malware domain blocklists.</p>
            </div>

            {{-- Detailed Security Reports --}}
            <div class="reveal bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition" style="transition-delay: 0.32s">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Detailed Security Reports</h3>
                <p class="text-sm text-slate-400">Export comprehensive PDF reports of scan results for documentation and compliance.</p>
            </div>

            {{-- Detection History & Analytics --}}
            <div class="reveal bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition" style="transition-delay: 0.4s">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Detection History &amp; Analytics</h3>
                <p class="text-sm text-slate-400">Track all past scans, visualise threat trends, and monitor platform-wide activity.</p>
            </div>

        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section id="how-it-works" class="scroll-mt-20 reveal max-w-7xl mx-auto px-6 py-24 text-center">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Simple Process</span>
        <h2 class="text-3xl font-bold text-white mb-16">How PhishCore Works</h2>

        <div class="grid md:grid-cols-3 gap-10 text-left">
            @php
                $steps = [
                    ['n' => '01', 'title' => 'Submit What You Received', 'desc' => 'Paste a suspicious link, sender email, phone number, or upload a screenshot into the PhishCore scanner.'],
                    ['n' => '02', 'title' => 'Multi-Layer Analysis', 'desc' => 'PhishCore inspects SSL certificates, domain age, URL structure, blacklists, and behavioural patterns simultaneously.'],
                    ['n' => '03', 'title' => 'Clear Risk Score & Report', 'desc' => 'Receive an easy-to-read risk score from 0–100 with a full breakdown of every indicator checked.'],
                ];
            @endphp

            @foreach ($steps as $s)
                <div class="reveal" style="transition-delay: {{ $loop->index * 0.12 }}s">
                    <div class="w-14 h-14 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 font-bold mb-4">
                        {{ $s['n'] }}
                    </div>
                    <h3 class="text-white font-semibold mb-2">{{ $s['title'] }}</h3>
                    <p class="text-sm text-slate-400">{{ $s['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

       {{-- LIVE DEMO PREVIEW --}}
          <section id="scanner" class="scroll-mt-20 reveal max-w-4xl mx-auto px-6 py-24 text-center">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Try the Scanner</span>
        <h2 class="text-3xl font-bold text-white mb-2">See PhishCore in Action</h2>
        <p class="text-slate-400 mb-10">Paste a URL, email, phone number, or upload a screenshot below — this is a live scan, not a demo.</p>

        <div class="text-left"
             x-data="{
                url: '',
                email: '',
                phone: '',
                subject: '',
                body: '',
                fileName: '',
                scanning: false,
                clearOthers(keep) {
                    if (keep !== 'url') this.url = '';
                    if (keep !== 'email') { this.email = ''; this.subject = ''; this.body = ''; }
                    if (keep !== 'phone') this.phone = '';
                    if (keep !== 'screenshot') this.fileName = '';
                }
             }"
             x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) scanning = false; })">

            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                    <span class="w-10 h-10 flex items-center justify-center shrink-0">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-10 h-10 object-contain">
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

                <div class="p-6">
                    <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="space-y-5" @submit="scanning = true">
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

                    <p class="text-center text-xs text-slate-500 mt-5">
                        @guest
                            <a href="{{ route('login') }}" class="text-sky-400">Sign in</a> to save your scans to a personal history.
                        @else
                            Your scan will be saved to your <a href="{{ route('dashboard') }}" class="text-sky-400">dashboard</a> history.
                        @endguest
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- WHY PHISHCORE --}}
    <section class="reveal max-w-7xl mx-auto px-6 py-24 grid md:grid-cols-2 gap-16 items-start">
        <div>
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Why PhishCore</span>
            <h2 class="text-3xl font-bold text-white mb-4 leading-snug">Designed to Protect, Not to Confuse.</h2>
            <p class="text-slate-400">PhishCore turns complex threat intelligence into decisions your users can act on immediately.</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
            @php
                $why = [
                    ['title' => 'Reduces Phishing Risk', 'desc' => 'Gives users a quick, reliable way to verify links before clicking, reducing exposure to credential theft.'],
                    ['title' => 'Understandable Results', 'desc' => 'Complex security checks are presented as a clear numeric score with plain-language explanations.'],
                    ['title' => 'Centralised Data', 'desc' => 'All scans, reports, and analytics are stored in one place so administrators can monitor threats across the institution.'],
                    ['title' => 'Cybersecurity Awareness', 'desc' => 'Helps everyday users build habits around link safety and digital vigilance.'],
                ];
            @endphp
            @foreach ($why as $w)
                <div class="reveal bg-slate-900/40 border border-slate-800 rounded-xl p-5" style="transition-delay: {{ $loop->index * 0.1 }}s">
                    <p class="text-white font-medium mb-1 flex items-center gap-2">
                        <svg width="16" height="16" class="text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        {{ $w['title'] }}
                    </p>
                    <p class="text-sm text-slate-400">{{ $w['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-6 pb-8">
        <div class="bg-orange-500/5 border border-orange-500/20 rounded-xl p-5 flex gap-4 items-start">
            <span class="w-8 h-8 rounded-lg bg-orange-500/10 border border-orange-500/20 flex items-center justify-center shrink-0">
                <svg width="16" height="16" class="text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            </span>
            <p class="text-sm text-slate-300">
                <span class="text-orange-400 font-medium">Stay Vigilant Online — </span>
                PhishCore supports safer browsing, but users should still avoid sharing passwords, financial details or personal information on unfamiliar websites.
            </p>
        </div>
    </div>

    {{-- ABOUT --}}
    <section id="about" class="scroll-mt-20 reveal max-w-7xl mx-auto px-6 py-24 grid md:grid-cols-3 gap-12 items-start">
        <div class="text-center md:text-left">
          <div class="w-16 h-16 mx-auto md:mx-0 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-3">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-10 h-10 object-contain">
</div>
            <p class="text-white font-medium">Final Year Project</p>
            <p class="text-sm text-slate-500">Politeknik Brunei · 2026</p>
        </div>

        <div class="md:col-span-2">
            <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">About the Project</span>
            <h2 class="text-2xl font-bold text-white mb-4">Built for Politeknik Brunei.</h2>
            <p class="text-slate-400 mb-4">
                PhishCore is a student final-year project that combines <span class="text-white font-medium">application development</span>,
                <span class="text-white font-medium">data analytics</span>, and <span class="text-white font-medium">cloud networking</span>
                to address a real cybersecurity challenge faced by educational institutions.
            </p>
            <p class="text-slate-400 mb-6">
                Its purpose is to help users recognise and respond to phishing websites through clear, accessible detection results rather than technical jargon.
                The platform centralises scanning activity so administrators can monitor emerging threats across the institution.
            </p>
            <p class="text-xs text-slate-600">
                Note: PhishCore is a prototype developed for academic demonstration. Results are provided for educational and security awareness purposes and should not be treated as a guarantee of complete protection.
            </p>
        </div>
    </section>

    {{-- CTA --}}
       <section id="contact" class="scroll-mt-20 reveal max-w-4xl mx-auto px-6 pb-24">
        <div class="bg-gradient-to-br from-sky-500/10 to-blue-600/10 border border-sky-500/20 rounded-2xl p-12 text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Ready to Check a Suspicious Link?</h2>
            <p class="text-slate-400 mb-8">Create an account or sign in to begin scanning websites and protecting your digital activity.</p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('register') }}" class="px-6 py-3 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white font-medium hover:opacity-90 transition">
                    Create Account
                </a>
                <a href="{{ route('login') }}" class="px-6 py-3 rounded-lg border border-slate-700 text-slate-200 hover:bg-slate-900 transition">
                    Sign In
                </a>
            </div>
        </div>
    </section>

    <style>
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s ease-out, transform 0.7s ease-out;
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        @media (prefers-reduced-motion: reduce) {
            .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const revealEls = document.querySelectorAll('.reveal');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: '0px 0px -60px 0px',
            });

            revealEls.forEach((el) => observer.observe(el));
        });
    </script>

    @if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scannerSection = document.getElementById('scanner');
        if (scannerSection) {
            scannerSection.scrollIntoView({ behavior: 'instant', block: 'start' });
        }
    });
</script>
@endif

</x-layouts.guest-landing>