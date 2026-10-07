<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use Illuminate\Console\Command;

class ScanBatch extends Command
{
    protected $signature = 'scan:batch
        {file : Text file with one entry per line: "legit|https://example.com" or "scam|https://bad.example"}
        {--delay=0 : Seconds to wait between scans (VirusTotal free keys allow about 4 lookups a minute)}
        {--csv= : Where to save the results (default: storage/app/scan-batch-<timestamp>.csv)}';

    protected $description = 'Runs a list of URLs through the analysis engine and reports detection rate and false alarms. Nothing is saved to the database.';

    public function handle(AnalysisEngine $engine): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $entries = $this->readEntries($path);

        if ($entries === []) {
            $this->error('No usable lines. Use "legit|https://..." or "scam|https://..." (lines starting with # are ignored).');

            return self::FAILURE;
        }

        $delay = max(0, (int) $this->option('delay'));
        $rows = [];

        foreach ($entries as $i => [$expected, $url]) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, count($entries), $url));

            try {
                $result = $engine->analyze('url', $url);
                $score = (int) $result['risk_score'];
                $verdict = (string) $result['verdict'];
                $unknown = collect($result['checks'] ?? [])->where('status', 'UNKNOWN')->count();
                $flagged = collect($result['checks'] ?? [])
                    ->filter(fn ($c) => ($c['points'] ?? 0) > 0)
                    ->map(fn ($c) => sprintf('%s (+%d): %s', $c['name'], $c['points'], $c['message']))
                    ->values()->all();
            } catch (\Throwable $e) {
                $score = null;
                $verdict = 'error';
                $unknown = 0;
                $flagged = [];
                $this->warn('  failed: '.$e->getMessage());
            }

            $rows[] = [
                'expected' => $expected,
                'url' => $url,
                'score' => $score,
                'verdict' => $verdict,
                'unknown_checks' => $unknown,
                'correct' => $this->isCorrect($expected, $verdict),
                'why' => $flagged,
            ];

            if ($delay > 0 && $i < count($entries) - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->table(
            ['Expected', 'URL', 'Score', 'Verdict', 'Unknown', 'Correct'],
            array_map(fn ($r) => [
                $r['expected'],
                mb_strimwidth($r['url'], 0, 55, '…'),
                $r['score'] ?? '-',
                $r['verdict'],
                $r['unknown_checks'],
                $r['correct'] === null ? '-' : ($r['correct'] ? 'yes' : 'NO'),
            ], $rows)
        );

        foreach ($rows as $r) {
            if ($r['correct'] === false) {
                $this->newLine();
                $this->warn("Wrong: {$r['url']} (expected {$r['expected']}, got {$r['verdict']} {$r['score']})");
                foreach ($r['why'] as $why) {
                    $this->line('  - '.$why);
                }
            }
        }

        $this->newLine();
        $this->summary($rows);
        $this->saveCsv($rows);

        return self::SUCCESS;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function readEntries(string $path): array
    {
        $entries = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '|')) {
                [$label, $url] = array_map('trim', explode('|', $line, 2));
                $label = strtolower($label);
                $label = in_array($label, ['scam', 'phishing', 'bad'], true) ? 'scam' : 'legit';
            } else {
                $label = 'legit';
                $url = $line;
            }

            if (! preg_match('#^https?://#i', $url)) {
                $url = 'https://'.$url;
            }

            $entries[] = [$label, $url];
        }

        return $entries;
    }

    /** A legit site is correct when judged clean; a scam is caught when judged suspicious or phishing. */
    private function isCorrect(string $expected, string $verdict): ?bool
    {
        if ($verdict === 'error') {
            return null;
        }

        return $expected === 'scam' ? $verdict !== 'clean' : $verdict === 'clean';
    }

    private function summary(array $rows): void
    {
        $scored = array_filter($rows, fn ($r) => $r['correct'] !== null);
        $scams = array_filter($scored, fn ($r) => $r['expected'] === 'scam');
        $legit = array_filter($scored, fn ($r) => $r['expected'] === 'legit');

        $caught = count(array_filter($scams, fn ($r) => $r['correct']));
        $falseAlarms = count(array_filter($legit, fn ($r) => ! $r['correct']));
        $errors = count($rows) - count($scored);

        $this->info('Results');
        if ($scams) {
            $this->line(sprintf('  Scams caught:  %d of %d (%.1f%%)', $caught, count($scams), $caught / count($scams) * 100));
        }
        if ($legit) {
            $this->line(sprintf('  False alarms:  %d of %d legitimate sites (%.1f%%)', $falseAlarms, count($legit), $falseAlarms / count($legit) * 100));
        }
        if ($scored) {
            $right = count(array_filter($scored, fn ($r) => $r['correct']));
            $this->line(sprintf('  Overall accuracy: %d of %d (%.1f%%)', $right, count($scored), $right / count($scored) * 100));
        }
        if ($errors) {
            $this->warn("  {$errors} scan(s) failed and are not counted.");
        }
    }

    private function saveCsv(array $rows): void
    {
        $file = $this->option('csv') ?: storage_path('app/scan-batch-'.date('Ymd-His').'.csv');
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($file, 'w');
        fputcsv($handle, ['expected', 'url', 'score', 'verdict', 'unknown_checks', 'correct'], ',', '"', '');

        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['expected'], $r['url'], $r['score'], $r['verdict'], $r['unknown_checks'],
                $r['correct'] === null ? '' : ($r['correct'] ? 'yes' : 'no'),
            ], ',', '"', '');
        }

        fclose($handle);
        $this->line("  Saved: {$file}");
    }
}