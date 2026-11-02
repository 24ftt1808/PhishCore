<?php

namespace App\Services\Concerns;

use App\Models\Report;
use App\Models\TrustedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
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

    /** How much of a PDF's wording is checked, and the most one compressed part may expand to. */
    private const PDF_TEXT_MAX_CHARS = 20000;

    private const PDF_STREAM_MAX_BYTES = 2 * 1024 * 1024;

    /** How many pages pdftotext reads, and how many are drawn as pictures for QR codes and scanned text. */
    private const PDF_PAGES_READ = 5;

    private const PDF_PAGES_RENDERED = 2;

    /** Earlier scans only count for this long, so a mistake fades out once the rules improve. */
    private const FILE_HISTORY_DAYS = 90;

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

        if (TrustedFile::isTrusted($sha256)) {
            return $this->trustedFileResult($sha256);
        }

        $kind = $this->detectFileKind($path);

        $typeResult = $this->checkFileType($kind, $originalName);
        $hashResult = $this->checkFileHash($sha256);
        $historyResult = $this->checkPreviousFileReports($sha256, $reportId);

        $checks = [
            $this->buildCheck('File Type', $typeResult, 'SUSPICIOUS'),
            $this->buildCheck('VirusTotal File Fingerprint', $hashResult, 'DETECTED'),
            $this->buildCheck('Previous Reports (File)', $historyResult, 'HIGH RISK'),
        ];
        $points = $typeResult['points'] + $hashResult['points'] + $historyResult['points'];
        $extractedUrl = null;

        if ($kind === 'apk') {
            $apkResult = $this->checkApkPermissions($path);
            $checks[] = $this->buildCheck('App Permissions', $apkResult, $apkResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
            $points += $apkResult['points'];
        } elseif ($kind === 'pdf') {
            // The first pages as pictures (only with the PDF tools installed), for QR codes and scanned text.
            $pages = $this->renderPdfPages($path);

            try {
                $pdfResult = $this->checkPdfContents($path, $pages);
                $checks[] = $this->buildCheck('PDF Contents', $pdfResult, $pdfResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
                $points += $pdfResult['points'];
                $extractedUrl = $pdfResult['worst_link'] ?? null;

                $textResult = $this->checkPdfText($path, $pages);
                $checks[] = $this->buildCheck('PDF Text', $textResult, $textResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
                $points += $textResult['points'];
            } finally {
                foreach ($pages as $page) {
                    @unlink($page);
                }
            }
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

    /** The result for a file an administrator has checked and marked as safe. No other check is needed. */
    private function trustedFileResult(string $sha256): array
    {
        return [
            'risk_score' => 0,
            'verdict' => 'clean',
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => [[
                'name' => 'Admin Review',
                'status' => 'SAFE',
                'message' => 'A PhishCore administrator checked this exact file and marked it as safe, so the other checks were skipped.',
                'points' => 0,
            ]],
            'extracted_url' => null,
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
            $reasons[] = 'Only PDF files can be checked in detail. Only the VirusTotal fingerprint check was done';
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

    /**
     * @param  array<int, string>  $pages  the first pages as pictures (see renderPdfPages), read for QR codes
     */
    private function checkPdfContents(string $path, array $pages = []): array
    {
        // Modern PDFs pack their links and actions inside compressed parts ("object streams"), so those are unpacked
        // and read too. Without this a link made in Word or a browser's "Save as PDF" could not be seen.
        $content = $this->expandPdf((string) file_get_contents($path, false, null, 0, self::PDF_READ_BYTES));
        $reasons = [];
        $points = 0;
        $worstLink = null;

        // A QR code printed on the page is a link too ("scan to claim"), and the favourite trick of scam PDFs,
        // because a QR code hides where it goes.
        $qrLinks = [];
        foreach ($pages as $page) {
            $qrLinks = array_merge($qrLinks, $this->decodeQrCodes($page));
        }
        $qrLinks = array_values(array_unique($qrLinks));
        $links = array_values(array_unique(array_merge($qrLinks, $this->extractPdfLinks($content))));

        $worstPoints = -1;
        $worstReasons = [];
        foreach ($links as $link) {
            $syntax = $this->checkUrlSyntax($link);
            // A link from a document to a page on a free hosting or cloud storage platform (an Azure static site, a
            // Google Apps Script) is how real phishing PDFs reach their fake login pages.
            $hosting = $this->checkFreeHostingPlatform($link);
            $linkPoints = $syntax['points'] + $hosting['points'];
            if ($linkPoints > $worstPoints) {
                $worstPoints = $linkPoints;
                $worstLink = $link;
                $worstReasons = array_merge($hosting['flagged'] ? ["it is a page on {$hosting['platform']}, where anyone can publish a page"] : [], array_filter($syntax['reasons'], fn ($r) => $syntax['points'] > 0));
            }
        }

        // The links themselves against the phishing lists and Google Safe Browsing (at most three, so the scan stays
        // quick). A link that is already reported is the strongest sign a PDF can carry.
        $listed = null;
        foreach (array_slice($links, 0, 3) as $link) {
            $blacklist = $this->checkBlacklist($link);
            if (($blacklist['flagged'] ?? false) && ! ($blacklist['unavailable'] ?? false) && ($listed === null || $blacklist['points'] > $listed['points'])) {
                $listed = ['link' => $link, 'points' => (int) $blacklist['points'], 'reasons' => $blacklist['reasons']];
            }
        }

        if ($listed !== null) {
            $host = parse_url($listed['link'], PHP_URL_HOST) ?: $listed['link'];
            $points += $listed['points'];
            $worstLink = $listed['link'];
            $reasons[] = "The PDF links to \"{$host}\", which is already reported: ".implode('; ', array_slice($listed['reasons'], 0, 2));
        }

        if ($worstLink !== null && ($listed === null || $listed['link'] !== $worstLink || $worstPoints > 0)) {
            $host = parse_url($worstLink, PHP_URL_HOST) ?: $worstLink;
            if ($worstPoints > 0) {
                $points += min(40, $worstPoints);
                $reasons[] = "The PDF links to \"{$host}\", which looks suspicious: ".implode('; ', array_slice($worstReasons, 0, 2));
            } elseif ($listed === null) {
                $reasons[] = "The PDF contains a link to \"{$host}\", and nothing about the address looks wrong";
            }
        }

        if ($qrLinks !== []) {
            $qrHost = parse_url($qrLinks[0], PHP_URL_HOST) ?: $qrLinks[0];
            $reasons[] = "A QR code on the page opens \"{$qrHost}\"";
        }

        if ($this->pdfActsByItself($content)) {
            $points += 20;
            $reasons[] = 'The PDF is set to open a website, a script or a program by itself as soon as it is opened, which real documents almost never do';
        }

        if (preg_match('~/Launch\b~', $content)) {
            $points += 25;
            $reasons[] = 'The PDF can start another program when opened, which real documents almost never do';
        }

        if (preg_match('~/(JavaScript|JS)\b~', $content)) {
            $points += 15;
            $reasons[] = 'The PDF contains a script. Forms can have these, but scam files use them to run hidden actions';
        }

        if (preg_match('~/EmbeddedFile\b~', $content)) {
            $points += 20;
            $reasons[] = 'The PDF has another file hidden inside it. Real documents rarely carry a hidden file, and scam PDFs use this to hide an app or a script';
        }

        if (preg_match('~/SubmitForm\b~', $content)) {
            $points += 20;
            $reasons[] = 'The PDF has a form that sends what you type to a server. Scam PDFs use fake forms to collect passwords and card details';
        }

        if ($reasons === []) {
            $reasons[] = $pages === []
                ? 'No links or hidden actions were found in the PDF'
                : 'No links, QR codes or hidden actions were found in the PDF';
        }

        // Below 15 points nothing is added, so a small sign is not called "suspicious".
        if ($points < 15) {
            $reasons = array_map(fn (string $reason) => str_replace(', which looks suspicious: ', ', which has one small warning sign, not enough on its own: ', $reason), $reasons);
        }

        return [
            'flagged' => $points >= 15,
            'points' => $points >= 15 ? $points : 0,
            'reasons' => $reasons,
            'worst_link' => $worstLink,
        ];
    }

    /**
     * Reads the words inside the PDF and looks for scam wording, the same way a pasted message is
     * checked. Only rules are used here: the text of an uploaded file is never sent to an AI service.
     */
    /**
     * @param  array<int, string>  $pages  the first pages as pictures (see renderPdfPages), read when the PDF has no text
     */
    private function checkPdfText(string $path, array $pages = []): array
    {
        $fromPicture = false;

        // Word, LibreOffice and most PDF makers store letters by font position, not as plain text, so the built-in reader
        // often finds nothing or only part of the words. pdftotext (the PDF tools) reads those properly, so it goes first.
        $text = $this->pdfToText($path)
            ?? $this->extractPdfText((string) file_get_contents($path, false, null, 0, self::PDF_READ_BYTES));

        // A PDF that is only a picture of text (a scanned or photographed letter): read the pictures of its pages.
        if (mb_strlen(trim($text)) < 20 && $pages !== []) {
            $read = [];
            foreach ($pages as $page) {
                $ocr = $this->readWithBackupOcr($page);
                if ($ocr !== null) {
                    $read[] = $ocr['text'];
                }
            }
            if ($read !== []) {
                $text = implode("\n", $read);
                $fromPicture = true;
            }
        }

        $text = mb_substr(trim($text), 0, self::PDF_TEXT_MAX_CHARS);

        if (mb_strlen($text) < 20) {
            return [
                'flagged' => false,
                'points' => 0,
                'unavailable' => true,
                'reasons' => ['No readable text was found, so the wording was not checked. Scanned pictures and some fonts cannot be read by this check'],
            ];
        }

        $content = $this->checkContentPatterns($text);
        $categories = $content['categories'] ?? [];

        // Fake tax refund notices ("you are eligible for a tax refund... Claim My Payment") are one of the most common
        // phishing PDFs. A real tax office does not send a refund claim button in an attachment.
        if (preg_match('/(eligible for an? (tax )?refund|tax refund (request|form|is ready|ready)|economic impact payment|claim (my|your) (payment|refund)|refund (of|is) \S{0,12}\s?(ready|available|pending|approved)|(overpayment|refund) .{0,40}(deposit|claim|request form))/iu', $text)) {
            $categories[] = 'refund_lure';
            $content['points'] += 25;
            $content['reasons'][] = 'Tax or payment refund offer that asks you to claim it, the wording of fake tax office notices';
        }

        // Real invoices, offer letters and notices mention deadlines, payments and bank details, so two everyday kinds of
        // wording are normal in a PDF. It counts as scam wording when three kinds stack up, or when two do and one of
        // them is a strong sign on its own: a threat to the account, a prize, a job fee, or a refund to claim. A request
        // for details is not strong on its own: a real offer letter asks for bank details for payroll.
        $strong = array_intersect($categories, ['account_threat', 'secret_ask', 'code_callback', 'prize_scam', 'job_fee', 'money_ask', 'sim_reregister', 'statement_forward', 'refund_lure']);
        $kinds = count(array_unique($categories));
        if ($kinds < 3 && ! ($kinds >= 2 && $strong !== [])) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => [$fromPicture ? 'No scam wording was found in the text read from the pictures of the pages' : 'No scam wording was found in the text of the PDF'],
            ];
        }

        return [
            'flagged' => $content['points'] > 0,
            'points' => $content['points'],
            'reasons' => $fromPicture
                ? array_merge(['The PDF is a picture of text, so its pages were read as images'], $content['reasons'])
                : $content['reasons'],
        ];
    }

    /** True when the PDF tools (poppler-utils) may be used: PDF_TOOLS_ENABLED=true, installed with "sudo apt install poppler-utils". */
    private function pdfToolsEnabled(): bool
    {
        return (bool) config('services.pdf_tools.enabled');
    }

    /** The text of the first pages read by pdftotext, or null when the tools are off, missing or find nothing. */
    private function pdfToText(string $path): ?string
    {
        if (! $this->pdfToolsEnabled()) {
            return null;
        }

        try {
            $result = Process::timeout(20)->run([
                (string) config('services.pdf_tools.pdftotext', 'pdftotext'),
                '-l', (string) self::PDF_PAGES_READ, '-enc', 'UTF-8', '-q', $path, '-',
            ]);
        } catch (Throwable $e) {
            Log::warning('pdftotext could not run', ['error' => mb_substr($e->getMessage(), 0, 200)]);

            return null;
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', mb_scrub($result->output())));

        return $result->successful() && $text !== '' ? $text : null;
    }

    /**
     * The first pages of the PDF as PNG pictures in the temp folder (the caller deletes them), for QR codes and
     * scanned text. Empty when the PDF tools are off or missing. Pages are drawn at 150 dpi, enough for both.
     *
     * @return array<int, string>
     */
    private function renderPdfPages(string $path): array
    {
        if (! $this->pdfToolsEnabled()) {
            return [];
        }

        $prefix = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phishcore-pdf-'.bin2hex(random_bytes(6));

        try {
            Process::timeout(30)->run([
                (string) config('services.pdf_tools.pdftoppm', 'pdftoppm'),
                '-png', '-r', '150', '-f', '1', '-l', (string) self::PDF_PAGES_RENDERED, $path, $prefix,
            ]);
        } catch (Throwable $e) {
            Log::warning('pdftoppm could not run', ['error' => mb_substr($e->getMessage(), 0, 200)]);
        }

        $pages = glob($prefix.'*.png') ?: [];
        sort($pages);

        return $pages;
    }

    /**
     * Whether the PDF does something by itself when it is opened: an /OpenAction or an /AA (additional action) that
     * opens a website, runs a script or starts a program. Opening at a page or zoom level is normal and not counted.
     */
    private function pdfActsByItself(string $content): bool
    {
        $risky = '~/S\s*/(URI|Launch|JavaScript|SubmitForm|ImportData)\b~';

        if (preg_match_all('~/(?:OpenAction|AA)\s*(<<.*?>>|\d+\s+\d+\s+R)~s', $content, $matches)) {
            foreach ($matches[1] as $target) {
                if (preg_match('~^(\d+)\s+(\d+)\s+R$~', trim($target), $ref)) {
                    // A reference to another object: read that object's dictionary.
                    if (preg_match('~(?<![0-9])'.$ref[1].'\s+'.$ref[2].'\s+obj\s*(<<.*?>>)~s', $content, $object)) {
                        $target = $object[1];
                    } else {
                        continue;
                    }
                }
                if (preg_match($risky, $target)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The PDF's own bytes followed by the unpacked contents of its compressed object streams (/ObjStm). Links,
     * scripts and actions stored inside those can then be found with the same simple searches.
     */
    private function expandPdf(string $content): string
    {
        if (! preg_match_all('~<<((?:(?!>>\s*stream).)*?/ObjStm(?:(?!>>\s*stream).)*?)>>\s*stream\r?\n(.*?)endstream~s', $content, $streams, PREG_SET_ORDER)) {
            return $content;
        }

        $unpacked = '';
        foreach (array_slice($streams, 0, 200) as [, $dictionary, $data]) {
            $decoded = $this->decodePdfStream($dictionary, $data);
            if ($decoded !== null) {
                $unpacked .= "\n".$decoded;
            }
            if (strlen($unpacked) > self::PDF_READ_BYTES) {
                break;
            }
        }

        return $content.$unpacked;
    }

    /**
     * One stream's data with its filters undone: FlateDecode (zlib) and ASCII85Decode, alone or chained, the two
     * that PDF makers use for text and structure. Null when it uses anything else or cannot be decoded.
     */
    private function decodePdfStream(string $dictionary, string $data): ?string
    {
        preg_match('~/Filter\s*(\[[^\]]*\]|/\w+)~', $dictionary, $filter);
        preg_match_all('~/(\w+)~', $filter[1] ?? '', $names);
        $filters = $names[1] ?? [];

        $data = rtrim($data, "\r\n");

        foreach ($filters as $name) {
            if ($name === 'ASCII85Decode' || $name === 'A85') {
                $data = $this->decodeAscii85($data);
            } elseif ($name === 'FlateDecode' || $name === 'Fl') {
                $inflated = @gzuncompress($data, self::PDF_STREAM_MAX_BYTES);
                if ($inflated === false) {
                    $inflated = @gzinflate(substr($data, 2), self::PDF_STREAM_MAX_BYTES);
                }
                $data = $inflated === false ? null : $inflated;
            } else {
                return null;
            }

            if ($data === null) {
                return null;
            }
        }

        return $data;
    }

    /** ASCII85 ("<~ ... ~>") back to bytes, or null when it is not valid ASCII85. */
    private function decodeAscii85(string $data): ?string
    {
        $data = (string) preg_replace('/\s+/', '', $data);
        if (str_starts_with($data, '<~')) {
            $data = substr($data, 2);
        }
        $end = strpos($data, '~>');
        if ($end !== false) {
            $data = substr($data, 0, $end);
        }

        $out = '';
        $group = [];
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $char = $data[$i];
            if ($char === 'z' && $group === []) {
                $out .= "\0\0\0\0";

                continue;
            }
            $code = ord($char) - 33;
            if ($code < 0 || $code > 84) {
                return null;
            }
            $group[] = $code;
            if (count($group) === 5) {
                $value = 0;
                foreach ($group as $digit) {
                    $value = $value * 85 + $digit;
                }
                $out .= pack('N', $value & 0xFFFFFFFF);
                $group = [];
            }
        }

        if ($group !== []) {
            $missing = 5 - count($group);
            $group = array_merge($group, array_fill(0, $missing, 84));
            $value = 0;
            foreach ($group as $digit) {
                $value = $value * 85 + $digit;
            }
            $out .= substr(pack('N', $value & 0xFFFFFFFF), 0, 4 - $missing);
        }

        return $out;
    }

    /** The plain text shown on a PDF's pages, or an empty string when it cannot be read. */
    private function extractPdfText(string $content): string
    {
        $found = preg_match_all('~obj\b((?:(?!endobj).)*?)stream\r?\n(.*?)endstream~s', $content, $objects, PREG_SET_ORDER);
        if (! $found) {
            return '';
        }

        $text = '';
        foreach (array_slice($objects, 0, 300) as [, $dictionary, $data]) {
            if (str_contains($dictionary, '/Filter')) {
                $decoded = $this->decodePdfStream($dictionary, $data);
                if ($decoded === null) {
                    continue;
                }
                $data = $decoded;
            }

            if (! str_contains($data, 'BT')) {
                continue;
            }

            $text .= ' '.$this->readPdfTextOperators($data);

            if (strlen($text) > self::PDF_TEXT_MAX_CHARS * 2) {
                break;
            }
        }

        return trim(mb_scrub((string) preg_replace('/\s+/', ' ', $text)));
    }

    /** Pulls the strings out of a page's drawing commands: (text) Tj and [(te) -20 (xt)] TJ. */
    private function readPdfTextOperators(string $stream): string
    {
        preg_match_all('~\[((?:[^\]\\\\]|\\\\.)*)\]\s*TJ|(\((?:\\\\.|[^\\\\)])*\))\s*Tj~s', $stream, $matches, PREG_SET_ORDER);

        $lines = [];
        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                $line = '';
                preg_match_all('~\(((?:\\\\.|[^\\\\)])*)\)|(-?\d+(?:\.\d+)?)~s', $match[1], $parts, PREG_SET_ORDER);
                foreach ($parts as $part) {
                    if (($part[1] ?? '') !== '') {
                        $line .= $this->unescapePdfString($part[1]);
                    } elseif (isset($part[2]) && (float) $part[2] < -200) {
                        $line .= ' ';
                    }
                }
                $lines[] = $line;
            } elseif (isset($match[2])) {
                $lines[] = $this->unescapePdfString(substr($match[2], 1, -1));
            }
        }

        return implode(' ', $lines);
    }

    /**
     * The web links a PDF opens (its /URI entries). A PDF may write a link with escapes such as
     * "\072\057\057" for "://" or as hex digits, and many PDF tools do this, so both are decoded
     * before the link is read.
     *
     * @return array<int, string> up to five different http(s) links
     */
    private function extractPdfLinks(string $content): array
    {
        $strings = [];

        if (preg_match_all('~/URI\s*\(((?:[^()\\\\]|\\\\.)*)\)~s', $content, $matches)) {
            foreach ($matches[1] as $value) {
                $strings[] = $this->unescapePdfString($value);
            }
        }

        if (preg_match_all('~/URI\s*<([0-9a-f\s]+)>~i', $content, $matches)) {
            foreach ($matches[1] as $hex) {
                $hex = preg_replace('/\s+/', '', $hex) ?? '';
                $strings[] = (string) hex2bin(strlen($hex) % 2 === 0 ? $hex : $hex.'0');
            }
        }

        $links = [];
        foreach ($strings as $value) {
            if (preg_match('~^\s*(https?://[^\s]{4,500})~i', $value, $found)) {
                $links[] = $found[1];
            }
        }

        return array_slice(array_values(array_unique($links)), 0, 5);
    }

    private function unescapePdfString(string $value): string
    {
        $value = preg_replace_callback('~\\\\([0-7]{1,3})~', fn ($m) => chr(octdec($m[1]) & 0xFF), $value);

        return strtr((string) $value, [
            '\\n' => ' ', '\\r' => ' ', '\\t' => ' ',
            '\\(' => '(', '\\)' => ')', '\\\\' => '\\',
        ]);
    }

    /**
     * Whether PhishCore has already judged this exact file (same fingerprint) to be phishing.
     *
     * Only earlier verdicts of "phishing" add points, and one person scanning a file again and again
     * counts once. A "suspicious" earlier verdict is shown for information only, so a borderline
     * file cannot build up a worse and worse reputation just by being scanned repeatedly.
     */
    private function checkPreviousFileReports(string $sha256, ?int $excludeReportId = null): array
    {
        $phishing = $this->previousFileReporters($sha256, $excludeReportId, ['phishing']);
        $suspicious = $this->previousFileReporters($sha256, $excludeReportId, ['suspicious']);

        $reporters = count(array_unique(array_filter($phishing))) + count(array_filter($phishing, fn ($id) => $id === null));

        if ($reporters >= 2) {
            return [
                'flagged' => true,
                'points' => 50,
                'reasons' => ["This exact file was already found to be phishing in {$reporters} other scans on PhishCore"],
            ];
        }

        if ($reporters === 1) {
            return [
                'flagged' => true,
                'points' => 30,
                'reasons' => ['This exact file was already found to be phishing in another scan on PhishCore'],
            ];
        }

        if ($suspicious !== []) {
            return [
                'flagged' => false,
                'points' => 0,
                'neutral' => true,
                'reasons' => ['This exact file was scanned on PhishCore before and looked suspicious (informational only, this does not change the risk score)'],
            ];
        }

        return [
            'flagged' => false,
            'points' => 0,
            'neutral' => true,
            'reasons' => ['This exact file has not been scanned on PhishCore before'],
        ];
    }

    /**
     * Who scanned this exact file earlier with one of these verdicts: a user id for each logged-in
     * scan and null for each guest scan.
     *
     * @param  array<int, string>  $verdicts
     * @return array<int, int|null>
     */
    private function previousFileReporters(string $sha256, ?int $excludeReportId, array $verdicts): array
    {
        $query = Report::where('status', 'completed')
            ->where('file_sha256', $sha256)
            ->where('created_at', '>=', now()->subDays(self::FILE_HISTORY_DAYS))
            ->whereHas('analyses', fn ($q) => $q->whereIn('verdict', $verdicts));

        if ($excludeReportId) {
            $query->where('id', '!=', $excludeReportId);
        }

        return $query->pluck('user_id')->all();
    }
}