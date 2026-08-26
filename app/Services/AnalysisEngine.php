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
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Could not connect to verify SSL (unrelated to certificate validity)']];
        } catch (\Throwable $e) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Could not verify SSL certificate due to an unexpected error']];
        }
    }

    public function checkBlacklist(string $url): array
    {
        $apiKey = config('services.google_safe_browsing.key');

        if (!$apiKey) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check skipped: no API key configured']];
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
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check unavailable']];
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
        return [
            'name' => $name,
            'status' => $result['flagged'] ? $flaggedStatus : 'SAFE',
            'message' => !empty($result['reasons'])
                ? implode(' ', $result['reasons'])
                : 'No issues detected for this check.',
            'points' => $result['points'],
        ];
    }

    public function analyze(string $type, ?string $url = null, ?string $email = null, ?string $phone = null, ?string $screenshotPath = null, ?int $reportId = null): array
    {
        return match ($type) {
            'email' => $this->analyzeEmail($email ?? ''),
            'phone' => $this->analyzePhone($phone ?? '', $reportId),
            'screenshot' => $this->analyzeScreenshot($screenshotPath ?? ''),
            default => $this->analyzeUrl($url ?? ''),
        };
    }

    private function analyzeUrl(string $url): array
    {
        $syntaxResult = $this->checkUrlSyntax($url);
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
        ];

        $result = [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'],
            'url_syntax_score' => $syntaxResult['points'],
            'ip_address' => $ipResult['ip'],
            'ip_reputation' => $ipResult['summary'],
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

    private function analyzeEmail(string $email): array
    {
        $emailResult = $this->checkEmailDomain($email);
        $domain = $emailResult['domain'];

        $ageResult = $domain
            ? $this->checkDomainAge($domain)
            : ['flagged' => false, 'points' => 0, 'domain_age_days' => null, 'reasons' => ['Could not extract a domain from this email']];

        $totalPoints = min(100, $emailResult['points'] + $ageResult['points']);
        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('Sender Domain Analysis', $emailResult, 'SUSPICIOUS'),
            $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
        ];

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

        // Genuinely unparseable input isn't a phone number report at all —
        // treat it like a screenshot with no extractable content: honest
        // "needs review" rather than a confident suspicious score, and skip
        // the Previous Reports lookup since there's no real number to match.
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

    private function analyzeScreenshot(string $imagePath): array
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

        $candidateUrlsRaw = $this->extractAllUrlsFromText($ocr['text']);
        // OCR-matched text can look URL-ish (starts with http://) without
        // being a genuinely valid URL. Normal URL submissions go through
        // Laravel's 'url' validation rule in the controller; this path
        // bypassed that entirely, so garbled OCR text could trigger a full
        // WHOIS/SSL/VirusTotal/IP pipeline on nonsense input. Validate here.
        $candidateUrls = array_values(array_filter(
            $candidateUrlsRaw,
            fn ($u) => filter_var($u, FILTER_VALIDATE_URL)
        ));

        $extractedUrl = null;
        if (!empty($candidateUrls)) {
            // A screenshot can contain more than one link. Rank candidates
            // using the free, no-network syntax check before running the
            // expensive full pipeline (WHOIS/SSL/VirusTotal/etc.) on only
            // the single most suspicious one — checking every candidate in
            // full would multiply scan time per extra link found.
            usort(
                $candidateUrls,
                fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']
            );
            $extractedUrl = $candidateUrls[0];
        }

        $extractedEmail = null;
        if (!$extractedUrl) {
            $candidateEmails = $this->extractAllEmailsFromText($ocr['text']);
            if (!empty($candidateEmails)) {
                // Same reasoning as above: a phishing screenshot can list
                // more than one sender address. Rank by the free
                // checkEmailDomain heuristic and analyze the worst one.
                usort(
                    $candidateEmails,
                    fn ($a, $b) => $this->checkEmailDomain($b)['points'] <=> $this->checkEmailDomain($a)['points']
                );
                $extractedEmail = $candidateEmails[0];
            }
        }

        // If nothing matched, surface what OCR actually returned so it's clear
        // whether this was an OCR miss (no readable text), a genuinely
        // unrelated image (game screenshot, photo, etc.), or actual phishing
        // language that just didn't happen to contain a link/email — using
        // only the OCR text already extracted, at no extra API cost.
        if ($ocr['text'] === '') {
            $noMatchDetail = ' No text was extracted from the image at all.';
        } elseif (!empty($candidateUrlsRaw) && !$extractedUrl) {
            $noMatchDetail = ' OCR found text resembling a URL ("' . $candidateUrlsRaw[0] . '") but it was not a validly formatted URL, so it was not scanned.';
        } elseif ($this->containsPhishingLureLanguage($ocr['text'])) {
            $noMatchDetail = ' OCR extracted text containing common scam/phishing language (e.g. urgency, account warnings, requests to verify or click), but no URL or email address was found in it. Manual review recommended. Extracted text: "' . Str::limit($ocr['text'], 200) . '"';
        } else {
            $noMatchDetail = ' This does not appear to be a phishing-related screenshot — no URL, email address, or common scam language was detected in the extracted text. Extracted text: "' . Str::limit($ocr['text'], 200) . '"';
        }

        $extractionCheck = [
            'name' => 'Screenshot Text Extraction',
            'status' => $extractedUrl || $extractedEmail ? 'SAFE' : 'REVIEW',
            'message' => $extractedUrl
                ? "Extracted a URL from the image text: {$extractedUrl}"
                : ($extractedEmail
                    ? "Extracted a sender email from the image text: {$extractedEmail}"
                    : 'No URL or email address was found in the image text.' . $noMatchDetail),
            'points' => 0,
        ];

        if ($extractedUrl) {
            $result = $this->analyzeUrl($extractedUrl);
            $result['checks'] = array_merge([$extractionCheck], $result['checks']);
            return $result;
        }

        if ($extractedEmail) {
            $result = $this->analyzeEmail($extractedEmail);
            $result['checks'] = array_merge([$extractionCheck], $result['checks']);
            return $result;
        }

        return [
            'risk_score' => 0,
            'verdict' => 'review',
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => [$extractionCheck],
        ];
    }
}