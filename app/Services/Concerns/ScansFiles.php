<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Http;
use Throwable;
use ZipArchive;

/**
 * The file scan (APK and PDF) used by AnalysisEngine.
 *
 * Privacy: the file is never sent anywhere. Only its SHA-256 fingerprint is looked up on VirusTotal,
 * so a private document cannot end up in a public database. A file VirusTotal has never seen is
 * reported as "not seen before", which says nothing about whether it is safe.
 */
trait ScansFiles
{
    /** Permissions that fake bank, parcel and "update" apps abuse, grouped by what they allow. */
    private const APK_RISKY_PERMISSIONS = [
        'sms' => [
            'names' => ['READ_SMS', 'RECEIVE_SMS', 'SEND_SMS', 'RECEIVE_MMS', 'RECEIVE_WAP_PUSH'],
            'text' => 'read or send your text messages (how fake apps steal one-time codes)',
            'points' => 20,
        ],
        'accessibility' => [
            'names' => ['BIND_ACCESSIBILITY_SERVICE'],
            'text' => 'control your screen through accessibility services',
            'points' => 25,
        ],
        'admin' => [
            'names' => ['BIND_DEVICE_ADMIN'],
            'text' => 'become a device administrator, which makes it hard to remove',
            'points' => 20,
        ],
        'notifications' => [
            'names' => ['BIND_NOTIFICATION_LISTENER_SERVICE'],
            'text' => 'read all your notifications, including one-time codes',
            'points' => 20,
        ],
        'overlay' => [
            'names' => ['SYSTEM_ALERT_WINDOW'],
            'text' => 'draw over other apps (used to put fake login screens on top of real banking apps)',
            'points' => 15,
        ],
        'install' => [
            'names' => ['REQUEST_INSTALL_PACKAGES'],
            'text' => 'install other apps',
            'points' => 10,
        ],
        'calls' => [
            'names' => ['READ_CALL_LOG', 'WRITE_CALL_LOG', 'PROCESS_OUTGOING_CALLS'],
            'text' => 'read or redirect your phone calls',
            'points' => 10,
        ],
        'contacts' => [
            'names' => ['READ_CONTACTS'],
            'text' => 'read your contacts',
            'points' => 5,
        ],
    ];

    /** Only the start of a PDF is read, which is where links and actions normally are. */
    private const PDF_READ_BYTES = 8 * 1024 * 1024;

    private function analyzeFile(string $path, ?string $originalName = null, ?int $reportId = null): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [
                'risk_score' => 0,
                'verdict' => 'review',
                'domain_age_days' => null,
                'url_syntax_score' => null,
                'checks' => [[
                    'name' => 'File Analysis',
                    'status' => 'REVIEW',
                    'message' => 'The file could not be read, so it was not checked.',
                    'points' => 0,
                ]],
                'extracted_url' => null,
                'file_sha256' => null,
            ];
        }

        $sha256 = (string) hash_file('sha256', $path);
        $kind = $this->detectFileKind($path);

        $typeResult = $this->checkFileType($kind, $originalName);
        $hashResult = $this->checkFileHash($sha256);

        $checks = [
            $this->buildCheck('File Type', $typeResult, 'SUSPICIOUS'),
            $this->buildCheck('VirusTotal File Fingerprint', $hashResult, 'DETECTED'),
        ];
        $points = $typeResult['points'] + $hashResult['points'];
        $extractedUrl = null;

        if ($kind === 'apk') {
            $apkResult = $this->checkApkPermissions($path);
            $checks[] = $this->buildCheck('App Permissions', $apkResult, $apkResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
            $points += $apkResult['points'];
        } elseif ($kind === 'pdf') {
            $pdfResult = $this->checkPdfContents($path);
            $checks[] = $this->buildCheck('PDF Contents', $pdfResult, $pdfResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
            $points += $pdfResult['points'];
            $extractedUrl = $pdfResult['worst_link'] ?? null;
        }

        $riskScore = min(100, $points);

        return [
            'risk_score' => $riskScore,
            'verdict' => $riskScore >= 60 ? 'phishing' : ($riskScore >= 25 ? 'suspicious' : 'clean'),
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => $checks,
            'extracted_url' => $extractedUrl,
            'file_sha256' => $sha256,
        ];
    }

    /** What the file really is, judged by its contents and not by its name: 'pdf', 'apk' or 'other'. */
    private function detectFileKind(string $path): string
    {
        $handle = @fopen($path, 'rb');
        $start = $handle ? (string) fread($handle, 8) : '';
        if ($handle) {
            fclose($handle);
        }

        if (str_starts_with($start, '%PDF-')) {
            return 'pdf';
        }

        if (str_starts_with($start, "PK\x03\x04") && class_exists(ZipArchive::class)) {
            $zip = new ZipArchive;
            if ($zip->open($path) === true) {
                $isApk = $zip->locateName('AndroidManifest.xml') !== false && $zip->locateName('classes.dex') !== false;
                $zip->close();

                return $isApk ? 'apk' : 'other';
            }
        }

        return 'other';
    }

    private function checkFileType(string $kind, ?string $originalName): array
    {
        $reasons = [];
        $points = 0;
        $name = strtolower(basename((string) $originalName));
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $label = ['pdf' => 'a PDF document', 'apk' => 'an Android app (APK)', 'other' => 'a file of an unsupported type'][$kind];
        $reasons[] = "The contents show that this is {$label}";

        if ($kind === 'other') {
            $reasons[] = 'Only APK and PDF files can be checked in detail. Only the VirusTotal fingerprint check was done';
        }

        $known = ['pdf', 'apk', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'mp4', 'mp3'];
        if ($kind !== 'other' && in_array($extension, $known, true) && $extension !== $kind) {
            $reasons[] = "The file is named \".{$extension}\" but it is really {$label}. Scam files often hide what they are this way";
            $points += 30;
        }

        if (preg_match('/\.(pdf|docx?|xlsx?|jpe?g|png|txt|mp4)\.(apk|exe|scr|bat|js|vbs|msi)$/', $name)) {
            $reasons[] = 'The file name has two endings, which makes a program look like a document or a picture';
            $points += 25;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'neutral' => $points === 0,
            'reasons' => $reasons,
        ];
    }

    /** Looks up the file's SHA-256 on VirusTotal. The file itself is never uploaded. */
    private function checkFileHash(string $sha256): array
    {
        $unavailable = fn (string $reason) => [
            'flagged' => false,
            'points' => 0,
            'unavailable' => true,
            'reasons' => [$reason],
        ];

        $apiKey = config('services.virustotal.key');
        if (! $apiKey) {
            return $unavailable('The VirusTotal check was skipped because no API key is set up');
        }

        try {
            $response = Http::withHeaders(['x-apikey' => $apiKey])
                ->timeout(20)
                ->acceptJson()
                ->get('https://www.virustotal.com/api/v3/files/'.$sha256);
        } catch (Throwable) {
            return $unavailable('The VirusTotal service could not be reached right now, so this check was skipped');
        }

        if ($response->status() === 404) {
            return [
                'flagged' => false,
                'points' => 0,
                'neutral' => true,
                'reasons' => ['VirusTotal has not seen this exact file before. That does not mean it is safe, because new scam files are often not known yet'],
            ];
        }

        if ($response->status() === 429) {
            return $unavailable('The VirusTotal service is busy right now, so this check was skipped. Please try again later');
        }

        if (! $response->successful()) {
            return $unavailable('The VirusTotal service did not give an answer, so this check was skipped');
        }

        $stats = (array) data_get($response->json(), 'data.attributes.last_analysis_stats', []);
        $bad = (int) ($stats['malicious'] ?? 0) + (int) ($stats['suspicious'] ?? 0);
        $total = max(1, (int) array_sum(array_map('intval', $stats)));

        if ($bad >= 5) {
            return ['flagged' => true, 'points' => 80, 'reasons' => ["{$bad} out of {$total} security vendors on VirusTotal flagged this file as malicious or suspicious"]];
        }

        if ($bad >= 1) {
            return ['flagged' => true, 'points' => 35, 'reasons' => ["{$bad} out of {$total} security vendors on VirusTotal flagged this file as malicious or suspicious. A very small number of flags can be a false alarm"]];
        }

        return ['flagged' => false, 'points' => 0, 'reasons' => ["0 out of {$total} security vendors on VirusTotal flagged this file"]];
    }

    /** The "android.permission.*" names listed in an APK's manifest, or null when it cannot be read. */
    private function readApkPermissions(string $path): ?array
    {
        if (! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }
        $manifest = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        if ($manifest === false || $manifest === '') {
            return null;
        }

        $names = [];

        // Plain (UTF-8) text.
        preg_match_all('/android\.permission\.([A-Z_]+)/', $manifest, $plain);
        // Text stored as two bytes per letter (UTF-16), which is what most manifests use.
        preg_match_all('/a\0n\0d\0r\0o\0i\0d\0\.\0p\0e\0r\0m\0i\0s\0s\0i\0o\0n\0\.\0((?:[A-Z_]\0)+)/', $manifest, $wide);

        foreach ($plain[1] as $name) {
            $names[$name] = true;
        }
        foreach ($wide[1] as $name) {
            $names[str_replace("\0", '', $name)] = true;
        }

        return array_keys($names);
    }

    private function checkApkPermissions(string $path): array
    {
        $permissions = $this->readApkPermissions($path);

        if ($permissions === null) {
            return [
                'flagged' => false,
                'points' => 0,
                'unavailable' => true,
                'reasons' => ['The list of permissions could not be read from this app'],
            ];
        }

        $reasons = [];
        $points = 0;
        $groupsFound = 0;

        foreach (self::APK_RISKY_PERMISSIONS as $group) {
            if (array_intersect($group['names'], $permissions) !== []) {
                $groupsFound++;
                $points += $group['points'];
                $reasons[] = 'This app asks to '.$group['text'];
            }
        }

        if ($groupsFound >= 3) {
            $reasons[] = 'Asking for this many sensitive permissions together is typical of fake banking and parcel apps';
            $points += 15;
        }

        $points = min(60, $points);

        if ($reasons === []) {
            $reasons[] = 'No sensitive permissions were found in the app\'s permission list';
        } else {
            $reasons[] = 'Some real apps need these permissions too, so check where the app came from. Real banks and shops give their apps through the Play Store or App Store';
        }

        return [
            'flagged' => $points >= 15,
            'points' => $points >= 15 ? $points : 0,
            'reasons' => $reasons,
        ];
    }

    private function checkPdfContents(string $path): array
    {
        $content = (string) file_get_contents($path, false, null, 0, self::PDF_READ_BYTES);
        $reasons = [];
        $points = 0;
        $worstLink = null;

        preg_match_all('~/URI\s*\(\s*(https?://[^)\s]{4,500})~i', $content, $matches);
        $links = array_slice(array_values(array_unique($matches[1])), 0, 5);

        $worstPoints = -1;
        foreach ($links as $link) {
            $syntax = $this->checkUrlSyntax($link);
            if ($syntax['points'] > $worstPoints) {
                $worstPoints = $syntax['points'];
                $worstLink = $link;
                $worstReasons = $syntax['reasons'];
            }
        }

        if ($worstLink !== null) {
            $host = parse_url($worstLink, PHP_URL_HOST) ?: $worstLink;
            if ($worstPoints > 0) {
                $points += min(40, $worstPoints);
                $reasons[] = "The PDF links to \"{$host}\", which looks suspicious: ".implode('; ', array_slice($worstReasons, 0, 2));
            } else {
                $reasons[] = "The PDF contains a link to \"{$host}\", and nothing about the address looks wrong";
            }
        }

        if (preg_match('~/Launch\b~', $content)) {
            $points += 25;
            $reasons[] = 'The PDF can start another program when opened, which real documents almost never do';
        }

        if (preg_match('~/(JavaScript|JS)\b~', $content)) {
            $points += 15;
            $reasons[] = 'The PDF contains a script. Forms can have these, but scam files use them to run hidden actions';
        }

        if ($reasons === []) {
            $reasons[] = 'No links or hidden actions were found in the start of the PDF. Content packed inside compressed parts of a PDF cannot be seen by this check';
        }

        return [
            'flagged' => $points >= 15,
            'points' => $points >= 15 ? $points : 0,
            'reasons' => $reasons,
            'worst_link' => $worstLink,
        ];
    }
}
