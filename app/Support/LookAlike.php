<?php

namespace App\Support;

/**
 * Spots a domain name that is written to be mistaken for a known brand, without needing one rule
 * per fake. The brands come from config/brands.php, so a new brand is protected the moment it is
 * added there.
 *
 * Only the registered name is compared ("gooogle" in "login.gooogle.com"), and only in the ways
 * impersonators really write a name:
 *  - letters from another alphabet that look the same (a Cyrillic "а" in "аpple"), including
 *    punycode ("xn--...") names, which are read back into their real letters first;
 *  - a name that mixes alphabets (Latin with Cyrillic or Greek), which real sites almost never do;
 *  - letters that merely look alike: "rn" for "m", "vv" for "w", "i" for "l", digits for letters,
 *    and doubled letters ("gooogle");
 *  - one letter dropped or two neighbours swapped ("microsft", "googel").
 *
 * It deliberately does NOT treat "any name one letter away" as a copy: "finance" is one letter from
 * "binance" and "lively" from "livelo", and both are ordinary words. A real site that still gets
 * caught can be listed under known_lookalikes in config/brands.php.
 */
final class LookAlike
{
    /** Letters from other alphabets (and a few Latin look-alikes) written as the Latin letter they imitate. */
    private const CONFUSABLES = [
        'а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p', 'с' => 'c', 'х' => 'x', 'у' => 'y',
        'і' => 'i', 'ј' => 'j', 'ѕ' => 's', 'һ' => 'h', 'ԁ' => 'd', 'ӏ' => 'l', 'ԛ' => 'q', 'ԝ' => 'w',
        'α' => 'a', 'ε' => 'e', 'ο' => 'o', 'ρ' => 'p', 'ν' => 'v', 'ι' => 'i', 'κ' => 'k', 'τ' => 't',
        'υ' => 'u', 'χ' => 'x', 'ɡ' => 'g', 'ı' => 'i', 'ł' => 'l', 'ɩ' => 'i', 'ǀ' => 'l',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'ç' => 'c', 'ñ' => 'n',
    ];

    /** Letters swapped for digits in impersonations ("paypa1", "g00gle"). */
    private const DIGITS = ['0' => 'o', '1' => 'l', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '8' => 'b'];

    /** A brand must have at least this many letters before everyday-letter confusion (i/l, rn/m) counts. */
    private const MIN_BRAND_LETTERS = 6;

    /**
     * @param  array<int, string>  $brands  single-word brand names, lower case
     * @param  array<int, string>  $knownGood  registered domains that are legitimate despite looking like a brand
     * @return array{type: string, brand: ?string, name: string}|null
     */
    public static function find(string $host, array $brands, array $knownGood = []): ?array
    {
        $decoded = self::decode($host);
        $registered = RegistrableDomain::of($decoded);

        if ($registered === null || str_contains($registered, ':') || preg_match('/^[\d.]+$/', $registered)) {
            return null;
        }

        if (in_array($registered, array_map('strtolower', $knownGood), true)) {
            return null;
        }

        $name = explode('.', $registered)[0];

        if (mb_strlen($name) < 4) {
            return null;
        }

        if (self::mixesAlphabets($name)) {
            return ['type' => 'mixed', 'brand' => null, 'name' => $name];
        }

        if (in_array($name, $brands, true)) {
            return null; // the brand's own name on some other ending is handled by the brand-word rules
        }

        $letters = str_replace('-', '', self::foldUnicode($name));
        $usesOtherLetters = $letters !== str_replace('-', '', $name);

        if ($usesOtherLetters && in_array($letters, $brands, true)) {
            return ['type' => 'homoglyph', 'brand' => $letters, 'name' => $name];
        }

        foreach ($brands as $brand) {
            if (mb_strlen($brand) < self::MIN_BRAND_LETTERS) {
                continue;
            }

            if (self::looksLike($letters, $brand)) {
                return ['type' => 'lookalike', 'brand' => $brand, 'name' => $name];
            }

            if (self::oneLetterDroppedOrSwapped($letters, $brand)) {
                return ['type' => 'typo', 'brand' => $brand, 'name' => $name];
            }
        }

        return null;
    }

    /** The host with any punycode ("xn--") names read back into their real letters (needs the intl extension). */
    public static function decode(string $host): string
    {
        $host = strtolower(trim($host));

        if (! str_contains($host, 'xn--') || ! function_exists('idn_to_utf8')) {
            return $host;
        }

        $labels = array_map(function (string $label): string {
            if (! str_starts_with($label, 'xn--')) {
                return $label;
            }

            $decoded = @idn_to_utf8($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            return is_string($decoded) && $decoded !== '' ? mb_strtolower($decoded) : $label;
        }, explode('.', $host));

        return implode('.', $labels);
    }

    /** Hyphens in a host name, not counting the ones inside punycode names ("xn--pypal-4ve" has none of its own). */
    public static function hyphenCount(string $host): int
    {
        $count = 0;

        foreach (explode('.', strtolower($host)) as $label) {
            if (! str_starts_with($label, 'xn--')) {
                $count += substr_count($label, '-');
            }
        }

        return $count;
    }

    /** True when one name uses both Latin letters and Cyrillic or Greek letters. */
    public static function mixesAlphabets(string $name): bool
    {
        return preg_match('/\p{Latin}/u', $name) === 1
            && preg_match('/[\p{Cyrillic}\p{Greek}]/u', $name) === 1;
    }

    /** Look-alike letters from other alphabets (and accented letters) written as plain Latin letters. */
    private static function foldUnicode(string $name): string
    {
        return strtr(mb_strtolower($name), self::CONFUSABLES);
    }

    /**
     * What a name looks like at a glance: hyphens gone, digits as letters, "rn" as "m", "i" as "l",
     * written as runs of the same letter ("gooogle" = g, ooo, g, l, e).
     *
     * @return array<int, array{0: string, 1: int}>
     */
    private static function skeleton(string $name): array
    {
        $name = strtr(str_replace('-', '', strtolower($name)), self::DIGITS);
        $name = str_replace(['rn', 'vv'], ['m', 'w'], $name);
        $name = str_replace('i', 'l', $name);

        preg_match_all('/(.)\1*/u', $name, $runs);

        return array_map(fn (string $run): array => [mb_substr($run, 0, 1), mb_strlen($run)], $runs[0]);
    }

    /**
     * True when the name reads as the brand at a glance. A letter may be repeated ("gooogle") but the
     * repeat has to be on a letter the brand already doubles or has, so "goggle" (a real word) is not "google".
     */
    private static function looksLike(string $name, string $brand): bool
    {
        $a = self::skeleton($name);
        $b = self::skeleton($brand);

        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($b as $i => [$letter, $count]) {
            if ($a[$i][0] !== $letter || $a[$i][1] < $count) {
                return false;
            }
        }

        return true;
    }

    /** True when the name is the brand with exactly one letter missing, or two neighbouring letters swapped. */
    private static function oneLetterDroppedOrSwapped(string $name, string $brand): bool
    {
        $length = strlen($brand);

        if (! preg_match('/^[a-z]+$/', $name) || ! preg_match('/^[a-z]+$/', $brand) || $name === $brand) {
            return false;
        }

        if (strlen($name) === $length - 1) {
            for ($i = 0; $i < $length; $i++) {
                if (substr($brand, 0, $i).substr($brand, $i + 1) === $name) {
                    return true;
                }
            }

            return false;
        }

        if (strlen($name) === $length) {
            for ($i = 0; $i < $length - 1; $i++) {
                $swapped = $brand;
                [$swapped[$i], $swapped[$i + 1]] = [$swapped[$i + 1], $swapped[$i]];

                if ($swapped === $name) {
                    return true;
                }
            }
        }

        return false;
    }
}
