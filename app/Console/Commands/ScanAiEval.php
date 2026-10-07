<?php

namespace App\Console\Commands;

use App\Services\AiTextCheck;
use App\Services\AnalysisEngine;
use App\Support\BatchMetrics;
use Illuminate\Console\Command;

class ScanAiEval extends Command
{
    protected $signature = 'scan:ai-eval
        {file : JSON file: a list of {"label": "scam|legit|ham", "from": "sender@example.com", "text": "subject and body"}}
        {--delay=7 : Seconds to wait between AI calls (free keys allow only a few calls a minute)}
        {--limit=0 : Only run the first N messages (0 = all)}
        {--csv= : Where to save the results (default: storage/app/scan-ai-eval-<timestamp>.csv)}';

    protected $description = 'Compares the email scan with and without the AI text check on a labelled set of messages. Needs AI_TEXT_CHECK=true and GEMINI_API_KEY in .env. Nothing is saved to the database.';

    public function handle(AnalysisEngine $engine, AiTextCheck $ai): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $cases = json_decode((string) file_get_contents($path), true);

        if (! is_array($cases) || $cases === []) {
            $this->error('The file is not a JSON list of messages.');

            return self::FAILURE;
        }

        if (! $ai->enabled()) {
            $this->error('Set AI_TEXT_CHECK=true and GEMINI_API_KEY=... in .env, then run php artisan config:clear.');

            return self::FAILURE;
        }

        if ((int) $this->option('limit') > 0) {
            $cases = array_slice($cases, 0, (int) $this->option('limit'));
        }

        $delay = max(0, (int) $this->option('delay'));
        $rulesRows = [];
        $bothRows = [];
        $csv = [];
        $unavailable = 0;

        foreach ($cases as $i => $case) {
            $expected = in_array(strtolower((string) ($case['label'] ?? '')), ['scam', 'phishing', 'bad'], true) ? 'scam' : 'legit';
            $sender = (string) ($case['from'] ?? '');
            $text = (string) ($case['text'] ?? '');
            $this->line(sprintf('[%d/%d] %s %s', $i + 1, count($cases), $expected, mb_strimwidth($sender, 0, 40, '…')));

            try {
                // Rules only: the AI step inside the engine is switched off for this call.
                config(['services.ai_text.enabled' => false]);
                $result = $engine->analyze('email', null, $sender !== '' ? $sender : 'unknown@example.com', null, null, null, null, $text);
                config(['services.ai_text.enabled' => true]);
                $rules = (int) $result['risk_score'];
            } catch (\Throwable $e) {
                config(['services.ai_text.enabled' => true]);
                $this->warn('  failed: '.$e->getMessage());

                continue;
            }

            $aiResult = $ai->assess($text);
            for ($try = 0; $try < 3 && ! $aiResult['available'] && str_contains($aiResult['reason'], 'busy'); $try++) {
                $this->warn('  AI service busy, waiting 40 seconds…');
                sleep(40);
                $aiResult = $ai->assess($text);
            }

            if (! $aiResult['available']) {
                $unavailable++;
                $this->warn('  AI unavailable: '.$aiResult['reason']);
            }

            $combined = min(100, $rules + $aiResult['points']);
            $verdictOf = fn (int $s) => $s >= 60 ? 'phishing' : ($s >= 25 ? 'suspicious' : 'clean');

            $rulesRows[] = ['expected' => $expected, 'verdict' => $verdictOf($rules)];
            $bothRows[] = ['expected' => $expected, 'verdict' => $verdictOf($combined)];

            $csv[] = [$expected, $sender, $rules, $aiResult['verdict'] ?? 'unavailable', $aiResult['confidence'] ?? '', $aiResult['points'], $combined, $verdictOf($rules), $verdictOf($combined), $aiResult['reason']];

            if ($delay > 0 && $i < count($cases) - 1) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->report('Rules only', $rulesRows);
        $this->report('Rules + AI check', $bothRows);

        if ($unavailable > 0) {
            $this->warn("The AI check was unavailable for {$unavailable} message(s); those count as rules-only in the second block.");
        }

        $file = $this->option('csv') ?: storage_path('app/scan-ai-eval-'.date('Ymd-His').'.csv');
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        $handle = fopen($file, 'w');
        fputcsv($handle, ['expected', 'sender', 'rule_score', 'ai_verdict', 'ai_confidence', 'ai_points', 'combined_score', 'rules_verdict', 'combined_verdict', 'ai_reason'], ',', '"', '');
        foreach ($csv as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
        fclose($handle);
        $this->line("Saved results: {$file}");

        return self::SUCCESS;
    }

    private function report(string $title, array $rows): void
    {
        $c = BatchMetrics::confusion($rows);
        $r = BatchMetrics::rates($c);

        $this->info($title);
        $this->line(sprintf('  Scams caught:  %d of %d (%s)', $c['tp'], $c['tp'] + $c['fn'], BatchMetrics::pct($r['recall'])));
        $this->line(sprintf('  False alarms:  %d of %d real messages (%s)', $c['fp'], $c['fp'] + $c['tn'], BatchMetrics::pct($r['false_positive_rate'])));
        $this->line(sprintf('  Precision: %s   F1: %s', BatchMetrics::pct($r['precision']), $r['f1'] === null ? 'n/a' : sprintf('%.3f', $r['f1'])));
        $this->newLine();
    }
}