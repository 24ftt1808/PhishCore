@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    $buttonColors = [
        'primary' => ['bg' => '#2563eb', 'gradient' => 'linear-gradient(90deg, #38bdf8, #2563eb)'],
        'blue' => ['bg' => '#2563eb', 'gradient' => 'linear-gradient(90deg, #38bdf8, #2563eb)'],
        'success' => ['bg' => '#16a34a', 'gradient' => 'linear-gradient(90deg, #22c55e, #16a34a)'],
        'green' => ['bg' => '#16a34a', 'gradient' => 'linear-gradient(90deg, #22c55e, #16a34a)'],
        'error' => ['bg' => '#dc2626', 'gradient' => 'linear-gradient(90deg, #f87171, #dc2626)'],
        'red' => ['bg' => '#dc2626', 'gradient' => 'linear-gradient(90deg, #f87171, #dc2626)'],
    ];
    $buttonColor = $buttonColors[$color] ?? $buttonColors['primary'];
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
{{-- The colour sits on the cell, not on the link: Gmail's dark mode then recolours one solid block instead of a link with borders. --}}
<td align="center" bgcolor="{{ $buttonColor['bg'] }}" style="background-color: {{ $buttonColor['bg'] }}; background-image: {{ $buttonColor['gradient'] }}; border-radius: 12px;">
<a href="{{ $url }}" target="_blank" rel="noopener" style="display: inline-block; padding: 16px 44px; border-radius: 12px; color: #ffffff; font-size: 15px; font-weight: 700; line-height: 1.2; text-decoration: none; -webkit-text-size-adjust: none;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>