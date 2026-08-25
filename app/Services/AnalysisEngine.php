<?php

namespace App\Services;

use Iodev\Whois\Factory;
use Illuminate\Support\Facades\Http;

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
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        foreach ($brands as $brand) {
            if (str_contains($host, $brand) && !str_starts_with($host, $brand . '.') && !str_ends_with($host, '.' . $brand . '.com')) {
                $reasons[] = "Contains brand name \"{$brand}\" in a suspicious position within the domain";
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
            $whois = Factory::get()->createWhois();
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
        } catch (\Exception $e) {
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
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== 'https') {
            return [
                'flagged' => true,
                'points' => 20,
                'reasons' => ['Website does not use HTTPS (no SSL encryption)'],
            ];
        }

        $context = stream_context_create([
            'ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false],
        ]);

        try {
            $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

            if (!$client) {
                return [
                    'flagged' => true,
                    'points' => 25,
                    'reasons' => ["Could not establish a valid SSL connection"],
                ];
            }

            $params = stream_context_get_params($client);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            fclose($client);

            if (!$cert) {
                return ['flagged' => true, 'points' => 25, 'reasons' => ['Could not read SSL certificate details']];
            }

            $validTo = $cert['validTo_time_t'] ?? null;
            if ($validTo && $validTo < time()) {
                return ['flagged' => true, 'points' => 30, 'reasons' => ['SSL certificate has expired']];
            }

            $certName = $cert['subject']['CN'] ?? '';
            $hostWithoutWww = str_replace('www.', '', $host);
            $hostMatches = str_contains($certName, $hostWithoutWww) || $certName === $host;

            if (!$hostMatches) {
                return [
                    'flagged' => true,
                    'points' => 20,
                    'reasons' => ["SSL certificate does not match the domain"],
                ];
            }

            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        } catch (\Exception $e) {
            return ['flagged' => true, 'points' => 15, 'reasons' => ['Could not verify SSL certificate']];
        }
    }

    public function checkBlacklist(string $url): array
    {
        $apiKey = config('services.google_safe_browsing.key');

        if (!$apiKey) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check skipped: no API key configured']];
        }

        try {
            $response = Http::post(
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
        } catch (\Exception $e) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check unavailable']];
        }
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
        foreach ($brands as $brand) {
            if (str_contains($domain, $brand) && !str_ends_with($domain, $brand . '.com') && $domain !== $brand . '.com') {
                $reasons[] = "Sender domain mimics the brand \"{$brand}\" without being the official domain";
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
     * Heuristic check for reported phone numbers. Foreign country codes are flagged
     * outright; +673 (Brunei) numbers are checked against valid local prefixes,
     * correct length, and fabricated/spoofed-looking digit patterns.
     */
    public function checkPhoneNumber(string $phone): array
    {
        $reasons = [];
        $points = 0;

        $clean = preg_replace('/[\s()\-]/', '', $phone);

        if (!str_starts_with($clean, '+')) {
            $reasons[] = 'Number has no international dialing code, making its true origin hard to verify';
            $points += 15;
        } elseif (!str_starts_with($clean, '+673')) {
            $reasons[] = 'Number uses a foreign country code rather than Brunei (+673), common in smishing scams';
            $points += 25;
        } else {
            $localDigits = substr($clean, 4);

            if (!preg_match('/^\d{7}$/', $localDigits)) {
                $reasons[] = 'Number does not match the standard 7-digit length used for Brunei numbers';
                $points += 20;
            } else {
                $validPrefixes = ['71', '72', '73', '77', '78', '86', '88', '22', '23'];
                $prefix = substr($localDigits, 0, 2);

                if (!in_array($prefix, $validPrefixes, true)) {
                    $reasons[] = "Number prefix ({$prefix}) does not match a known Brunei mobile or landline range";
                    $points += 20;
                }

                if (preg_match('/^(\d)\1{6}$/', $localDigits)) {
                    $reasons[] = 'Number is a single digit repeated 7 times, a common sign of a fabricated number';
                    $points += 25;
                } elseif ($this->isSequentialDigits($localDigits)) {
                    $reasons[] = 'Number follows a simple sequential digit pattern, a common sign of a fabricated number';
                    $points += 20;
                }
            }
        }

        $digitsOnly = preg_replace('/\D/', '', $clean);
        if (strlen($digitsOnly) < 7) {
            $reasons[] = 'Number is unusually short for a valid contact number';
            $points += 15;
        } elseif (strlen($digitsOnly) > 15) {
            $reasons[] = 'Number is unusually long, which can indicate a malformed or spoofed number';
            $points += 10;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
        ];
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

        try {
            $response = Http::asMultipart()
                ->attach('file', file_get_contents($imagePath), basename($imagePath))
                ->timeout(30)
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => $apiKey,
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                    'OCREngine' => '2',
                ]);

            $data = $response->json();

            if (($data['IsErroredOnProcessing'] ?? true) === true) {
                return [
                    'success' => false,
                    'text' => '',
                    'error' => $data['ErrorMessage'][0] ?? 'OCR processing failed',
                ];
            }

            $text = $data['ParsedResults'][0]['ParsedText'] ?? '';

            return ['success' => true, 'text' => trim($text), 'error' => null];
        } catch (\Exception $e) {
            return ['success' => false, 'text' => '', 'error' => 'Could not reach the OCR service'];
        }
    }

    private function extractUrlFromText(string $text): ?string
    {
        if (preg_match('/https?:\/\/[^\s"\'<>]+/i', $text, $matches)) {
            return rtrim($matches[0], '.,;:)');
        }
        return null;
    }

    private function extractEmailFromText(string $text): ?string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return $matches[0];
        }
        return null;
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

    public function analyze(string $type, ?string $url = null, ?string $email = null, ?string $phone = null, ?string $screenshotPath = null): array
    {
        return match ($type) {
            'email' => $this->analyzeEmail($email ?? ''),
            'phone' => $this->analyzePhone($phone ?? ''),
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

        $totalPoints = min(
            100,
            $syntaxResult['points'] + $ageResult['points'] + $sslResult['points'] + $blacklistResult['points']
        );

        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('SSL Certificate', $sslResult, 'SUSPICIOUS'),
            $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('URL Structure', $syntaxResult, 'SUSPICIOUS'),
            $this->buildCheck('Blacklist Database', $blacklistResult, 'DETECTED'),
        ];

        return [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'],
            'url_syntax_score' => $syntaxResult['points'],
            'checks' => $checks,
        ];
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

    private function analyzePhone(string $phone): array
    {
        $phoneResult = $this->checkPhoneNumber($phone);
        $totalPoints = min(100, $phoneResult['points']);
        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('Phone Number Analysis', $phoneResult, 'SUSPICIOUS'),
        ];

        return [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => $checks,
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

        $extractedUrl = $this->extractUrlFromText($ocr['text']);
        $extractedEmail = $extractedUrl ? null : $this->extractEmailFromText($ocr['text']);

        $extractionCheck = [
            'name' => 'Screenshot Text Extraction',
            'status' => $extractedUrl || $extractedEmail ? 'SAFE' : 'REVIEW',
            'message' => $extractedUrl
                ? "Extracted a URL from the image text: {$extractedUrl}"
                : ($extractedEmail
                    ? "Extracted a sender email from the image text: {$extractedEmail}"
                    : 'No URL or email address was found in the image text. Manual review recommended.'),
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