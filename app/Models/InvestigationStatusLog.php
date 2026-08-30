<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['investigation_id', 'status', 'changed_by'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}