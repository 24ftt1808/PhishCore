@props(['title', 'text', 'items' => [], 'icon' => null])

<div class="auth-float relative w-24 h-24 mx-auto mb-8 grid place-items-center rounded-3xl border border-white/10 bg-white/[0.04] shadow-[0_0_50px_-10px_rgba(56,189,248,0.35)]">
    @if ($icon)
        <svg class="w-11 h-11 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
    @else
        <img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" class="w-14 h-14 object-contain">
    @endif
</div>

<h2 class="text-3xl font-bold text-white mb-4 leading-snug">{{ $title }}</h2>
<p class="text-slate-400 mb-9 leading-relaxed">{{ $text }}</p>

<div class="space-y-3 text-left">
    @foreach ($items as $i => $item)
        <div class="auth-in glass-card flex items-center gap-4 px-5 py-4" style="--d:{{ 0.3 + $i * 0.1 }}s">
            <span class="icon-tile shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item[0] }}" /></svg>
            </span>
            <span class="text-sm text-slate-200">{{ $item[1] }}</span>
        </div>
    @endforeach
</div>