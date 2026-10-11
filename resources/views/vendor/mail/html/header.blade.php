@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('phishcore-logo-icon.png') }}" alt="PhishCore logo" width="48" height="48">
<span style="display: inline-block; vertical-align: middle; text-align: left;">
<span style="display: block; color: #f8fafc; font-size: 22px; font-weight: 800; letter-spacing: -0.01em; line-height: 1.1;">{{ config('app.name') }}</span>
<span class="tagline" style="display: block; color: #7dd3fc; font-size: 10px; font-weight: 700; letter-spacing: 0.18em; margin-top: 5px;">DETECTION PLATFORM</span>
</span>
</a>
</td>
</tr>