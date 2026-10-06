<x-guest-layout>

    <x-slot:rightPanel>
        <x-auth-aside title="Secure Account Recovery"
                      text="PhishCore uses secure, time-limited links to protect your account during password recovery."
                      :items="[
            ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'Reset links expire after 15 minutes'],
            ['M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'Each link can only be used once'],
            ['M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z', 'Your password is never sent by email'],
        ]" />
    </x-slot:rightPanel>

    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-slate-200 mb-8">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Sign In
    </a>

    <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center mb-6">
        <svg class="w-6 h-6 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
        </svg>
    </div>

    <h1 class="text-3xl font-bold text-white mb-1">Set a new password</h1>
    <p class="text-slate-400 mb-8">Choose a strong new password for your PhishCore account.</p>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" x-data="{ showPassword: false, showConfirm: false }">
        @csrf

        {{-- Password Reset Token --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div>
            <label for="email" class="block text-xs tracking-wide text-slate-400 mb-2">EMAIL ADDRESS</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                   placeholder="yourname@example.com"
                   class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
            @error('email')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs tracking-wide text-slate-400 mb-2">NEW PASSWORD</label>
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

        <div>
            <label for="password_confirmation" class="block text-xs tracking-wide text-slate-400 mb-2">CONFIRM NEW PASSWORD</label>
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

        <button type="submit"
                class="w-full py-3 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white font-medium hover:opacity-90 transition">
            Reset Password
        </button>

        <div class="flex items-start gap-3 bg-slate-900/60 border border-slate-800 rounded-lg px-4 py-3 mt-6">
            <span class="text-sky-400 mt-0.5">🔒</span>
            <p class="text-xs text-slate-500">
                This reset link can only be used once. If it has expired, request a new one from the sign-in page.
            </p>
        </div>
    </form>

</x-guest-layout>