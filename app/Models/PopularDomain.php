<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One of the most visited sites in the world, with its position in the ranking (Tranco list, 1 = most visited). Filled by feeds:update.
 */
class PopularDomain extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'domain';

    protected $keyType = 'string';

    protected $fillable = ['domain', 'position'];
}
