<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use App\Support\BatchMetrics;
use Illuminate\Console\Command;

class ScanPhones extends Command
{
    protected $signature = 'scan:phones
        {file : Text file with one number per line: label|group|number (label is scam, legit or unknown)}
        {--delay=1 : Seconds to wait between scans (the AbstractAPI free plan allows about 1 lookup a second)}
        {--no-api : Switch off the AbstractAPI lookup so only PhishCore\'s own checks decide}
        {--csv= : Where to save the results (default: storage/app/scan-phones-<timestamp>.csv)}';

    protected $description = 'Runs a list of phone numbers through the phone scan and shows each score and the reasons. Numbers labelled scam or legit are also counted in the accuracy figures. Nothing is saved to the database.';

    public function handle(AnalysisEngine $engine): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $entries = $this->readEntries($path);

        if ($entries === []) {
            $this->error('No usable lines. Use: scam|group|+6737123456 (lines starting with # are ignored).');

            return self::FAILURE;
        }

        if ($this->option('no-api')) {
            config(['services.abstractapi_phone.key' => null]);
            $this->warn('AbstractAPI is switched off for this run.');
        } elseif (! config('services.abstractapi_phone.key')) {
            $this->warn('ABSTRACTAPI_PHONE_KEY is not set, so the reputation check will be skipped.');
        }

        $delay = max(0, (int) $this->option('delay'));
        $rows = [];

        foreach ($entries as $i => [$expected, $group, $number]) {
            $this->line(sprintf('[%d/%d] %s', $i + 1, count($entries), $number));

            try {
                $result = $engine->analyze('phone', null, null, $number);
                $score = (int) $result['risk_score'];
                $verdict = (string) $result['verdict'];
                $checks = collect($result['checks'] ?? []);
                $why = $checks
                    ->filter(fn ($c) => ($c['points'] ?? 0) > 0)
                    ->map(fn ($c) => sprintf('%s (+%d): %s', $c['name'], $c['points'], $c['message']))
                    ->values()->all();
                $apiStatus = (string) ($checks->firstWhere('name', 'Phone Reputation (AbstractAPI)')['status'] ?? '');
            } catch (\Throwable $e) {
                $score = null;
                $verdict = 'error';
                $why = [];
                $apiStatus = '';
                $this->warn('  failed: '.$e->getMessage());
            }

            $rows[] = [
                'expected' => $expected,
                'category' => $group,
                'url' => $number,
                'score' => $score,
                'verdict' => $verdict,
                'api' => $apiStatus,
                'correct' => $this->isCorrect($expected, $verdict),
                'why' => $why,
            ];

            if ($delay > 0 && $i < count($entries) - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->table(
            ['Expected', 'Group', 'Number', 'Score', 'Verdict', 'AbstractAPI', 'Correct'],
            array_map(fn ($r) => [
                $r['expected'],
                $r['category'] ?? '-',
                $r['url'],
                $r['score'] ?? '-',
                $r['verdict'],
                $r['api'] !== '' ? $r['api'] : '-',
                $r['correct'] === null ? '-' : ($r['correct'] ? 'yes' : 'NO'),
            ], $rows)
        );

        $this->newLine();
        $this->info('Why each number scored as it did');
        foreach ($rows as $r) {
            $this->line("  {$r['url']}  ->  {$r['verdict']} ".($r['score'] ?? ''));
            foreach ($r['why'] ?: ['No check added any points.'] as $line) {
                $this->line('      - '.mb_strimwidth($line, 0, 160, '…'));
            }
        }

        $this->newLine();
        $this->summary($rows);
        $this->saveCsv($rows);

        return self::SUCCESS;
    }

    /** @return array<int, array{0: string, 1: ?string, 2: string}> expected, group, number */
    private function readEntries(string $path): array
    {
        $entries = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));

            if (count($parts) === 1) {
                $label = 'unknown';
                $group = null;
                $number = $parts[0];
            } elseif (count($parts) === 2) {
                [$label, $number] = $parts;
                $group = null;
            } else {
                [$label, $group, $number] = $parts;
            }

            $label = strtolower($label);
            $label = in_array($label, ['scam', 'phishing', 'bad'], true) ? 'scam' : ($label === 'legit' ? 'legit' : 'unknown');

            $entries[] = [$label, $group !== null ? (strtolower($group) ?: null) : null, $number];
        }

        return $entries;
    }

    /** Only numbers labelled scam or legit are judged. "review" means the input was not a phone number at all. */
    private function isCorrect(string $expected, string $verdict): ?bool
    {
        if ($expected === 'unknown' || in_array($verdict, ['error', 'review'], true)) {
            return null;
        }

        return $expected === 'scam' ? $verdict !== 'clean' : $verdict === 'clean';
    }

    private function summary(array $rows): void
    {
        $judged = array_values(array_filter($rows, fn ($r) => $r['correct'] !== null));
        $unlabelled = count(array_filter($rows, fn ($r) => $r['expected'] === 'unknown'));

        $this->info('Results');

        if ($judged !== []) {
            $c = BatchMetrics::confusion($judged);
            $r = BatchMetrics::rates($c);

            if ($c['tp'] + $c['fn'] > 0) {
                $this->line(sprintf('  Scam numbers caught:  %d of %d (%s)', $c['tp'], $c['tp'] + $c['fn'], BatchMetrics::pct($r['recall'])));
            }
            if ($c['fp'] + $c['tn'] > 0) {
                $this->line(sprintf('  False alarms:         %d of %d legitimate numbers (%s)', $c['fp'], $c['fp'] + $c['tn'], BatchMetrics::pct($r['false_positive_rate'])));
            }
        } else {
            $this->line('  No numbers were labelled scam or legit, so there are no accuracy figures.');
        }

        if ($unlabelled > 0) {
            $this->line("  {$unlabelled} number(s) were labelled unknown and are shown above but not counted.");
        }
    }

    private function saveCsv(array $rows): void
    {
        $file = $this->option('csv') ?: storage_path('app/scan-phones-'.date('Ymd-His').'.csv');
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($file, 'w');
        fputcsv($handle, ['expected', 'group', 'number', 'score', 'verdict', 'abstractapi', 'correct', 'reasons'], ',', '"', '');

        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['expected'], $r['category'], $r['url'], $r['score'], $r['verdict'], $r['api'],
                $r['correct'] === null ? '' : ($r['correct'] ? 'yes' : 'no'),
                implode(' / ', $r['why']),
            ], ',', '"', '');
        }

        fclose($handle);
        $this->line("  Saved results: {$file}");
    }
}