@props(['title', 'updated' => '11 October 2026'])

<x-layouts.guest-landing>
    <section class="max-w-3xl mx-auto px-6 pt-8 md:pt-10 pb-16 md:pb-24">
        <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-sky-500/10 text-sky-300 border border-sky-400/20 mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> Legal
        </span>
        <h1 class="text-[1.7rem] md:text-3xl font-bold text-white mb-2">{{ $title }}</h1>
        <p class="text-slate-400 text-sm mb-8">Last updated {{ $updated }}</p>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 md:p-8 space-y-7 text-sm leading-relaxed text-slate-300
                    [&_h2]:text-white [&_h2]:text-base [&_h2]:font-semibold [&_h2]:mb-2
                    [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5 [&_ul]:mt-2
                    [&_a]:text-sky-400 hover:[&_a]:text-sky-300">
            {{ $slot }}
        </div>

        <div class="mt-6 flex flex-wrap gap-4 text-xs text-slate-500">
            <a href="{{ route('terms') }}" class="hover:text-white transition">Terms of Use</a>
            <a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy Policy</a>
            <a href="{{ route('welcome') }}" class="hover:text-white transition">Back to home</a>
        </div>
    </section>
</x-layouts.guest-landing>