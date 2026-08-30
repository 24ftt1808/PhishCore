<footer class="border-t border-slate-800/60 bg-slate-950">
    <div class="max-w-7xl mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-3 gap-10">
        <div>
            <div class="flex items-center gap-2 mb-3">
             <span class="w-8 h-8 flex items-center justify-center">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-8 h-8 object-contain">
</span>
                <span class="font-bold text-white">PhishCore</span>
            </div>
            <p class="text-sm text-slate-400 max-w-xs">
                An AI-assisted phishing detection platform developed as a final-year project for Politeknik Brunei to support cybersecurity awareness and safe browsing.
            </p>
        </div>

        <div>
            <p class="text-xs tracking-wider text-slate-500 mb-4">QUICK LINKS</p>
            <ul class="space-y-2 text-sm text-slate-400">
                <li><a href="#" class="hover:text-white">Home</a></li>
                <li><a href="#features" class="hover:text-white">Features</a></li>
                <li><a href="#how-it-works" class="hover:text-white">How It Works</a></li>
                <li><a href="#about" class="hover:text-white">About</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-white">Sign In</a></li>
            </ul>
        </div>

        <div>
            <p class="text-xs tracking-wider text-slate-500 mb-4">PLATFORM</p>
            <ul class="space-y-2 text-sm text-slate-400">
                <li>Real-Time URL Scanning</li>
                <li>AI Threat Detection</li>
                <li>Security Reports</li>
                <li>Scan History</li>
            </ul>
        </div>
    </div>

    <div class="border-t border-slate-800/60">
        <div class="max-w-7xl mx-auto px-6 py-5 flex flex-col md:flex-row justify-between text-xs text-slate-500 gap-2">
            <span>Politeknik Brunei — Final Year Project 2026. Results are provided for security guidance and should not be treated as a guarantee.</span>
            <span>Built with care for a safer Brunei.</span>
        </div>
    </div>
</footer>