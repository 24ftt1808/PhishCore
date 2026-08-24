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

    public function checkDomainAge(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?? $url;
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

    public function analyze(string $url): array
    {
        $syntaxResult = $this->checkUrlSyntax($url);
        $ageResult = $this->checkDomainAge($url);
        $sslResult = $this->checkSslCertificate($url);
        $blacklistResult = $this->checkBlacklist($url);

        $totalPoints = min(
            100,
            $syntaxResult['points'] + $ageResult['points'] + $sslResult['points'] + $blacklistResult['points']
        );

        if ($totalPoints >= 60) {
            $verdict = 'phishing';
        } elseif ($totalPoints >= 25) {
            $verdict = 'suspicious';
        } else {
            $verdict = 'clean';
        }

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
}