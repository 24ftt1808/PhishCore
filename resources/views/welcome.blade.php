<x-layouts.guest-landing>

{{-- HERO --}}
<section id="home" class="scroll-mt-28 max-w-7xl mx-auto px-6 pt-6 pb-8 md:py-14 lg:py-16 grid gap-8 md:gap-10 md:max-lg:landscape:grid-cols-2 lg:grid-cols-2 lg:gap-12 items-center">
    <div>
        <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-sky-500/10 text-sky-300 border border-sky-400/20 mb-4 md:mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> AI-Powered Phishing Protection
        </span>
        <h1 class="text-[2.15rem] sm:text-5xl md:text-5xl font-extrabold text-white leading-[1.1] mb-1 md:mb-2">Detect Phishing Threats</h1>
        <h2 class="text-[2.15rem] sm:text-5xl md:text-5xl font-extrabold text-sky-400 leading-[1.1] mb-4 md:mb-6">
            Before They Cause Harm.
        </h2>
        <p class="text-slate-400 text-[15px] md:text-base mb-6 md:mb-8 max-w-lg">
            PhishCore helps anyone analyse suspicious links, emails, phone numbers and screenshots &mdash; identifying phishing threats and protecting sensitive information.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <a href="{{ route('register') }}" class="btn-primary !rounded-lg !px-6 !py-3.5 md:!py-3">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                Start Scanning
            </a>
            <a href="#how-it-works" class="btn-ghost !rounded-lg !px-6 !py-3.5 md:!py-3">
                Learn How It Works
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        </div>
    </div>

    <div class="risk-card relative rounded-2xl p-6 md:p-8 hidden md:block md:max-lg:portrait:w-full md:max-lg:portrait:max-w-xl md:max-lg:portrait:mx-auto">
        <div class="relative w-14 h-14 mx-auto rounded-xl bg-sky-400/10 border border-sky-300/25 flex items-center justify-center mb-6">
            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-8 h-8 object-contain">
        </div>

        <div class="risk-field flex items-center gap-2.5 rounded-lg px-4 py-3 mb-4 text-sm text-slate-300 font-mono">
            <svg width="16" height="16" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
            </svg>
            https://suspicious-example.com
        </div>

        <div class="grid grid-cols-2 gap-3 mb-5">
            <div class="risk-chip risk-chip--danger"><i></i>SSL Invalid</div>
            <div class="risk-chip risk-chip--warn"><i></i>Domain &lt; 7d</div>
            <div class="risk-chip risk-chip--danger"><i></i>Blacklisted</div>
            <div class="risk-chip risk-chip--info"><i></i>Scan: 1.8s</div>
        </div>

        <div class="text-center border-t border-white/10 pt-4">
            <p class="text-xs text-slate-400 mb-1 tracking-wide">RISK SCORE</p>
            <p class="text-4xl font-bold text-rose-400">92<span class="text-lg text-slate-500">/100</span></p>
            <div class="risk-bar mx-auto mt-3 h-1.5 w-40 rounded-full overflow-hidden"><span style="width: 92%"></span></div>
            <p class="text-xs text-rose-300 font-medium mt-2">HIGH RISK &mdash; PHISHING DETECTED</p>
        </div>
    </div>

    {{-- Phone-only compact scan preview --}}
    <div class="risk-card md:hidden rounded-2xl p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="eyebrow !text-[10px]">Live scan preview</span>
            <span class="flex items-center gap-1.5 text-[10px] text-rose-300 font-medium"><span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>Phishing</span>
        </div>
        <div class="risk-field flex items-center gap-2 rounded-lg px-3 py-2.5 mb-3 text-[13px] text-slate-300 font-mono min-w-0">
            <svg width="14" height="14" class="text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
            <span class="truncate">https://suspicious-example.com</span>
        </div>
        <div class="grid grid-cols-2 gap-2 mb-3">
            <div class="risk-chip risk-chip--danger !py-2 !px-3 !text-[12px]"><i></i>SSL Invalid</div>
            <div class="risk-chip risk-chip--warn !py-2 !px-3 !text-[12px]"><i></i>Domain &lt; 7d</div>
            <div class="risk-chip risk-chip--danger !py-2 !px-3 !text-[12px]"><i></i>Blacklisted</div>
            <div class="risk-chip risk-chip--info !py-2 !px-3 !text-[12px]"><i></i>Scan: 1.8s</div>
        </div>
        <div class="flex items-center gap-4 border-t border-white/10 pt-3">
            <p class="text-4xl font-bold text-rose-400 leading-none">92<span class="text-base text-slate-500">/100</span></p>
            <div class="flex-1 min-w-0">
                <div class="risk-bar h-1.5 w-full rounded-full overflow-hidden"><span style="width: 92%"></span></div>
                <p class="text-[11px] text-rose-300 font-medium mt-2 tracking-wide">HIGH RISK &mdash; PHISHING DETECTED</p>
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<section class="reveal max-w-7xl mx-auto px-6 py-2 md:py-12">
    {{-- Phone: compact stat strip --}}
    <div class="grid grid-cols-3 gap-2.5 md:hidden">
        <div class="glass-card px-2 py-4 text-center">
            <div class="icon-tile !w-9 !h-9 !rounded-lg mx-auto mb-2.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg></div>
            <p class="text-2xl font-bold text-white tabular-nums leading-none" data-count="{{ $totalScans }}">{{ number_format($totalScans) }}</p>
            <p class="mt-1.5 text-[11px] text-slate-300 leading-tight">Scans<br>performed</p>
        </div>
        <div class="glass-card px-2 py-4 text-center">
            <div class="icon-tile !w-9 !h-9 !rounded-lg mx-auto mb-2.5 !bg-rose-400/10 !border-rose-300/20 !text-rose-300"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg></div>
            <p class="text-2xl font-bold text-white tabular-nums leading-none" data-count="{{ $threatsDetected }}">{{ number_format($threatsDetected) }}</p>
            <p class="mt-1.5 text-[11px] text-slate-300 leading-tight">Threats<br>detected</p>
        </div>
        <div class="glass-card px-2 py-4 text-center">
            <div class="icon-tile !w-9 !h-9 !rounded-lg mx-auto mb-2.5 !bg-emerald-400/10 !border-emerald-300/20 !text-emerald-300"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg></div>
            <p class="text-2xl font-bold text-white tabular-nums leading-none" data-count="{{ $avgScanSeconds }}" data-suffix="s">{{ $avgScanSeconds }}s</p>
            <p class="mt-1.5 text-[11px] text-slate-300 leading-tight">Avg scan<br>time</p>
        </div>
    </div>

    <div class="hidden md:grid md:grid-cols-3 gap-4">

        <div class="glass-card p-6 flex items-center gap-5">
            <div class="icon-tile !w-12 !h-12 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
            </div>
            <div>
                <p class="text-3xl font-bold text-white tabular-nums leading-none" data-count="{{ $totalScans }}">{{ number_format($totalScans) }}</p>
                <p class="mt-2 text-sm text-slate-300">Scans Performed</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Total scans submitted</p>
            </div>
        </div>

        <div class="glass-card p-6 flex items-center gap-5">
            <div class="icon-tile !w-12 !h-12 shrink-0 !bg-rose-400/10 !border-rose-300/20 !text-rose-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            </div>
            <div>
                <p class="text-3xl font-bold text-white tabular-nums leading-none" data-count="{{ $threatsDetected }}">{{ number_format($threatsDetected) }}</p>
                <p class="mt-2 text-sm text-slate-300">Threats Detected</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Flagged as phishing</p>
            </div>
        </div>

        <div class="glass-card p-6 flex items-center gap-5">
            <div class="icon-tile !w-12 !h-12 shrink-0 !bg-emerald-400/10 !border-emerald-300/20 !text-emerald-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
            </div>
            <div>
                <p class="text-3xl font-bold text-white tabular-nums leading-none" data-count="{{ $avgScanSeconds }}" data-suffix="s">{{ $avgScanSeconds }}s</p>
                <p class="mt-2 text-sm text-slate-300">Average Scan Time</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Per scan, on average</p>
            </div>
        </div>

    </div>
</section>

{{-- FEATURES --}}
<section id="features" class="scroll-mt-24 md:scroll-mt-28 reveal max-w-7xl mx-auto px-6 py-12 md:py-20 lg:py-24 text-center">
    <span class="inline-block text-xs px-3 py-1 rounded-full bg-white/5 text-slate-300 border border-white/10 mb-4">Platform Features</span>
    <h2 class="text-2xl md:text-3xl font-bold text-white mb-7 md:mb-12">Everything You Need to Stay Protected</h2>

    <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory -mx-6 px-6 pb-3 md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-5 lg:gap-6 md:overflow-visible md:mx-0 md:px-0 md:pb-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden text-left">

        <div class="reveal glass-card p-5 md:p-6 shrink-0 snap-center w-[80%] sm:w-[46%] md:w-auto" style="transition-delay: 0s">
            <div class="icon-tile mb-3.5 md:mb-4">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 8v5M8 10.5h5" />
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-2">Multi-Format Threat Scanning</h3>
            <p class="text-sm text-slate-400">Submit a URL, email, phone number, or screenshot and receive instant threat analysis powered by multiple detection layers.</p>
        </div>

        <div class="reveal glass-card p-5 md:p-6 shrink-0 snap-center w-[80%] sm:w-[46%] md:w-auto" style="transition-delay: 0.08s">
            <div class="icon-tile mb-3.5 md:mb-4">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-2">AI-Powered Threat Detection</h3>
            <p class="text-sm text-slate-400">Machine learning models trained on phishing patterns classify URLs with high accuracy.</p>
        </div>

        <div class="reveal glass-card p-5 md:p-6 shrink-0 snap-center w-[80%] sm:w-[46%] md:w-auto" style="transition-delay: 0.16s">
            <div class="icon-tile mb-3.5 md:mb-4">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-2">SSL &amp; Domain Analysis</h3>
            <p class="text-sm text-slate-400">Checks certificate validity, domain age, registrar reputation, and WHOIS data.</p>
        </div>

        <div class="reveal glass-card p-5 md:p-6 shrink-0 snap-center w-[80%] sm:w-[46%] md:w-auto" style="transition-delay: 0.24s">
            <div class="icon-tile mb-3.5 md:mb-4">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-2">Blacklist Database Checks</h3>
            <p class="text-sm text-slate-400">Cross-references URLs against known phishing and malware domain blocklists.</p>
        </div>

        <div class="reveal glass-card p-5 md:p-6 shrink-0 snap-center w-[80%] sm:w-[46%] md:w-auto" style="transition-delay: 0.32s">
            <div class="icon-tile mb-3.5 md:mb-4">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-2">Detailed Security Reports</h3>
            <p class="text-sm text-slate-400">Export comprehensive PDF reports of scan results for documentation and compliance.</p>
        </div>

        <div class="reveal glass-card p-5 md:p-6 shrink-0 snap-center w-[80%] sm:w-[46%] md:w-auto" style="transition-delay: 0.4s">
            <div class="icon-tile mb-3.5 md:mb-4">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l3.75-3.75 3 3 4.5-4.5m0 0h-3m3 0v3M3 19.5h18" />
                </svg>
            </div>
            <h3 class="text-white font-semibold mb-2">Detection History &amp; Analytics</h3>
            <p class="text-sm text-slate-400">Track all past scans, visualise threat trends, and monitor platform-wide activity.</p>
        </div>

    </div>
    <p class="md:hidden mt-3 text-[11px] text-slate-500 flex items-center justify-center gap-1.5">Swipe to explore <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg></p>
</section>

{{-- HOW IT WORKS --}}
<section id="how-it-works" class="scroll-mt-24 md:scroll-mt-28 reveal max-w-7xl mx-auto px-6 py-12 md:py-20 lg:py-24 text-center">
    <span class="inline-block text-xs px-3 py-1 rounded-full bg-white/5 text-slate-300 border border-white/10 mb-4">Simple Process</span>
    <h2 class="text-2xl md:text-3xl font-bold text-white mb-8 md:mb-14">How PhishCore Works</h2>

    <div class="hidden md:max-lg:landscape:grid lg:grid grid-cols-3 gap-5 text-left">
        @php
            $steps = [
                ['n' => '01', 'title' => 'Submit What You Received', 'desc' => 'Paste a suspicious link, sender email, phone number, or upload a screenshot into the PhishCore scanner.'],
                ['n' => '02', 'title' => 'Multi-Layer Analysis', 'desc' => 'PhishCore inspects SSL certificates, domain age, URL structure, blacklists, and behavioural patterns simultaneously.'],
                ['n' => '03', 'title' => 'Clear Risk Score & Report', 'desc' => 'Receive an easy-to-read risk score from 0–100 with a full breakdown of every indicator checked.'],
            ];
        @endphp

        @php
            $stepIcons = [
                'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5',
                'M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3',
                'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
            ];
        @endphp

        @foreach ($steps as $s)
            <div class="reveal glass-card p-7" style="transition-delay: {{ $loop->index * 0.12 }}s">
                <div class="flex items-center gap-4 mb-6">
                    <div class="icon-tile !w-12 !h-12 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stepIcons[$loop->index] }}" /></svg>
                    </div>
                    <span class="flex-1 h-px" style="background: repeating-linear-gradient(90deg, rgba(125,211,252,.3) 0 5px, transparent 5px 11px)"></span>
                    <span class="font-mono text-sm font-semibold text-sky-300/90 tracking-wider">{{ $s['n'] }}</span>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">{{ $s['title'] }}</h3>
                <p class="text-sm text-slate-400 leading-relaxed">{{ $s['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Phones and portrait tablets: vertical timeline --}}
    <ol class="lg:hidden md:max-lg:landscape:hidden text-left max-w-xl mx-auto">
        @foreach ($steps as $s)
            <li class="reveal relative flex gap-4 pb-8 last:pb-0" style="transition-delay: {{ $loop->index * 0.1 }}s">
                <div class="flex flex-col items-center shrink-0">
                    <div class="icon-tile !w-11 !h-11 !rounded-xl shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stepIcons[$loop->index] }}" /></svg>
                    </div>
                    @unless ($loop->last)
                        <span class="flex-1 w-px mt-2" style="background: repeating-linear-gradient(180deg, rgba(125,211,252,.4) 0 5px, transparent 5px 11px)"></span>
                    @endunless
                </div>
                <div class="pt-0.5 min-w-0">
                    <span class="font-mono text-[11px] font-semibold text-sky-300/90 tracking-[0.16em]">STEP {{ $s['n'] }}</span>
                    <h3 class="text-white font-semibold text-[17px] mt-1 mb-1.5">{{ $s['title'] }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">{{ $s['desc'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>

{{-- LIVE SCANNER --}}
<style>
    .scan-pill{transition:transform .38s cubic-bezier(.34,1.35,.64,1)}
    .scan-sweep{animation:scan-sweep 1.1s ease-in-out infinite}
    @keyframes scan-sweep{from{transform:translateX(-100%)}to{transform:translateX(300%)}}
    @media (prefers-reduced-motion:reduce){.scan-pill{transition:none}.scan-sweep{animation:none;width:100%}}
</style>
@php
    $scanTab = 'url';
    if ($errors->has('email') || $errors->has('subject') || $errors->has('body')) $scanTab = 'email';
    elseif ($errors->has('phone')) $scanTab = 'phone';
    elseif ($errors->has('screenshot')) $scanTab = 'screenshot';
@endphp
<section id="scanner" class="scroll-mt-28 reveal max-w-2xl mx-auto px-4 sm:px-6 py-16 md:py-24 text-center">
    <span class="inline-block text-xs px-3 py-1 rounded-full bg-white/5 text-slate-300 border border-white/10 mb-4">Try the Scanner</span>
    <h2 class="text-3xl font-bold text-white mb-2">See PhishCore in Action</h2>
    <p class="text-slate-400 mb-8">Pick what you want to check and scan it. This is a live scan, not a demo.</p>

    <div class="text-left"
         x-data="{
            tab: '{{ $scanTab }}',
            url: '', email: '', phone: '', subject: '', body: '',
            fileName: '', drag: false, scanning: false, more: false,
            tabs: [
                { key: 'url', label: 'URL', icon: 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244' },
                { key: 'email', label: 'Email', icon: 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6' },
                { key: 'phone', label: 'Phone', icon: 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3' },
                { key: 'screenshot', label: 'Screenshot', icon: 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5zM10.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z' }
            ],
            pick(k) {
                if (this.scanning) return;
                this.tab = k;
                this.clearOthers(k);
            },
            clearOthers(keep) {
                if (keep !== 'url') this.url = '';
                if (keep !== 'email') { this.email = ''; this.subject = ''; this.body = ''; }
                if (keep !== 'phone') this.phone = '';
                if (keep !== 'screenshot') this.removeFile();
            },
            removeFile() {
                this.fileName = '';
                if (this.$refs.file) this.$refs.file.value = '';
            }
         }"
         x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) scanning = false; })">

        <div class="glass-panel rounded-3xl overflow-hidden transition-shadow duration-500" :class="scanning ? 'shadow-[0_0_40px_-8px_rgba(56,189,248,0.35)]' : ''">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-white/10">
                <div class="flex items-center gap-2.5 min-w-0">
                    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="" class="w-6 h-6 object-contain shrink-0">
                    <p class="text-white font-semibold text-sm truncate">PhishCore Scanner</p>
                </div>
                <span class="shrink-0 flex items-center gap-2 text-[11px] px-2.5 py-1 rounded-full border"
                      :class="scanning ? 'text-sky-300 border-sky-300/25 bg-sky-400/[0.07]' : 'text-emerald-300 border-emerald-300/20 bg-emerald-400/[0.06]'">
                    <span class="relative flex w-1.5 h-1.5"><span class="absolute inset-0 rounded-full animate-ping opacity-60" :class="scanning ? 'bg-sky-300' : 'bg-emerald-400'"></span><span class="relative w-1.5 h-1.5 rounded-full" :class="scanning ? 'bg-sky-300' : 'bg-emerald-400'"></span></span>
                    <span x-text="scanning ? 'Scanning...' : 'Engine online'"></span>
                </span>
            </div>
            <div x-show="scanning" x-cloak class="scan-track h-0.5 bg-sky-300/10 relative overflow-hidden"><span class="scan-sweep absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-sky-300 to-transparent"></span></div>

            <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="p-4 sm:p-6" @submit="scanning = true">
                @csrf

                {{-- Type selector --}}
                <div class="relative grid grid-cols-4 p-1 rounded-2xl bg-black/25 border border-white/[0.07]" role="tablist">
                    <span class="scan-pill absolute top-1 bottom-1 left-1 rounded-xl bg-white/10 border border-white/15 pointer-events-none"
                          :style="`width:calc((100% - 0.5rem) / 4);transform:translateX(${tabs.findIndex(t => t.key === tab) * 100}%)`"></span>
                    <template x-for="t in tabs" :key="t.key">
                        <button type="button" role="tab" @click="pick(t.key)" :aria-selected="tab === t.key"
                                :class="tab === t.key ? 'text-white' : 'text-slate-400 hover:text-slate-200'"
                                class="relative z-10 flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 py-2 px-1 rounded-xl text-[11px] sm:text-xs font-medium transition-colors duration-200 active:scale-95">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" :d="t.icon" /></svg>
                            <span x-text="t.label"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-4">
                    {{-- URL --}}
                    <div x-show="tab === 'url'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="relative">
                            <input type="text" name="url" x-model="url" :readonly="scanning"
                                   @input="clearOthers('url')"
                                   :class="{ 'opacity-50 pointer-events-none': scanning }"
                                   placeholder="https://example.com"
                                   class="field !pl-4 !pr-24 !py-3.5">
                            <button type="button" :disabled="scanning"
                                    @click="navigator.clipboard.readText().then(text => { url = text; clearOthers('url') })"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white transition disabled:opacity-50">
                                Paste
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Include the full link, starting with <code class="text-slate-300">http://</code> or <code class="text-slate-300">https://</code>.</p>
                        @error('url')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div x-show="tab === 'email'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                        <input type="text" name="email" x-model="email" :readonly="scanning"
                               @input="clearOthers('email')"
                               :class="{ 'opacity-50 pointer-events-none': scanning }"
                               placeholder="scammer@suspicious-domain.com" class="field !py-3.5">
                        @error('email')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror

                        <button type="button" @click="more = !more" class="mt-3 flex items-center gap-1.5 text-xs text-sky-300 hover:text-sky-200 transition">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="more ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            Add subject &amp; body <span class="text-slate-500">(optional, improves accuracy)</span>
                        </button>
                        <div x-show="more || subject || body" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="mt-3 space-y-3">
                            <input type="text" name="subject" x-model="subject" :readonly="scanning"
                                   @input="clearOthers('email')"
                                   :class="{ 'opacity-50 pointer-events-none': scanning }"
                                   placeholder="Email subject" class="field">
                            @error('subject')
                                <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                            <textarea name="body" x-model="body" :readonly="scanning" rows="3"
                                      @input="clearOthers('email')"
                                      :class="{ 'opacity-50 pointer-events-none': scanning }"
                                      placeholder="Paste the email body text" class="field resize-y min-h-[84px]"></textarea>
                            @error('body')
                                <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div x-show="tab === 'phone'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                        <input type="text" name="phone" x-model="phone" :readonly="scanning"
                               @input="clearOthers('phone')"
                               :class="{ 'opacity-50 pointer-events-none': scanning }"
                               placeholder="+673 XXX XXXX" class="field !py-3.5">
                        @error('phone')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Screenshot --}}
                    <div x-show="tab === 'screenshot'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0">
                        <label class="relative flex items-center justify-center gap-3 w-full rounded-2xl border border-dashed px-4 py-5 text-sm cursor-pointer transition"
                               :class="[
                                   drag ? 'border-sky-300/70 bg-sky-400/[0.07] text-sky-200' : (fileName ? 'border-emerald-300/40 bg-emerald-400/[0.05] text-slate-200 pr-24' : 'border-white/15 bg-black/20 text-slate-400 hover:border-sky-300/50 hover:bg-black/30'),
                                   scanning ? 'opacity-50 pointer-events-none' : ''
                               ]"
                               @dragenter="drag = true" @dragleave="drag = false" @drop="drag = false">
                            <svg x-show="!fileName" class="w-5 h-5 shrink-0 text-sky-300/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                            <svg x-show="fileName" x-cloak class="w-5 h-5 shrink-0 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="truncate" x-text="drag ? 'Drop it here' : (fileName || 'Drop an image or tap to upload (PNG/JPG, max 5MB)')"></span>
                            <input type="file" name="screenshot" x-ref="file" accept="image/png, image/jpeg, image/jpg, .png, .jpg, .jpeg"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                   @change="fileName = $event.target.files[0]?.name ?? ''; if (fileName) clearOthers('screenshot')">
                            <button type="button" x-show="fileName" x-cloak @click.prevent.stop="removeFile()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 z-10 text-xs px-2.5 py-1 rounded-lg border border-white/10 bg-black/30 text-slate-300 hover:text-white hover:bg-black/50 transition">
                                Remove
                            </button>
                        </label>
                        @error('screenshot')
                            <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <button type="submit" :disabled="scanning" class="btn-primary !rounded-xl !py-3.5 !text-sm font-semibold w-full mt-4 transition active:scale-[0.98]">
                    <svg x-show="!scanning" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                    <svg x-show="scanning" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="scanning ? 'Scanning...' : 'Scan Now'"></span>
                </button>

                <p class="text-center text-[11px] text-slate-500 mt-4 leading-relaxed">
                    Checks URL structure, SSL, domain age, blacklists, sender domain, phone patterns and screenshot text.
                    @guest
                        <br><a href="{{ route('login') }}" class="text-sky-300 hover:text-sky-200">Sign in</a> to save your scans to a personal history.
                    @else
                        <br>Saved to your <a href="{{ route('dashboard') }}" class="text-sky-300 hover:text-sky-200">dashboard</a> history.
                    @endguest
                </p>
            </form>
        </div>
    </div>
</section>

{{-- WHY PHISHCORE --}}
<section class="reveal max-w-7xl mx-auto px-6 py-12 md:py-20 lg:py-24 grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
    <div>
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-white/5 text-slate-300 border border-white/10 mb-4">Why PhishCore</span>
        <h2 class="text-2xl md:text-3xl font-bold text-white mb-3 md:mb-4 leading-snug">Designed to Protect, Not to Confuse.</h2>
        <p class="text-slate-400">PhishCore turns complex threat intelligence into decisions your users can act on immediately.</p>
        <a href="#scanner" class="btn-ghost !rounded-lg !px-5 !py-2.5 !text-sm mt-7">
            Try the scanner
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        </a>
    </div>

    <div class="grid sm:grid-cols-2 gap-3 md:gap-5 lg:gap-6">
        @php
            $why = [
                ['title' => 'Reduces Phishing Risk', 'desc' => 'Gives users a quick, reliable way to verify links before clicking, reducing exposure to credential theft.'],
                ['title' => 'Understandable Results', 'desc' => 'Complex security checks are presented as a clear numeric score with plain-language explanations.'],
                ['title' => 'Centralised Data', 'desc' => 'All scans, reports, and analytics are stored in one place so administrators can monitor threats across the institution.'],
                ['title' => 'Cybersecurity Awareness', 'desc' => 'Helps everyday users build habits around link safety and digital vigilance.'],
            ];
        @endphp
        @foreach ($why as $w)
            @php
                $whyIcons = [
                    'M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z',
                    'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
                    'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
                    'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5',
                ];
            @endphp
            <div class="reveal glass-card p-4 sm:p-5 md:p-6 flex sm:block gap-3.5" style="transition-delay: {{ $loop->index * 0.1 }}s">
                <div class="icon-tile shrink-0 sm:mb-3 md:mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $whyIcons[$loop->index] }}" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-white font-semibold mb-1">{{ $w['title'] }}</p>
                    <p class="text-sm text-slate-400 leading-relaxed">{{ $w['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

<div class="max-w-7xl mx-auto px-6 pb-8">
    <div class="rounded-xl border border-amber-300/20 bg-amber-400/[0.06] p-5 flex gap-4 items-start">
        <span class="w-8 h-8 rounded-lg bg-amber-400/10 border border-amber-300/25 flex items-center justify-center shrink-0">
            <svg width="16" height="16" class="text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        </span>
        <p class="text-sm text-slate-300">
            <span class="text-amber-300 font-medium">Stay Vigilant Online — </span>
            PhishCore supports safer browsing, but users should still avoid sharing passwords, financial details or personal information on unfamiliar websites.
        </p>
    </div>
</div>

{{-- ABOUT --}}
<section id="about" class="scroll-mt-24 md:scroll-mt-28 reveal max-w-7xl mx-auto px-6 py-12 md:py-20 lg:py-24 grid lg:grid-cols-3 gap-8 lg:gap-12 items-start">
    <div class="text-center lg:text-left">
        <div class="w-16 h-16 mx-auto lg:mx-0 rounded-xl bg-sky-400/10 border border-sky-300/25 flex items-center justify-center mb-3">
            <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-10 h-10 object-contain">
        </div>
        <p class="text-white font-medium">PhishCore</p>
        <p class="text-sm text-slate-500">Detection Platform · 2026</p>
    </div>

    <div class="lg:col-span-2">
        <span class="inline-block text-xs px-3 py-1 rounded-full bg-white/5 text-slate-300 border border-white/10 mb-4">About the Project</span>
        <h2 class="text-2xl font-bold text-white mb-4">Built for Everyone.</h2>
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
<section id="contact" class="scroll-mt-24 md:scroll-mt-28 reveal max-w-4xl mx-auto px-6 pb-16 md:pb-24">
    <div class="glass-panel rounded-2xl p-6 md:p-12 text-center">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-2">Ready to Check a Suspicious Link?</h2>
        <p class="text-slate-400 text-[15px] md:text-base mb-6 md:mb-8">Create an account or sign in to begin scanning websites and protecting your digital activity.</p>
        <div class="flex flex-col sm:flex-row sm:justify-center gap-3 md:gap-4">
            <a href="{{ route('register') }}" class="btn-primary !rounded-lg !px-6 !py-3.5 md:!py-3">Create Account</a>
            <a href="{{ route('login') }}" class="btn-ghost !rounded-lg !px-6 !py-3.5 md:!py-3">Sign In</a>
        </div>
    </div>
</section>

<style>
    .reveal {
        opacity: 0;
        transform: var(--from, translateY(32px));
        transition: opacity .7s ease, transform .95s cubic-bezier(.2, 1.1, .3, 1);
        will-change: opacity, transform;
    }
    .reveal.is-visible { opacity: 1; transform: none; }

    /* off-screen starting positions must never create a sideways scrollbar */
    section { overflow-x: clip; }

    .reveal[data-fx="rise"]  { --from: translateY(36px) scale(.96); }
    .reveal[data-fx="pop"]   { --from: scale(.6); }
    .reveal[data-fx="zoom"]  { --from: scale(.88) translateY(20px); }
    .reveal[data-fx="flip"]  { --from: perspective(900px) rotateX(18deg) translateY(30px); transform-origin: 50% 100%; }
    .reveal[data-fx="left"]  { --from: translateX(-56px) rotate(-1.5deg); }
    .reveal[data-fx="right"] { --from: translateX(56px) rotate(1.5deg); }
    .reveal[data-fx="drop"]  { --from: translateY(-22px) scale(1.06); }

    @media (prefers-reduced-motion: reduce) {
        .reveal { opacity: 1; transform: none; transition: none; }
    }
</style>

<script>
    (function () {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const wide = window.matchMedia('(min-width: 768px)').matches;
        if (reduce) return;

        const mark = (el, fx, delay) => {
            el.classList.add('reveal');
            el.dataset.fx = fx;
            el.style.transitionDelay = delay + 's';
        };
        const hasReveal = (el) => el.querySelector('.reveal') !== null;

        document.querySelectorAll('section.reveal').forEach((sec) => {
            sec.classList.remove('reveal');
            sec.style.transitionDelay = '';
            let n = 0;

            const handle = (c) => {
                if (c.classList.contains('reveal')) return;
                if (c.tagName === 'SPAN')      return mark(c, 'pop', 0);
                if (c.tagName === 'H2')        return mark(c, 'drop', 0.08);
                if (c.tagName === 'P')         return mark(c, 'rise', 0.16);
                if (c.classList.contains('grid') && !hasReveal(c)) {
                    Array.from(c.children).forEach((g, i) => mark(g, 'rise', i * 0.1));
                    return;
                }
                if (hasReveal(c)) return;  /* its own cards animate */
                const side = wide && sec.classList.contains('grid') && sec.children.length === 2 ? (n === 0 ? 'left' : 'right') : 'zoom';
                mark(c, side, n * 0.1);
                n++;
            };
            Array.from(sec.children).forEach(handle);
        });

        /* cards: each row gets its own staggered, varied entrance */
        document.querySelectorAll('.reveal.glass-card').forEach((card) => {
            const sibs = Array.from(card.parentElement.children).filter((e) => e.classList.contains('glass-card'));
            const i = sibs.indexOf(card);
            const isSteps = card.closest('#how-it-works') !== null;
            let fx = 'rise';
            if (isSteps) fx = 'flip';
            else if (wide) fx = ['left', 'zoom', 'right'][i % 3];
            card.dataset.fx = fx;
            card.style.transitionDelay = ((i % 3) * 0.1) + 's';
        });
    })();

    document.addEventListener('DOMContentLoaded', function () {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                el.classList.add('is-visible');
                observer.unobserve(el);
                /* once settled, hand the element back to its normal styles so hover effects work again */
                const wait = (parseFloat(el.style.transitionDelay) || 0) * 1000 + 1100;
                setTimeout(() => {
                    el.classList.remove('reveal', 'is-visible');
                    el.style.transitionDelay = '';
                    el.removeAttribute('data-fx');
                }, wait);
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

        /* count-up for the stats */
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-count]').forEach((el) => {
            const raw = el.dataset.count;
            const target = parseFloat(raw) || 0;
            const dec = (raw.split('.')[1] || '').length;
            const suffix = el.dataset.suffix || '';
            const fmt = (v) => v.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suffix;
            if (reduceMotion) return;
            el.textContent = fmt(0);
            const counter = new IntersectionObserver((entries) => {
                if (!entries[0].isIntersecting) return;
                counter.disconnect();
                const start = performance.now();
                const dur = 1400;
                const tick = (now) => {
                    const p = Math.min((now - start) / dur, 1);
                    el.textContent = fmt(target * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
            }, { threshold: 0.6 });
            counter.observe(el);
        });

        document.querySelectorAll('.glass-card').forEach((card) => {
            card.addEventListener('mousemove', (e) => {
                const r = card.getBoundingClientRect();
                card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                card.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
        });
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