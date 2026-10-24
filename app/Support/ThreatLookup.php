<?php

namespace App\Support;

use App\Models\PopularDomain;
use App\Models\ThreatUrl;
use App\Services\ThreatFeeds;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Looks an address up in the lists feeds:update saved (see App\Services\ThreatFeeds). Every method answers
 * "not found" when the lists are empty, were never downloaded, or the database cannot be read, so a scan
 * never fails because of them. That also keeps the rules that use them working in offline tests.
 */
final class ThreatLookup
{
    /**
     * The list that reports exactly this address, or null.
     *
     * @return array{source: string, name: string}|null
     */
    public static function exactMatch(string $url): ?array
    {
        try {
            $row = ThreatUrl::query()->where('url_hash', sha1(ThreatFeeds::normalizeUrl($url)))->first();

            return $row ? ['source' => $row->source, 'name' => self::nameOf($row->source)] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * A list that reports some other page on the same registered domain, or null.
     *
     * @return array{source: string, name: string}|null
     */
    public static function domainMatch(string $host): ?array
    {
        $registrable = RegistrableDomain::of($host);

        if ($registrable === null) {
            return null;
        }

        try {
            $row = ThreatUrl::query()->where('registrable', $registrable)->first();

            return $row ? ['source' => $row->source, 'name' => self::nameOf($row->source)] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** The site's position in the popular-sites ranking (1 = most visited), or null when it is not listed. */
    public static function position(string $host): ?int
    {
        $registrable = RegistrableDomain::of($host);

        if ($registrable === null) {
            return null;
        }

        try {
            $position = PopularDomain::query()->where('domain', $registrable)->value('position');

            return $position === null ? null : (int) $position;
        } catch (Throwable) {
            return null;
        }
    }

    /** True for a site that is high enough in the popular-sites ranking to count as well known. */
    public static function isPopular(string $host): bool
    {
        $position = self::position($host);

        return $position !== null && $position <= (int) config('threatfeeds.popular_rank', 50000);
    }

    /**
     * The registered names of the most visited sites ("wikipedia" from wikipedia.org), as spell-alike targets:
     * the look-alike rule compares a new address against them, so "wikipedla.org" is caught with no rule written
     * for Wikipedia. Only plain names of at least eight letters are used. A short name is one or two letters
     * from many ordinary words and sites, a long one is not, so this keeps real small sites out of trouble.
     *
     * @return array<int, string>
     */
    public static function popularNames(): array
    {
        $load = function (): array {
            $rows = PopularDomain::query()
                ->where('position', '<=', (int) config('threatfeeds.lookalike_rank', 3000))
                ->orderBy('position')
                ->pluck('domain');

            $names = [];

            foreach ($rows as $domain) {
                $name = explode('.', (string) $domain)[0];

                if (preg_match('/^[a-z]{8,}$/', $name)) {
                    $names[$name] = true;
                }
            }

            return array_keys($names);
        };

        try {
            return Cache::remember('popular_lookalike_names', 3600, $load);
        } catch (Throwable) {
            try {
                return $load();
            } catch (Throwable) {
                return [];
            }
        }
    }

    private static function nameOf(string $source): string
    {
        foreach ((array) config('threatfeeds.feeds', []) as $feed) {
            if (($feed['source'] ?? null) === $source) {
                return (string) ($feed['name'] ?? $source);
            }
        }

        return $source;
    }
}