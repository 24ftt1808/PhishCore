<?php

namespace App\Services;

use Iodev\Whois\Factory;
use Iodev\Whois\Loaders\SocketLoader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Report;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class AnalysisEngine
{
    public function checkUrlSyntax(string $url): array
    {
        $reasons = [];
        $points = 0;

        if (preg_match('/^https?:\/\/(\d{1,3}\.){3}\d{1,3}/', $url)) {
            $reasons[] = 'URL uses a raw IP address instead of a domain name';
            $points += 25;
        }

        if (str_contains($url, '@')) {
            $reasons[] = 'URL contains an "@" symbol, which can mask the real destination';
            $points += 25;
        }

        $hyphenCount = substr_count(parse_url($url, PHP_URL_HOST) ?? '', '-');
        if ($hyphenCount >= 2) {
            $reasons[] = "Domain contains {$hyphenCount} hyphens, which is unusually high";
            $points += 15;
        }

        if (strlen($url) > 75) {
            $reasons[] = 'URL is unusually long';
            $points += 10;
        }

        $brands = ['paypal', 'google', 'facebook', 'apple', 'microsoft', 'amazon', 'bank'];
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $normalizedHost = $this->normalizeForBrandMatch($host);
        foreach ($brands as $brand) {
            $matchesBrand = str_contains($normalizedHost, $brand);
            // The legit-domain exclusion must run on the ORIGINAL host, not the
            // normalized one — otherwise a lookalike like "micros0ft.com" would
            // normalize into looking identical to "microsoft.com" and get
            // incorrectly whitelisted instead of flagged.
            $isLegitDomain = str_starts_with($host, $brand . '.') || str_ends_with($host, '.' . $brand . '.com');

            if ($matchesBrand && !$isLegitDomain) {
                $reasons[] = str_contains($host, $brand)
                    ? "Contains brand name \"{$brand}\" in a suspicious position within the domain"
                    : "Domain appears to mimic \"{$brand}\" using character substitution (e.g. a digit in place of a letter)";
                $points += 30;
                break;
            }
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
        ];
    }

    /**
     * Accepts either a full URL (http://example.com) or a bare domain/host (example.com).
     */
    public function checkDomainAge(string $urlOrHost): array
    {
        $host = str_contains($urlOrHost, '://')
            ? (parse_url($urlOrHost, PHP_URL_HOST) ?? $urlOrHost)
            : $urlOrHost;

        $host = preg_replace('/^www\./', '', $host);

        try {
            // Confirmed via the installed package's own Factory.php source
            // (createLoader() returns SocketLoader by default) — its
            // constructor defaults to a 60-second timeout, which can
            // dominate a scan's total runtime if a WHOIS server is slow
            // or unresponsive. Bound it to 8s like our other checks.
            $loader = new SocketLoader(8);
            $whois = Factory::get()->createWhois($loader);
            $info = $whois->loadDomainInfo($host);

                       if (!$info || !$info->creationDate) {
                return [
                    'flagged' => false,
                    'points' => 0,
                    'domain_age_days' => null,
                    'reasons' => ['WHOIS data unavailable for this domain'],
                    'unavailable' => true,
                ];
            }

            $createdAt = \Carbon\Carbon::createFromTimestamp($info->creationDate);
            $ageDays = (int) round($createdAt->diffInDays(now()));

            $points = 0;
            $reasons = [];

            if ($ageDays < 7) {
                $points = 40;
                $reasons[] = "Domain was registered only {$ageDays} day(s) ago — a strong phishing indicator";
            } elseif ($ageDays < 30) {
                $points = 25;
                $reasons[] = "Domain was registered {$ageDays} days ago — recently registered domains are higher risk";
            } elseif ($ageDays < 180) {
                $points = 10;
                $reasons[] = "Domain is relatively new ({$ageDays} days old)";
            }

            return [
                'flagged' => $points > 0,
                'points' => $points,
                'domain_age_days' => $ageDays,
                'reasons' => $reasons,
            ];
              } catch (\Throwable $e) {
            return [
                'flagged' => false,
                'points' => 0,
                'domain_age_days' => null,
                'reasons' => ['Could not retrieve WHOIS data'],
                'unavailable' => true,
            ];
        }
    }

    public function checkSslCertificate(string $url): array
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== 'https') {
            return [
                'flagged' => true,
                'points' => 20,
                'reasons' => ['Website does not use HTTPS (no SSL encryption)'],
            ];
        }

        try {
            // Confirmed via testing: a raw stream_socket_client TLS handshake
            // (the previous approach) failed on a legitimate, correctly
            // configured site (politeknikbrunei.edu.bn) even on a good
            // connection, while every other check in this class — using
            // Laravel's Http client — reached the same site without issue.
            // Curl (which Http uses under the hood) handles SNI, modern TLS
            // versions, and certificate chains far more robustly than a raw
            // socket handshake. A HEAD request is enough to trigger the full
            // TLS handshake without downloading the page body.
            Http::timeout(10)->head($url);

            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $message = $e->getMessage();

            if (stripos($message, 'certificate has expired') !== false) {
                return ['flagged' => true, 'points' => 30, 'reasons' => ['SSL certificate has expired']];
            }

            if (stripos($message, 'does not match') !== false || stripos($message, 'subject name') !== false) {
                return ['flagged' => true, 'points' => 20, 'reasons' => ['SSL certificate does not match the domain']];
            }

            if (stripos($message, 'ssl') !== false || stripos($message, 'certificate') !== false) {
                return ['flagged' => true, 'points' => 25, 'reasons' => ['Could not verify a valid, trusted SSL certificate for this domain']];
            }

                     // A connection-level failure that isn't SSL-specific (DNS
            // failure, connection refused, timeout) shouldn't count against
            // the SSL check specifically — other checks (redirect chain, IP
            // reputation) already surface general connectivity problems.
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Could not connect to verify SSL (unrelated to certificate validity)'], 'unavailable' => true];
        } catch (\Throwable $e) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Could not verify SSL certificate due to an unexpected error'], 'unavailable' => true];
        }
    }

    public function checkBlacklist(string $url): array
    {
        $apiKey = config('services.google_safe_browsing.key');

               if (!$apiKey) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check skipped: no API key configured'], 'unavailable' => true];
        }

        try {
            // Every other network call in this class has an explicit timeout;
            // this one was missing it and could hang with no defined bound.
            $response = Http::timeout(10)->post(
                "https://safebrowsing.googleapis.com/v4/threatMatches:find?key={$apiKey}",
                [
                    'client' => ['clientId' => 'phishcore', 'clientVersion' => '1.0.0'],
                    'threatInfo' => [
                        'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
                        'platformTypes' => ['ANY_PLATFORM'],
                        'threatEntryTypes' => ['URL'],
                        'threatEntries' => [['url' => $url]],
                    ],
                ]
            );

            $matches = $response->json('matches', []);

            if (!empty($matches)) {
                return [
                    'flagged' => true,
                    'points' => 50,
                    'reasons' => ["URL is flagged on Google Safe Browsing's threat list"],
                ];
            }

                     return ['flagged' => false, 'points' => 0, 'reasons' => []];
        } catch (\Throwable $e) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check unavailable'], 'unavailable' => true];
        }
    }

    /**
     * Checks a URL against VirusTotal's multi-vendor threat database.
     * Tries a fast cached lookup first; only submits + polls for a fresh
     * scan if VT has no existing record for this URL.
     */
    public function checkVirusTotal(string $url): array
    {
        $apiKey = config('services.virustotal.key');

        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'malicious' => null,
            'total' => null,
            'raw' => null,
            'unavailable' => true,
        ];

        if (!$apiKey) {
            $empty['reasons'][] = 'VirusTotal check skipped: no API key configured';
            return $empty;
        }

        try {
            $urlId = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');

            $lookup = Http::withHeaders(['x-apikey' => $apiKey])
                ->timeout(10)
                ->get("https://www.virustotal.com/api/v3/urls/{$urlId}");

            $stats = null;
            $raw = null;

            if ($lookup->successful()) {
                $stats = $lookup->json('data.attributes.last_analysis_stats');
                $raw = $lookup->json();
            } elseif ($lookup->status() === 404) {
                $submit = Http::withHeaders(['x-apikey' => $apiKey])
                    ->timeout(10)
                    ->asForm()
                    ->post('https://www.virustotal.com/api/v3/urls', ['url' => $url]);

                if (!$submit->successful()) {
                    $empty['reasons'][] = 'Could not submit URL to VirusTotal for scanning';
                    return $empty;
                }

                $analysisId = $submit->json('data.id');

                // VT's own scan queue time is outside our control, but a brand
                // new URL previously polled up to 4x with a 2s wait and a 15s
                // timeout each — up to 68s just for this one check. Trimmed to
                // bound our worst-case patience without giving up entirely.
                for ($attempt = 0; $attempt < 3; $attempt++) {
                    sleep(1);
                    $analysisResp = Http::withHeaders(['x-apikey' => $apiKey])
                        ->timeout(8)
                        ->get("https://www.virustotal.com/api/v3/analyses/{$analysisId}");

                    if ($analysisResp->json('data.attributes.status') === 'completed') {
                        $stats = $analysisResp->json('data.attributes.stats');
                        $raw = $analysisResp->json();
                        break;
                    }
                }

                if (!$stats) {
                    $empty['reasons'][] = 'VirusTotal analysis is still processing for this new URL — try scanning again shortly';
                    return $empty;
                }
            } else {
                $empty['reasons'][] = 'VirusTotal lookup failed';
                return $empty;
            }

            if (!$stats) {
                $empty['reasons'][] = 'VirusTotal has no analysis data available for this URL';
                return $empty;
            }

            $malicious = $stats['malicious'] ?? 0;
            $suspicious = $stats['suspicious'] ?? 0;
            $harmless = $stats['harmless'] ?? 0;
            $undetected = $stats['undetected'] ?? 0;
            $total = $malicious + $suspicious + $harmless + $undetected;
            $flaggedCount = $malicious + $suspicious;

            $points = 0;
            $reasons = [];

            if ($flaggedCount > 0) {
                $points = min(50, $flaggedCount * 5);
                $reasons[] = "{$flaggedCount} out of {$total} security vendors on VirusTotal flagged this URL as malicious or suspicious";
            } else {
                $reasons[] = "0 out of {$total} security vendors on VirusTotal flagged this URL";
            }

            return [
                'flagged' => $flaggedCount > 0,
                'points' => $points,
                'reasons' => $reasons,
                'malicious' => $flaggedCount,
                'total' => $total,
                'raw' => $raw,
            ];
        } catch (\Throwable $e) {
            $empty['reasons'][] = 'Could not reach VirusTotal';
            return $empty;
        }
    }

    /**
     * Resolves the URL's domain to an IP address and checks its geolocation,
     * ISP, and whether it's a proxy/VPN or generic hosting provider — common
     * traits of rapidly-deployed phishing infrastructure.
     */
    public function checkIpReputation(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);

              $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'ip' => null,
            'country' => null,
            'summary' => null,
            'unavailable' => true,
        ];

        if (!$host) {
            $empty['reasons'][] = 'Could not determine host from URL';
            return $empty;
        }

        $ip = @gethostbyname($host);
        if (!$ip || $ip === $host) {
            $empty['reasons'][] = 'Could not resolve domain to an IP address';
            return $empty;
        }

        try {
            $response = Http::timeout(10)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,message,country,countryCode,isp,org,proxy,hosting,query',
            ]);

            if (!$response->successful() || $response->json('status') !== 'success') {
                $empty['ip'] = $ip;
                $empty['reasons'][] = 'IP reputation lookup unavailable';
                return $empty;
            }

            $data = $response->json();
            $country = $data['country'] ?? 'Unknown';
            $isp = $data['isp'] ?? 'Unknown ISP';
            $isProxy = $data['proxy'] ?? false;
            $isHosting = $data['hosting'] ?? false;

            $reasons = [];
            $points = 0;

            if ($isProxy) {
                $reasons[] = 'IP address is associated with a known proxy or VPN service, often used to mask phishing infrastructure';
                $points += 20;
            }

            if ($isHosting) {
                // Reduced from 15: datacenter/cloud hosting is now the norm
                // for the vast majority of legitimate websites too (AWS,
                // Google Cloud, Cloudflare, etc.) — including Google's own
                // infrastructure — so this alone is weak evidence on its own,
                // unlike proxy/VPN usage above which remains far more specific.
                $reasons[] = 'IP address belongs to a datacenter/hosting provider rather than a residential ISP — common for phishing infrastructure, but also true of most legitimate modern websites';
                $points += 5;
            }

            if (empty($reasons)) {
                $reasons[] = "Hosted in {$country} via {$isp}, no proxy or datacenter flags";
            }

            $summary = "{$ip} — {$country} ({$isp})"
                . ($isProxy ? ' · Proxy/VPN' : '')
                . ($isHosting ? ' · Hosting/Datacenter' : '');

            return [
                'flagged' => $points > 0,
                'points' => $points,
                'reasons' => $reasons,
                'ip' => $ip,
                'country' => $country,
                'summary' => $summary,
            ];
        } catch (\Throwable $e) {
            $empty['ip'] = $ip;
            $empty['reasons'][] = 'Could not reach IP reputation service';
            return $empty;
        }
    }

    /**
     * Manually follows the URL's redirect chain (without auto-following)
     * to detect domain-hopping and excessive redirect counts.
     */
       public function checkRedirectChain(string $url): array
    {
        $chain = [$url];
        $originalHost = parse_url($url, PHP_URL_HOST);
        $current = $url;

        for ($hop = 0; $hop < 5; $hop++) {
            try {
                $response = Http::withOptions(['allow_redirects' => false])
                    ->timeout(8)
                    ->get($current);
            } catch (\Throwable $e) {
                break;
            }

            $status = $response->status();

            if (!in_array($status, [301, 302, 303, 307, 308])) {
                break;
            }

            $location = $response->header('Location');
            if (!$location) {
                break;
            }

            if (!str_starts_with($location, 'http')) {
                $parsed = parse_url($current);
                $location = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $location;
            }

            $chain[] = $location;
            $current = $location;
        }

        $hopCount = count($chain) - 1;
        $finalHost = parse_url(end($chain), PHP_URL_HOST);

        $reasons = [];
        $points = 0;

        if ($hopCount >= 3) {
            $reasons[] = "URL redirects through {$hopCount} hops before reaching its final destination, an unusually long chain";
            $points += 15;
        }

        // Comparing hosts as raw strings would flag the extremely common,
        // totally benign "apex domain redirects to www subdomain" pattern
        // (e.g. google.com -> www.google.com) as if it were a suspicious
        // cross-domain redirect. Strip a leading "www." before comparing so
        // only genuinely different domains get flagged.
        $normalizeHost = fn (?string $h) => preg_replace('/^www\./', '', strtolower($h ?? ''));

        if ($finalHost && $originalHost && $normalizeHost($finalHost) !== $normalizeHost($originalHost)) {
            $reasons[] = "URL ultimately redirects to a different domain ({$finalHost}) than the one submitted ({$originalHost})";
            $points += 20;
        }

        if (empty($reasons)) {
            $reasons[] = $hopCount > 0
                ? "Redirects {$hopCount} time(s) but stays on the same domain"
                : 'No redirects detected';
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
            'chain' => $chain,
        ];
    }
    /**
     * Normalizes common leetspeak digit-for-letter substitutions used to
     * evade simple brand-name substring matching (e.g. "micros0ft.com"
     * using a zero in place of the letter "o"). This deliberately covers
     * only the most common digit tricks, not full Unicode homoglyph
     * detection (e.g. Cyrillic lookalike characters), which is a much
     * larger problem outside this project's scope.
     */
    private function normalizeForBrandMatch(string $text): string
    {
        return strtr($text, [
            '0' => 'o',
            '1' => 'l',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '7' => 't',
            '8' => 'b',
        ]);
    }

    /**
     * Extracts the domain after "@" and flags brand-impersonation / suspicious patterns.
     */
    public function checkEmailDomain(string $email): array
    {
        if (!str_contains($email, '@')) {
            return [
                'flagged' => true,
                'points' => 30,
                'reasons' => ['Not a valid email address format'],
                'domain' => null,
            ];
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1));
        $reasons = [];
        $points = 0;

        $brands = ['paypal', 'google', 'facebook', 'apple', 'microsoft', 'amazon', 'bank', 'dhl', 'fedex', 'ups', 'maybank', 'bibd'];
        $normalizedDomain = $this->normalizeForBrandMatch($domain);
        foreach ($brands as $brand) {
            $matchesBrand = str_contains($normalizedDomain, $brand);
            $isOfficialDomain = str_ends_with($domain, $brand . '.com') || $domain === $brand . '.com';

            if ($matchesBrand && !$isOfficialDomain) {
                $reasons[] = str_contains($domain, $brand)
                    ? "Sender domain mimics the brand \"{$brand}\" without being the official domain"
                    : "Sender domain mimics the brand \"{$brand}\" using character substitution (e.g. a digit in place of a letter), without being the official domain";
                $points += 35;
                break;
            }
        }

        $hyphenCount = substr_count($domain, '-');
        if ($hyphenCount >= 2) {
            $reasons[] = "Sender domain contains {$hyphenCount} hyphens, which is unusually high";
            $points += 15;
        }

        if (preg_match('/\d{2,}/', $domain)) {
            $reasons[] = 'Sender domain contains an unusual number sequence, common in disposable phishing domains';
            $points += 15;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
            'domain' => $domain,
        ];
    }

    /**
     * Validates reported phone numbers using Google's libphonenumber (via the
     * giggsey/libphonenumber-for-php port) instead of hand-rolled per-country
     * rules. This correctly validates format and numbering-plan assignment for
     * any country, not just Brunei, and identifies the number's line type
     * (mobile, VoIP, premium-rate, etc.) using real carrier metadata.
     *
     * "BN" is passed as the default region: if the number already includes an
     * explicit country code (e.g. "+1 555..."), that code takes priority
     * regardless; if it's a bare local-style number with no country code, it's
     * assumed to be a Brunei number, matching how people naturally type local
     * numbers into this platform.
     */
    public function checkPhoneNumber(string $phone): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsed = $phoneUtil->parse($phone, 'BN');

            if (!$phoneUtil->isValidNumber($parsed)) {
                return [
                    'flagged' => true,
                    'points' => 35,
                    'reasons' => ["Number is not a valid, assignable number under its country's numbering plan"],
                ];
            }

            $reasons = [];
            $points = 0;

            $region = $phoneUtil->getRegionCodeForNumber($parsed);
            $type = $phoneUtil->getNumberType($parsed);
            $typeLabel = match ($type) {
                PhoneNumberType::FIXED_LINE => 'Fixed line',
                PhoneNumberType::MOBILE => 'Mobile',
                PhoneNumberType::FIXED_LINE_OR_MOBILE => 'Fixed line or mobile',
                PhoneNumberType::TOLL_FREE => 'Toll-free',
                PhoneNumberType::PREMIUM_RATE => 'Premium rate',
                PhoneNumberType::SHARED_COST => 'Shared cost',
                PhoneNumberType::VOIP => 'VoIP',
                PhoneNumberType::PERSONAL_NUMBER => 'Personal number',
                PhoneNumberType::PAGER => 'Pager',
                PhoneNumberType::UAN => 'Universal access number',
                PhoneNumberType::VOICEMAIL => 'Voicemail',
                PhoneNumberType::EMERGENCY => 'Emergency',
                PhoneNumberType::SHORT_CODE => 'Short code',
                PhoneNumberType::STANDARD_RATE => 'Standard rate',
                default => 'Unknown',
            };

            switch ($type) {
                case PhoneNumberType::PREMIUM_RATE:
                    $reasons[] = 'Number is a premium-rate line, unusual for a personal sender and often used in call-back scams';
                    $points += 35;
                    break;
                case PhoneNumberType::VOIP:
                    $reasons[] = 'Number is a VoIP/internet-based line, inexpensive to acquire anonymously and commonly used in phishing/smishing campaigns';
                    $points += 20;
                    break;
                case PhoneNumberType::UNKNOWN:
                    $reasons[] = 'Number type could not be determined by the numbering plan, which can indicate an unusual allocation';
                    $points += 15;
                    break;
                case PhoneNumberType::PAGER:
                case PhoneNumberType::UAN:
                case PhoneNumberType::SHARED_COST:
                    $reasons[] = "Number is a {$typeLabel} line, an uncommon type for a personal sender";
                    $points += 10;
                    break;
                default:
                    // Mobile, Fixed line, Fixed line or mobile, Toll-free,
                    // Personal number, Voicemail: no penalty by default.
                    break;
            }

            // Deliberately NOT a flat penalty for being non-Brunei — a real
            // foreign contact (family, courier, overseas business) is
            // completely normal. This is informational context only.
            if ($region && $region !== 'BN') {
                $reasons[] = "Number originates from outside Brunei (region: {$region}) — worth verifying if it claims to represent a local Brunei service";
            }

            $nationalDigits = preg_replace('/\D/', '', $phoneUtil->format($parsed, PhoneNumberFormat::NATIONAL));

            if ($nationalDigits !== '' && preg_match('/^(\d)\1+$/', $nationalDigits)) {
                $reasons[] = 'Number consists of a single digit repeated throughout, a common sign of a fabricated number even if technically within a valid range';
                $points += 25;
            } elseif ($nationalDigits !== '' && $this->isSequentialDigits($nationalDigits)) {
                $reasons[] = 'Number follows a simple sequential digit pattern, a common sign of a fabricated number';
                $points += 20;
            }

            if (empty($reasons)) {
                $reasons[] = "Valid {$typeLabel} number" . ($region ? " registered in {$region}" : '') . ', no issues detected';
            }

            return [
                'flagged' => $points > 0,
                'points' => $points,
                'reasons' => $reasons,
                'region' => $region,
                'type' => $typeLabel,
            ];
        } catch (NumberParseException $e) {
            // Genuinely unparseable input ("2", random text) isn't evidence
            // of a scam — it's more likely a typo or incomplete report. Flag
            // this distinctly so analyzePhone() can route it to an honest
            // "not enough information" result instead of a confident
            // suspicious score, the same way screenshot scanning handles an
            // image with no extractable phishing content.
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['This does not appear to be a phone number at all — please check the format and try again'],
                'unparseable' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['Could not validate this number due to an unexpected error'],
            ];
        }
    }

    private function checkContentPatterns(string $text): array
{
    $text = strtolower($text);
    $reasons = [];
    $points = 0;
    $matchedCategories = 0;

    $patterns = [
        'urgency' => [
            'regex' => '/\b(within (the )?next 24 hours|less than 24 hours|act now|act immediately|urgent|final notice|immediately)\b/',
            'points' => 15,
            'label' => 'Urgency language detected (e.g. "urgent", "24 hours", "act now")',
        ],
        'account_threat' => [
            'regex' => '/(account will be suspended|account (is|has been) (locked|suspended|deactivated)|temporary suspension|avoid deactivation)/',
            'points' => 15,
            'label' => 'Account threat language detected (suspension/deactivation)',
        ],
        'credential_request' => [
            'regex' => '/(verify your account|verify my|confirm your password|enter your login|verification code|verify now)/',
            'points' => 20,
            'label' => 'Credential/verification request detected',
        ],
        'financial_request' => [
            'regex' => '/(transfer (of )?funds|payment required|invoice|bank account|refund)/',
            'points' => 15,
            'label' => 'Financial request or invoice language detected',
        ],
        'call_to_action' => [
            'regex' => '/(click here|click the button|click below|follow the link)/',
            'points' => 10,
            'label' => 'Suspicious call-to-action phrasing detected (click/follow link)',
        ],
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern['regex'], $text)) {
            $reasons[] = $pattern['label'];
            $points += $pattern['points'];
            $matchedCategories++;
        }
    }

    if ($matchedCategories >= 3) {
        $reasons[] = "{$matchedCategories} distinct phishing behavior patterns found together — a coordinated social-engineering pattern, not an isolated keyword";
        $points += 15;
    }

    return [
        'flagged' => $points > 0,
        'points' => min(90, $points),
        'reasons' => $reasons,
        'matched_categories' => $matchedCategories,
    ];
}

private function detectBrandInText(string $text): array
{
    $knownBrands = ['dhl', 'fedex', 'ups', 'paypal', 'google', 'facebook', 'apple',
        'microsoft', 'outlook', 'amazon', 'netflix', 'maybank', 'bibd'];

    $lowerText = strtolower($text);

    // Exact match first — cheapest and most reliable when the brand name
    // is spelled correctly in the message.
    foreach ($knownBrands as $brand) {
        if (preg_match('/\b' . preg_quote($brand, '/') . '\b/', $lowerText)) {
            return ['brand' => $brand, 'surface' => $brand];
        }
    }

    // Fuzzy fallback — catches cases where the VISIBLE brand text itself is
    // a typosquat (e.g. "DHI" instead of "DHL"), not just the sender domain.
    // Length-difference guard keeps short/common words from false-matching
    // against short brand names.
    preg_match_all('/\b[A-Za-z]{2,12}\b/', $text, $m);
    $words = array_unique(array_map('strtolower', $m[0] ?? []));

    $bestBrand = null;
    $bestSurface = null;
    $bestPercent = 0;

    foreach ($words as $word) {
        foreach ($knownBrands as $brand) {
            if ($word === $brand || abs(strlen($word) - strlen($brand)) > 2) {
                continue;
            }
            similar_text($word, $brand, $percent);
            if ($percent >= 65 && $percent > $bestPercent) {
                $bestPercent = $percent;
                $bestBrand = $brand;
                $bestSurface = $word;
            }
        }
    }

    return ['brand' => $bestBrand, 'surface' => $bestSurface];
}

private function checkBrandSenderMismatch(?string $brand, ?string $surfaceText, ?string $senderEmail): array
{
    if (!$brand || !$senderEmail || !str_contains($senderEmail, '@')) {
        return ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    [$localPart, $domain] = explode('@', $senderEmail, 2);
    $domain = strtolower($domain);
    $localPart = strtolower($localPart);
    $displayBrand = $surfaceText && $surfaceText !== $brand
        ? strtoupper($surfaceText) . '" (a lookalike of "' . ucfirst($brand)
        : ucfirst($brand);

    $brandDomains = [
        'dhl' => ['dhl.com'],
        'fedex' => ['fedex.com'],
        'ups' => ['ups.com'],
        'paypal' => ['paypal.com'],
        'google' => ['google.com', 'gmail.com'],
        'facebook' => ['facebook.com', 'fb.com'],
        'apple' => ['apple.com', 'icloud.com'],
        'microsoft' => ['microsoft.com', 'outlook.com', 'live.com', 'hotmail.com'],
        'outlook' => ['outlook.com', 'live.com', 'hotmail.com', 'microsoft.com'],
        'amazon' => ['amazon.com'],
        'netflix' => ['netflix.com'],
        'maybank' => ['maybank2u.com.my', 'maybank.com'],
        'bibd' => ['bibd.com.bn'],
    ];

    $allowed = $brandDomains[$brand] ?? [$brand . '.com'];
    $isOfficial = false;
    foreach ($allowed as $officialDomain) {
        if ($domain === $officialDomain || str_ends_with($domain, '.' . $officialDomain)) {
            $isOfficial = true;
            break;
        }
    }

    if ($isOfficial) {
        return ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    $reasons = ["\"" . $displayBrand . "\" branding was detected in the message, but the sender domain (\"{$domain}\") does not belong to {$brand}"];
    $points = 25;

    $cleanedLocal = preg_replace('/^(no-?reply|support|info|admin|service|notification|alert|team|contact)[-_.]?/i', '', $localPart);
    if ($cleanedLocal === '') {
        $cleanedLocal = $localPart;
    }
    $domainRoot = explode('.', $domain)[0] ?? $domain;

    similar_text($cleanedLocal, $brand, $localPercent);
    similar_text($domainRoot, $brand, $domainPercent);
    $bestPercent = max($localPercent, $domainPercent);
    $comparedAgainst = $localPercent >= $domainPercent ? $cleanedLocal : $domainRoot;

    if ($bestPercent >= 45 && $comparedAgainst !== $brand) {
        $reasons[] = "\"{$comparedAgainst}\" is " . round($bestPercent) . "% similar to \"{$brand}\", suggesting a lookalike/typosquat attempt";
        $points += 15;
    }

    return ['flagged' => true, 'points' => $points, 'reasons' => $reasons];
}

private function detectAttachment(string $text): array
{
    if (preg_match('/([\w\-]+\.(exe|scr|js|bat|vbs))\b/i', $text, $m)) {
        return ['flagged' => true, 'points' => 25, 'reasons' => ["Executable/script attachment detected: {$m[1]} — high risk file type"]];
    }
    if (preg_match('/([\w\-]+\.(zip|rar|7z))\b/i', $text, $m)) {
        return ['flagged' => true, 'points' => 15, 'reasons' => ["Compressed archive attachment detected: {$m[1]}"]];
    }
    if (preg_match('/([\w\-]+\.(docm|xlsm))\b/i', $text, $m)) {
        return ['flagged' => true, 'points' => 15, 'reasons' => ["Macro-enabled Office document attachment detected: {$m[1]} — can execute code when opened"]];
    }
    if (preg_match('/([\w\-]+\.(doc|docx|xls|xlsx))\b/i', $text, $m)) {
        return ['flagged' => true, 'points' => 10, 'reasons' => ["Office document attachment detected: {$m[1]}"]];
    }
    return ['flagged' => false, 'points' => 0, 'reasons' => []];
}

    private function isSequentialDigits(string $digits): bool
    {
        $ascending = true;
        $descending = true;

        for ($i = 1; $i < strlen($digits); $i++) {
            if ((int) $digits[$i] !== (int) $digits[$i - 1] + 1) {
                $ascending = false;
            }
            if ((int) $digits[$i] !== (int) $digits[$i - 1] - 1) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }

    /**
     * Sends the uploaded image to OCR.space and returns the extracted text.
     *
     * OCR.space's free tier can return inconsistent results for the exact
     * same image between calls — empty or truncated text on one run, full
     * text on the next. To reduce false "needs review" results caused by
     * this flakiness, we retry once if the first attempt fails outright or
     * comes back with no usable text.
     */
    public function checkScreenshotOcr(string $imagePath): array
    {
        $apiKey = config('services.ocr_space.key');

        if (!$apiKey) {
            return ['success' => false, 'text' => '', 'error' => 'OCR API key not configured'];
        }

        if (!file_exists($imagePath)) {
            return ['success' => false, 'text' => '', 'error' => 'Uploaded image could not be found'];
        }

        $lastError = 'OCR processing failed';

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $response = Http::asMultipart()
                    ->attach('file', file_get_contents($imagePath), basename($imagePath))
                    ->timeout(30)
                    ->post('https://api.ocr.space/parse/image', [
                        'apikey' => $apiKey,
                        'language' => 'eng',
                        'isOverlayRequired' => 'false',
                        'OCREngine' => '2',
                        'scale' => 'true',
                    ]);

                $data = $response->json();

                if (($data['IsErroredOnProcessing'] ?? true) === true) {
                    $lastError = $data['ErrorMessage'][0] ?? 'OCR processing failed';
                    continue; // try again before giving up
                }

                $text = trim($data['ParsedResults'][0]['ParsedText'] ?? '');

                if ($text === '') {
                    $lastError = 'OCR returned no readable text';
                    continue; // empty text — worth one retry in case this run was flaky
                }

                return ['success' => true, 'text' => $text, 'error' => null];
            } catch (\Throwable $e) {
                $lastError = 'Could not reach the OCR service';
            }
        }

        return ['success' => false, 'text' => '', 'error' => $lastError];
    }

    /**
     * Returns ALL URL-like matches in the text, not just the first — a
     * screenshot can contain multiple links (e.g. a legitimate-looking one
     * alongside the actual phishing link), and only checking whichever
     * appears first in reading order risks missing the more suspicious one.
     */
    private function extractAllUrlsFromText(string $text): array
    {
        preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $text, $matches);
        return array_map(fn($m) => rtrim($m, '.,;:)'), $matches[0] ?? []);
    }

    /**
     * OCR often introduces stray spaces around "@" and "." in email addresses
     * (e.g. "services @ paypal - accounts . com"). Normalize those artifacts
     * before matching so noisy-but-readable text still extracts correctly.
     *
     * Returns ALL email-like matches, not just the first — a phishing
     * screenshot can list more than one sender address (e.g. a display
     * address and a reply-to), and checking only whichever comes first
     * positionally can miss a more obviously fake one listed afterward.
     */
    private function extractAllEmailsFromText(string $text): array
    {
        $normalized = preg_replace('/\s*@\s*/', '@', $text);
        $normalized = preg_replace('/\s*\.\s*(?=[a-zA-Z]{2,}\b)/', '.', $normalized);

        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $normalized, $matches);
        return $matches[0] ?? [];
    }

    /**
     * A lightweight keyword check for common phishing/scam lure language.
     * Used purely to make the "no URL or email found" message clearer when a
     * screenshot has no extractable link/address — distinguishing a genuine
     * phishing message that OCR just couldn't pull a link/email out of, from
     * an entirely unrelated image (a game screenshot, a personal photo, etc.)
     * that was never phishing-related in the first place. This is a simple
     * substring check, not real language understanding — it will miss
     * cleverly-worded scams and can occasionally flag legitimate messages
     * that happen to use similar phrasing (e.g. a real password-reset email).
     * It only affects wording, not scoring.
     */
    private function containsPhishingLureLanguage(string $text): bool
    {
        $lureKeywords = [
            'urgent', 'verify your', 'verify account', 'account suspended', 'account locked',
            'account has been', 'confirm your', 'unusual activity', 'security alert',
            'password expired', 'click here', 'click the link', 'log in to', 'login to',
            'reset your password', 'update your payment', 'update your billing',
            'otp', 'one-time password', 'one time password', 'verification code',
            'congratulations', 'you have won', 'you\'ve won', 'claim your', 'limited time',
            'act now', 'act immediately', 'final notice', 'immediate action required',
            'failed delivery', 'delivery failed', 'tracking number', 'could not be delivered',
            'invoice attached', 'payment failed', 'refund', 'suspended due to',
        ];

        $lowerText = strtolower($text);
        foreach ($lureKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a structured check result for the UI (name, status, message).
     */
        private function buildCheck(string $name, array $result, string $flaggedStatus = 'SUSPICIOUS'): array
    {
        if ($result['unavailable'] ?? false) {
            return [
                'name' => $name,
                'status' => 'UNKNOWN',
                'message' => !empty($result['reasons'])
                    ? implode(' ', $result['reasons'])
                    : 'This check could not be completed.',
                'points' => $result['points'],
            ];
        }

        return [
            'name' => $name,
            'status' => $result['flagged'] ? $flaggedStatus : 'SAFE',
            'message' => !empty($result['reasons'])
                ? implode(' ', $result['reasons'])
                : 'No issues detected for this check.',
            'points' => $result['points'],
        ];
    }

            public function analyze(string $type, ?string $url = null, ?string $email = null, ?string $phone = null, ?string $screenshotPath = null, ?int $reportId = null, ?string $emailSubject = null, ?string $emailBody = null): array
    {
        return match ($type) {
            'email' => $this->analyzeEmail($email ?? '', $reportId, $emailSubject, $emailBody),
            'phone' => $this->analyzePhone($phone ?? '', $reportId),
            'screenshot' => $this->analyzeScreenshot($screenshotPath ?? '', $reportId),
            default => $this->analyzeUrl($url ?? '', $reportId),
        };
    }

        private function analyzeUrl(string $url, ?int $reportId = null): array
    {
        $syntaxResult = $this->checkUrlSyntax($url);
        $host = parse_url($url, PHP_URL_HOST);
        $domainHistoryResult = $this->checkPreviousDomainReports($host, $reportId);
        $ageResult = $this->checkDomainAge($url);
        $sslResult = $this->checkSslCertificate($url);
        $blacklistResult = $this->checkBlacklist($url);
        $vtResult = $this->checkVirusTotal($url);
        $ipResult = $this->checkIpReputation($url);
        $redirectResult = $this->checkRedirectChain($url);

        $totalPoints = min(
    100,
    $syntaxResult['points'] + $ageResult['points'] + $sslResult['points']
        + $blacklistResult['points'] + $vtResult['points'] + $ipResult['points'] + $redirectResult['points']
);

        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

              $checks = [
            $this->buildCheck('SSL Certificate', $sslResult, 'SUSPICIOUS'),
            $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('URL Structure', $syntaxResult, 'SUSPICIOUS'),
            $this->buildCheck('Blacklist Database', $blacklistResult, 'DETECTED'),
            $this->buildCheck('VirusTotal / CTI Check', $vtResult, 'DETECTED'),
            $this->buildCheck('IP Reputation & Location', $ipResult, 'SUSPICIOUS'),
            $this->buildCheck('Redirect Chain', $redirectResult, 'SUSPICIOUS'),
            $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS'),
        ];

                $result = [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'],
            'url_syntax_score' => $syntaxResult['points'],
            'ip_address' => $ipResult['ip'],
            'ip_reputation' => $ipResult['summary'],
            'country' => $ipResult['country'],
            'redirect_chain' => $redirectResult['chain'],
            'checks' => $checks,
        ];

        if ($vtResult['raw'] !== null) {
            $threatScore = $vtResult['total'] > 0
                ? round(($vtResult['malicious'] / $vtResult['total']) * 100, 1)
                : 0.0;

            $result['cti'] = [
                'source' => 'VirusTotal',
                'raw_response' => $vtResult['raw'],
                'threat_score' => $threatScore,
            ];
        }

        return $result;
    }

              private function analyzeEmail(string $email, ?int $reportId = null, ?string $subject = null, ?string $body = null): array
    {
        $emailResult = $this->checkEmailDomain($email);
        $domain = $emailResult['domain'];

        $ageResult = $domain
            ? $this->checkDomainAge($domain)
            : ['flagged' => false, 'points' => 0, 'domain_age_days' => null, 'reasons' => ['Could not extract a domain from this email']];

        $domainHistoryResult = $this->checkPreviousDomainReports($domain, $reportId);

        $combinedText = trim(($subject ?? '') . ' ' . ($body ?? ''));
        $hasContentText = $combinedText !== '';

        $contentResult = $hasContentText
            ? $this->checkContentPatterns($combinedText)
            : ['flagged' => false, 'points' => 0, 'reasons' => [], 'matched_categories' => 0];

        $brandDetection = $hasContentText
            ? $this->detectBrandInText($combinedText)
            : ['brand' => null, 'surface' => null];

        $brandResult = $this->checkBrandSenderMismatch($brandDetection['brand'], $brandDetection['surface'], $email);

        $totalPoints = min(
            100,
            $emailResult['points'] + $ageResult['points'] + $contentResult['points'] + $brandResult['points']
        );
        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('Sender Domain Analysis', $emailResult, 'SUSPICIOUS'),
            $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS'),
        ];

        if ($hasContentText) {
            $checks[] = $this->buildCheck('Message Content / Behavior Patterns', $contentResult, $contentResult['matched_categories'] >= 3 ? 'HIGH RISK' : 'SUSPICIOUS');
            if ($brandDetection['brand']) {
                $checks[] = $this->buildCheck('Brand / Sender Correlation', $brandResult, 'HIGH RISK');
            }
        } else {
            $checks[] = [
                'name' => 'Message Content / Behavior Patterns',
                'status' => 'REVIEW',
                'message' => 'No subject or body text was provided — content analysis skipped. Add the subject/body for a more accurate result.',
                'points' => 0,
            ];
        }

        return [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'] ?? null,
            'url_syntax_score' => null,
            'checks' => $checks,
        ];
    }

   private function analyzePhone(string $phone, ?int $reportId = null): array
{
    $phoneResult = $this->checkPhoneNumber($phone);

    if ($phoneResult['unparseable'] ?? false) {
        return [
            'risk_score' => 0,
            'verdict' => 'review',
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => [
                $this->buildCheck('Phone Number Analysis', $phoneResult, 'REVIEW'),
            ],
        ];
    }

    $historyResult = $this->checkPreviousReports($phone, $reportId);

    $totalPoints = min(100, $phoneResult['points'] + $historyResult['points']);
    $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

    $checks = [
        $this->buildCheck('Phone Number Analysis', $phoneResult, 'SUSPICIOUS'),
        $this->buildCheck('Previous Reports', $historyResult, $historyResult['points'] >= 45 ? 'HIGH RISK' : 'SUSPICIOUS'),
    ];

    return [
        'risk_score' => $totalPoints,
        'verdict' => $verdict,
        'domain_age_days' => null,
        'url_syntax_score' => null,
        'checks' => $checks,
    ];
}

        /**
 * Checks how many times a domain (from a URL host or an email's
 * sender domain) has already appeared in OTHER completed reports on
 * PhishCore — across url, email, AND screenshot scans — regardless of
 * how each one individually scored. The same phishing infrastructure
 * is frequently reused across multiple different pretexts (a fake DHL
 * email today, a fake Outlook email tomorrow, all from the same
 * domain), and a domain surfacing repeatedly is strong evidence even
 * when a single report in isolation looks unremarkable.
 *
 * Uses a LIKE match against the raw url/sender_email columns rather
 * than an exact host comparison, since those columns store full
 * submitted values (e.g. "https://sub.domain.com/path") not bare
 * hosts. This can over-match in rare edge cases (e.g. a domain name
 * appearing as a substring of an unrelated longer domain), a known
 * limitation acceptable for this project's scope.
 */
private function checkPreviousDomainReports(?string $domain, ?int $excludeReportId = null): array
{
    $domain = strtolower(trim((string) $domain));
    $domain = preg_replace('/^www\./', '', $domain);

    if ($domain === '') {
        return ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    $query = Report::where('status', 'completed')
        ->whereHas('analyses', function ($q) {
            $q->whereIn('verdict', ['suspicious', 'phishing']);
        })
        ->where(function ($q) use ($domain) {
            $q->where('url', 'like', "%{$domain}%")
                ->orWhere('sender_email', 'like', "%{$domain}%");
        });

    if ($excludeReportId) {
        $query->where('id', '!=', $excludeReportId);
    }

    $candidateReports = $query->get(['id', 'user_id', 'url', 'sender_email']);

    // The LIKE query above is only a cheap first pass to shrink the result
    // set. It matches SUBSTRINGS, so a phishing lookalike domain like
    // "google.com.verify-account.tk" would incorrectly match "google.com"
    // — which is backwards: that domain is impersonating Google, not
    // evidence against the real one. Re-check precisely here by parsing
    // out the ACTUAL host/domain from each candidate and requiring an
    // exact match (or genuine subdomain), not just a substring.
    $matchingReports = $candidateReports->filter(function ($report) use ($domain) {
        $reportDomain = null;

        if ($report->url) {
            $host = parse_url($report->url, PHP_URL_HOST);
            $reportDomain = $host ? strtolower(preg_replace('/^www\./', '', $host)) : null;
        }

        if (!$reportDomain && $report->sender_email && str_contains($report->sender_email, '@')) {
            $reportDomain = strtolower(substr(strrchr($report->sender_email, '@'), 1));
        }

        if (!$reportDomain) {
            return false;
        }

        return $reportDomain === $domain || str_ends_with($reportDomain, '.' . $domain);
    });

    $distinctLoggedInReporters = $matchingReports->pluck('user_id')->filter()->unique()->count();
    $guestReportCount = $matchingReports->whereNull('user_id')->count();
    $reporterCount = $distinctLoggedInReporters + $guestReportCount;

    if ($reporterCount === 0) {
        return [
            'flagged' => false,
            'points' => 0,
            'reasons' => ["No prior reports found for \"{$domain}\" on PhishCore"],
        ];
    }

   if ($reporterCount === 1) {
    return [
        'flagged' => true,
        'points' => 10,
        'reasons' => ["This domain has appeared in 1 other report on PhishCore"],
    ];
}

if ($reporterCount <= 4) {
    return [
        'flagged' => true,
        'points' => 18,
        'reasons' => ["This domain has appeared in {$reporterCount} other reports on PhishCore — reused across multiple submissions"],
    ];
}

return [
    'flagged' => true,
    'points' => 25,
    'reasons' => ["This domain has appeared in {$reporterCount} other reports on PhishCore — repeatedly reused phishing infrastructure"],
];
}

    /**
     * Checks how many times this exact phone number has already been
     * reported on PhishCore by other scans — a crowd-sourced signal similar
     * in spirit to caller-ID/spam-reporting apps, built from the platform's
     * own report history rather than an external database.
     *
     * Numbers are compared with punctuation/whitespace stripped so the same
     * number submitted in different formats (e.g. "+673 811 1346" vs
     * "6738111346") is still recognized as a repeat.
     *
     * $excludeReportId excludes the current report's own row — the Report
     * record for this scan already exists in the database by the time this
     * runs, so without excluding it, every number would show as "reported
     * at least once" on its very first scan.
     */
    private function checkPreviousReports(string $phone, ?int $excludeReportId = null): array
    {
        $normalizedInput = preg_replace('/[\s()\-]/', '', $phone);

        $query = Report::where('type', 'phone')
            ->where('status', 'completed')
            ->whereNotNull('phone_number');

        if ($excludeReportId) {
            $query->where('id', '!=', $excludeReportId);
        }

        $matchingReports = $query->get()
            ->filter(function ($report) use ($normalizedInput) {
                return preg_replace('/[\s()\-]/', '', $report->phone_number) === $normalizedInput;
            });

        // Count DISTINCT reporters, not raw scan submissions — otherwise one
        // person scanning the same number several times (e.g. while testing)
        // looks identical to several different people independently flagging
        // it, which is a much stronger and more meaningful signal.
        // Guest scans (user_id is null) can't be deduplicated against each
        // other without session/IP tracking, so each guest scan is counted
        // as its own reporter — a known limitation, not a perfect count.
        $distinctLoggedInReporters = $matchingReports->pluck('user_id')->filter()->unique()->count();
        $guestReportCount = $matchingReports->whereNull('user_id')->count();
        $reporterCount = $distinctLoggedInReporters + $guestReportCount;

        if ($reporterCount === 0) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['No prior reports found for this number on PhishCore'],
            ];
        }

        if ($reporterCount === 1) {
            return [
                'flagged' => true,
                'points' => 15,
                'reasons' => ['This number has been reported by 1 user before on PhishCore'],
            ];
        }

        if ($reporterCount <= 4) {
            return [
                'flagged' => true,
                'points' => 30,
                'reasons' => ["This number has been reported by {$reporterCount} different users before on PhishCore"],
            ];
        }

        return [
            'flagged' => true,
            'points' => 45,
            'reasons' => ["This number has been reported by {$reporterCount} different users before on PhishCore — repeatedly flagged"],
        ];
    }

     private function analyzeScreenshot(string $imagePath, ?int $reportId = null): array
{
    $ocr = $this->checkScreenshotOcr($imagePath);

    if (!$ocr['success']) {
        return [
            'risk_score' => 0,
            'verdict' => 'review',
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => [[
                'name' => 'Screenshot Text Extraction',
                'status' => 'REVIEW',
                'message' => 'Could not read text from this image (' . ($ocr['error'] ?? 'unknown error') . '). Manual review recommended.',
                'points' => 0,
            ]],
        ];
    }

    $text = $ocr['text'];

    $candidateUrlsRaw = $this->extractAllUrlsFromText($text);
    $candidateUrls = array_values(array_filter($candidateUrlsRaw, fn ($u) => filter_var($u, FILTER_VALIDATE_URL)));
    $extractedUrl = null;
    if (!empty($candidateUrls)) {
        usort($candidateUrls, fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']);
        $extractedUrl = $candidateUrls[0];
    }

    $candidateEmails = $this->extractAllEmailsFromText($text);
    $extractedEmail = null;
    if (!empty($candidateEmails)) {
        usort($candidateEmails, fn ($a, $b) => $this->checkEmailDomain($b)['points'] <=> $this->checkEmailDomain($a)['points']);
        $extractedEmail = $candidateEmails[0];
    }

     $brandDetection = $this->detectBrandInText($text);
    $detectedBrand = $brandDetection['brand'];
    $brandSurfaceText = $brandDetection['surface'];
    $contentResult = $this->checkContentPatterns($text);
    $attachmentResult = $this->detectAttachment($text);
    $brandResult = $this->checkBrandSenderMismatch($detectedBrand, $brandSurfaceText, $extractedEmail);
    $hasAnyEvidence = $extractedUrl || $extractedEmail || $detectedBrand || $contentResult['flagged'] || $attachmentResult['flagged'];

    $checks = [];
    $totalPoints = 0;
    $signalCategories = 0;
    $ageResult = ['domain_age_days' => null];

    $extractionParts = [];
    if ($extractedUrl) $extractionParts[] = "URL: {$extractedUrl}";
    if ($extractedEmail) $extractionParts[] = "Sender: {$extractedEmail}";
    if ($detectedBrand) $extractionParts[] = 'Brand referenced: ' . ucfirst($detectedBrand);

    $checks[] = [
        'name' => 'Screenshot Text Extraction',
        'status' => $hasAnyEvidence ? 'SAFE' : 'REVIEW',
        'message' => $hasAnyEvidence
            ? ('Extracted from image: ' . implode(' | ', $extractionParts ?: ['phishing-style language']))
            : 'No URL, email address, brand reference, or phishing-style language was found in the image text. Extracted text: "' . Str::limit($text, 200) . '"',
        'points' => 0,
    ];

    if ($extractedEmail) {
        $emailResult = $this->checkEmailDomain($extractedEmail);
        $domain = $emailResult['domain'];
        $ageResult = $domain
            ? $this->checkDomainAge($domain)
            : ['flagged' => false, 'points' => 0, 'domain_age_days' => null, 'reasons' => []];

        $totalPoints += $emailResult['points'] + $ageResult['points'];
        if ($emailResult['points'] > 0 || $ageResult['points'] > 0) $signalCategories++;

        $checks[] = $this->buildCheck('Sender Domain Analysis', $emailResult, 'SUSPICIOUS');
        if ($domain) {
            $checks[] = $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
        }
    }

    if ($detectedBrand) {
        if ($brandResult['flagged']) {
            $totalPoints += $brandResult['points'];
            $signalCategories++;
        }
        $checks[] = $this->buildCheck('Brand / Sender Correlation', $brandResult, 'HIGH RISK');
    }

    if ($contentResult['flagged']) {
        $totalPoints += $contentResult['points'];
        $signalCategories++;
    }
    $checks[] = $this->buildCheck('Message Content / Behavior Patterns', $contentResult, $contentResult['matched_categories'] >= 3 ? 'HIGH RISK' : 'SUSPICIOUS');

    if ($attachmentResult['flagged']) {
        $totalPoints += $attachmentResult['points'];
        $signalCategories++;
        $checks[] = $this->buildCheck('Attachment', $attachmentResult, 'SUSPICIOUS');
    }

        if ($extractedUrl) {
        $urlAnalysis = $this->analyzeUrl($extractedUrl, $reportId);
        $totalPoints += (int) round($urlAnalysis['risk_score'] * 0.6);
        $signalCategories++;
        $checks = array_merge($checks, $urlAnalysis['checks']);
    }

    if ($extractedEmail) {
    $emailDomain = $this->checkEmailDomain($extractedEmail)['domain'];
    $domainHistoryResult = $this->checkPreviousDomainReports($emailDomain, $reportId);
    $checks[] = $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS');
}

    $riskScore = min(100, $totalPoints);
    $verdict = !$hasAnyEvidence ? 'review' : ($riskScore >= 60 ? 'phishing' : ($riskScore >= 25 ? 'suspicious' : 'clean'));
    $confidence = $hasAnyEvidence ? min(95, 40 + $signalCategories * 13) : 20;

    return [
        'risk_score' => $riskScore,
        'confidence' => $confidence,
        'verdict' => $verdict,
        'domain_age_days' => $ageResult['domain_age_days'] ?? null,
        'url_syntax_score' => null,
        'checks' => $checks,
        'extracted_url' => $extractedUrl,
        'extracted_email' => $extractedEmail,
    ];
}
}