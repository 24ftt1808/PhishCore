<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use App\Support\BatchMetrics;
use Illuminate\Console\Command;

class ScanScreenshots extends Command
{
    protected $signature = 'scan:screenshots
        {file : Text file with one image per line: label|group|image path (label is scam or legit, the path is relative to this file)}
        {--delay=2 : Seconds to wait between scans (the OCR.space free plan limits how fast images can be sent)}
        {--csv= : Where to save the results (default: storage/app/scan-screenshots-<timestamp>.csv)}';

    protected $description = 'Runs a list of screenshots through the screenshot scan (OCR, then the email, phone, brand and link checks) and reports detection rate and false alarms. Nothing is saved to the database.';

    public function handle(AnalysisEngine $engine): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $baseDir = dirname(realpath($path));
        $entries = $this->readEntries($path, $baseDir);

        if ($entries === []) {
            $this->error('No usable lines. Use: scam|sms|scan-screenshots/scam-01.png (lines starting with # are ignored).');

            return self::FAILURE;
        }

        if (! config('services.ocr_space.key')) {
            $this->error('OCR_SPACE_API_KEY is not set in .env, so the screenshot scan cannot read any image.');

            return self::FAILURE;
        }

        $delay = max(0, (int) $this->option('delay'));
        $rows = [];

        foreach ($entries as $i => [$expected, $group, $file, $fullPath]) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, count($entries), $file));

            if (! is_file($fullPath)) {
                $this->warn('  image not found, skipped');
                $rows[] = $this->row($expected, $group, $file, null, 'error', null, '', []);

                continue;
            }

            try {
                $result = $engine->analyze('screenshot', null, null, null, $fullPath);
                $score = (int) $result['risk_score'];
                $verdict = (string) $result['verdict'];
                $confidence = $result['confidence'] ?? null;
                $found = collect([
                    ! empty($result['extracted_url']) ? 'url' : null,
                    ! empty($result['extracted_email']) ? 'email' : null,
                    ! empty($result['extracted_phone']) ? 'phone' : null,
                ])->filter()->implode(', ');
                $why = collect($result['checks'] ?? [])
                    ->filter(fn ($c) => ($c['points'] ?? 0) > 0 || ($c['name'] ?? '') === 'Screenshot Text Extraction')
                    ->map(fn ($c) => sprintf('%s%s: %s', $c['name'], ($c['points'] ?? 0) > 0 ? " (+{$c['points']})" : '', $c['message']))
                    ->values()->all();
            } catch (\Throwable $e) {
                $score = null;
                $verdict = 'error';
                $confidence = null;
                $found = '';
                $why = [];
                $this->warn('  failed: '.$e->getMessage());
            }

            $rows[] = $this->row($expected, $group, $file, $score, $verdict, $confidence, $found, $why);

            if ($delay > 0 && $i < count($entries) - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->table(
            ['Expected', 'Group', 'Image', 'Score', 'Verdict', 'Confidence', 'Found in image', 'Correct'],
            array_map(fn ($r) => [
                $r['expected'],
                $r['category'] ?? '-',
                mb_strimwidth(basename($r['url']), 0, 28, '…'),
                $r['score'] ?? '-',
                $r['verdict'],
                $r['confidence'] !== null ? $r['confidence'].'%' : '-',
                $r['found'] !== '' ? $r['found'] : '-',
                $r['correct'] === null ? '-' : ($r['correct'] ? 'yes' : 'NO'),
            ], $rows)
        );

        foreach ($rows as $r) {
            if ($r['correct'] === false || $r['verdict'] === 'review') {
                $this->newLine();
                $this->warn(($r['verdict'] === 'review' ? 'Needs manual review: ' : 'Wrong: ')."{$r['url']} (expected {$r['expected']}, got {$r['verdict']} {$r['score']})");
                foreach ($r['why'] ?: ['No check added any points.'] as $line) {
                    $this->line('  - '.mb_strimwidth($line, 0, 200, '…'));
                }
            }
        }

        $this->newLine();
        $this->summary($rows);
        $this->saveCsv($rows);

        return self::SUCCESS;
    }

    private function row(string $expected, ?string $group, string $file, ?int $score, string $verdict, ?int $confidence, string $found, array $why): array
    {
        return [
            'expected' => $expected,
            'category' => $group,
            'url' => $file,
            'score' => $score,
            // The metrics treat "review" as not flagged, because the person sees "needs manual review", not a warning.
            'verdict' => $verdict,
            'confidence' => $confidence,
            'found' => $found,
            'correct' => $this->isCorrect($expected, $verdict),
            'why' => $why,
        ];
    }

    /** @return array<int, array{0: string, 1: ?string, 2: string, 3: string}> expected, group, file as written, full path */
    private function readEntries(string $path, string $baseDir): array
    {
        $entries = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 3));

            if (count($parts) < 3 || $parts[2] === '') {
                $this->warn('Skipped a line that is not label|group|image: '.mb_strimwidth($line, 0, 60, '…'));

                continue;
            }

            [$label, $group, $file] = $parts;
            $label = in_array(strtolower($label), ['scam', 'phishing', 'bad'], true) ? 'scam' : 'legit';
            $full = preg_match('#^([A-Za-z]:[\\\\/]|/)#', $file) ? $file : $baseDir.DIRECTORY_SEPARATOR.$file;

            $entries[] = [$label, strtolower($group) ?: null, $file, $full];
        }

        return $entries;
    }

    /** A scam is caught when judged suspicious or phishing; a legit image is correct unless it is warned about. "review" counts as not flagged. */
    private function isCorrect(string $expected, string $verdict): ?bool
    {
        if ($verdict === 'error') {
            return null;
        }

        $flagged = in_array($verdict, ['suspicious', 'phishing'], true);

        return $expected === 'scam' ? $flagged : ! $flagged;
    }

    private function summary(array $rows): void
    {
        $scored = array_values(array_filter($rows, fn ($r) => $r['verdict'] !== 'error'));
        $asClean = array_map(fn ($r) => $r['verdict'] === 'review' ? array_merge($r, ['verdict' => 'clean']) : $r, $scored);

        $c = BatchMetrics::confusion($asClean);
        $r = BatchMetrics::rates($c);
        $reviews = count(array_filter($rows, fn ($x) => $x['verdict'] === 'review'));
        $errors = count($rows) - count($scored);

        $this->info('Results');

        if ($c['tp'] + $c['fn'] > 0) {
            $this->line(sprintf('  Scam screenshots caught:  %d of %d (%s)', $c['tp'], $c['tp'] + $c['fn'], BatchMetrics::pct($r['recall'])));
        }
        if ($c['fp'] + $c['tn'] > 0) {
            $this->line(sprintf('  False alarms:             %d of %d legitimate screenshots (%s)', $c['fp'], $c['fp'] + $c['tn'], BatchMetrics::pct($r['false_positive_rate'])));
        }
        if ($reviews > 0) {
            $this->line("  {$reviews} screenshot(s) came back as \"needs manual review\" and count as not flagged.");
        }
        if ($errors) {
            $this->warn("  {$errors} scan(s) failed and are not counted.");
        }
    }

    private function saveCsv(array $rows): void
    {
        $file = $this->option('csv') ?: storage_path('app/scan-screenshots-'.date('Ymd-His').'.csv');
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($file, 'w');
        fputcsv($handle, ['expected', 'group', 'image', 'score', 'verdict', 'confidence', 'found', 'correct', 'details'], ',', '"', '');

        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['expected'], $r['category'], $r['url'], $r['score'], $r['verdict'], $r['confidence'], $r['found'],
                $r['correct'] === null ? '' : ($r['correct'] ? 'yes' : 'no'),
                implode(' / ', $r['why']),
            ], ',', '"', '');
        }

        fclose($handle);
        $this->line("  Saved results: {$file}");
    }
}