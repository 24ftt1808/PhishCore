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
