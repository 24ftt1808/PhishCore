<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Analysis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $analyses = fn () => Analysis::whereHas('report', fn ($q) => $q->where('user_id', $user->id));

        return view('settings', [
            'user' => $user,
            'activity' => [
                'scans' => $analyses()->count(),
                'phishing' => $analyses()->where('verdict', 'phishing')->count(),
                'clean' => $analyses()->where('verdict', 'clean')->count(),
                'last_scan' => $analyses()->latest()->value('created_at'),
            ],
            'devices' => $this->signedInDevices($user->id, $request->session()->getId()),
        ]);
    }

    /**
     * Devices with an active session for this user, newest first, or null when sessions are not stored in the database.
     *
     * @return list<array{label: string, ip: ?string, last_active: Carbon, current: bool, mobile: bool}>|null
     */
    private function signedInDevices(int $userId, string $currentSessionId): ?array
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->orderByDesc('last_activity')
            ->limit(8)
            ->get()
            ->map(function (object $session) use ($currentSessionId): array {
                [$label, $mobile] = $this->describeUserAgent($session->user_agent);

                return [
                    'label' => $label,
                    'ip' => $session->ip_address,
                    'last_active' => Carbon::createFromTimestamp($session->last_activity),
                    'current' => $session->id === $currentSessionId,
                    'mobile' => $mobile,
                ];
            })
            ->all();
    }

    /**
     * A short "Browser on System" label for a user agent, and whether it looks like a phone or tablet.
     *
     * @return array{0: string, 1: bool}
     */
    private function describeUserAgent(?string $userAgent): array
    {
        $agent = (string) $userAgent;

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return [$system ? "{$browser} on {$system}" : $browser, in_array($system, ['Android', 'iOS'], true)];
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete the old photo if one exists, so we don't accumulate
        // orphaned files every time someone changes their picture.
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->update(['profile_photo_path' => $path]);

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    /**
     * Remove the user's profile photo, reverting to initials.
     */
    public function removePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return Redirect::route('profile.edit')->with('status', 'photo-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
