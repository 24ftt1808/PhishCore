<x-guest-layout>

    <x-slot:rightPanel>
        <x-auth-aside title="Join the PhishCore Platform"
                      text="Help protect your digital environment by detecting and reporting suspicious websites."
                      :items="[
            ['M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z', 'Scan suspicious website links'],
            ['M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'Access detailed detection results'],
            ['M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z', 'Monitor previous scans and security reports'],
        ]" />
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
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener" class="text-sky-400 hover:text-sky-300">Terms of Use</a>
                    and
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="text-sky-400 hover:text-sky-300">Privacy Policy</a>
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