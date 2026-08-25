<nav
    x-data="{
        active: 'home',
        mobileOpen: false,
        sections: ['home', 'features', 'how-it-works', 'about', 'contact'],
       updateActive() {
    // If scrolled to (or near) the bottom of the page, force the last section active
    const scrolledToBottom = (window.innerHeight + window.scrollY) >= (document.body.scrollHeight - 10);
    if (scrolledToBottom) {
        this.active = this.sections[this.sections.length - 1];
        return;
    }

    let current = 'home';
    for (const id of this.sections) {
        const el = document.getElementById(id);
        if (el) {
            const rect = el.getBoundingClientRect();
            if (rect.top <= 120) {
                current = id;
            }
        }
    }
    this.active = current;
}
    }"
    x-init="updateActive(); window.addEventListener('scroll', () => updateActive())"
    class="border-b border-slate-800/60 bg-slate-950/80 backdrop-blur sticky top-0 z-50"
>
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <a href="#home" class="flex items-center gap-2">
            <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
                </svg>
            </span>
            <span class="leading-tight">
                <span class="block font-bold text-white">PhishCore</span>
                <span class="block text-[11px] text-slate-400 -mt-1">Detection Platform</span>
            </span>
        </a>

        <div class="hidden md:flex items-center gap-8 text-sm">
            <a href="#home" :class="active === 'home' ? 'text-sky-400' : 'text-slate-300 hover:text-white'" class="transition">Home</a>
            <a href="#features" :class="active === 'features' ? 'text-sky-400' : 'text-slate-300 hover:text-white'" class="transition">Features</a>
            <a href="#how-it-works" :class="active === 'how-it-works' ? 'text-sky-400' : 'text-slate-300 hover:text-white'" class="transition">How It Works</a>
            <a href="#about" :class="active === 'about' ? 'text-sky-400' : 'text-slate-300 hover:text-white'" class="transition">About</a>
            <a href="#contact" :class="active === 'contact' ? 'text-sky-400' : 'text-slate-300 hover:text-white'" class="transition">Contact</a>
        </div>

        <div class="hidden md:flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm text-slate-300 hover:text-white">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm text-slate-300 hover:text-white">Sign In</a>
                <a href="{{ route('register') }}"
                   class="text-sm font-medium px-4 py-2 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white hover:opacity-90 transition">
                    Create Account
                </a>
            @endauth
        </div>

        <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-slate-300">
            <svg x-show="!mobileOpen" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
            <svg x-show="mobileOpen" x-cloak width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- MOBILE MENU --}}
    <div x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="md:hidden border-t border-slate-800/60 bg-slate-950 px-6 py-4 space-y-1">
        <a href="#home" @click="mobileOpen = false" :class="active === 'home' ? 'text-sky-400 bg-sky-500/10' : 'text-slate-300'" class="block px-3 py-2.5 rounded-lg text-sm transition">Home</a>
        <a href="#features" @click="mobileOpen = false" :class="active === 'features' ? 'text-sky-400 bg-sky-500/10' : 'text-slate-300'" class="block px-3 py-2.5 rounded-lg text-sm transition">Features</a>
        <a href="#how-it-works" @click="mobileOpen = false" :class="active === 'how-it-works' ? 'text-sky-400 bg-sky-500/10' : 'text-slate-300'" class="block px-3 py-2.5 rounded-lg text-sm transition">How It Works</a>
        <a href="#about" @click="mobileOpen = false" :class="active === 'about' ? 'text-sky-400 bg-sky-500/10' : 'text-slate-300'" class="block px-3 py-2.5 rounded-lg text-sm transition">About</a>
        <a href="#contact" @click="mobileOpen = false" :class="active === 'contact' ? 'text-sky-400 bg-sky-500/10' : 'text-slate-300'" class="block px-3 py-2.5 rounded-lg text-sm transition">Contact</a>

        <div class="pt-3 mt-3 border-t border-slate-800/60 space-y-2">
            @auth
                <a href="{{ route('dashboard') }}" @click="mobileOpen = false" class="block px-3 py-2.5 rounded-lg text-sm text-slate-300">Dashboard</a>
            @else
                <a href="{{ route('login') }}" @click="mobileOpen = false" class="block px-3 py-2.5 rounded-lg text-sm text-slate-300">Sign In</a>
                <a href="{{ route('register') }}" @click="mobileOpen = false"
                   class="block text-center text-sm font-medium px-4 py-2.5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white">
                    Create Account
                </a>
            @endauth
        </div>
    </div>
</nav>