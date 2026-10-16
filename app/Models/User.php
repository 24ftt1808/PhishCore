<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'identifier', 'department', 'role', 'is_team_member', 'suspended_at', 'profile_photo_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'floating_chat_enabled' => 'boolean',
        ];
    }

    /**
     * Whether the floating Cora button should show. On unless the person switched it off in Settings, so a
     * user who has no saved choice yet (or a model that was just created) still gets it.
     */
    public function wantsFloatingChat(): bool
    {
        return $this->floating_chat_enabled !== false;
    }

    public const SCAN_TABS = ['url', 'email', 'phone', 'screenshot', 'file'];

    public const RETENTION_CHOICES = [30, 90];

    /** The scanner tab that opens first. */
    public function defaultScanTab(): string
    {
        return in_array($this->default_scan_tab, self::SCAN_TABS, true) ? $this->default_scan_tab : 'url';
    }

    /** How many days this person's scans are kept, or null to keep them until they delete them. */
    public function scanRetentionDays(): ?int
    {
        return in_array((int) $this->scan_retention_days, self::RETENTION_CHOICES, true) ? (int) $this->scan_retention_days : null;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Remember that this person has just signed in (password login or sign-up). Shown to
     * administrators on the user management page.
     */
    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->saveQuietly();
    }

    /**
     * Returns the user's uploaded profile photo URL, or null if they
     * haven't uploaded one — callers fall back to initials in that case.
     */
    public function photoUrl(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/'.$this->profile_photo_path)
            : null;
    }
}
