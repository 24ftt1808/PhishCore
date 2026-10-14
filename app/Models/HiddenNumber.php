<?php

namespace App\Models;

use App\Services\CommunityStats;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A phone number a team member or administrator has taken off the public lists because the listing
 * is wrong (for example a number that was reassigned). A hidden number is also treated as having no
 * reports when it is scanned, so a scan never contradicts the lists. The reports themselves are kept.
 */
class HiddenNumber extends Model
{
    protected $fillable = ['phone', 'hidden_by'];

    protected static function booted(): void
    {
        static::saved(fn () => CommunityStats::forget());
        static::deleted(fn () => CommunityStats::forget());
    }

    /**
     * The hidden numbers, written the same way NumberReport::key() writes them.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return static::query()->pluck('phone')->all();
    }

    public function hiddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }
}
