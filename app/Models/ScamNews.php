<?php

namespace App\Models;

use Database\Factories\ScamNewsFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One article in the dashboard's "Latest Scam News" box. Saved by news:fetch from RSS feeds, or added
 * by hand by the team. A hidden article stays in the table (so the next fetch does not bring it back)
 * but is not shown on the dashboard.
 */
class ScamNews extends Model
{
    /** @use HasFactory<ScamNewsFactory> */
    use HasFactory;

    protected $table = 'scam_news';

    protected $fillable = ['title', 'url', 'url_hash', 'source', 'image_url', 'published_at', 'is_visible', 'is_manual'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_visible' => 'boolean',
            'is_manual' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ScamNews $article) {
            $article->url_hash ??= self::hashFor($article->url);
        });
    }

    /** The same link with or without a #fragment or a trailing space is still the same article. */
    public static function hashFor(string $url): string
    {
        $url = trim($url);
        $url = preg_replace('/#.*$/', '', $url) ?? $url;

        return sha1($url);
    }

    /** @param Builder<ScamNews> $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }
}
