<x-layouts.guest-landing>

       {{-- HERO --}}
    <section id="home" class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-12 items-center">
        <div>
                       <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> AI-Powered Phishing Protection
            </span>
            <h1 class="text-5xl font-extrabold text-white leading-tight mb-2">Detect Phishing Websites</h1>
            <h2 class="text-5xl font-extrabold bg-gradient-to-r from-sky-400 to-blue-500 bg-clip-text text-transparent leading-tight mb-6">
                Before They Cause Harm.
            </h2>
                     <p class="text-slate-400 mb-8 max-w-lg">
                PhishCore helps anyone analyse suspicious links, emails, phone numbers and screenshots &mdash; identifying phishing threats and protecting sensitive information.
            </p>
                     <div class="flex gap-4">
                <a href="{{ route('register') }}" class="flex items-center gap-2 px-6 py-3 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white font-medium hover:opacity-90 transition">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                    Scan a Website
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
                <svg width="28" height="28" class="text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                </svg>
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
    <section class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-2 md:grid-cols-4 gap-8 text-center border-y border-slate-800/60">
        <div><p class="text-3xl font-bold text-sky-400">489</p><p class="text-sm text-slate-500">URLs Scanned</p></div>
        <div><p class="text-3xl font-bold text-sky-400">97.4%</p><p class="text-sm text-slate-500">Detection Accuracy</p></div>
        <div><p class="text-3xl font-bold text-sky-400">56</p><p class="text-sm text-slate-500">Phishing Sites Detected</p></div>
        <div><p class="text-3xl font-bold text-sky-400">2.1s</p><p class="text-sm text-slate-500">Average Scan Time</p></div>
    </section>

        {{-- FEATURES --}}
    <section id="features" class="max-w-7xl mx-auto px-6 py-24 text-center">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Platform Features</span>
        <h2 class="text-3xl font-bold text-white mb-14">Everything You Need to Stay Protected</h2>

        <div class="grid md:grid-cols-3 gap-6 text-left">

            {{-- Real-Time URL Scanning --}}
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 8v5M8 10.5h5" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Real-Time URL Scanning</h3>
                <p class="text-sm text-slate-400">Submit any URL and receive instant threat analysis powered by multiple detection layers.</p>
            </div>

            {{-- AI-Powered Threat Detection --}}
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">AI-Powered Threat Detection</h3>
                <p class="text-sm text-slate-400">Machine learning models trained on phishing patterns classify URLs with high accuracy.</p>
            </div>

            {{-- SSL & Domain Analysis --}}
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">SSL &amp; Domain Analysis</h3>
                <p class="text-sm text-slate-400">Checks certificate validity, domain age, registrar reputation, and WHOIS data.</p>
            </div>

            {{-- Blacklist Database Checks --}}
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Blacklist Database Checks</h3>
                <p class="text-sm text-slate-400">Cross-references URLs against known phishing and malware domain blocklists.</p>
            </div>

            {{-- Detailed Security Reports --}}
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition">
                <div class="w-10 h-10 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-2">Detailed Security Reports</h3>
                <p class="text-sm text-slate-400">Export comprehensive PDF reports of scan results for documentation and compliance.</p>
            </div>

            {{-- Detection History & Analytics --}}
            <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-6 hover:border-sky-500/40 transition">
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
    <section id="how-it-works" class="max-w-7xl mx-auto px-6 py-24 text-center">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Simple Process</span>
        <h2 class="text-3xl font-bold text-white mb-16">How PhishCore Works</h2>

        <div class="grid md:grid-cols-3 gap-10 text-left">
            @php
                $steps = [
                    ['n' => '01', 'title' => 'Paste a Suspicious Link', 'desc' => 'Enter any URL you want to verify into the PhishCore scanner input field.'],
                    ['n' => '02', 'title' => 'Multi-Layer Analysis', 'desc' => 'PhishCore inspects SSL certificates, domain age, URL structure, blacklists, and keyword patterns simultaneously.'],
                    ['n' => '03', 'title' => 'Clear Risk Score & Report', 'desc' => 'Receive an easy-to-read risk score from 0–100 with a full breakdown of every indicator checked.'],
                ];
            @endphp

            @foreach ($steps as $s)
                <div>
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
    <section class="max-w-4xl mx-auto px-6 py-24 text-center">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-slate-900 text-slate-400 border border-slate-800 mb-4">Try the Scanner</span>
        <h2 class="text-3xl font-bold text-white mb-2">See PhishCore in Action</h2>
        <p class="text-slate-400 mb-10">Paste a URL, email, phone number, or upload a screenshot below — this is a live scan, not a demo.</p>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden text-left" x-data="{ fileName: '' }">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-800 text-xs text-slate-500">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                <span class="ml-2">phishcore.test</span>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <p class="text-xs text-slate-500 mb-2">WEBSITE URL</p>
                        <div class="flex gap-3">
                            <input type="text" name="url"
                                   placeholder="Enter or paste a website URL, e.g. https://example.com"
                                   class="flex-1 bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                            <button type="submit"
                                    class="px-5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-medium hover:opacity-90 transition whitespace-nowrap">
                                Scan Now
                            </button>
                        </div>
                        @error('url')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="flex-1 h-px bg-slate-800"></span>
                        <span class="text-[10px] tracking-wide text-slate-600">OR REPORT ONE OF THESE INSTEAD</span>
                        <span class="flex-1 h-px bg-slate-800"></span>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 mb-2">SENDER EMAIL</p>
                            <input type="text" name="email"
                                   placeholder="scammer@suspicious-domain.com"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                            @error('email')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-2">PHONE NUMBER</p>
                            <input type="text" name="phone"
                                   placeholder="+673 XXX XXXX"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                            @error('phone')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-slate-500 mb-2">UPLOAD SCREENSHOT</p>
                        <label class="flex items-center justify-center gap-3 w-full bg-slate-950 border border-dashed border-slate-700 rounded-lg px-4 py-4 text-sm text-slate-500 cursor-pointer hover:border-sky-500/60 transition">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                            <span x-text="fileName || 'Drop an image or click to upload (PNG/JPG, max 5MB)'"></span>
                            <input type="file" name="screenshot" accept="image/png, image/jpeg" class="hidden"
                                   @change="fileName = $event.target.files[0]?.name ?? ''">
                        </label>
                        @error('screenshot')
                            <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </form>

                <p class="text-center text-xs text-slate-500 mt-5">
                    @guest
                        <a href="{{ route('login') }}" class="text-sky-400">Sign in</a> to save your scans to a personal history.
                    @else
                        Your scan will be saved to your <a href="{{ route('dashboard') }}" class="text-sky-400">dashboard</a> history.
                    @endguest
                </p>
            </div>
        </div>
    </section>
    
    {{-- WHY PHISHCORE --}}
    <section class="max-w-7xl mx-auto px-6 py-24 grid md:grid-cols-2 gap-16 items-start">
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
                              <div class="bg-slate-900/40 border border-slate-800 rounded-xl p-5">
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
    <section id="about" class="max-w-7xl mx-auto px-6 py-24 grid md:grid-cols-3 gap-12 items-start">
        <div class="text-center md:text-left">
            <div class="w-16 h-16 mx-auto md:mx-0 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                </svg>
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
    <section id="contact" class="max-w-4xl mx-auto px-6 pb-24">
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

</x-layouts.guest-landing>