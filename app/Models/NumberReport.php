<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person saying "this phone number is a scam", and what kind. One report per person per number
 * (reporting again changes the kind). These reports count towards the phone scan and feed the
 * public "Most reported phone numbers" list.
 */
class NumberReport extends Model
{
    /** @var array<string, string> key => what the person sees */
    public const CATEGORIES = [
        'bank' => 'Pretending to be a bank',
        'parcel' => 'Parcel or delivery scam',
        'job' => 'Fake job offer',
        'prize' => 'Prize or lottery scam',
        'authority' => 'Pretending to be police or government',
        'investment' => 'Investment or money scam',
        'other' => 'Other scam',
    ];

    protected $fillable = ['user_id', 'phone', 'category'];

    /** The same number written with spaces or dashes is still the same number. */
    public static function key(string $phone): string
    {
        return preg_replace('/[\s()\-]/', '', $phone) ?? $phone;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}