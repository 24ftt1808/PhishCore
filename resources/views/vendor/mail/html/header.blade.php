@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" width="44" height="44">
<span style="display: inline-block; vertical-align: middle; text-align: left;">
<span style="display: block; color: #f1f5f9; font-size: 20px; font-weight: 800; line-height: 1.1;">{{ config('app.name') }}</span>
<span class="tagline" style="display: block; color: #7dd3fc; font-size: 11px; font-weight: 600; letter-spacing: 0.14em; margin-top: 4px;">DETECTION PLATFORM</span>
</span>
</a>
</td>
</tr>