<x-guest-layout>

    <x-slot:rightPanel>
     <div class="w-20 h-20 mx-auto mb-8 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-12 h-12 object-contain">
</div>

        <h2 class="text-2xl font-bold text-white mb-4">Join the PhishCore Platform</h2>
        <p class="text-slate-400 mb-10">
            Help protect Politeknik Brunei's digital environment by detecting and reporting suspicious websites.
        </p>

        <div class="space-y-4 text-left">
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🔍</span>
                <span class="text-sm text-slate-200">Scan suspicious website links</span>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🛡️</span>
                <span class="text-sm text-slate-200">Access detailed detection results</span>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">📄</span>
                <span class="text-sm text-slate-200">Monitor previous scans and security reports</span>
            </div>
        </div>
    </x-slot:rightPanel>

    <div x-data="{ showPassword: false, showConfirm: false, agreed: false }">

        <h1 class="text-3xl font-bold text-white mb-1">Create your account</h1>
        <p class="text-slate-400 mb-8">Register to access the PhishCore security platform.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            {{-- Full Name --}}
            <div>
                <label for="name" class="block text-xs tracking-wide text-slate-400 mb-2">FULL NAME</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       placeholder="e.g. Ahmad Razif bin Haji Rosli"
                       class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                @error('name')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                               <label for="email" class="block text-xs tracking-wide text-slate-400 mb-2">EMAIL ADDRESS</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                       placeholder="yourname@example.com"
                       class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                @error('email')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

           

            {{-- Password --}}
            <div>
                <label for="password" class="block text-xs tracking-wide text-slate-400 mb-2">PASSWORD</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password"
                           placeholder="••••••••"
                           class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 pr-11 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-xs tracking-wide text-slate-400 mb-2">CONFIRM PASSWORD</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                           placeholder="••••••••"
                           class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 pr-11 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
                    <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Terms checkbox --}}
            <label class="flex items-start gap-3 text-sm text-slate-400">
                <input type="checkbox" x-model="agreed" required
                       class="mt-0.5 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500">
                <span>
                    I agree to the
                    <a href="#" class="text-sky-400 hover:text-sky-300">Terms of Use</a>
                    and
                    <a href="#" class="text-sky-400 hover:text-sky-300">Privacy Policy</a>
                </span>
            </label>

            {{-- Submit --}}
            <button type="submit" :disabled="!agreed"
                    :class="agreed ? 'bg-gradient-to-r from-sky-400 to-blue-600 hover:opacity-90 cursor-pointer' : 'bg-slate-800 cursor-not-allowed'"
                    class="w-full py-3 rounded-lg text-white font-medium transition">
                Create Account
            </button>

            <p class="text-center text-sm text-slate-400">
                Already have an account?
                <a href="{{ route('login') }}" class="text-sky-400 hover:text-sky-300">Sign in</a>
            </p>
        </form>
    </div>

</x-guest-layout>