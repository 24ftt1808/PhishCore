@props(['stroke' => 1.8])

{{-- Cora's face: a smiling shield. Eyes blink all the time. On hover (floating button, nav item, chat avatars) it bounces and
     squints into a bigger happy smile. The styles are the .rb-* rules in chat-widget. Colour comes from the text colour.
     (The file keeps its old name so every place that uses <x-robot-head> picks up the new face.) --}}
<svg {{ $attributes->merge(['class' => 'rb']) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 2.4l8.4 3.3v6.3c0 4.9-3.5 8.6-8.4 10-4.9-1.4-8.4-5.1-8.4-10V5.7L12 2.4z" />
    <path d="M12 4.6l6.2 2.4v5c0 3.6-2.5 6.5-6.2 7.7-3.7-1.2-6.2-4.1-6.2-7.7V7L12 4.6z" fill="currentColor" fill-opacity=".14" stroke="none" />
    <ellipse class="rb-eye" cx="9.2" cy="10.4" rx="1.1" ry="1.55" fill="currentColor" stroke="none" />
    <ellipse class="rb-eye" cx="14.8" cy="10.4" rx="1.1" ry="1.55" fill="currentColor" stroke="none" />
    <path class="rb-eye-happy" d="M7.9 11.2q1.3-2.1 2.6 0M13.5 11.2q1.3-2.1 2.6 0" stroke-width="1.5" />
    <path class="rb-mouth" d="M9.7 14.3q2.3 1.9 4.6 0" stroke-width="1.5" />
    <path class="rb-smile" d="M9 13.7q3 3.5 6 0" stroke-width="1.5" />
</svg>