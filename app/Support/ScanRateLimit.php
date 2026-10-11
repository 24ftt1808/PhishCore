<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * How many scans one person may start (see config/scan.php).
 *
 * Guests are counted by IP address and signed-in people by account, each with a per-minute and a
 * per-hour allowance. When someone goes over, they are sent back to the form with a plain
 * explanation instead of a bare "429 Too Many Requests" page.
 *
 * The scan controller calls attempt() only when a real scan is about to run, so a result that is
 * reused from a recent scan (which costs nothing) does not use up the allowance.
 */
class ScanRateLimit
{
    /**
     * Count this scan, or return the redirect that explains why it cannot run.
     */
    public static function attempt(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        $who = $user ? 'user:'.$user->id : 'ip:'.$request->ip();
        $prefix = $user ? 'user' : 'guest';

        $limits = [
            ['key' => "scan:minute:{$who}", 'max' => max(1, (int) config("scan.{$prefix}_per_minute")), 'decay' => 60, 'hourly' => false],
            ['key' => "scan:hour:{$who}", 'max' => max(1, (int) config("scan.{$prefix}_per_hour")), 'decay' => 3600, 'hourly' => true],
        ];

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                return self::tooMany($request, RateLimiter::availableIn($limit['key']), $limit['hourly']);
            }
        }

        foreach ($limits as $limit) {
            RateLimiter::hit($limit['key'], $limit['decay']);
        }

        return null;
    }

    private static function tooMany(Request $request, int $wait, bool $hourly): RedirectResponse
    {
        $wait = max(1, $wait);
        $when = $wait >= 90 ? 'in about '.(int) ceil($wait / 60).' minutes' : 'in a minute';

        if (! $hourly) {
            $message = "You're scanning very quickly. Please wait a moment and try again.";
        } elseif ($request->user()) {
            $message = "You've reached the scan limit for now. Please try again {$when}.";
        } else {
            $message = "You've used all the free guest scans for now. Create a free account to keep scanning, or try again {$when}.";
        }

        return self::back($request, $message);
    }

    /** Send the person back to the form with a message under the field of the scan they sent. */
    public static function back(Request $request, string $message): RedirectResponse
    {
        return redirect()->back()->withInput($request->except(['screenshot']))->withErrors([self::field($request) => $message]);
    }

    /** The form field that should show the message, so the right tab opens. */
    private static function field(Request $request): string
    {
        return match (true) {
            $request->hasFile('screenshot') => 'screenshot',
            $request->filled('email') || $request->filled('subject') || $request->filled('body') => 'email',
            $request->filled('phone') => 'phone',
            default => 'url',
        };
    }
}
