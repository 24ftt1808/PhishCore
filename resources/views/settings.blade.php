<x-layouts.dashboard>

    <div class="flex items-start justify-between mb-8 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1">Settings</h1>
            <p class="text-slate-400 text-sm">Manage your account and security preferences.</p>
        </div>
    </div>

        <div x-data="{ tab: '{{ $errors->userDeletion->isNotEmpty() ? 'security' : 'profile' }}' }">
        <div class="flex gap-1 bg-slate-900 border border-slate-800 rounded-lg p-1 mb-6 w-fit">
            <button @click="tab = 'profile'"
                    :class="tab === 'profile' ? 'bg-sky-500/20 text-sky-400' : 'text-slate-400 hover:text-slate-200'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition">
                Profile
            </button>
            <button @click="tab = 'security'"
                    :class="tab === 'security' ? 'bg-sky-500/20 text-sky-400' : 'text-slate-400 hover:text-slate-200'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition">
                Security
            </button>
        </div>

        {{-- PROFILE TAB --}}
        <div x-show="tab === 'profile'" x-cloak class="space-y-6">

            {{-- Account Overview --}}
                      <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
                @if (session('status') === 'photo-updated')
                    <div class="mb-4 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
                        Profile photo updated.
                    </div>
                @endif
                @if (session('status') === 'photo-removed')
                    <div class="mb-4 text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-4 py-2.5">
                        Profile photo removed.
                    </div>
                @endif
                @error('photo')
                    <div class="mb-4 text-sm text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex items-center gap-5 mb-6" x-data="{ preview: null }">
                    <form id="photo-upload-form" method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="contents">
                        @csrf
                        <label for="photo-input" class="relative w-16 h-16 shrink-0 cursor-pointer group">
                            <template x-if="preview">
                                <img :src="preview" class="w-16 h-16 rounded-full object-cover">
                            </template>
                            <template x-if="!preview">
                                @if ($user->photoUrl())
                                    <img src="{{ $user->photoUrl() }}" class="w-16 h-16 rounded-full object-cover">
                                @else
                                    <span class="w-16 h-16 rounded-full bg-sky-500 text-white text-lg font-bold flex items-center justify-center">
                                        {{ collect(explode(' ', $user->name))->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') }}
                                    </span>
                                @endif
                            </template>
                            <span class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.132.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.132-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                            </span>
                        </label>
                        <input id="photo-input" type="file" name="photo" accept="image/*" class="hidden"
                               @change="
                                   const file = $event.target.files[0];
                                   if (file) {
                                       preview = URL.createObjectURL(file);
                                       document.getElementById('photo-upload-form').submit();
                                   }
                               ">
                    </form>

                    <div>
                        <p class="text-white font-semibold text-lg">{{ $user->name }}</p>
                        <p class="text-sm text-slate-500">{{ $user->email }}</p>
                        <div class="flex items-center gap-3 mt-1.5">
                            <label for="photo-input" class="text-xs text-sky-400 hover:text-sky-300 cursor-pointer">Change photo</label>
                            @if ($user->photoUrl())
                                <form method="POST" action="{{ route('profile.photo.destroy') }}" onsubmit="return confirm('Remove your profile photo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-slate-500 hover:text-red-400 transition">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 pt-5 border-t border-slate-800">
                    <div>
                        <p class="text-xs text-slate-500 mb-1.5">ROLE</p>
                        <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full {{ $user->role === 'admin' ? 'bg-sky-500/10 text-sky-400' : 'bg-slate-500/10 text-slate-400' }}">
                            {{ $user->role === 'admin' ? 'ADMINISTRATOR' : 'MEMBER' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1.5">TEAM MEMBER</p>
                        @if ($user->is_team_member)
                            <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full bg-violet-500/10 text-violet-400">YES</span>
                        @else
                            <span class="text-sm text-slate-400">No</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1.5">MEMBER SINCE</p>
                        <p class="text-sm text-slate-300">{{ $user->created_at->format('j F Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Profile Information --}}
            <div class="grid lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-white font-semibold mb-1">Profile Information</h2>
                    <p class="text-sm text-slate-500 mb-5">Update your account's name and email address.</p>

                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>

                    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
                        @csrf
                        @method('patch')
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs text-slate-500 mb-1.5">NAME</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                                       class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="email" class="block text-xs text-slate-500 mb-1.5">EMAIL ADDRESS</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                                       class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                                @error('email')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="bg-orange-500/5 border border-orange-500/20 rounded-lg px-4 py-3">
                                <p class="text-xs text-orange-300">
                                    Your email address is unverified.
                                    <button form="send-verification" class="underline hover:text-orange-200">
                                        Click here to re-send the verification email.
                                    </button>
                                </p>
                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-1.5 text-xs text-emerald-400">A new verification link has been sent to your email address.</p>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold hover:opacity-90 transition">
                                Save Changes
                            </button>
                            @if (session('status') === 'profile-updated')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                   class="text-sm text-emerald-400">Saved.</p>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Info sidebar --}}
                <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-9 h-9 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                        </span>
                        <h3 class="text-white font-medium text-sm">About Your Info</h3>
                    </div>
                    <ul class="space-y-3 text-xs text-slate-400 leading-relaxed">
                        <li class="flex gap-2">
                            <span class="text-sky-400 shrink-0">•</span>
                            Changing your email requires re-verification before it's confirmed.
                        </li>
                        <li class="flex gap-2">
                            <span class="text-sky-400 shrink-0">•</span>
                            Your name appears on scan reports you submit and investigations you manage.
                        </li>
                        <li class="flex gap-2">
                            <span class="text-sky-400 shrink-0">•</span>
                            Role and Team Member access are managed by an administrator — visit User Management to request a change.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

               {{-- SECURITY TAB --}}
        <div x-show="tab === 'security'" x-cloak class="space-y-6">

            <div class="grid lg:grid-cols-3 gap-4">
                {{-- Update Password --}}
                <div class="lg:col-span-2 bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-white font-semibold mb-1">Update Password</h2>
                    <p class="text-sm text-slate-500 mb-5">Ensure your account is using a long, random password to stay secure.</p>

                    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
                        @csrf
                        @method('put')
                                            <div x-data="{ show: false }">
                            <label for="update_password_current_password" class="block text-xs text-slate-500 mb-1.5">CURRENT PASSWORD</label>
                            <div class="relative">
                                <input id="update_password_current_password" name="current_password" :type="show ? 'text' : 'password'" autocomplete="current-password"
                                       class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 pr-11 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                                <button type="button" @click="show = !show" tabindex="-1"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                </button>
                            </div>
                            @error('current_password', 'updatePassword')
                                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                                                      <div x-data="{ show: false }">
                                <label for="update_password_password" class="block text-xs text-slate-500 mb-1.5">NEW PASSWORD</label>
                                <div class="relative">
                                    <input id="update_password_password" name="password" :type="show ? 'text' : 'password'" autocomplete="new-password"
                                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 pr-11 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                                    <button type="button" @click="show = !show" tabindex="-1"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition">
                                        <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    </button>
                                </div>
                                @error('password', 'updatePassword')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div x-data="{ show: false }">
                                <label for="update_password_password_confirmation" class="block text-xs text-slate-500 mb-1.5">CONFIRM PASSWORD</label>
                                <div class="relative">
                                    <input id="update_password_password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" autocomplete="new-password"
                                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 pr-11 text-sm text-slate-200 focus:outline-none focus:border-sky-500 transition">
                                    <button type="button" @click="show = !show" tabindex="-1"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition">
                                        <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    </button>
                                </div>
                                @error('password_confirmation', 'updatePassword')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-sky-400 to-blue-600 text-white text-sm font-semibold hover:opacity-90 transition">
                                Update Password
                            </button>
                            @if (session('status') === 'password-updated')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                                   class="text-sm text-emerald-400">Saved.</p>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Password tips sidebar --}}
                <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                        </span>
                        <h3 class="text-white font-medium text-sm">Password Tips</h3>
                    </div>
                    <ul class="space-y-3 text-xs text-slate-400 leading-relaxed">
                        <li class="flex gap-2"><span class="text-emerald-400 shrink-0">•</span> Use at least 12 characters mixing letters, numbers, and symbols.</li>
                        <li class="flex gap-2"><span class="text-emerald-400 shrink-0">•</span> Avoid reusing passwords from other accounts.</li>
                        <li class="flex gap-2"><span class="text-emerald-400 shrink-0">•</span> Never share your password, even with PhishCore team members.</li>
                    </ul>
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6" x-data="{ confirmingDeletion: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex items-start gap-3">
                        <span class="w-9 h-9 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </span>
                        <div>
                            <h2 class="text-red-400 font-semibold">Delete Account</h2>
                            <p class="text-sm text-slate-500 mt-1 max-w-md">Once your account is deleted, all of its resources and data will be permanently deleted. This action cannot be undone.</p>
                        </div>
                    </div>
                    <button @click="confirmingDeletion = true"
                            class="shrink-0 px-5 py-2.5 rounded-lg border border-red-500/40 bg-red-500/10 text-red-400 text-sm font-semibold hover:bg-red-500/20 transition">
                        Delete Account
                    </button>
                </div>

                <div x-show="confirmingDeletion" x-cloak
                     class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
                     x-transition>
                    <div @click.outside="confirmingDeletion = false"
                         class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full">
                        <h3 class="text-white font-semibold mb-2">Are you sure you want to delete your account?</h3>
                        <p class="text-sm text-slate-400 mb-4">
                            Once your account is deleted, all of its resources and data will be permanently deleted. Enter your password to confirm.
                        </p>
                        <form method="post" action="{{ route('profile.destroy') }}">
                            @csrf
                            @method('delete')
                            <input type="password" name="password" placeholder="Password"
                                   class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-red-500 transition mb-2">
                            @error('password', 'userDeletion')
                                <p class="mb-3 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" @click="confirmingDeletion = false"
                                        class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 text-sm hover:bg-slate-800 transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 rounded-lg bg-red-500 text-white text-sm font-semibold hover:bg-red-600 transition">
                                    Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

</x-layouts.dashboard>