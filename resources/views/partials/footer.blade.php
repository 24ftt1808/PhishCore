<footer class="relative border-t border-white/10 bg-slate-950/60">
    <div class="max-w-7xl mx-auto px-6 pt-16 pb-10">
        <div class="grid gap-12 md:grid-cols-[1.4fr_1fr_1fr_1fr]">

            {{-- Brand --}}
            <div>
                <a href="{{ route('welcome') }}#home" class="inline-flex items-center gap-3">
                    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-9 h-9 object-contain">
                    <span class="leading-tight">
                        <span class="block font-bold text-white">PhishCore</span>
                        <span class="block text-[11px] text-sky-200/90 tracking-wide">Detection Platform</span>
                    </span>
                </a>
                <p class="mt-5 text-sm text-slate-400 leading-relaxed max-w-xs">
                    An AI-assisted phishing detection platform built to support cybersecurity awareness and safe browsing.
                </p>
                <span class="mt-5 inline-flex items-center gap-2 text-xs text-emerald-300 px-3 py-1.5 rounded-full border border-emerald-300/20 bg-emerald-400/[0.07]">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Engine online
                </span>
            </div>

            {{-- Quick links --}}
            <div>
                <p class="eyebrow mb-5 !text-slate-500">Quick links</p>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('welcome') }}#home" class="footer-link">Home</a></li>
                    <li><a href="{{ route('welcome') }}#features" class="footer-link">Features</a></li>
                    <li><a href="{{ route('welcome') }}#how-it-works" class="footer-link">How It Works</a></li>
                    <li><a href="{{ route('welcome') }}#about" class="footer-link">About</a></li>
                    <li><a href="{{ route('reports.public') }}" class="footer-link">Public Reports</a></li>
                </ul>
            </div>

            {{-- Platform --}}
            <div>
                <p class="eyebrow mb-5 !text-slate-500">Platform</p>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('welcome') }}#scanner" class="footer-link">URL Scanning</a></li>
                    <li><a href="{{ route('welcome') }}#features" class="footer-link">AI Threat Detection</a></li>
                    <li><a href="{{ route('welcome') }}#features" class="footer-link">Security Reports</a></li>
                    <li><a href="{{ route('welcome') }}#features" class="footer-link">Scan History</a></li>
                </ul>
            </div>

            {{-- Account --}}
            <div>
                <p class="eyebrow mb-5 !text-slate-500">Account</p>
                <ul class="space-y-3 text-sm">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="footer-link">Dashboard</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="footer-link">Sign In</a></li>
                        <li><a href="{{ route('register') }}" class="footer-link">Create Account</a></li>
                    @endauth
                </ul>
            </div>
        </div>

        <div class="mt-14 pt-6 border-t border-white/10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
                PhishCore &copy; 2026. Results are provided for security guidance and should not be treated as a guarantee.
            </p>
            <div class="flex items-center gap-5 shrink-0">
                <span class="text-xs text-slate-500">Built with care for a safer Brunei.</span>
                <a href="{{ route('welcome') }}#home" aria-label="Back to top"
                   class="w-9 h-9 grid place-items-center rounded-full border border-white/10 text-slate-400 hover:text-white hover:border-white/25 hover:bg-white/5 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer-link {
        color: #94a3b8;
        transition: color 0.2s ease, padding-left 0.2s ease;
    }
    .footer-link:hover {
        color: #fff;
        padding-left: 4px;
    }
</style>