<x-guest-layout>

    <x-slot:rightPanel>
        <div class="w-20 h-20 mx-auto mb-8 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center">
            <svg class="w-10 h-10 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25 4.5-4.5M21 12c0 4.556-3.6 8.318-8.25 8.965-4.65-.647-8.25-4.409-8.25-8.965V6.75l8.25-3.75 8.25 3.75V12z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-white mb-4">Protect Your Account</h2>
        <p class="text-slate-400 mb-10">
            A strong, unique password helps prevent unauthorised access to your PhishCore account.
        </p>

        <div class="space-y-4 text-left">
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🔒</span>
                <span class="text-sm text-slate-200">Never share your password</span>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🔁</span>
                <span class="text-sm text-slate-200">Avoid reusing passwords from other accounts</span>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🛡️</span>
                <span class="text-sm text-slate-200">Enable two-factor authentication after signing in</span>
            </div>
        </div>
    </x-slot:rightPanel>

    <div x-data="{
        password: '',
        confirmation: '',
        showPassword: false,
        showConfirm: false,
        get hasLength() { return this.password.length >= 8 },
        get hasLower() { return /[a-z]/.test(this.password) },
        get hasUpper() { return /[A-Z]/.test(this.password) },
        get hasNumber() { return /[0-9]/.test(this.password) },
        get hasSpecial() { return /[^A-Za-z0-9]/.test(this.password) },
        get score() {
            return [this.hasLength, this.hasLower, this.hasUpper, this.hasNumber, this.hasSpecial].filter(Boolean).length;
        },
        get strengthLabel() {
            if (this.password.length === 0) return 'No password';
            if (this.score <= 2) return 'Weak';
            if (this.score <= 4) return 'Good';
            return 'Strong';
        },
        get strengthColor() {
            if (this.password.length === 0) return 'bg-slate-800';
            if (this.score <= 2) return 'bg-red-500';
            if (this.score <= 4) return 'bg-yellow-500';
            return 'bg-emerald-500';
        },
        get passwordsMatch() { return this.password.length > 0 && this.password === this.confirmation },
        get canSubmit() { return this.score === 5 && this.passwordsMatch }
    }">

        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-slate-200 mb-8">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Sign In
        </a>

        <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-6">
            <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>

        <h1 class="text-3xl font-bold text-white mb-1">Create a new password</h1>
        <p class="text-slate-400 mb-6">Choose a strong password that you have not previously used for this account.</p>

        {{-- Email chip --}}
        @php
            $initials = collect(explode('.', explode('@', $request->email ?? old('email'))[0]))
                ->map(fn($p) => strtoupper(substr($p, 0, 1)))
                ->take(2)
                ->implode('');
        @endphp
        <div class="inline-flex items-center gap-2 bg-slate-900/60 border border-slate-800 rounded-full pl-2 pr-4 py-1.5 mb-8">
            <span class="w-6 h-6 rounded-full bg-sky-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ $initials ?: '?' }}
            </span>
            <span class="text-sm text-slate-300">{{ $request->email ?? old('email') }}</span>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <input type="hidden" name="email" value="{{ $request->email ?? old('email') }}">

            {{-- New Password --}}
            <div>
                <label for="password" class="block text-xs tracking-wide text-slate-400 mb-2">NEW PASSWORD</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" x-model="password" required autofocus
                           class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 pr-11 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
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
                <label for="password_confirmation" class="block text-xs tracking-wide text-slate-400 mb-2">CONFIRM NEW PASSWORD</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" x-model="confirmation" required
                           class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 pr-11 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
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

            {{-- Strength meter --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex gap-1.5 flex-1 mr-4">
                        <template x-for="i in 4" :key="i">
                            <div class="h-1.5 flex-1 rounded-full" :class="i <= score ? strengthColor : 'bg-slate-800'"></div>
                        </template>
                    </div>
                    <span class="text-xs text-slate-500 whitespace-nowrap" x-text="strengthLabel"></span>
                </div>

                <div class="grid grid-cols-2 gap-x-4 gap-y-2 mt-4">
                    <div class="flex items-center gap-2 text-xs" :class="hasLength ? 'text-emerald-400' : 'text-slate-500'">
                        <span class="w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0" :class="hasLength ? 'border-emerald-400 bg-emerald-400/20' : 'border-slate-600'">
                            <svg x-show="hasLength" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        At least 8 characters
                    </div>
                    <div class="flex items-center gap-2 text-xs" :class="hasUpper ? 'text-emerald-400' : 'text-slate-500'">
                        <span class="w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0" :class="hasUpper ? 'border-emerald-400 bg-emerald-400/20' : 'border-slate-600'">
                            <svg x-show="hasUpper" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        One uppercase letter
                    </div>
                    <div class="flex items-center gap-2 text-xs" :class="hasLower ? 'text-emerald-400' : 'text-slate-500'">
                        <span class="w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0" :class="hasLower ? 'border-emerald-400 bg-emerald-400/20' : 'border-slate-600'">
                            <svg x-show="hasLower" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        One lowercase letter
                    </div>
                    <div class="flex items-center gap-2 text-xs" :class="hasNumber ? 'text-emerald-400' : 'text-slate-500'">
                        <span class="w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0" :class="hasNumber ? 'border-emerald-400 bg-emerald-400/20' : 'border-slate-600'">
                            <svg x-show="hasNumber" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        One number
                    </div>
                    <div class="flex items-center gap-2 text-xs" :class="hasSpecial ? 'text-emerald-400' : 'text-slate-500'">
                        <span class="w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0" :class="hasSpecial ? 'border-emerald-400 bg-emerald-400/20' : 'border-slate-600'">
                            <svg x-show="hasSpecial" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        One special character
                    </div>
                    <div class="flex items-center gap-2 text-xs" :class="passwordsMatch ? 'text-emerald-400' : 'text-slate-500'">
                        <span class="w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0" :class="passwordsMatch ? 'border-emerald-400 bg-emerald-400/20' : 'border-slate-600'">
                            <svg x-show="passwordsMatch" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        Not a previously used password
                    </div>
                </div>
            </div>

            <button type="submit" :disabled="!canSubmit"
                    :class="canSubmit ? 'bg-gradient-to-r from-sky-400 to-blue-600 hover:opacity-90 cursor-pointer' : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
                    class="w-full py-3 rounded-lg font-medium transition">
                Reset Password
            </button>

            <p class="text-center text-sm">
                <a href="{{ route('login') }}" class="text-sky-400 hover:text-sky-300">Return to Sign In</a>
            </p>
        </form>
    </div>

</x-guest-layout>