<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtiLookup extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'source',
        'raw_response',
        'threat_score',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}