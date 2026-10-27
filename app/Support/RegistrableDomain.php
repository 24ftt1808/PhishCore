<?php

namespace App\Support;

/**
 * Finds the part of a host name that one owner registered: "lms.pb.edu.bn" belongs to "pb.edu.bn",
 * "www.bibd.com.bn" to "bibd.com.bn". Two hosts with the same registrable domain are the same
 * site for the engine's checks, so a login on shop.example.com that posts to accounts.example.com
 * is not treated as a different website.
 *
 * Free hosting and website-builder platforms are the exception: every subdomain belongs to a
 * different person ("a.github.io" and "b.github.io"), so there the subdomain is the registrable
 * part. Other country domains follow the common "second level + two-letter country" shape
 * (com.bn, gov.bn, edu.bn, co.uk, com.my), which this reads by rule instead of by a long list.
 */
final class RegistrableDomain
{
    /** Platforms where each subdomain belongs to a different person. Also used by the free-hosting check. */
    public const FREE_HOSTING_SUFFIXES = [
        'weebly.com', 'wixsite.com', 'blogspot.com', 'netlify.app',
        'github.io', 'pages.dev', 'carrd.co', 'glitch.me',
        '000webhostapp.com', 'firebaseapp.com', 'web.app',
        'herokuapp.com', 'vercel.app', 'repl.co', 'r2.dev',
        'sites.google.com', 'wordpress.com', 'square.site',
        // Free subdomains and dynamic-DNS services: anyone can get a name here, so the age of the service
        // (eu.org dates from 1995) says nothing about the page.
        'eu.org', 'duckdns.org', 'no-ip.org', 'ddns.net', 'hopto.org', 'zapto.org',
        'serveo.net', 'ngrok.io', 'ngrok-free.app', 'trycloudflare.com', 'workers.dev',
        'onrender.com', 'fly.dev', 'railway.app', 'surge.sh', 'webflow.io', 'infinityfreeapp.com',
        'wasmer.app', 'replit.app', 'replit.dev', 'pythonanywhere.com', 'streamlit.app', 'deno.dev',
    ];

    /** Second-level labels that sit in front of a two-letter country domain (com.bn, co.uk, edu.my ...). */
    private const SECOND_LEVEL_LABELS = ['com', 'co', 'org', 'net', 'gov', 'edu', 'ac', 'or', 'ne', 'go', 'mil', 'sch', 'web', 'name'];

    /** The registrable domain of a host, or null when there is no host. An IP address is returned as it is. */
    public static function of(?string $host): ?string
    {
        $host = strtolower(trim((string) $host));

        // An IPv6 address is full of colons, so it is recognised before any ":port" is cut off.
        if (str_starts_with($host, '[') || substr_count($host, ':') > 1) {
            $host = str_starts_with($host, '[') ? (string) strstr(substr($host, 1).']', ']', true) : $host;
            $host = trim($host, '[]. ');

            return $host === '' ? null : $host;
        }

        $host = trim($host, '[].');
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        if ($host === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $host)) {
            return $host;
        }

        $labels = explode('.', $host);

        if (count($labels) <= 2) {
            return $host;
        }

        foreach (self::FREE_HOSTING_SUFFIXES as $suffix) {
            if (str_ends_with($host, '.'.$suffix)) {
                $suffixLabels = substr_count($suffix, '.') + 1;

                return implode('.', array_slice($labels, -($suffixLabels + 1)));
            }
        }

        $topLevel = $labels[count($labels) - 1];
        $secondLevel = $labels[count($labels) - 2];

        if (strlen($topLevel) === 2 && in_array($secondLevel, self::SECOND_LEVEL_LABELS, true)) {
            return implode('.', array_slice($labels, -3));
        }

        return implode('.', array_slice($labels, -2));
    }

    /**
     * The shared platform a host is a page on ("github.io" for "someone.github.io"), or null.
     * Such a site has no age of its own: the registration date belongs to the platform.
     */
    public static function sharedPlatform(?string $host): ?string
    {
        $host = strtolower(trim((string) $host, " .\t"));

        foreach (self::FREE_HOSTING_SUFFIXES as $suffix) {
            if (str_ends_with($host, '.'.$suffix)) {
                return $suffix;
            }
        }

        return null;
    }

    /** True when both hosts belong to the same registrable domain. */
    public static function same(?string $a, ?string $b): bool
    {
        $first = self::of($a);

        return $first !== null && $first === self::of($b);
    }
}