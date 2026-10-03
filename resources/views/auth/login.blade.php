<x-guest-layout>

    <div x-data="{ showPassword: false, busy: false }"
         x-init="window.addEventListener('pageshow', (e) => { if (e.persisted) busy = false; })">

        <h1 class="text-3xl font-bold text-white mb-1.5">Welcome back</h1>
        <p class="text-slate-400 mb-7">Sign in to your security dashboard</p>

        {{-- Session Status --}}
        @if (session('status'))
            <div class="mb-5 flex items-center gap-2 text-sm text-emerald-300 rounded-xl border border-emerald-300/20 bg-emerald-400/[0.06] px-4 py-3">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5" @submit="busy = true">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs tracking-wide text-slate-400 mb-2">EMAIL ADDRESS</label>
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0l-9.75 6-9.75-6" /></svg>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           placeholder="yourname@example.com"
                           class="field !pl-11 !py-3.5">
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-xs tracking-wide text-slate-400 mb-2">PASSWORD</label>
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           class="field !pl-11 !pr-12 !py-3.5">
                    <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-500 hover:text-slate-200 transition"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'">
                        <svg x-show="!showPassword" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <svg x-show="showPassword" x-cloak class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember + Forgot --}}
            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer select-none">
                    <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-sky-500 focus:ring-sky-500/50 focus:ring-offset-0">
                    Remember me
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-sky-300 hover:text-sky-200 transition">
                        Forgot password?
                    </a>
                @endif
            </div>

            {{-- Submit --}}
            <button type="submit" :disabled="busy"
                    class="btn-primary !rounded-xl !py-3.5 !text-sm font-semibold w-full transition active:scale-[0.98] disabled:opacity-70">
                <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="busy ? 'Signing in...' : 'Sign In'"></span>
                <svg x-show="!busy" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </button>

            <div class="flex items-center gap-3">
                <span class="flex-1 h-px bg-white/10"></span>
                <span class="text-[10px] tracking-[0.16em] text-slate-500">NEW HERE?</span>
                <span class="flex-1 h-px bg-white/10"></span>
            </div>

            <a href="{{ route('register') }}" class="btn-ghost !rounded-xl !py-3 !text-sm w-full justify-center">
                Create an account
            </a>
        </form>
    </div>

</x-guest-layout>