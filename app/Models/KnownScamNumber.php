<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A phone number an administrator added because a trusted source (the police, BDCB, a news report) named
 * it as a scam. The phone scan treats it as a strong sign. The number is stored the same way
 * NumberReport::key() writes it, so the scan finds it however the visitor typed it.
 */
class KnownScamNumber extends Model
{
    protected $fillable = ['phone', 'category', 'source', 'note', 'added_by'];

    public function addedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}