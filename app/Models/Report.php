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
        'preview_url',
        'preview_path',
        'preview_note',
        'file_name',
        'file_sha256',
        'sender_email',
        'phone_number',
        'description',
        'status',
        'reused_from_report_id',
    ];

    protected $casts = [
        'preview_note' => 'array',
    ];

    /**
     * Who may open this scan's private result page (and its PDF).
     * Admins and team members can open any scan; a signed-in user can open
     * their own; a guest can open scans made in their current browser session.
     * Everyone else gets a 404, so scan IDs cannot be enumerated.
     */
    /**
     * The link a page preview can be taken of: the scanned link, the link read from a screenshot,
     * or the link found in an email. Null when the scan has no link, so no preview is offered.
     */
    public function previewLink(): ?string
    {
        $link = match ($this->type) {
            'url', 'screenshot' => $this->url,
            'email', 'file' => $this->preview_url,
            default => null,
        };

        return filled($link) ? (string) $link : null;
    }

    /**
     * Where the saved preview picture is remembered. Link scans use screenshot_path, but for a
     * screenshot scan that column already holds the uploaded image, so the others use preview_path.
     */
    public function previewColumn(): string
    {
        return $this->type === 'url' ? 'screenshot_path' : 'preview_path';
    }

    public function previewPicture(): ?string
    {
        return $this->{$this->previewColumn()};
    }

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