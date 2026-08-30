<x-guest-layout>

    <x-slot:rightPanel>
<div class="w-20 h-20 mx-auto mb-8 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center">
    <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-12 h-12 object-contain">
</div>

        <h2 class="text-2xl font-bold text-white mb-4">Secure Account Recovery</h2>
        <p class="text-slate-400 mb-10">
            PhishCore uses secure, time-limited links to protect your account during password recovery.
        </p>

        <div class="space-y-4 text-left">
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🕐</span>
                <span class="text-sm text-slate-200">Reset links expire after 15 minutes</span>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🛡️</span>
                <span class="text-sm text-slate-200">Each link can only be used once</span>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/60 border border-slate-800 rounded-xl px-5 py-4">
                <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shrink-0">🔒</span>
                <span class="text-sm text-slate-200">Your password is never sent by email</span>
            </div>
        </div>
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

    <h1 class="text-3xl font-bold text-white mb-1">Forgot your password?</h1>
    <p class="text-slate-400 mb-8">Enter your registered email address and we'll send you instructions to reset your password.</p>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="mb-4 text-sm font-medium text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-xs tracking-wide text-slate-400 mb-2">EMAIL ADDRESS</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   placeholder="yourname@example.com"
                   class="w-full bg-slate-900 border border-slate-800 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-sky-500 transition">
            @error('email')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white font-medium hover:opacity-90 transition">
            Send Reset Link
        </button>

        <p class="text-center text-sm text-slate-400">
            Remember your password?
            <a href="{{ route('login') }}" class="text-sky-400 hover:text-sky-300">Sign in</a>
        </p>

        <div class="flex items-start gap-3 bg-slate-900/60 border border-slate-800 rounded-lg px-4 py-3 mt-6">
            <span class="text-sky-400 mt-0.5">🔒</span>
            <p class="text-xs text-slate-500">
                For your security, the reset link will expire after 15 minutes.
            </p>
        </div>
    </form>

</x-guest-layout>