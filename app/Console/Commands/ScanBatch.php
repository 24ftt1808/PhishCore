<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use App\Support\BatchMetrics;
use Illuminate\Console\Command;

class ScanBatch extends Command
{
    protected $signature = 'scan:batch
        {file : Text file with one entry per line: "legit|https://example.com" or "scam|https://bad.example". An optional group name goes in the middle: "scam|brunei|https://bad.example"}
        {--delay=0 : Seconds to wait between scans (VirusTotal free keys allow about 4 lookups a minute)}
        {--heuristics-only : Switch off the Google Safe Browsing and VirusTotal lookups so only PhishCore\'s own checks decide}
        {--csv= : Where to save the results (default: storage/app/scan-batch-<timestamp>.csv)}
        {--report= : Where to save the Markdown report (default: storage/app/scan-batch-<timestamp>.md)}';

    /** Engine check name => short key used in the CSV and report. */
    private const CHECK_KEYS = [
        'SSL Certificate' => 'ssl',
        'Domain Age' => 'domain_age',
        'URL Structure' => 'url_structure',
        'Blacklist Database' => 'blacklist',
        'VirusTotal / CTI Check' => 'virustotal',
        'IP Reputation & Location' => 'ip_reputation',
        'Redirect Chain' => 'redirect',
        'Page Content Analysis' => 'page_content',
        'Hosting Platform' => 'hosting',
        'Previous Reports (Domain)' => 'previous_reports',
    ];

    protected $description = 'Runs a list of URLs through the analysis engine and reports detection rate, false alarms and other accuracy figures. Nothing is saved to the database.';

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

        $heuristicsOnly = (bool) $this->option('heuristics-only');

        if ($heuristicsOnly) {
            // Google Safe Browsing and VirusTotal already know most URLs taken
            // from public phishing feeds, so leaving them on measures how well
            // those services remember the feed, not how well PhishCore's own
            // checks work. The engine reports both as "unavailable" without a key.
            config([
                'services.google_safe_browsing.key' => null,
                'services.virustotal.key' => null,
            ]);
            $this->warn('Heuristics-only run: Google Safe Browsing and VirusTotal are switched off.');
        }

        $delay = max(0, (int) $this->option('delay'));
        $stamp = date('Ymd-His');
        $rows = [];

        foreach ($entries as $i => [$expected, $category, $url]) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, count($entries), $url));

            try {
                $result = $engine->analyze('url', $url);
                $score = (int) $result['risk_score'];
                $verdict = (string) $result['verdict'];
                $unknown = collect($result['checks'] ?? [])->where('status', 'UNKNOWN')->count();
                $points = $this->pointsPerCheck($result['checks'] ?? []);
                $siteStatus = (string) (collect($result['checks'] ?? [])->firstWhere('name', 'Site Availability')['status'] ?? '');
                $flagged = collect($result['checks'] ?? [])
                    ->filter(fn ($c) => ($c['points'] ?? 0) > 0)
                    ->map(fn ($c) => sprintf('%s (+%d): %s', $c['name'], $c['points'], $c['message']))
                    ->values()->all();
            } catch (\Throwable $e) {
                $score = null;
                $verdict = 'error';
                $unknown = 0;
                $points = [];
                $siteStatus = '';
                $flagged = [];
                $this->warn('  failed: '.$e->getMessage());
            }

            $rows[] = [
                'expected' => $expected,
                'category' => $category,
                'url' => $url,
                'score' => $score,
                'verdict' => $verdict,
                'unknown_checks' => $unknown,
                'site_status' => $siteStatus,
                'points' => $points,
                'correct' => $this->isCorrect($expected, $verdict),
                'why' => $flagged,
            ];

            if ($delay > 0 && $i < count($entries) - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->table(
            ['Expected', 'Group', 'URL', 'Score', 'Verdict', 'Site', 'Unknown', 'Correct'],
            array_map(fn ($r) => [
                $r['expected'],
                $r['category'] ?? '-',
                mb_strimwidth($r['url'], 0, 50, '…'),
                $r['score'] ?? '-',
                $r['verdict'],
                $r['site_status'] !== '' ? $r['site_status'] : '-',
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
        $this->saveCsv($rows, $stamp);
        $this->saveReport($rows, $stamp, basename($path), $heuristicsOnly);

        return self::SUCCESS;
    }

    /** @return array<int, array{0: string, 1: ?string, 2: string}> expected, group, url */
    private function readEntries(string $path): array
    {
        $entries = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $category = null;

            if (str_contains($line, '|')) {
                [$label, $rest] = array_map('trim', explode('|', $line, 2));
                $label = strtolower($label);
                $label = in_array($label, ['scam', 'phishing', 'bad'], true) ? 'scam' : 'legit';

                // "group|url": a group name is a single plain word, so a URL
                // that happens to contain a "|" is not mistaken for one.
                if (preg_match('/^([A-Za-z0-9_-]+)\s*\|\s*(.+)$/', $rest, $m)) {
                    $category = strtolower($m[1]);
                    $rest = trim($m[2]);
                }

                $url = $rest;
            } else {
                $label = 'legit';
                $url = $line;
            }

            if (! preg_match('#^https?://#i', $url)) {
                $url = 'https://'.$url;
            }

            $entries[] = [$label, $category, $url];
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
        $c = BatchMetrics::confusion($rows);
        $r = BatchMetrics::rates($c);
        $errors = count($rows) - count(BatchMetrics::scored($rows));

        $this->info('Results');

        if ($c['tp'] + $c['fn'] > 0) {
            $this->line(sprintf('  Scams caught:  %d of %d (%s)', $c['tp'], $c['tp'] + $c['fn'], BatchMetrics::pct($r['recall'])));
        }
        if ($c['fp'] + $c['tn'] > 0) {
            $this->line(sprintf('  False alarms:  %d of %d legitimate sites (%s)', $c['fp'], $c['fp'] + $c['tn'], BatchMetrics::pct($r['false_positive_rate'])));
        }
        if ($r['total'] > 0) {
            $this->line(sprintf('  Overall accuracy: %d of %d (%s)', $c['tp'] + $c['tn'], $r['total'], BatchMetrics::pct($r['accuracy'])));
            $this->line(sprintf('  Precision: %s   F1: %s', BatchMetrics::pct($r['precision']), $r['f1'] === null ? 'n/a' : sprintf('%.3f', $r['f1'])));
        }
        $live = BatchMetrics::liveScams($rows);
        if ($live['dead'] > 0) {
            $this->line(sprintf(
                '  Scams still online: caught %d of %d (%s). %d more scam URL(s) were already offline or taken down.',
                $live['live_caught'], $live['live'], BatchMetrics::pct($live['live_rate']), $live['dead']
            ));
        }
        if ($errors) {
            $this->warn("  {$errors} scan(s) failed and are not counted.");
        }
    }

    /**
     * Points each check added, keyed by short name. Every check is listed
     * (0 when it added nothing) so the CSV columns always line up.
     *
     * @return array<string, int>
     */
    private function pointsPerCheck(array $checks): array
    {
        $out = array_fill_keys(array_values(self::CHECK_KEYS), 0);

        foreach ($checks as $check) {
            $key = self::CHECK_KEYS[$check['name'] ?? ''] ?? null;

            if ($key !== null) {
                $out[$key] = (int) ($check['points'] ?? 0);
            }
        }

        return $out;
    }

    private function saveCsv(array $rows, string $stamp): void
    {
        $file = $this->option('csv') ?: storage_path("app/scan-batch-{$stamp}.csv");
        $this->ensureDirectory($file);

        $handle = fopen($file, 'w');
        $keys = array_values(self::CHECK_KEYS);
        fputcsv($handle, array_merge(['expected', 'group', 'url', 'score', 'verdict', 'site_status', 'unknown_checks', 'correct'], array_map(fn ($k) => "pts_{$k}", $keys)), ',', '"', '');

        foreach ($rows as $r) {
            fputcsv($handle, array_merge([
                $r['expected'], $r['category'], $r['url'], $r['score'], $r['verdict'], $r['site_status'], $r['unknown_checks'],
                $r['correct'] === null ? '' : ($r['correct'] ? 'yes' : 'no'),
            ], array_map(fn ($k) => $r['points'][$k] ?? '', $keys)), ',', '"', '');
        }

        fclose($handle);
        $this->line("  Saved results: {$file}");
    }

    private function saveReport(array $rows, string $stamp, string $source, bool $heuristicsOnly): void
    {
        $file = $this->option('report') ?: storage_path("app/scan-batch-{$stamp}.md");
        $this->ensureDirectory($file);

        file_put_contents($file, BatchMetrics::toMarkdown($rows, [
            'generated' => now()->format('Y-m-d H:i').' ('.config('app.timezone').')',
            'mode' => $heuristicsOnly
                ? "Heuristics only (Google Safe Browsing and VirusTotal switched off)"
                : 'Full engine',
            'source' => $source,
        ]));

        $this->line("  Saved report:  {$file}");
    }

    private function ensureDirectory(string $file): void
    {
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}