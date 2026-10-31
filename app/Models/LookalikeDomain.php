<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A new website name that copies a Brunei brand, found by the Look-alike Watch (see App\Services\LookalikeWatch). */
class LookalikeDomain extends Model
{
    protected $fillable = ['domain', 'brand', 'source', 'certificate_issued_at', 'address_points', 'status', 'dismissed_by'];

    protected function casts(): array
    {
        return [
            'certificate_issued_at' => 'datetime',
            'address_points' => 'integer',
        ];
    }

    public function dismissedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    /** The newest scan of this website, if anyone has scanned it on PhishCore. */
    public function latestScan(): ?Report
    {
        return Report::query()
            ->with('analyses')
            ->where('type', 'url')
            ->where('status', 'completed')
            ->where(fn ($q) => $q->where('url', 'like', '%//'.$this->domain.'%')->orWhere('url', 'like', '%.'.$this->domain.'%'))
            ->latest()
            ->first();
    }
}