<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Asks a domain's registry when it was registered, using RDAP (the JSON replacement for WHOIS).
 *
 * The right server for each ending (.com, .net, .my ...) comes from the IANA list, which is kept for a week.
 * An ending that has no RDAP server simply returns null, and the caller falls back to WHOIS.
 */
final class Rdap
{
    private const BOOTSTRAP_URL = 'https://data.iana.org/rdap/dns.json';

    /** Unix time the domain was registered, or null when the registry cannot say. */
    public static function registrationDate(string $domain): ?int
    {
        $domain = strtolower(trim($domain, ". \t"));
        $labels = explode('.', $domain);
        if (count($labels) < 2) {
            return null;
        }

        $server = self::serverFor(end($labels));
        if ($server === null) {
            return null;
        }

        $response = Http::acceptJson()->timeout(6)->get(rtrim($server, '/').'/domain/'.$domain);
        if (! $response->successful()) {
            return null;
        }

        foreach ((array) $response->json('events', []) as $event) {
            if (($event['eventAction'] ?? null) === 'registration' && ! empty($event['eventDate'])) {
                $time = strtotime((string) $event['eventDate']);

                return $time === false ? null : $time;
            }
        }

        return null;
    }

    /** The base address of the RDAP server for a domain ending, or null when it has none. */
    public static function serverFor(string $tld): ?string
    {
        $tld = strtolower($tld);
        $services = Cache::get('rdap:bootstrap');

        if (! is_array($services)) {
            $response = Http::acceptJson()->timeout(6)->get(self::BOOTSTRAP_URL);
            $services = $response->successful() ? $response->json('services') : null;

            if (! is_array($services) || $services === []) {
                return null;
            }

            Cache::put('rdap:bootstrap', $services, now()->addDays(7));
        }

        foreach ($services as $service) {
            $endings = $service[0] ?? [];
            $urls = $service[1] ?? [];

            if (in_array($tld, $endings, true)) {
                foreach ($urls as $url) {
                    if (is_string($url) && str_starts_with($url, 'https://')) {
                        return $url;
                    }
                }
            }
        }

        return null;
    }
}