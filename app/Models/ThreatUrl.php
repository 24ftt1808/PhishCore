<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One address that a community phishing / malware list (OpenPhish, URLhaus, PhishTank) currently reports.
 * Filled and cleaned by feeds:update (see App\Services\ThreatFeeds).
 */
class ThreatUrl extends Model
{
    protected $fillable = ['source', 'url_hash', 'url', 'host', 'registrable', 'seen_at'];

    protected function casts(): array
    {
        return ['seen_at' => 'datetime'];
    }
}
