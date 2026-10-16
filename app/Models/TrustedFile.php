<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file (identified by its fingerprint) that an administrator has checked and marked as safe, for
 * example a real app the scan wrongly called phishing. A scan of the same file shows it as safe.
 * Only the fingerprint and the original file name are kept, never the file.
 */
class TrustedFile extends Model
{
    protected $fillable = ['file_sha256', 'file_name', 'marked_by'];

    public static function isTrusted(?string $sha256): bool
    {
        return $sha256 !== null && $sha256 !== '' && static::where('file_sha256', $sha256)->exists();
    }

    public function markedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
