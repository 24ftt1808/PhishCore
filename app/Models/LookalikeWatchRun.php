<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One Look-alike Watch check: whether the certificate logs answered, how many names were read and how many were new. */
class LookalikeWatchRun extends Model
{
    protected $fillable = ['ok', 'names_checked', 'new_found', 'note'];

    protected function casts(): array
    {
        return ['ok' => 'boolean'];
    }
}