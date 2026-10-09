<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per player: their Phish Lab XP, best scores, badges and look. Only game state, no personal details.
 */
class PlayProgress extends Model
{
    protected $table = 'play_progress';

    protected $fillable = ['user_id', 'xp', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}