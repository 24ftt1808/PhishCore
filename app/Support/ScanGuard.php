<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Keeps robots away from the scan forms: a hidden trap field that only robots fill in, and an
 * optional Cloudflare Turnstile "are you human" check for guests.
 */
class ScanGuard
{
    /** The name of the hidden trap field. People never see it, so only robots fill it in. */
    public const HONEYPOT = 'contact_fax';

    /** How long a guest who passed the check is trusted, so they do not repeat it on every scan. */
    private const TRUST_MINUTES = 60;

    public static function honeypotTripped(Request $request): bool
    {
        return filled($request->input(self::HONEYPOT));
    }

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /** True when this visitor still has to pass the human check before scanning. */
    public static function needsHumanCheck(Request $request): bool
    {
        if (! self::turnstileEnabled() || $request->user()) {
            return false;
        }

        $until = (int) $request->session()->get('human_verified_until', 0);

        return $until < now()->timestamp;
    }

    /** Ask Cloudflare whether the answer from the form is genuine. */
    public static function passesHumanCheck(Request $request): bool
    {
        $token = (string) $request->input('cf-turnstile-response');

        if ($token === '') {
            return false;
        }

        try {
            $result = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            // If Cloudflare itself cannot be reached, let the person through. The scan limits still apply.
            return true;
        }

        if ($result->serverError()) {
            return true;
        }

        if (! $result->json('success')) {
            return false;
        }

        $request->session()->put('human_verified_until', now()->addMinutes(self::TRUST_MINUTES)->timestamp);

        return true;
    }
}
