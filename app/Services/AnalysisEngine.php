<?php

namespace App\Services;

use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Iodev\Whois\Factory;
use Iodev\Whois\Loaders\SocketLoader;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Zxing\QrReader;

class AnalysisEngine
{
    /**
     * Brunei-specific brands and their OFFICIAL domains. Every domain here was
     * confirmed against the organisation's own site/Wikipedia before adding —
     * a wrong domain would flag the brand's real site as impersonating itself.
     *
     * Deliberately multi-word for names that are ordinary words or surnames
     * ("imagine", "taib", "dst"): a bare match would fire on unrelated pages.
     * Merged into the page-content and email brand checks below.
     */
    private const BRUNEI_BRANDS = [
        'baiduri' => ['baiduri.com.bn'],
        'bdcb' => ['bdcb.gov.bn'],
        'progresif' => ['progresif.com'],
        'datastream digital' => ['dst.com.bn'],
        'dst brunei' => ['dst.com.bn'],
        'imagine brunei' => ['imagine.com.bn'],
        'perbadanan taib' => ['taib.com.bn', 'insuranstaib.com.bn'],
        'insurans islam taib' => ['insuranstaib.com.bn'],
        'brunei customs' => ['customs.gov.bn'],
        'royal customs and excise' => ['customs.gov.bn'],
        'royal brunei airlines' => ['flyroyalbrunei.com'],
        'royal brunei police force' => ['polis.gov.bn'],
        'polis diraja brunei' => ['polis.gov.bn'],
        'immigration and national registration department' => ['immigration.gov.bn'],
        'jabatan imigresen' => ['immigration.gov.bn'],
        'land transport department' => ['jpd.gov.bn'],
        'jabatan pengangkutan darat' => ['jpd.gov.bn'],
        'department of electrical services' => ['des.gov.bn'],
        'jabatan perkhidmatan elektrik' => ['des.gov.bn'],
        'brunei post' => ['post.gov.bn'],
        'brunei postal services' => ['post.gov.bn'],
    ];

    /**
     * Brand => official domains, shared by the page-content brand check and
     * by checkUrlSyntax() so a brand's own site is never treated as imitating
     * it. Add a domain here only after confirming it against the
     * organisation's own site: a wrong entry would flag the real site.
     */
    private const OFFICIAL_BRAND_DOMAINS = [
        'dhl' => ['dhl.com'],
        'fedex' => ['fedex.com'],
        'ups' => ['ups.com'],
        'paypal' => ['paypal.com'],
        // google.github.io, googleblog.blogspot.com and blog.google are
        // Google's own pages; microsoft/netflix/spotify .github.io are those
        // companies' official open-source pages. Each was opened and confirmed
        // before adding, because GitHub account names are first come first
        // served and other brands' same-named accounts may belong to someone else.
        'google' => ['google.com', 'gmail.com', 'blog.google', 'googleblog.blogspot.com', 'google.github.io'],
        'facebook' => ['facebook.com', 'fb.com'],
        'apple' => ['apple.com', 'icloud.com'],
        'microsoft' => ['microsoft.com', 'outlook.com', 'live.com', 'hotmail.com', 'microsoft.github.io'],
        'outlook' => ['outlook.com', 'live.com', 'hotmail.com', 'microsoft.com'],
        'amazon' => ['amazon.com'],
        'netflix' => ['netflix.com', 'netflix.github.io'],
        'maybank' => ['maybank2u.com.my', 'maybank.com'],
        'bibd' => ['bibd.com.bn'],
        // Verified by opening each site: whatsapp.com, livelo.com.br, correios.com.br;
        // bradesco.com.br redirects to banco.bradesco.
        'bradesco' => ['bradesco.com.br', 'banco.bradesco'],
        'livelo' => ['livelo.com.br'],
        'correios' => ['correios.com.br'],
        'whatsapp' => ['whatsapp.com', 'whatsapp.net'],
        // Crypto/wallets — domains verified via web search before adding,
        // not assumed, after an earlier session mistake (a wrong domain
        // guess for politeknikbrunei.edu.bn) taught the cost of guessing.
        'trezor' => ['trezor.io'],
        'metamask' => ['metamask.io'],
        'binance' => ['binance.com'],
        'coinbase' => ['coinbase.com'],
        'kraken' => ['kraken.com'],
        'crypto.com' => ['crypto.com'],
        'blockchain.com' => ['blockchain.com'],
        'trust wallet' => ['trustwallet.com'],
        // Regional banking — baiduri.com.bn and progresif.com confirmed
        // via web search; both differ from a naive first guess.
        'baiduri' => ['baiduri.com.bn'],
        'hsbc' => ['hsbc.com', 'hsbc.com.bn'],
        'citibank' => ['citibank.com', 'citi.com'],
        'standard chartered' => ['sc.com'],
        // E-commerce
        'shopee' => ['shopee.com'],
        'lazada' => ['lazada.com'],
        'alibaba' => ['alibaba.com'],
        'ebay' => ['ebay.com'],
        // Telco
        'progresif' => ['progresif.com'],
        // Tech/social — steam and zoom deliberately do NOT use
        // "brandname.com" (steampowered.com, zoom.us), confirmed before
        // adding since a wrong domain here would flag the brand's own
        // real site as impersonating itself.
        'instagram' => ['instagram.com'],
        'whatsapp' => ['whatsapp.com'],
        'linkedin' => ['linkedin.com'],
        'tiktok' => ['tiktok.com'],
        'spotify' => ['spotify.com', 'spotify.github.io'],
        'adobe' => ['adobe.com'],
        'dropbox' => ['dropbox.com'],
        'steam' => ['steampowered.com'],
        'zoom' => ['zoom.us'],
        'twitter' => ['twitter.com', 'x.com'],
        // Shipping
        'usps' => ['usps.com'],
        'royal mail' => ['royalmail.com'],
        'singpost' => ['singpost.com'],
    ];

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
        // A brand's own site can contain a brand word without imitating
        // anything: "maybank2u.com.my" contains "bank" and is Maybank's real
        // domain. Exact official domains (and their subdomains) are skipped.
        $isOfficialBrandSite = $this->isOfficialBrandDomain($host);
        foreach ($brands as $brand) {
            $matchesBrand = ! $isOfficialBrandSite && str_contains($normalizedHost, $brand);
            // The legit-domain exclusion must run on the ORIGINAL host, not the
            // normalized one — otherwise a lookalike like "micros0ft.com" would
            // normalize into looking identical to "microsoft.com" and get
            // incorrectly whitelisted instead of flagged.
            $isLegitDomain = $host === $brand.'.com' || str_ends_with($host, '.'.$brand.'.com');

            if ($matchesBrand && ! $isLegitDomain) {
                $reasons[] = str_contains($host, $brand)
                    ? "Contains brand name \"{$brand}\" in a suspicious position within the domain"
                    : "Domain appears to mimic \"{$brand}\" using character substitution (e.g. a digit in place of a letter)";
                $points += 30;
                break;
            }
        }

        // Brunei-specific lookalikes. Official domains differ from the
        // naive "brand.com" guess (e.g. bibd.com.bn), so they are checked
        // against an explicit allow-list rather than the loop above.
        if (! preg_grep('/brand name|mimic/', $reasons)) {
            $bnBrands = [
                'bibd' => ['bibd.com.bn'],
                'baiduri' => ['baiduri.com.bn'],
                'bdcb' => ['bdcb.gov.bn'],
                'progresif' => ['progresif.com'],
                'bruneipost' => ['post.gov.bn'],
                'royalbrunei' => ['flyroyalbrunei.com'],
            ];
            foreach ($bnBrands as $bnBrand => $officialDomains) {
                if (! str_contains($normalizedHost, $bnBrand)) {
                    continue;
                }
                $isOfficial = false;
                foreach ($officialDomains as $official) {
                    if ($host === $official || str_ends_with($host, '.'.$official)) {
                        $isOfficial = true;
                        break;
                    }
                }
                if (! $isOfficial) {
                    $reasons[] = "Domain imitates the Brunei brand \"{$bnBrand}\" but is not its official domain";
                    $points += 30;
                    break;
                }
            }
        }

        // Fake Brunei-government look: "gov.bn" / "gov-bn" used inside a
        // domain that does not actually end in .gov.bn.
        if (preg_match('/gov[.\-]?bn/', $host) && ! str_ends_with($host, '.gov.bn') && $host !== 'gov.bn') {
            $reasons[] = 'Domain pretends to be a Brunei government site (contains "gov.bn") but is not under .gov.bn';
            $points += 30;
        } elseif (
            preg_match('/brunei|(^|[.\-])bn([.\-]|$)/', $host)
            && preg_match('/customs|kastam|police|polis|ministry|refund|fine|gov/', $host)
            && ! str_ends_with($host, '.bn')
            && ! str_ends_with($host, 'flyroyalbrunei.com')
        ) {
            $reasons[] = 'Domain uses Brunei authority wording (customs, police, ministry, refund) outside a .bn domain';
            $points += 20;
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

            if (! $info || ! $info->creationDate) {
                return [
                    'flagged' => false,
                    'points' => 0,
                    'domain_age_days' => null,
                    'reasons' => ['WHOIS data unavailable for this domain'],
                    'unavailable' => true,
                ];
            }

            $createdAt = Carbon::createFromTimestamp($info->creationDate);
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
        } catch (ConnectionException $e) {
            $message = $e->getMessage();

            if (stripos($message, 'certificate has expired') !== false) {
                return ['flagged' => true, 'points' => 30, 'reasons' => ['SSL certificate has expired']];
            }

            if (stripos($message, 'does not match') !== false || stripos($message, 'subject name') !== false) {
                return ['flagged' => true, 'points' => 20, 'reasons' => ['SSL certificate does not match the domain']];
            }

            // "unable to get local issuer certificate" (OpenSSL error 20) means
            // the certificate's chain could not be completed from this server.
            // The most common cause is a site that does not send its
            // intermediate certificate (browsers fetch it themselves, curl
            // does not) — a server misconfiguration, not evidence of
            // phishing. Report it as UNKNOWN rather than a misleading
            // SUSPICIOUS, consistent with how failed checks are handled.
            if (stripos($message, 'unable to get local issuer') !== false) {
                return [
                    'flagged' => false,
                    'points' => 0,
                    'reasons' => ['The certificate chain could not be fully verified from our server (the site may not send its intermediate certificate). This is often a server misconfiguration and does not count against the site.'],
                    'unavailable' => true,
                ];
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

        if (! $apiKey) {
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

            if (! empty($matches)) {
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

        if (! $apiKey) {
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

                if (! $submit->successful()) {
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

                if (! $stats) {
                    $empty['reasons'][] = 'VirusTotal analysis is still processing for this new URL — try scanning again shortly';

                    return $empty;
                }
            } else {
                $empty['reasons'][] = 'VirusTotal lookup failed';

                return $empty;
            }

            if (! $stats) {
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
                if ($flaggedCount <= 3) {
                    $reasons[] = 'Only a very small share of vendors flagged it. This is often a false alarm on well-known, legitimate sites, so it counts as a minor signal.';
                }
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

        if (! $host) {
            $empty['reasons'][] = 'Could not determine host from URL';

            return $empty;
        }

        $ip = @gethostbyname($host);
        if (! $ip || $ip === $host) {
            $empty['reasons'][] = 'Could not resolve domain to an IP address';

            return $empty;
        }

        try {
            $response = Http::timeout(10)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,message,country,countryCode,isp,org,proxy,hosting,query',
            ]);

            if (! $response->successful() || $response->json('status') !== 'success') {
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

            // On a free hosting platform the IP is shared by every site on it.
            // ip-api marks those shared IPs as proxies (github.com and every
            // github.io page get the flag), which says nothing about this one
            // site; the hosting-platform check already covers the platform.
            $sharedPlatform = $this->checkFreeHostingPlatform($url)['platform'];

            if ($isProxy && $sharedPlatform !== null) {
                $reasons[] = "IP address is flagged as a proxy but is shared by all sites on \"{$sharedPlatform}\", so it says nothing about this site";
            } elseif ($isProxy) {
                // Lowered from 20 after the accuracy test (scan:batch): ip-api
                // marks shared platform IPs as proxies, so github.com and
                // moh.gov.bn scored 25 and were flagged, and 6 of the 8 scams
                // that got this flag were github.io pages sharing GitHub's own
                // address. At 10 both real sites pass and those scams are still
                // caught by the hosting-platform check on top of this one.
                $reasons[] = 'IP address is associated with a known proxy or VPN service, often used to mask phishing infrastructure';
                $points += 10;
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
                .($isProxy ? ' · Proxy/VPN' : '')
                .($isHosting ? ' · Hosting/Datacenter' : '');

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

            if (! in_array($status, [301, 302, 303, 307, 308])) {
                break;
            }

            $location = $response->header('Location');
            if (! $location) {
                break;
            }

            if (! str_starts_with($location, 'http')) {
                $parsed = parse_url($current);
                $location = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').$location;
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
            if ($this->isOfficialBrandDomain((string) $originalHost)) {
                // Domains on the curated official list cannot be registered by a
                // scammer, so where they send visitors is the owner's choice.
                $reasons[] = "URL redirects to {$finalHost}; the submitted domain is on PhishCore's official-domain list";
            } elseif ($this->isBruneiGovernmentHost($originalHost) && $this->isBruneiGovernmentHost($finalHost)) {
                // e.g. www.mofe.gov.bn -> www.mof.gov.bn after a ministry rename.
                $reasons[] = "URL redirects to another .gov.bn domain ({$finalHost}); redirects between Brunei government sites are normal";
            } else {
                $reasons[] = "URL ultimately redirects to a different domain ({$finalHost}) than the one submitted ({$originalHost})";
                $points += 20;
            }
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
     * Flags domains hosted on free website-builder / static-hosting
     * platforms (Weebly, Wix, Netlify, GitHub Pages, etc.) — a well-known,
     * legitimate phishing-kit pattern: these platforms require no identity
     * verification, are free, and can be spun up and abandoned in minutes.
     * This is a supporting signal only (modest points), since plenty of
     * hobbyists and small legitimate projects also use these same
     * platforms — the check flags the infrastructure choice, not intent.
     *
     * Matches on the registrable base domain (e.g. "weebly.com"), not a
     * substring, so a legitimate domain that merely contains one of these
     * words (unlikely, but possible) isn't wrongly matched.
     */
    public function checkFreeHostingPlatform(string $url): array
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $host = preg_replace('/^www\./', '', $host);

        $freeHostSuffixes = [
            'weebly.com', 'wixsite.com', 'blogspot.com', 'netlify.app',
            'github.io', 'pages.dev', 'carrd.co', 'glitch.me',
            '000webhostapp.com', 'firebaseapp.com', 'web.app',
            'herokuapp.com', 'vercel.app', 'repl.co', 'r2.dev',
            'sites.google.com', 'wordpress.com', 'square.site',
        ];

        foreach ($freeHostSuffixes as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                $points = 10;
                $reasons = ["Hosted on \"{$suffix}\", a free website-builder/hosting platform commonly used for disposable phishing pages — legitimate small projects also use these, so this is a supporting signal only"];

                // On a free platform the subdomain is whoever signed up, so a
                // famous brand in the PATH (github.io/Amazon-Clone, /DHL,
                // /Netflix) is the usual shape of a fake page copying that brand.
                // A brand's own official pages are skipped.
                $brand = $this->brandInPath((string) parse_url($url, PHP_URL_PATH));
                if ($brand !== null && ! $this->isOfficialBrandDomain($host)) {
                    $points += 15;
                    $reasons[] = "The page address on this free platform names a well-known brand (\"{$brand}\") that the site does not belong to";
                }

                return [
                    'flagged' => true,
                    'points' => $points,
                    'reasons' => $reasons,
                    'platform' => $suffix,
                ];
            }
        }

        return ['flagged' => false, 'points' => 0, 'reasons' => [], 'platform' => null];
    }

    /**
     * Whether the host has any DNS record. gethostbyname() only looks for an
     * IPv4 address and can fail on some resolvers or for IPv6-only and CNAME
     * hosts, which made real sites (a Brunei bank and ministry sites among
     * them) show as "no longer resolves". Falling back to a full record lookup
     * means only a domain with no A, AAAA or CNAME record is called offline.
     */
    private function hostResolves(string $host): bool
    {
        $ip = @gethostbyname($host);

        if ($ip && $ip !== $host) {
            return true;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA | DNS_CNAME);

        return is_array($records) && $records !== [];
    }

    /**
     * Checks whether the site is currently reachable at all, distinguishing
     * WHY it isn't (if it isn't) rather than lumping every failure into a
     * single generic "unavailable." This is informational only — it does
     * NOT affect the risk score in either direction. A site going offline
     * doesn't undo the fact that it was confirmed malicious when reported
     * (the original scan/VT/content evidence stands), and a site still
     * being live doesn't make it more dangerous than the other checks
     * already established. This exists purely so a reader — especially a
     * team member managing an investigation — can see at a glance whether
     * the threat is still active or already taken down.
     */
    public function checkSiteAvailability(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ['Could not determine host from URL'],
            ];
        }

        if (! $this->hostResolves($host)) {
            return [
                'status_label' => 'OFFLINE',
                'reasons' => ["This domain (\"{$host}\") no longer resolves — it may have expired, been suspended, or been taken down entirely"],
            ];
        }

        try {
            // Same browser-style User-Agent as the page-content check: many
            // banks and CDNs drop connections from the default HTTP-client agent.
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->withOptions(['allow_redirects' => ['max' => 3]])
                ->timeout(8)
                ->get($url);
        } catch (ConnectionException $e) {
            // A failed connection is not proof the site is down: slow servers
            // and bot protection cause the same error on perfectly live sites
            // (real Brunei banks were reported "offline" this way). Only a
            // domain that no longer resolves is reported as OFFLINE.
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ['The domain resolves, but the server did not answer in time or refused the connection. It may be offline, slow, or blocking automated requests, so its status could not be confirmed'],
            ];
        } catch (\Throwable $e) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ['Could not determine site availability due to an unexpected error'],
            ];
        }

        $status = $response->status();

        if (in_array($status, [404, 410])) {
            return [
                'status_label' => 'TAKEN DOWN',
                'reasons' => ["The server responded with HTTP {$status} — the specific page appears to have been removed, though the domain itself is still active"],
            ];
        }

        if ($status >= 500) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ["The server responded with HTTP {$status} — a server-side error, which may be temporary"],
            ];
        }

        return [
            'status_label' => 'LIVE',
            'reasons' => ["Site is currently live and reachable (HTTP {$status})"],
        ];
    }

    /**
     * Tier 1 content validation: fetches the page's actual HTML (rather than
     * just checking reputation/metadata about the URL) and looks for concrete
     * phishing evidence — a credential-harvesting login form, phishing-style
     * lure language in the visible text, and brand impersonation (the page
     * content claims to be a known brand while the domain does not belong to
     * that brand). This is potentially the strongest signal a scan can
     * produce, but Tier 1 pattern-matching alone can't truly verify intent,
     * so it's weighted as a supporting signal (capped well below VirusTotal)
     * rather than a dominant one until proven out with real-world testing.
     *
     * Safety guards: only http(s) schemes are fetched, private/internal IPs
     * are refused (prevents this server being tricked into scanning its own
     * internal network), redirects are capped, and a short timeout is used —
     * this server is deliberately visiting live, possibly-malicious URLs.
     */
    public function checkPageContent(string $url): array
    {
        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'unavailable' => true,
        ];

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'])) {
            $empty['reasons'][] = 'Content check skipped: unsupported URL scheme';

            return $empty;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            $empty['reasons'][] = 'Content check skipped: could not determine host';

            return $empty;
        }

        // Refuse to fetch when the URL's host is a raw IP literal pointing
        // at a private/internal address (e.g. "http://127.0.0.1" or an
        // internal IP) — the direct, obvious SSRF risk: someone submitting
        // a URL that makes this server attack its own internal network.
        //
        // Deliberately does NOT attempt to pre-resolve domain names to an
        // IP and check that instead — confirmed via testing that PHP's
        // gethostbyname() can fail to resolve a domain that the actual
        // fetch (via curl) resolves and reaches successfully, which caused
        // a real, legitimate site to be wrongly blocked as "private."
        // Known scope limitation: a domain specifically crafted to resolve
        // to an internal IP (DNS rebinding) would not be caught by this
        // check — a genuinely hard problem even for production-grade
        // security tools, and out of scope for this project.
        if (filter_var($host, FILTER_VALIDATE_IP)
            && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $empty['reasons'][] = 'Content check skipped: URL points directly at a private/internal address';

            return $empty;
        }

        try {
            // A browser-style User-Agent avoids unnecessary 403s from sites
            // that block generic/bot-looking requests (confirmed via testing
            // against Wikipedia) — improving real-world coverage without
            // pretending to be anything other than an automated fetch.
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->withOptions(['allow_redirects' => ['max' => 3]])
                ->timeout(8)
                ->get($url);
        } catch (\Throwable $e) {
            $empty['reasons'][] = 'Could not fetch page content for analysis';

            return $empty;
        }

        if (! $response->successful()) {
            $empty['reasons'][] = "Page returned HTTP {$response->status()} — content could not be analyzed";

            return $empty;
        }

        $html = $response->body();
        $serverHeader = $response->header('Server');

        // Cap how much HTML we process — phishing pages are almost always
        // small, and this bounds worst-case processing time on a huge page.
        $html = substr($html, 0, 200000);

        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch);
        $title = trim(strip_tags($titleMatch[1] ?? ''));

        $sensitiveFields = $this->detectSensitiveInputFields($html);
        $hasPasswordField = $sensitiveFields['has_password'];
        $hasSensitiveField = $hasPasswordField || $sensitiveFields['has_other_sensitive'];

        // Strip script/style blocks before converting to plain text so their
        // contents (JS code, CSS rules) don't pollute keyword/brand matching.
        $bodyText = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);
        $bodyText = trim(strip_tags($bodyText));
        $bodyText = Str::limit($bodyText, 5000, '');

        $combinedText = trim($title.' '.$bodyText);

        $reasons = [];
        $points = 0;

        // A password field alone is near-universal on legitimate sites
        // (banks, universities, webmail, forums) and is NOT evidence of
        // phishing by itself — confirmed via testing against a real
        // Politeknik Brunei login page, which has a password field and
        // nothing else suspicious, yet was being marked SUSPICIOUS purely
        // for having a login form. It's kept as context only (0 points);
        // it still unlocks the corroborating checks below (brand mismatch,
        // form-action security), which is where genuine evidence comes from.
        if ($hasPasswordField) {
            $reasons[] = 'Page contains a password input field';
        } elseif ($sensitiveFields['has_other_sensitive']) {
            $fieldList = implode(', ', $sensitiveFields['matched']);
            $reasons[] = "Page requests sensitive information without a login form: {$fieldList} — a common non-login credential/PII harvesting pattern (e.g. fake refund or OTP-verification pages)";
            $points += 12;
        }
        $patternResult = $this->checkContentPatterns($combinedText);
        // Deliberately requires 2+ co-occurring lure patterns, not just 1 —
        // confirmed via testing that a single matched category (e.g. the
        // word "account" or "verify" appearing anywhere) triggers on
        // completely legitimate e-commerce pages like Amazon's, which
        // naturally use this vocabulary throughout checkout/account flows.
        // Multiple DIFFERENT lure categories appearing together is a much
        // more specific, less common pattern.
        if ($patternResult['matched_categories'] >= 3) {
            $reasons[] = 'Page text contains multiple phishing-style lure phrases (urgency, credential requests, etc.)';
            $points += 15;
        } elseif ($patternResult['matched_categories'] >= 2) {
            $reasons[] = 'Page text contains phishing-style lure language';
            $points += 8;
        }

        // Brand impersonation is only flagged when CORROBORATED by an actual
        // password field on the same page. Brand mention alone is a weak,
        // extremely common signal — any Wikipedia article, news piece, or
        // review site legitimately mentions a brand by name while living on
        // a different domain. Confirmed via testing: without this gate, a
        // Wikipedia article about PayPal was flagged identically to a real
        // PayPal phishing clone. A genuine credential-harvesting page
        // impersonates a brand AND asks for a password together — an
        // informational page about that brand does not do both.
        if ($hasSensitiveField) {
            $brandDetection = $this->detectBrandExactMatch(
                $combinedText,
                fn (string $brand) => $this->brandIsPageIdentityOrLoginPrompt($brand, $title, $html, $bodyText)
            );
            if ($brandDetection['brand']) {
                $brandMismatch = $this->checkPageBrandMismatch($brandDetection['brand'], $brandDetection['surface'], $host);
                if ($brandMismatch['flagged']) {
                    $reasons = array_merge($reasons, $brandMismatch['reasons']);
                    $points += $brandMismatch['points'];
                }
            }

            $formSecurityResult = $this->checkFormActionSecurity($html, $host);
            if ($formSecurityResult['flagged']) {
                $reasons = array_merge($reasons, $formSecurityResult['reasons']);
                $points += $formSecurityResult['points'];
            }
        }

        // Static HTML redirect trick that checkRedirectChain() can't see —
        // that check only follows real HTTP 3xx responses.
        $metaRefreshResult = $this->checkMetaRefresh($html, $host);
        if ($metaRefreshResult['flagged']) {
            $reasons = array_merge($reasons, $metaRefreshResult['reasons']);
            $points += $metaRefreshResult['points'];
        }

        $obfuscationResult = $this->checkObfuscationPatterns($html);
        if ($obfuscationResult['flagged']) {
            $reasons = array_merge($reasons, $obfuscationResult['reasons']);
            $points += $obfuscationResult['points'];
        }

        if (empty($reasons)) {
            $reasons[] = 'No login forms, sensitive-data requests, phishing-style language, brand impersonation, hidden iframes, or obfuscated scripts detected in page content';
        }

        // Purely informational — doesn't affect flagged/points. Legitimate
        // sites and phishing pages both routinely omit or fake this header,
        // so it's context for a reader, not evidence on its own.
        if ($serverHeader) {
            $reasons[] = "Server responds as: {$serverHeader}";
        }

        return [
            'flagged' => $points > 0,
            'points' => min(70, $points),
            'reasons' => $reasons,
        ];
    }

    /**
     * Scans <input> tags for fields beyond just "password" that also
     * indicate credential/PII harvesting — card numbers, CVV, bank
     * account/routing numbers, OTP/PIN codes, and national ID/SSN numbers.
     * A fake "claim your refund" or "verify OTP" page often has NO password
     * field at all, so gating every deeper check on password presence alone
     * (the previous behavior) let this entire class of phishing page through
     * with a clean result. Matches on name/id/placeholder attributes since
     * that's how these fields are actually labeled in real markup.
     */
    private function detectSensitiveInputFields(string $html): array
    {
        $hasPassword = (bool) preg_match('/<input[^>]+type\s*=\s*["\']password["\']/i', $html);

        $labeledPatterns = [
            'card number' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:card[-_ ]?number|cardnumber|cc[-_]?num)[^"\']*["\']/i',
            'CVV/CVC' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:cvv|cvc|security[-_ ]?code)[^"\']*["\']/i',
            'bank account/routing number' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:account[-_ ]?number|routing[-_ ]?number|iban|swift)[^"\']*["\']/i',
            'OTP/PIN' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:otp|one[-_ ]?time[-_ ]?password|verification[-_ ]?code|\bpin\b)[^"\']*["\']/i',
            'national ID/SSN' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:ssn|social[-_ ]?security|national[-_ ]?id|nric)[^"\']*["\']/i',
        ];

        preg_match_all('/<input\b[^>]*>/i', $html, $inputTags);
        $matched = [];

        foreach ($inputTags[0] as $tag) {
            foreach ($labeledPatterns as $label => $regex) {
                if (preg_match($regex, $tag) && ! in_array($label, $matched)) {
                    $matched[] = $label;
                }
            }
        }

        return [
            'has_password' => $hasPassword,
            'has_other_sensitive' => ! empty($matched),
            'matched' => $matched,
        ];
    }

    /**
     * Unlabeled regex list shared with detectSensitiveInputFields() so
     * checkFormActionSecurity() can flag a card/OTP/bank-harvesting form's
     * action destination exactly the same way it already does for password
     * forms, without duplicating the pattern definitions.
     */
    private function sensitiveFieldPatterns(): array
    {
        return [
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:card[-_ ]?number|cardnumber|cc[-_]?num)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:cvv|cvc|security[-_ ]?code)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:account[-_ ]?number|routing[-_ ]?number|iban|swift)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:otp|one[-_ ]?time[-_ ]?password|verification[-_ ]?code|\bpin\b)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:ssn|social[-_ ]?security|national[-_ ]?id|nric)[^"\']*["\']/i',
        ];
    }

    private function formContainsSensitiveField(string $formBody): bool
    {
        foreach ($this->sensitiveFieldPatterns() as $regex) {
            if (preg_match($regex, $formBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects a <meta http-equiv="refresh"> tag that redirects to a
     * DIFFERENT domain than the page itself — the meta-tag equivalent of
     * the cross-domain redirect already flagged in checkRedirectChain(),
     * which only follows real HTTP 3xx status codes and would miss this
     * entirely, since a page using meta-refresh returns a normal HTTP 200.
     */
    private function checkMetaRefresh(string $html, string $pageHost): array
    {
        if (! preg_match('/<meta[^>]+http-equiv\s*=\s*["\']refresh["\'][^>]*content\s*=\s*["\']([^"\']*)["\']/i', $html, $m)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        if (! preg_match('/url\s*=\s*(\S+)/i', $m[1], $urlMatch)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $targetUrl = trim($urlMatch[1], '\'" ');
        $targetHost = str_starts_with($targetUrl, 'http')
            ? parse_url($targetUrl, PHP_URL_HOST)
            : $pageHost;

        $normalize = fn (?string $h) => preg_replace('/^www\./', '', strtolower($h ?? ''));

        if ($this->isOfficialBrandDomain($pageHost)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        if ($targetHost && $normalize($targetHost) !== $normalize($pageHost)) {
            return [
                'flagged' => true,
                'points' => 15,
                'reasons' => ["Page uses a meta-refresh tag to automatically redirect visitors to a different domain ({$targetHost}) than the one they loaded"],
            ];
        }

        return ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    /**
     * The first well-known brand named as a whole word in a URL path, or null.
     * Words are split on anything that is not a letter or digit. A brand of
     * five letters or more also matches a word that starts with it
     * ("tiktokshop"); shorter brands ("dhl", "ups") must match exactly so
     * that "setups" or "groups" never count.
     */
    private function brandInPath(string $path): ?string
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower($path), -1, PREG_SPLIT_NO_EMPTY);

        foreach (array_keys(self::OFFICIAL_BRAND_DOMAINS) as $brand) {
            if (! preg_match('/^[a-z0-9]+$/', $brand)) {
                continue; // multi-word or dotted names such as "royal mail"
            }

            foreach ($words as $word) {
                if ($word === $brand || (strlen($brand) >= 5 && str_starts_with($word, $brand))) {
                    return $brand;
                }
            }
        }

        return null;
    }

    /**
     * True when the host is, or is a subdomain of, a domain on the official
     * brand list (the same list the page-content brand check uses).
     */
    private function isOfficialBrandDomain(string $host): bool
    {
        $host = strtolower(preg_replace('/^www\./', '', $host));

        foreach (array_merge(self::OFFICIAL_BRAND_DOMAINS, self::BRUNEI_BRANDS) as $domains) {
            foreach ($domains as $official) {
                if ($host === $official || str_ends_with($host, '.'.$official)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** True for gov.bn itself and anything under it. */
    /**
     * A message that pushes the reader to act (fine, threat, prize, urgency)
     * and carries a link to a site that is not a known official domain. Real
     * authorities send these through their own app or website, so the pairing
     * is worth points even when the link itself looks plain.
     */
    /**
     * Optional AI read of the message (see AiTextCheck). Skipped when the rules alone
     * already say phishing, since it could add nothing useful there.
     */
    private function checkAiText(string $text, int $rulePoints): ?array
    {
        $ai = app(AiTextCheck::class);

        if (! $ai->enabled() || $rulePoints >= 60) {
            return null;
        }

        return $ai->assess($text);
    }

    private function checkLinkInPressureMessage(array $contentResult, ?string $url): array
    {
        $none = ['flagged' => false, 'points' => 0, 'reasons' => []];

        if (! $url || ($contentResult['matched_categories'] ?? 0) < 1) {
            return $none;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        if ($host === '' || $this->isOfficialBrandDomain($host) || $this->isBruneiGovernmentHost($host)) {
            return $none;
        }

        return [
            'flagged' => true,
            'points' => 15,
            'reasons' => ["The message pressures the reader to act and links to \"{$host}\", which is not a known official domain"],
        ];
    }

    private function isBruneiGovernmentHost(?string $host): bool
    {
        $host = strtolower((string) $host);

        return $host === 'gov.bn' || str_ends_with($host, '.gov.bn');
    }

    /**
     * Recognises the hidden iframe Google Tag Manager uses as its <noscript>
     * fallback, in the three forms seen on real sites: a googletagmanager.com
     * link, the same link kept in data-src (lazy loading), and a first-party
     * path on the site's own server that carries a GTM container id.
     *
     * A link that merely contains "GTM-" on another site does not qualify, so
     * a phishing page cannot use the exemption by copying the id.
     */
    private function isTagManagerIframe(string $tag): bool
    {
        $read = function (string $attribute) use ($tag): ?string {
            // (?<![\w-]) stops "src" matching inside "data-src".
            if (! preg_match('/(?<![\w-])'.$attribute.'\s*=\s*["\']([^"\']*)["\']/i', $tag, $m)) {
                return null;
            }

            $value = html_entity_decode(trim($m[1]));

            // about:blank and inline images (the 1x1 GIF lazy-loaders use as a
            // placeholder) load nothing. Other data: URLs, such as data:text/html,
            // are real content and must not be skipped.
            if ($value === '' || strcasecmp($value, 'about:blank') === 0 || stripos($value, 'data:image/') === 0) {
                return null;
            }

            return $value;
        };

        // The browser loads src when there is one, so src decides. data-src is
        // only consulted when src is absent or blank (lazy-loading scripts fill
        // src in later); otherwise a page could pair an evil src with a
        // harmless-looking data-src to slip past the check.
        $value = $read('src') ?? $read('data-src');

        if ($value === null) {
            return false;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if ($host && preg_match('/(^|\.)googletagmanager\.com$/i', $host)) {
            return true;
        }

        return str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && preg_match('/[?&]id=GTM-[A-Z0-9]+/i', $value) === 1;
    }

    /**
     * Flags two Tier-1-detectable evasion patterns that need no headless
     * browser or JS execution: (1) a hidden iframe (zero-size or
     * display:none/visibility:hidden), commonly used to load malicious
     * content invisibly, and (2) eval() combined with atob()/unescape() —
     * decode-then-execute, a common way phishing kits hide their real
     * payload from simple text scanning. Neither requires a sensitive
     * field to be present — a hidden iframe or obfuscated script is
     * suspicious on any page.
     */
    private function checkObfuscationPatterns(string $html): array
    {
        $reasons = [];
        $points = 0;

        // Zero-size iframes are also how Google Tag Manager's standard
        // <noscript> snippet works, and that snippet is on a large share of
        // legitimate sites (found when PayPal, Grab and several Brunei sites
        // were flagged for it). Those are skipped; any other hidden iframe
        // still counts.
        if (preg_match_all('/<iframe\b[^>]*>/i', $html, $iframeTags)) {
            foreach ($iframeTags[0] as $iframeTag) {
                // (?<![\w-]) so "marginwidth=\"0\"" and "data-width=\"0\"" are not read as width="0".
                $isHidden = preg_match('/(?<![\w-])(?:width|height)\s*=\s*["\']0["\']|display\s*:\s*none|visibility\s*:\s*hidden/i', $iframeTag);

                if ($isHidden && ! $this->isTagManagerIframe($iframeTag)) {
                    $reasons[] = 'Page contains a hidden iframe (zero-size or display:none/visibility:hidden) — commonly used to load malicious content invisibly to the visitor';
                    $points += 20;
                    break;
                }
            }
        }

        if (preg_match('/eval\s*\(\s*(?:atob|unescape)\s*\(/i', $html)) {
            $reasons[] = 'Page contains obfuscated JavaScript (eval combined with atob/unescape decoding) — a common technique for hiding a script\'s real behavior from simple text scanning';
            $points += 15;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
        ];
    }

    /**
     * Whether a brand named somewhere in a page is being USED by the page, not
     * just mentioned. It counts when the brand is in the page's identity (title,
     * a heading, an image's alt/title text, or a site-name meta tag), or when
     * it sits within a few words of a login or credential word ("log in to your
     * PayPal account"). A school page that lists "Microsoft Paint, Microsoft
     * Excel" among its computer-lab software does neither, and was being
     * called brand impersonation because it also has a staff login box.
     */
    private function brandIsPageIdentityOrLoginPrompt(string $brand, string $title, string $html, string $bodyText): bool
    {
        $b = preg_quote($brand, '/');

        $identity = $title;

        if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $headings)) {
            $identity .= ' '.strip_tags(implode(' ', $headings[1]));
        }

        if (preg_match_all('/<img[^>]+(?:alt|title)\s*=\s*["\']([^"\']*)["\']/i', $html, $imageText)) {
            $identity .= ' '.implode(' ', $imageText[1]);
        }

        if (preg_match_all('/<meta[^>]+(?:og:site_name|application-name)[^>]*content\s*=\s*["\']([^"\']*)["\']/i', $html, $siteNames)) {
            $identity .= ' '.implode(' ', $siteNames[1]);
        }

        if (preg_match('/\b'.$b.'\b/i', $identity)) {
            return true;
        }

        $credential = '(?:sign\s*in|log\s*in|login|logon|password|passcode|verify|verification|confirm|secure|account|billing|payment|wallet|recover|unlock|suspended)';

        return preg_match('/\b'.$credential.'\b(?:\W+\w+){0,6}?\W+\b'.$b.'\b/i', $bodyText) === 1
            || preg_match('/\b'.$b.'\b(?:\W+\w+){0,6}?\W+\b'.$credential.'\b/i', $bodyText) === 1;
    }

    /**
     * Exact-only brand mention detection for page content — deliberately
     * simpler than detectBrandInText()'s fuzzy fallback. See the caller in
     * checkPageContent() for why: fuzzy matching against a full page body
     * has far more false-positive surface than short OCR/email text.
     */
    private function detectBrandExactMatch(string $text, ?callable $confirm = null): array
    {
        $knownBrands = [
            'dhl', 'fedex', 'ups', 'paypal', 'google', 'facebook', 'apple',
            'microsoft', 'outlook', 'amazon', 'netflix', 'maybank', 'bibd',
            // Crypto/wallets — added after testing revealed a real Trezor
            // phishing site slipped through with the original list.
            'trezor', 'metamask', 'binance', 'coinbase', 'kraken',
            'crypto.com', 'blockchain.com', 'trust wallet',
            // Regional banking (Brunei/SEA-relevant)
            'baiduri', 'hsbc', 'citibank', 'standard chartered',
            // E-commerce (major SEA phishing targets)
            'shopee', 'lazada', 'alibaba', 'ebay',
            // Telco (Brunei-relevant)
            'progresif',
            // Tech/social (common credential-phishing targets)
            'instagram', 'whatsapp', 'linkedin', 'tiktok', 'spotify',
            'adobe', 'dropbox', 'steam', 'zoom', 'twitter',
            // Shipping (beyond the original 3)
            'usps', 'royal mail', 'singpost',
        ];

        $knownBrands = array_values(array_unique(array_merge($knownBrands, array_keys(self::BRUNEI_BRANDS))));

        $lowerText = strtolower($text);

        foreach ($knownBrands as $brand) {
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/', $lowerText) && ($confirm === null || $confirm($brand))) {
                return ['brand' => $brand, 'surface' => $brand];
            }
        }

        return ['brand' => null, 'surface' => null];
    }

    /**
     * Checks whether a brand referenced in a page's visible content actually
     * belongs to the domain serving that page — the page-content equivalent
     * of checkBrandSenderMismatch() for emails. Reuses the same official
     * domain list so a brand is judged by the same standard everywhere.
     */
    private function checkPageBrandMismatch(string $brand, ?string $surfaceText, string $host): array
    {
        $host = strtolower(preg_replace('/^www\./', '', $host));

        $brandDomains = array_merge(self::OFFICIAL_BRAND_DOMAINS, self::BRUNEI_BRANDS);

        $allowed = $brandDomains[$brand] ?? [$brand.'.com'];
        foreach ($allowed as $officialDomain) {
            if ($host === $officialDomain || str_ends_with($host, '.'.$officialDomain)) {
                return ['flagged' => false, 'points' => 0, 'reasons' => []];
            }
        }

        $displayBrand = $surfaceText && $surfaceText !== $brand
            ? strtoupper($surfaceText).'" (a lookalike of "'.ucfirst($brand).'")'
            : ucfirst($brand);

        return [
            'flagged' => true,
            'points' => 30,
            'reasons' => ['Page content references "'.$displayBrand."\" branding, but the domain (\"{$host}\") does not belong to {$brand} — likely brand impersonation"],
        ];
    }

    /**
     * Checks a login form's own <form action="..."> destination — two
     * well-documented static-HTML phishing indicators from published
     * anti-phishing research: (1) the form submits credentials to a
     * DIFFERENT domain than the page itself is hosted on, and (2) the form
     * submits directly via "mailto:" rather than a server endpoint, a
     * common low-effort phishing-kit pattern that needs no backend at all.
     * Both are considered stronger, more specific signals than a bare
     * password field alone, since a cloned legitimate page's HTML often
     * carries the real site's structure but must redirect stolen
     * credentials somewhere the attacker actually controls.
     *
     * Deliberately scoped PER-FORM: only forms that themselves contain a
     * password field are checked. This avoids false positives from
     * unrelated forms elsewhere on the same page (e.g. a legitimate
     * newsletter signup submitting to Mailchimp) being flagged just
     * because a login form exists somewhere else on the page.
     */
    private function checkFormActionSecurity(string $html, string $pageHost): array
    {
        $reasons = [];
        $points = 0;

        preg_match_all('/<form\b[^>]*\baction\s*=\s*["\']([^"\']*)["\'][^>]*>(.*?)<\/form>/is', $html, $matches, PREG_SET_ORDER);

        $flaggedExternal = false;
        $flaggedEmail = false;

        foreach ($matches as $match) {
            $action = trim($match[1]);
            $formBody = $match[2];

            $formHasPassword = (bool) preg_match('/<input[^>]+type\s*=\s*["\']password["\']/i', $formBody);
            $formHasSensitive = $formHasPassword || $this->formContainsSensitiveField($formBody);
            if (! $formHasSensitive || $action === '') {
                continue;
            }

            if (stripos($action, 'mailto:') === 0) {
                $flaggedEmail = true;

                continue;
            }

            $actionHost = parse_url($action, PHP_URL_HOST);
            if ($actionHost === null) {
                // A relative path ("/login", "submit.php") resolves to the
                // same host the page is already on — not suspicious.
                continue;
            }

            $actionHost = strtolower(preg_replace('/^www\./', '', $actionHost));
            $normalizedPageHost = strtolower(preg_replace('/^www\./', '', $pageHost));

            $sameOrSubdomain = $actionHost === $normalizedPageHost
                || str_ends_with($normalizedPageHost, '.'.$actionHost)
                || str_ends_with($actionHost, '.'.$normalizedPageHost);

            if (! $sameOrSubdomain) {
                $flaggedExternal = true;
            }
        }

        if ($flaggedEmail) {
            $reasons[] = 'Login form submits directly to an email address (mailto:) instead of a server endpoint — a common low-effort phishing-kit pattern requiring no backend';
            $points += 20;
        }

        if ($flaggedExternal) {
            $reasons[] = 'Login form submits entered credentials to a different domain than the page itself — a strong indicator of credential harvesting';
            $points += 25;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
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
        if (! str_contains($email, '@')) {
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

        $brands = ['paypal', 'google', 'facebook', 'apple', 'microsoft', 'amazon', 'bank', 'dhl', 'fedex', 'ups', 'maybank', 'bibd', 'coinbase', 'binance', 'metamask', 'bradesco', 'livelo', 'correios', 'whatsapp'];
        $normalizedDomain = $this->normalizeForBrandMatch($domain);
        // A sender on a verified official domain (bibd.com.bn, maybank2u.com.my,
        // blog.google and so on) is not mimicking anything.
        $isOfficialSender = $this->isOfficialBrandDomain($domain);
        foreach ($brands as $brand) {
            if ($isOfficialSender) {
                break;
            }
            $matchesBrand = str_contains($normalizedDomain, $brand);
            $isOfficialDomain = str_ends_with($domain, $brand.'.com') || $domain === $brand.'.com';

            if ($matchesBrand && ! $isOfficialDomain) {
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

            if (! $phoneUtil->isValidNumber($parsed)) {
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
                $reasons[] = "Valid {$typeLabel} number".($region ? " registered in {$region}" : '').', no issues detected';
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
        $text = mb_strtolower($text);
        $reasons = [];
        $points = 0;
        $matchedCategories = 0;

        $patterns = [
            'urgency' => [
                'regex' => '/\b(within (the )?next 24 hours|less than 24 hours|within (24|48|72) hours|expires? in \d+ (hours|days)|act now|act immediately|urgent|urgently|final notice|immediately|offer ends|ends today|tindakan segera|segera (sahkan|kemas kini|bertindak|hubungi)|dalam masa 24 jam|notis akhir|amaran terakhir|at[eé] amanh[aã]|pr[oó]ximos de expirar|[uú]ltimo aviso|intento final|aviso final|dringend|sofort)\b/u',
                'points' => 15,
                'label' => 'Urgency language detected (e.g. "urgent", "24 hours", "act now")',
            ],
            'account_threat' => [
                'regex' => '/(account will be suspended|(account|wallet|access|password|mailbox|membership) (is|has been|was|will be|is now)( temporarily| permanently)? (locked|suspended|deactivated|blocked|restricted|limited|expired)|password (will )?expir(es|y)|temporary suspension|avoid deactivation|will be blocked|have been blocked|messages blocked|unusual sign-?in|unusual (login|activity)|akaun (anda )?(akan |telah |sedang )?(di)?(sekat|bekukan|gantung|kunci|tutup)|valor bloqueado|conta (foi|ser[aá]|est[aá]) (bloquead|suspens|cancelad)|cuenta (ha sido|ser[aá]) (bloquead|suspendid)|vor[uü]bergehend|einschr[aä]nkung|activit[eé]s de connexion inhabituelles)/u',
                'points' => 15,
                'label' => 'Account threat language detected (suspension/deactivation)',
            ],
            'credential_request' => [
                'regex' => '/(verify your account|verify my|verify your (identity|wallet|email)|confirm your (password|information|identity|details)|enter your login|verification code|verify now|update your (payment|billing|account)|reset access|secret recovery phrase|seed phrase|recovery phrase|connect (your )?wallet|wallet verification|sahkan (akaun|maklumat|identiti|kata laluan)|masukkan (kata laluan|nombor pin|no\\.? pin|otp)|kod pengesahan|kod otp|confirme (seus|suas) dados|atualize (seus|suas|sua) (dados|conta|cadastro)|aktualisieren sie|kontoinformationen|mettez [aà] jour)/u',
                'points' => 20,
                'label' => 'Credential/verification request detected',
            ],
            'financial_request' => [
                'regex' => '/(transfer (of )?funds|payment required|invoice|bank account|refund|pindahan wang|pindah wang|bayaran (diperlukan|segera)|akaun bank|bayaran balik)/',
                'points' => 15,
                'label' => 'Financial request or invoice language detected',
            ],
            'prize_scam' => [
                'regex' => '/(you have won|you\'ve won|you could win|you have been chosen|you\'ve been chosen|chosen to (receive|participate)|congratulations.{0,40}(won|selected|chosen)|claim your (prize|reward|gift|refund|free|share|tokens?)|free (gift|rewards?)|weekly lottery|survey zone|redeem (your|for) |tahniah.{0,40}(menang|dipilih|terpilih)|anda (telah )?memenangi|tuntut hadiah|pontos acumulados|resgate (agora|pendente)|resgatar|b[oô]nus de .{0,20}pontos)/u',
                'points' => 20,
                'label' => 'Prize/reward language detected (e.g. "you have won", "claim your reward", "tahniah anda menang")',
            ],
            'crypto_scam' => [
                'regex' => '/(airdrop|token allocation|free mint|erc-?20|ethereum 2\.0|claim your (xrp|usdt|eth|btc|nft)|usdt|nft cashback|bitcoin (payment|accounts?)|crypto(currency)? (giveaway|reward))/',
                'points' => 25,
                'label' => 'Cryptocurrency giveaway or wallet-claim language detected (a common scam lure)',
            ],
            'advance_fee' => [
                'regex' => '/(dear friend\b(?!s)|god bless you|next of kin|inheritance|you have a donation|donation of \$|contacting you for the second time|reply urgently|powerball|meu caro amigo)/',
                'points' => 25,
                'label' => 'Advance-fee / "dear friend" scam language detected',
            ],
            'fine_lure' => [
                'regex' => '/(traffic (violation|fine|ticket|challan|record)|red[- ]light|e-?challan|challan|speeding (fine|ticket)|parking (fine|ticket)|unpaid (toll|fine|ticket)|toll (fee|charge)|outstanding (fine|penalty)|saman (trafik|jalan raya|tertunggak|anda)|\bkompaun\b|compound (fine|notice))/',
                'points' => 20,
                'label' => 'Traffic-fine / penalty language detected (a common SMS scam: "violation", "e-challan", "unpaid toll", "saman")',
            ],
            'authority_threat' => [
                'regex' => '/(notice of involvement|notis (penglibatan|siasatan)|royal brunei police|polis diraja brunei|interpol|waran (tangkap|geledah)|arrest warrant|section 420|seksyen 420|keep (this|the) matter confidential|rahsiakan (perkara|hal) ini)/',
                'points' => 20,
                'label' => 'Authority-impersonation language detected (police/Interpol/investigation notice, or demands for secrecy)',
            ],
            'customs_fee' => [
                'regex' => '/((customs|kastam).{0,30}(fee|duty|charge|clearance|caj|duti|yuran)|(caj|yuran|bayaran) (kastam|penghantaran|pelepasan)|redelivery|delivery (attempt )?failed|will be sent back|taxa de recolhimento|alf[aâ]ndega)/u',
                'points' => 15,
                'label' => 'Customs/delivery-fee language detected (a common parcel scam)',
            ],
            'call_to_action' => [
                'regex' => '/(click here|click the button|click below|follow the link|klik (di ?sini|pautan|link|butang)|ikut pautan)/',
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

    /**
     * Reduces a word to a form where look-alike characters compare equal:
     * digits become the letters they imitate, "i" and "l" are treated as the
     * same, "rn" reads as "m" and "vv" as "w".
     */
    private function foldLookalikes(string $word): string
    {
        $word = strtolower($this->normalizeForBrandMatch($word));
        $word = str_replace(['rn', 'vv'], ['m', 'w'], $word);

        return str_replace(['i', '|'], 'l', $word);
    }

    private function detectBrandInText(string $text): array
    {
        $knownBrands = ['dhl', 'fedex', 'ups', 'paypal', 'google', 'facebook', 'apple',
            'microsoft', 'outlook', 'amazon', 'netflix', 'maybank', 'bibd',
            'coinbase', 'binance', 'metamask', 'bradesco', 'livelo', 'correios', 'whatsapp'];
        $knownBrands = array_merge($knownBrands, array_keys(self::BRUNEI_BRANDS));

        $lowerText = strtolower($text);

        // Exact match first — cheapest and most reliable when the brand name
        // is spelled correctly in the message.
        foreach ($knownBrands as $brand) {
            // "ups" is an everyday word ("follow-ups", "ups and downs"); only the
            // courier's written form, capitalised UPS, counts.
            if ($brand === 'ups') {
                if (preg_match('/\bUPS\b/', $text)) {
                    return ['brand' => $brand, 'surface' => $brand];
                }

                continue;
            }
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/', $lowerText)) {
                return ['brand' => $brand, 'surface' => $brand];
            }
        }

        // Lookalike fallback — catches a VISIBLE brand name that is itself a
        // typosquat (e.g. "DHI" for "DHL", "Paypa1", "Arnazon"). It only
        // accepts look-alike characters (1/l/i, 0/o, 3/e, 5/s, rn/m, vv/w),
        // never general similarity: an earlier similarity rule matched
        // ordinary words such as "up" (UPS), "apply" (Apple), "people",
        // "again", "image" and "build", and flagged normal emails.
        $foldBrand = fn (string $w) => $this->foldLookalikes($w);
        preg_match_all('/\b[A-Za-z0-9]{2,12}\b/', $text, $m);
        $words = array_unique(array_map('strtolower', $m[0] ?? []));

        foreach ($words as $word) {
            foreach ($knownBrands as $brand) {
                if ($word === $brand || str_contains($brand, ' ')) {
                    continue;
                }
                if ($foldBrand($word) === $foldBrand($brand)) {
                    return ['brand' => $brand, 'surface' => $word];
                }
            }
        }

        $bestBrand = null;
        $bestSurface = null;

        return ['brand' => $bestBrand, 'surface' => $bestSurface];
    }

    private function checkBrandSenderMismatch(?string $brand, ?string $surfaceText, ?string $senderEmail): array
    {
        if (! $brand || ! $senderEmail || ! str_contains($senderEmail, '@')) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        [$localPart, $domain] = explode('@', $senderEmail, 2);
        $domain = strtolower($domain);
        $localPart = strtolower($localPart);
        $displayBrand = $surfaceText && $surfaceText !== $brand
            ? strtoupper($surfaceText).'" (a lookalike of "'.ucfirst($brand)
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
            'coinbase' => ['coinbase.com'],
            'binance' => ['binance.com'],
            'metamask' => ['metamask.io'],
            'bradesco' => ['bradesco.com.br', 'banco.bradesco'],
            'livelo' => ['livelo.com.br'],
            'correios' => ['correios.com.br'],
            'whatsapp' => ['whatsapp.com', 'whatsapp.net'],
        ];
        $brandDomains = array_merge($brandDomains, self::BRUNEI_BRANDS);

        $allowed = $brandDomains[$brand] ?? [$brand.'.com'];
        $isOfficial = false;
        foreach ($allowed as $officialDomain) {
            if ($domain === $officialDomain || str_ends_with($domain, '.'.$officialDomain)) {
                $isOfficial = true;
                break;
            }
        }

        if ($isOfficial) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $reasons = ['"'.$displayBrand."\" branding was detected in the message, but the sender domain (\"{$domain}\") does not belong to {$brand}"];
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
            $reasons[] = "\"{$comparedAgainst}\" is ".round($bestPercent)."% similar to \"{$brand}\", suggesting a lookalike/typosquat attempt";
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

        if (! $apiKey) {
            return ['success' => false, 'text' => '', 'error' => 'OCR API key not configured'];
        }

        if (! file_exists($imagePath)) {
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

        return array_map(fn ($m) => rtrim($m, '.,;:)'), $matches[0] ?? []);
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
     * Returns ALL phone-number-like sequences found in the text, validated
     * through the SAME libphonenumber parsing checkPhoneNumber() uses, not
     * just a loose regex match.
     *
     * Requires EITHER a leading "+" (international format) OR at least one
     * separator character (space/dash) between digit groups — a bare,
     * unbroken digit run (e.g. "9523732") is far more likely to be a
     * report ID, order number, or database reference than a phone number.
     * Confirmed via testing: a phishing-database listing's own reference
     * ID number was being misread as a phone number candidate under the
     * previous looser pattern, purely because it happened to be 7 digits
     * long. Genuine phone numbers, even typed carelessly, are almost
     * always separated somehow (spaces, dashes, or a leading "+").
     */
    private function extractAllPhonesFromText(string $text): array
    {
        preg_match_all('/\+\d[\d\-\s\(\)]{5,17}\d|\d{2,4}[\s\-]\d{2,4}[\s\-]\d{2,6}(?:[\s\-]\d{2,4})?/', $text, $matches);

        $candidates = array_map(function ($m) {
            return trim(preg_replace('/\s{2,}/', ' ', $m));
        }, $matches[0] ?? []);

        return array_values(array_unique(array_filter($candidates, fn ($c) => strlen(preg_replace('/\D/', '', $c)) >= 7)));
    }

    private function decodeQrCode(string $imagePath): ?string
    {
        if (! class_exists(QrReader::class)) {
            return null;
        }

        try {
            $qrReader = new QrReader($imagePath);
            $decoded = $qrReader->text();

            return (is_string($decoded) && $decoded !== '') ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
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
            // Malay
            'tindakan segera', 'sahkan akaun', 'akaun anda', 'kod pengesahan', 'kod otp',
            'tahniah', 'memenangi', 'klik di sini', 'klik pautan', 'bayaran balik',
            'caj kastam', 'notis penglibatan',
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
     * Join reasons into one readable message, making sure each reads as its
     * own sentence (several reasons are written without a final full stop).
     */
    private function joinReasons(array $reasons): string
    {
        return implode(' ', array_map(
            fn ($reason) => preg_match('/[.!?)]$/', $reason = rtrim((string) $reason)) ? $reason : $reason.'.',
            $reasons
        ));
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
                'message' => ! empty($result['reasons'])
                    ? $this->joinReasons($result['reasons'])
                    : 'This check could not be completed.',
                'points' => $result['points'],
            ];
        }

        return [
            'name' => $name,
            'status' => $result['flagged'] ? $flaggedStatus : 'SAFE',
            'message' => ! empty($result['reasons'])
                ? $this->joinReasons($result['reasons'])
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
        $contentResult = $this->checkPageContent($url);
        $hostingResult = $this->checkFreeHostingPlatform($url);
        $availabilityResult = $this->checkSiteAvailability($url);

        $totalPoints = min(
            100,
            $syntaxResult['points'] + $ageResult['points'] + $sslResult['points']
                + $blacklistResult['points'] + $vtResult['points'] + $ipResult['points'] + $redirectResult['points']
                + $contentResult['points'] + $hostingResult['points'] + $domainHistoryResult['points']
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
            $this->buildCheck('Page Content Analysis', $contentResult, $contentResult['points'] >= 30 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('Hosting Platform', $hostingResult, 'SUSPICIOUS'),
            [
                'name' => 'Site Availability',
                'status' => $availabilityResult['status_label'],
                'message' => $this->joinReasons($availabilityResult['reasons']),
                'points' => 0,
            ],
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

        $combinedText = trim(($subject ?? '').' '.($body ?? ''));
        $hasContentText = $combinedText !== '';

        $contentResult = $hasContentText
            ? $this->checkContentPatterns($combinedText)
            : ['flagged' => false, 'points' => 0, 'reasons' => [], 'matched_categories' => 0];

        $brandDetection = $hasContentText
            ? $this->detectBrandInText($combinedText)
            : ['brand' => null, 'surface' => null];

        $brandResult = $this->checkBrandSenderMismatch($brandDetection['brand'], $brandDetection['surface'], $email);

        $rulePoints = $emailResult['points'] + $ageResult['points'] + $contentResult['points'] + $brandResult['points']
            + $domainHistoryResult['points'];
        $aiResult = $hasContentText ? $this->checkAiText($combinedText, $rulePoints) : null;

        $totalPoints = min(100, $rulePoints + ($aiResult['points'] ?? 0));
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
            if ($aiResult !== null) {
                $checks[] = $this->buildCheck('AI Message Review', $aiResult, $aiResult['verdict'] === 'scam' ? 'HIGH RISK' : 'SUSPICIOUS');
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
        $reputationResult = $this->checkPhoneReputation($phone);

        $totalPoints = min(100, $phoneResult['points'] + $historyResult['points'] + $reputationResult['points']);
        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('Phone Number Analysis', $phoneResult, 'SUSPICIOUS'),
            $this->buildCheck('Previous Reports', $historyResult, $historyResult['points'] >= 45 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('Phone Reputation (AbstractAPI)', $reputationResult, $reputationResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
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

        $candidateReports = $query->get(['id', 'user_id', 'url', 'sender_email', 'created_at']);

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

            if (! $reportDomain && $report->sender_email && str_contains($report->sender_email, '@')) {
                $reportDomain = strtolower(substr(strrchr($report->sender_email, '@'), 1));
            }

            if (! $reportDomain) {
                return false;
            }

            return $reportDomain === $domain || str_ends_with($reportDomain, '.'.$domain);
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

        $firstSeen = $matchingReports->min('created_at');
        $firstSeenLabel = $firstSeen ? 'First seen on PhishCore: '.$firstSeen->format('j M Y') : null;

        if ($reporterCount === 1) {
            $reasons = ['This domain has appeared in 1 other report on PhishCore.'];
            if ($firstSeenLabel) {
                $reasons[] = $firstSeenLabel;
            }
            $reasons[] = '(Informational only — this does not change the risk score.)';

            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => $reasons,
            ];
        }

        if ($reporterCount <= 4) {
            $reasons = ["This domain has appeared in {$reporterCount} other reports on PhishCore — reused across multiple submissions."];
            if ($firstSeenLabel) {
                $reasons[] = $firstSeenLabel;
            }
            $reasons[] = '(Informational only — this does not change the risk score.)';

            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => $reasons,
            ];
        }

        $reasons = ["This domain has appeared in {$reporterCount} other reports on PhishCore — repeatedly reused phishing infrastructure."];
        if ($firstSeenLabel) {
            $reasons[] = $firstSeenLabel;
        }
        $reasons[] = '(Informational only — this does not change the risk score.)';

        return [
            'flagged' => false,
            'points' => 0,
            'reasons' => $reasons,
        ];
    }

    /**
     * Checks how many times this exact phone number has already been
     * reported on PhishCore by other scans — a crowd-sourced signal similar
     * in spirit to caller-ID/spam-reporting apps, built from the platform's
     * own report history rather than an external database. This is
     * necessarily a much smaller dataset than a service like Truecaller,
     * which relies on hundreds of millions of users — that scale gap can't
     * be closed by a single platform's own report volume, and is a known,
     * honest limitation of phone-based detection generally, not something
     * this project can fully solve.
     *
     * Reports are weighted by RECENCY, not counted flat. Phone numbers are
     * routinely reassigned by telcos after a period of subscriber
     * inactivity — a number reported as a scam a year ago may since belong
     * to an unrelated, innocent person. Treating a stale report the same
     * as a fresh one (the previous behavior) is a real source of the
     * inaccurate results common to phone-lookup tools generally. Recent
     * reports (<=90 days) count at full weight; moderately old ones
     * (91-365 days) at half weight; anything older is tapered further
     * rather than dropped outright, since an old report is still weak
     * supporting evidence, just not proof of current activity.
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

        if ($matchingReports->isEmpty()) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => [
                    'No prior reports found for this number on PhishCore — note that phone-scam detection relies on crowdsourced reports, so this does not guarantee the number is safe, only that it has not been reported here before.',
                ],
            ];
        }

        // Deduplicate by reporter first (same rationale as before — one
        // person testing repeatedly shouldn't look like multiple
        // independent reporters), THEN apply a recency weight to each
        // distinct reporter's most recent report of this number.
        $byReporter = $matchingReports->groupBy(fn ($r) => $r->user_id ?? 'guest_'.$r->id);

        $now = now();
        $weightedScore = 0.0;

        foreach ($byReporter as $reportsForReporter) {
            $mostRecent = $reportsForReporter->sortByDesc('created_at')->first();
            $ageDays = $mostRecent->created_at->diffInDays($now);

            $weight = match (true) {
                $ageDays <= 90 => 1.0,
                $ageDays <= 365 => 0.5,
                default => 0.2,
            };

            $weightedScore += $weight;
        }

        $reporterCount = $byReporter->count();
        $mostRecentReport = $matchingReports->max('created_at');
        $recencyNote = 'Most recent report: '.$mostRecentReport->diffForHumans().'.';

        if ($weightedScore < 1) {
            return [
                'flagged' => true,
                'points' => 8,
                'reasons' => [
                    "This number was reported {$reporterCount} time(s) on PhishCore, but the most recent report is over a year old — the number may since have been reassigned to a different subscriber by the telco.",
                    $recencyNote,
                ],
            ];
        }

        if ($weightedScore < 2) {
            return [
                'flagged' => true,
                'points' => 15,
                'reasons' => [
                    "This number has been reported by {$reporterCount} user(s) on PhishCore.",
                    $recencyNote,
                ],
            ];
        }

        if ($weightedScore < 4) {
            return [
                'flagged' => true,
                'points' => 30,
                'reasons' => [
                    "This number has been reported by {$reporterCount} different users on PhishCore, including recent reports.",
                    $recencyNote,
                ],
            ];
        }

        return [
            'flagged' => true,
            'points' => 45,
            'reasons' => [
                "This number has been reported by {$reporterCount} different users on PhishCore — repeatedly and recently flagged.",
                $recencyNote,
            ],
        ];
    }

    /**
     * Checks phone number reputation via AbstractAPI's Phone Intelligence
     * API — an independent, carrier-backed signal to sit alongside
     * PhishCore's own crowdsourced report history. checkPhoneNumber() only
     * validates FORMAT and numbering-plan assignment, which is static,
     * offline data derived purely from the number's shape. This instead
     * queries a live third-party risk assessment.
     *
     * Field paths confirmed against a real response (not assumed from
     * marketing docs, which showed a different/inconsistent shape):
     * risk data lives under phone_risk.{risk_level,is_disposable,
     * is_abuse_detected}, and validity/line status under
     * phone_validation.{is_valid,line_status} — NOT top-level "valid" or
     * "risk_score" as an earlier version of this check assumed, which
     * caused every successful response to be misread as an error.
     *
     * Skip-safe like the other optional external checks (VirusTotal,
     * Google Safe Browsing) — if no API key is configured, this is
     * silently skipped rather than failing the scan.
     */
    public function checkPhoneReputation(string $phone): array
    {
        $apiKey = config('services.abstractapi_phone.key');

        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'unavailable' => true,
        ];

        if (! $apiKey) {
            $empty['reasons'][] = 'Phone reputation check skipped: no API key configured';

            return $empty;
        }

        $normalized = preg_replace('/[^\d+]/', '', $phone);

        try {
            $response = Http::timeout(10)->get('https://phoneintelligence.abstractapi.com/v1/', [
                'api_key' => $apiKey,
                'phone' => $normalized,
            ]);

            $data = $response->json();

            if (! $response->successful() || ! is_array($data) || ! isset($data['phone_risk'])) {
                $empty['reasons'][] = $data['error']['message'] ?? 'Phone reputation lookup unavailable';

                return $empty;
            }

            $riskLevel = $data['phone_risk']['risk_level'] ?? null;
            $isDisposable = $data['phone_risk']['is_disposable'] ?? false;
            $isAbuseDetected = $data['phone_risk']['is_abuse_detected'] ?? false;
            $isValid = $data['phone_validation']['is_valid'] ?? true;
            $lineStatus = $data['phone_validation']['line_status'] ?? null;

            $reasons = [];
            $points = 0;

            if ($riskLevel !== null) {
                if (strtolower($riskLevel) === 'high') {
                    $reasons[] = 'AbstractAPI flags this number as HIGH risk';
                    $points += 35;
                } elseif (strtolower($riskLevel) === 'medium') {
                    $reasons[] = 'AbstractAPI flags this number as MEDIUM risk';
                    $points += 18;
                }
            }

            if ($isDisposable) {
                $reasons[] = 'AbstractAPI flags this as a disposable/temporary number';
                $points += 25;
            }

            if ($isAbuseDetected) {
                $reasons[] = 'AbstractAPI has recorded abuse associated with this number';
                $points += 35;
            }

            // Informational only — an invalid/inactive number isn't
            // double-penalized here since checkPhoneNumber() already
            // scores format/numbering-plan validity independently.
            if (! $isValid) {
                $reasons[] = 'AbstractAPI reports this number as not currently valid';
            } elseif ($lineStatus && strtolower($lineStatus) !== 'active') {
                $reasons[] = "Line status: {$lineStatus}";
            }

            if (empty($reasons)) {
                $reasons[] = 'AbstractAPI: no significant risk flags for this number';
            }

            return [
                'flagged' => $points > 0,
                'points' => min(60, $points),
                'reasons' => $reasons,
            ];
        } catch (\Throwable $e) {
            $empty['reasons'][] = 'Could not reach AbstractAPI';

            return $empty;
        }
    }

    private function analyzeScreenshot(string $imagePath, ?int $reportId = null): array
    {
        $ocr = $this->checkScreenshotOcr($imagePath);

        if (! $ocr['success']) {
            return [
                'risk_score' => 0,
                'verdict' => 'review',
                'domain_age_days' => null,
                'url_syntax_score' => null,
                'checks' => [[
                    'name' => 'Screenshot Text Extraction',
                    'status' => 'REVIEW',
                    'message' => 'Could not read text from this image ('.($ocr['error'] ?? 'unknown error').'). Manual review recommended.',
                    'points' => 0,
                ]],
            ];
        }

        $text = $ocr['text'];

        $qrUrl = $this->decodeQrCode($imagePath);

        $candidateUrlsRaw = $this->extractAllUrlsFromText($text);
        if ($qrUrl) {
            $candidateUrlsRaw[] = $qrUrl;
        }
        $candidateUrls = array_values(array_unique(array_filter($candidateUrlsRaw, fn ($u) => filter_var($u, FILTER_VALIDATE_URL))));
        $extractedUrl = null;
        if (! empty($candidateUrls)) {
            usort($candidateUrls, fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']);
            $extractedUrl = $candidateUrls[0];
        }
        $urlCameFromQr = $qrUrl && $extractedUrl === $qrUrl;

        $candidateEmails = $this->extractAllEmailsFromText($text);
        $extractedEmail = null;
        if (! empty($candidateEmails)) {
            usort($candidateEmails, fn ($a, $b) => $this->checkEmailDomain($b)['points'] <=> $this->checkEmailDomain($a)['points']);
            $extractedEmail = $candidateEmails[0];
        }

        $candidatePhones = $this->extractAllPhonesFromText($text);
        $extractedPhone = null;
        $phoneCheckResult = null;
        foreach ($candidatePhones as $candidate) {
            $result = $this->checkPhoneNumber($candidate);
            if ($result['unparseable'] ?? false) {
                continue;
            }
            if ($phoneCheckResult === null || $result['points'] > $phoneCheckResult['points']) {
                $extractedPhone = $candidate;
                $phoneCheckResult = $result;
            }
        }

        $brandDetection = $this->detectBrandInText($text);
        $detectedBrand = $brandDetection['brand'];
        $brandSurfaceText = $brandDetection['surface'];
        $contentResult = $this->checkContentPatterns($text);
        $attachmentResult = $this->detectAttachment($text);
        $aiResult = $this->checkAiText($text, $contentResult['points']);
        $hasAnyEvidence = $extractedUrl || $extractedEmail || $extractedPhone || $detectedBrand || $contentResult['flagged'] || $attachmentResult['flagged']
            || ($aiResult['flagged'] ?? false);

        $checks = [];
        $totalPoints = 0;
        $signalCategories = 0;
        $ageResult = ['domain_age_days' => null];
        $extractedUrlCti = null;

        $extractionParts = [];
        if ($extractedUrl) {
            $extractionParts[] = ($urlCameFromQr ? 'URL (from QR code): ' : 'URL: ').$extractedUrl;
        }
        if ($extractedEmail) {
            $extractionParts[] = "Sender: {$extractedEmail}";
        }
        if ($extractedPhone) {
            $extractionParts[] = "Phone number: {$extractedPhone}";
        }
        if ($detectedBrand) {
            $extractionParts[] = 'Brand referenced: '.ucfirst($detectedBrand);
        }

        $checks[] = [
            'name' => 'Screenshot Text Extraction',
            'status' => $hasAnyEvidence ? 'SAFE' : 'REVIEW',
            'message' => $hasAnyEvidence
                ? ('Extracted from image: '.implode(' | ', $extractionParts ?: ['phishing-style language']))
                : 'No URL, email address, phone number, brand reference, QR code, or phishing-style language was found in the image. Extracted text: "'.Str::limit($text, 200).'"',
            'points' => 0,
        ];

        if ($extractedEmail) {
            $emailResult = $this->checkEmailDomain($extractedEmail);
            $domain = $emailResult['domain'];
            $ageResult = $domain
                ? $this->checkDomainAge($domain)
                : ['flagged' => false, 'points' => 0, 'domain_age_days' => null, 'reasons' => []];

            $totalPoints += $emailResult['points'] + $ageResult['points'];
            if ($emailResult['points'] > 0 || $ageResult['points'] > 0) {
                $signalCategories++;
            }

            $checks[] = $this->buildCheck('Sender Domain Analysis', $emailResult, 'SUSPICIOUS');
            if ($domain) {
                $checks[] = $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
            }
        }

        if ($extractedPhone && $phoneCheckResult) {
            $totalPoints += (int) round($phoneCheckResult['points'] * 0.7);
            if ($phoneCheckResult['points'] > 0) {
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('Phone Number Analysis', $phoneCheckResult, 'SUSPICIOUS');

            $phoneHistoryResult = $this->checkPreviousReports($extractedPhone, $reportId);
            if ($phoneHistoryResult['flagged'] ?? false) {
                $totalPoints += (int) round($phoneHistoryResult['points'] * 0.7);
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('Previous Reports (Phone)', $phoneHistoryResult, $phoneHistoryResult['points'] >= 45 ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        if ($detectedBrand) {
            $brandCheckLabel = 'Brand / Domain Correlation';
            if ($extractedEmail) {
                $brandDomainResult = $this->checkBrandSenderMismatch($detectedBrand, $brandSurfaceText, $extractedEmail);
                $brandCheckLabel = 'Brand / Sender Correlation';
            } elseif ($extractedUrl && ($urlHost = parse_url($extractedUrl, PHP_URL_HOST))) {
                $brandDomainResult = $this->checkPageBrandMismatch($detectedBrand, $brandSurfaceText, $urlHost);
            } else {
                $brandDomainResult = [
                    'flagged' => false,
                    'points' => 0,
                    'reasons' => ['Brand "'.ucfirst($detectedBrand).'" referenced in image text, but no URL or sender email was extracted to verify it against'],
                ];
            }

            if ($brandDomainResult['flagged']) {
                $totalPoints += $brandDomainResult['points'];
                $signalCategories++;
            }
            $checks[] = $this->buildCheck($brandCheckLabel, $brandDomainResult, 'HIGH RISK');
        }

        if ($contentResult['flagged']) {
            $totalPoints += $contentResult['points'];
            $signalCategories++;
        }
        $checks[] = $this->buildCheck('Message Content / Behavior Patterns', $contentResult, $contentResult['matched_categories'] >= 3 ? 'HIGH RISK' : 'SUSPICIOUS');

        if ($aiResult !== null) {
            if ($aiResult['flagged']) {
                $totalPoints += $aiResult['points'];
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('AI Message Review', $aiResult, $aiResult['verdict'] === 'scam' ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        $pressureLink = $this->checkLinkInPressureMessage($contentResult, $extractedUrl);
        if ($pressureLink['flagged']) {
            $totalPoints += $pressureLink['points'];
            $signalCategories++;
            $checks[] = $this->buildCheck('Link in Pressure Message', $pressureLink, 'SUSPICIOUS');
        }

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
            $extractedUrlCti = $urlAnalysis['cti'] ?? null;
        }

        if ($extractedEmail) {
            $emailDomain = $this->checkEmailDomain($extractedEmail)['domain'];
            $domainHistoryResult = $this->checkPreviousDomainReports($emailDomain, $reportId);
            if ($domainHistoryResult['flagged'] ?? false) {
                $totalPoints += $domainHistoryResult['points'];
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        $riskScore = min(100, $totalPoints);
        $verdict = ! $hasAnyEvidence ? 'review' : ($riskScore >= 60 ? 'phishing' : ($riskScore >= 25 ? 'suspicious' : 'clean'));
        $confidence = $hasAnyEvidence ? min(95, 40 + $signalCategories * 13) : 20;

        $result = [
            'risk_score' => $riskScore,
            'confidence' => $confidence,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'] ?? null,
            'url_syntax_score' => null,
            'checks' => $checks,
            'extracted_url' => $extractedUrl,
            'extracted_email' => $extractedEmail,
            'extracted_phone' => $extractedPhone,
        ];

        if (! empty($extractedUrlCti)) {
            $result['cti'] = $extractedUrlCti;
        }

        return $result;
    }
}