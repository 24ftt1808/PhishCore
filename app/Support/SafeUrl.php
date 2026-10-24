<?php

namespace App\Support;

/**
 * Decides whether PhishCore's own server may fetch a web address that a visitor typed in.
 *
 * Every scan makes this server visit the submitted link, and follow its redirects. Without a
 * guard, anyone could submit an address that leads to the server's own network (127.0.0.1, a
 * cloud metadata address, a private IP) and make it knock on those doors. An address is refused when:
 *  - it is not http or https, or uses an unusual port;
 *  - the host is an IP address (in ANY spelling: 2130706433, 0x7f.0.0.1, 127.1, [::1]) that is internal;
 *  - the host name resolves to an internal address (any one of its addresses is enough).
 *
 * A name that does not resolve at all is allowed: nothing can be reached through it, and the
 * normal "could not reach the website" result then applies. Callers check the address before the
 * first request and again for every redirect (see redirectOptions()).
 */
final class SafeUrl
{
    /** Ports a web page is normally served on. Anything else is not fetched. */
    private const ALLOWED_PORTS = [80, 443, 8080, 8443];

    /** Addresses that are not on the public internet: private, loopback, link-local, reserved, multicast, transition. */
    private const INTERNAL_RANGES = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12',
        '192.0.0.0/24', '192.0.2.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
        '203.0.113.0/24', '224.0.0.0/3',
        '::/128', '::1/128', '64:ff9b::/96', '100::/64', '2001::/32', '2001:db8::/32', '2002::/16',
        'fc00::/7', 'fe80::/10', 'ff00::/8',
    ];

    /** Names that only ever mean "this machine or this network". */
    private const INTERNAL_NAME = '/(^|\.)(localhost|local|internal|intranet|lan|home|corp)$/';

    /** @var (callable(string): array<int, string>)|null */
    private static $resolver = null;

    /** For tests: answer name lookups without using DNS. Pass null to go back to real DNS. */
    public static function resolveUsing(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    /** True when the address may be fetched. */
    public static function isSafe(string $url): bool
    {
        return self::problem($url) === null;
    }

    /** Why the address must not be fetched, in plain words, or null when it is fine. */
    public static function problem(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return 'The address has no website name.';
        }

        if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return 'Only web addresses (http or https) are checked.';
        }

        if (isset($parts['port']) && ! in_array((int) $parts['port'], self::ALLOWED_PORTS, true)) {
            return 'The address uses an unusual port, so it was not visited.';
        }

        $host = strtolower(trim((string) $parts['host'], '[]. '));

        $literal = self::parseIpLiteral($host);
        if ($literal !== null) {
            return self::isInternalIp($literal) ? 'The address points to an internal or private location, so it was not visited.' : null;
        }

        // A colon means an IPv6 address; if it could not be read (for example one with a zone id) it is not trusted.
        if (str_contains($host, ':')) {
            return 'The address could not be read safely, so it was not visited.';
        }

        if (preg_match(self::INTERNAL_NAME, $host)) {
            return 'The address points to an internal or private location, so it was not visited.';
        }

        foreach (self::resolve($host) as $ip) {
            if (self::isInternalIp($ip)) {
                return 'The website name points to an internal or private location, so it was not visited.';
            }
        }

        return null;
    }

    /**
     * Guzzle options that re-check every redirect. A page on a public site can answer with
     * "go to http://169.254.169.254/", and the redirect would otherwise be followed blindly.
     *
     * @return array{allow_redirects: array{max: int, protocols: array<int, string>, on_redirect: callable}}
     */
    public static function redirectOptions(int $maxRedirects = 5): array
    {
        return ['allow_redirects' => [
            'max' => $maxRedirects,
            'protocols' => ['http', 'https'],
            'on_redirect' => function ($request, $response, $uri): void {
                if (($why = self::problem((string) $uri)) !== null) {
                    throw new \RuntimeException('Redirect refused: '.$why);
                }
            },
        ]];
    }

    /**
     * Reads a host as an IP address if it is one in any spelling a browser or curl accepts:
     * dotted, a single number (2130706433), hex (0x7f000001, 0x7f.0.0.1), octal (0177.0.0.1),
     * short forms (127.1), IPv6 and IPv4-in-IPv6. Returns the address in normal form, or null
     * when the host is an ordinary name.
     */
    public static function parseIpLiteral(string $host): ?string
    {
        $host = trim($host, '[]');

        if (str_contains($host, ':')) {
            $packed = @inet_pton($host);

            return $packed === false ? null : inet_ntop($packed);
        }

        $parts = explode('.', $host);
        if (count($parts) > 4) {
            return null;
        }

        $numbers = [];
        foreach ($parts as $part) {
            if (preg_match('/^0x[0-9a-f]*$/i', $part)) {
                $numbers[] = $part === '0x' || strlen($part) > 10 ? null : hexdec($part);
            } elseif (preg_match('/^0[0-7]*$/', $part)) {
                $numbers[] = strlen($part) > 12 ? null : octdec($part);
            } elseif (preg_match('/^[1-9][0-9]*$/', $part) && strlen($part) <= 10) {
                $numbers[] = (int) $part;
            } else {
                return null;
            }
        }

        if (in_array(null, $numbers, true)) {
            return null;
        }

        $last = array_pop($numbers);
        $leading = count($numbers);

        foreach ($numbers as $number) {
            if ($number > 255) {
                return null;
            }
        }

        if ($last >= 256 ** (4 - $leading)) {
            return null;
        }

        $value = $last;
        foreach ($numbers as $position => $number) {
            $value += $number * (256 ** (3 - $position));
        }

        return long2ip((int) $value);
    }

    /** True when an IP address (normal form) is not on the public internet. */
    public static function isInternalIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return true;
        }

        // An IPv4 address wrapped in IPv6 (::ffff:127.0.0.1) is judged as the IPv4 address it carries.
        if (strlen($packed) === 16 && str_starts_with($packed, str_repeat("\0", 10)."\xff\xff")) {
            return self::isInternalIp(inet_ntop(substr($packed, 12)));
        }

        foreach (self::INTERNAL_RANGES as $range) {
            [$base, $bits] = explode('/', $range);
            $basePacked = inet_pton($base);

            if (strlen($basePacked) === strlen($packed) && self::sharesPrefix($packed, $basePacked, (int) $bits)) {
                return true;
            }
        }

        return false;
    }

    private static function sharesPrefix(string $a, string $b, int $bits): bool
    {
        $whole = intdiv($bits, 8);

        if ($whole > 0 && substr($a, 0, $whole) !== substr($b, 0, $whole)) {
            return false;
        }

        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($a[$whole]) & $mask) === (ord($b[$whole]) & $mask);
    }

    /** @return array<int, string> every address the name points to (empty when it does not resolve) */
    private static function resolve(string $host): array
    {
        if (self::$resolver !== null) {
            return (self::$resolver)($host);
        }

        $ips = [];

        foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }

        // The system resolver also follows CNAME chains, which the lookup above can miss.
        foreach (@gethostbynamel($host) ?: [] as $ip) {
            $ips[] = $ip;
        }

        return array_values(array_unique(array_filter($ips)));
    }
}
