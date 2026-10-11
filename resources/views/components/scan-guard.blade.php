{{-- Robot protection for the scan forms: a hidden trap field, plus the human check for guests when it is switched on. --}}
@php
    $showHumanCheck = \App\Support\ScanGuard::turnstileEnabled()
        && ! auth()->check()
        && (int) session('human_verified_until', 0) < now()->timestamp;
@endphp

<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
    <label>Leave this empty
        <input type="text" name="{{ \App\Support\ScanGuard::HONEYPOT }}" value="" tabindex="-1" autocomplete="off">
    </label>
</div>

@if ($showHumanCheck)
    <div class="cf-turnstile mt-3" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark" data-size="flexible"></div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif