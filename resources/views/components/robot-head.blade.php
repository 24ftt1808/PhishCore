@props(['stroke' => 1.8])

{{-- Little robot mascot. Eyes blink and the antenna light pulses all the time. On hover (floating button, nav item, chat avatars)
     it bounces, squints into a happy face and waves. The styles are the .rb-* rules in chat-widget. Colour comes from the text colour. --}}
<svg {{ $attributes->merge(['class' => 'rb']) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 5.4V3.6" />
    <circle class="rb-ant" cx="12" cy="2.7" r="1.2" fill="currentColor" stroke="none" />
    <path d="M8.6 22v-1.3c0-1.4 1.5-2.3 3.4-2.3s3.4.9 3.4 2.3V22" />
    <g class="rb-hand"><rect x="18.4" y="14.4" width="2.6" height="6.4" rx="1.3" fill="currentColor" stroke="none" /></g>
    <rect x="1.7" y="9.8" width="1.9" height="4.4" rx=".95" fill="currentColor" stroke="none" />
    <rect x="20.4" y="9.8" width="1.9" height="4.4" rx=".95" fill="currentColor" stroke="none" />
    <rect x="4.2" y="5.4" width="15.6" height="12.2" rx="4.6" />
    <rect x="6.5" y="8" width="11" height="7.4" rx="2.8" fill="currentColor" fill-opacity=".16" stroke="none" />
    <ellipse class="rb-eye" cx="9.2" cy="11.5" rx="1.25" ry="1.75" fill="currentColor" stroke="none" />
    <ellipse class="rb-eye" cx="14.8" cy="11.5" rx="1.25" ry="1.75" fill="currentColor" stroke="none" />
    <path class="rb-eye-happy" d="M7.7 12.3q1.5-2.3 3 0M13.3 12.3q1.5-2.3 3 0" stroke-width="1.5" />
    <path class="rb-mouth" d="M10.4 14.6h3.2" stroke-width="1.5" />
    <path class="rb-smile" d="M9.4 13.9q2.6 3 5.2 0" stroke-width="1.5" />
</svg>