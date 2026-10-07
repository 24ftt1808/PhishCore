<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use App\Support\BatchMetrics;
use Illuminate\Console\Command;

class ScanEmails extends Command
{
    protected $signature = 'scan:emails
        {file : Text file with one email per line: label|group|sender|subject|body (label is scam or legit)}
        {--delay=0 : Seconds to wait between scans}
        {--csv= : Where to save the results (default: storage/app/scan-emails-<timestamp>.csv)}';

    protected $description = 'Runs a list of emails (sender, subject, body) through the email scan and reports detection rate and false alarms. Nothing is saved to the database.';

    public function handle(AnalysisEngine $engine): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $entries = $this->readEntries($path);

        if ($entries === []) {
            $this->error('No usable lines. Use: scam|group|sender@example.com|Subject|Body text (lines starting with # are ignored).');

            return self::FAILURE;
        }

        $delay = max(0, (int) $this->option('delay'));
        $rows = [];

        foreach ($entries as $i => [$expected, $group, $sender, $subject, $body]) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, count($entries), $sender));

            try {
                $result = $engine->analyze('email', null, $sender, null, null, null, $subject, $body);
                $score = (int) $result['risk_score'];
                $verdict = (string) $result['verdict'];
                $why = collect($result['checks'] ?? [])
                    ->filter(fn ($c) => ($c['points'] ?? 0) > 0)
                    ->map(fn ($c) => sprintf('%s (+%d): %s', $c['name'], $c['points'], $c['message']))
                    ->values()->all();
            } catch (\Throwable $e) {
                $score = null;
                $verdict = 'error';
                $why = [];
                $this->warn('  failed: '.$e->getMessage());
            }

            $rows[] = [
                'expected' => $expected,
                'category' => $group,
                'url' => $sender,
                'subject' => $subject,
                'score' => $score,
                'verdict' => $verdict,
                'correct' => $this->isCorrect($expected, $verdict),
                'why' => $why,
            ];

            if ($delay > 0 && $i < count($entries) - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->table(
            ['Expected', 'Group', 'Sender', 'Subject', 'Score', 'Verdict', 'Correct'],
            array_map(fn ($r) => [
                $r['expected'],
                $r['category'] ?? '-',
                mb_strimwidth($r['url'], 0, 34, '…'),
                mb_strimwidth($r['subject'], 0, 30, '…'),
                $r['score'] ?? '-',
                $r['verdict'],
                $r['correct'] === null ? '-' : ($r['correct'] ? 'yes' : 'NO'),
            ], $rows)
        );

        foreach ($rows as $r) {
            if ($r['correct'] === false) {
                $this->newLine();
                $this->warn("Wrong: {$r['url']} (expected {$r['expected']}, got {$r['verdict']} {$r['score']})");
                foreach ($r['why'] ?: ['No check added any points.'] as $line) {
                    $this->line('  - '.$line);
                }
            }
        }

        $this->newLine();
        $this->summary($rows);
        $this->saveCsv($rows);

        return self::SUCCESS;
    }

    /** @return array<int, array{0: string, 1: ?string, 2: string, 3: string, 4: string}> expected, group, sender, subject, body */
    private function readEntries(string $path): array
    {
        $entries = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // The body is the last field, so it may contain "|" itself.
            $parts = array_map('trim', explode('|', $line, 5));

            if (count($parts) < 5 || ! str_contains($parts[2], '@')) {
                $this->warn('Skipped a line that is not label|group|sender|subject|body: '.mb_strimwidth($line, 0, 60, '…'));

                continue;
            }

            [$label, $group, $sender, $subject, $body] = $parts;
            $label = in_array(strtolower($label), ['scam', 'phishing', 'bad'], true) ? 'scam' : 'legit';

            $entries[] = [$label, strtolower($group) ?: null, $sender, $subject, $body];
        }

        return $entries;
    }

    /** A legit email is correct when judged clean; a scam is caught when judged suspicious or phishing. */
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
            $this->line(sprintf('  False alarms:  %d of %d legitimate emails (%s)', $c['fp'], $c['fp'] + $c['tn'], BatchMetrics::pct($r['false_positive_rate'])));
        }
        if ($r['total'] > 0) {
            $this->line(sprintf('  Overall accuracy: %d of %d (%s)', $c['tp'] + $c['tn'], $r['total'], BatchMetrics::pct($r['accuracy'])));
            $this->line(sprintf('  Precision: %s   F1: %s', BatchMetrics::pct($r['precision']), $r['f1'] === null ? 'n/a' : sprintf('%.3f', $r['f1'])));
        }
        if ($errors) {
            $this->warn("  {$errors} scan(s) failed and are not counted.");
        }
    }

    private function saveCsv(array $rows): void
    {
        $file = $this->option('csv') ?: storage_path('app/scan-emails-'.date('Ymd-His').'.csv');
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($file, 'w');
        fputcsv($handle, ['expected', 'group', 'sender', 'subject', 'score', 'verdict', 'correct'], ',', '"', '');

        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['expected'], $r['category'], $r['url'], $r['subject'], $r['score'], $r['verdict'],
                $r['correct'] === null ? '' : ($r['correct'] ? 'yes' : 'no'),
            ], ',', '"', '');
        }

        fclose($handle);
        $this->line("  Saved results: {$file}");
    }
}