<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

/**
 * Looks at the HTML of a fetched page with a real HTML parser, to answer the questions the scan must not guess:
 *  - did we get the real page, or a security check ("Just a moment...") in front of it?
 *  - was the page built by JavaScript, so that a login form could exist that this fetch never saw?
 *  - what does the page say once scripts, styles and hidden helpers are taken out?
 *
 * Nothing here is scored. It only lets the page-content check say "we could not read this page properly"
 * instead of a clean pass.
 */
final class PageInspector
{
    /** Ids that single-page apps use for the empty element they fill in with JavaScript. */
    private const APP_ROOT_IDS = ['root', 'app', '__next', '__nuxt', '___gatsby', 'svelte', 'q-app'];

    /**
     * The name of the service that answered with a security check instead of the page, or null.
     * A real page is never this small, so large pages are not looked at.
     */
    public static function challenge(string $html, ?string $mitigatedHeader = null): ?string
    {
        if ($mitigatedHeader !== null && stripos($mitigatedHeader, 'challenge') !== false) {
            return 'Cloudflare';
        }

        if (strlen($html) > 80000) {
            return null;
        }

        $markers = [
            'Cloudflare' => [
                '/<title>\s*Just a moment\.\.\.\s*<\/title>/i',
                '/<title>\s*Attention Required! \| Cloudflare/i',
                '/cdn-cgi\/challenge-platform/i',
                '/Checking your browser before accessing/i',
            ],
            'Sucuri' => ['/Sucuri WebSite Firewall/i'],
            'Akamai' => ['/<title>\s*Access Denied\s*<\/title>.*errors\.edgesuite\.net/is'],
            'DDoS-Guard' => ['/<title>\s*DDoS-Guard\s*<\/title>/i'],
        ];

        foreach ($markers as $name => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html)) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * True when the page is an empty shell that JavaScript fills in: almost no readable text, no form in the HTML,
     * and either a script file or the usual empty "root" element. A form built by that script is invisible to a
     * plain fetch, so "no login form found" would mean nothing for such a page.
     */
    public static function needsJavaScript(string $html): bool
    {
        $doc = self::load($html);
        if ($doc === null) {
            return false;
        }

        $xpath = new DOMXPath($doc);

        if ($xpath->query('//form | //input')->length > 0) {
            return false;
        }

        if (mb_strlen(self::visibleText($html)) >= 150) {
            return false;
        }

        if ($xpath->query('//script[@src]')->length > 0) {
            return true;
        }

        foreach (self::APP_ROOT_IDS as $id) {
            if ($xpath->query('//*[@id="'.$id.'"]')->length > 0) {
                return true;
            }
        }

        return false;
    }

    /** The text a visitor would read: no scripts, styles, templates, images or the noscript fallback. */
    public static function visibleText(string $html): string
    {
        $doc = self::load($html);
        if ($doc === null) {
            return '';
        }

        $xpath = new DOMXPath($doc);
        $remove = [];
        foreach ($xpath->query('//script | //style | //noscript | //template | //svg | //head') as $node) {
            $remove[] = $node;
        }
        foreach ($remove as $node) {
            $node->parentNode?->removeChild($node);
        }

        $text = $doc->documentElement?->textContent ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * The page as UTF-8. A page in another character set (Windows-1252, Shift-JIS, GBK ...) would otherwise come
     * out as garbage, and the word lists and brand names would not match in it.
     */
    public static function toUtf8(string $html, ?string $contentType = null): string
    {
        if ($html === '' || mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }

        $charset = null;
        if ($contentType !== null && preg_match('/charset\s*=\s*["\']?([\w\-]+)/i', $contentType, $m)) {
            $charset = $m[1];
        } elseif (preg_match('/<meta[^>]+charset\s*=\s*["\']?([\w\-]+)/i', substr($html, 0, 4096), $m)) {
            $charset = $m[1];
        }

        foreach (array_filter([$charset, 'Windows-1252']) as $candidate) {
            try {
                $converted = @mb_convert_encoding($html, 'UTF-8', $candidate);
            } catch (\Throwable) {
                continue;
            }

            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return $html;
    }

    private static function load(string $html): ?DOMDocument
    {
        if (trim($html) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $loaded = $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $doc : null;
    }
}   