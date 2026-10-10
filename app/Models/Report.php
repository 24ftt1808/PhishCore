<?php

namespace App\Models;

use App\Services\CommunityStats;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // The Analytics "Community" numbers are recalculated on the next page load.
        static::saved(fn () => CommunityStats::forget());
        static::deleted(fn () => CommunityStats::forget());
    }

    protected $fillable = [
        'user_id',
        'type',
        'url',
        'screenshot_path',
        'sender_email',
        'phone_number',
        'description',
        'status',
    ];

    /**
     * Who may open this scan's private result page (and its PDF).
     * Admins and team members can open any scan; a signed-in user can open
     * their own; a guest can open scans made in their current browser session.
     * Everyone else gets a 404, so scan IDs cannot be enumerated.
     */
    public function canBeViewedBy(?User $user): bool
    {
        if ($user && ($user->role === 'admin' || $user->is_team_member)) {
            return true;
        }

        if ($user && $this->user_id !== null && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        return in_array((int) $this->id, array_map('intval', session('guest_report_ids', [])), true);
    }

    /** Only finished link scans with a result can be shared publicly. */
    public function isShareable(): bool
    {
        return $this->type === 'url'
            && $this->status === 'completed'
            && $this->analyses()->whereNotNull('verdict')->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    public function ctiLookups(): HasMany
    {
        return $this->hasMany(CtiLookup::class);
    }

    public function investigation(): HasOne
    {
        return $this->hasOne(Investigation::class);
    }
}