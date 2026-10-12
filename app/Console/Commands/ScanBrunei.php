<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use Illuminate\Console\Command;

class ScanBrunei extends Command
{
    protected $signature = 'scan:brunei
        {file? : Optional JSON list of {"label": "scam|legit", "lang": "en|ms|bm", "text": "..."} to test instead, e.g. real Brunei samples}
        {--show : List every message with its score}';

    protected $description = 'Runs the Brunei sample set (or your own file) through the message rules and prints how many scams were caught and how many normal messages were wrongly flagged. Rules only: no network, phone or AI checks. Nothing is saved.';

    public function handle(AnalysisEngine $engine): int
    {
        $groups = $this->loadGroups();
        if ($groups === null) {
            return self::FAILURE;
        }

        foreach ($groups as $name => $rows) {
            $byLang = [];
            $totals = ['scam' => [0, 0], 'legit' => [0, 0]];
            $lines = [];

            foreach ($rows as $row) {
                [$label, $type, $lang, $text] = $row;
                $result = $engine->scoreMessageRules($text);
                $flagged = $result['score'] >= 25;
                $totals[$label][0] += $flagged ? 1 : 0;
                $totals[$label][1]++;
                $byLang[$label][$lang][0] = ($byLang[$label][$lang][0] ?? 0) + ($flagged ? 1 : 0);
                $byLang[$label][$lang][1] = ($byLang[$label][$lang][1] ?? 0) + 1;
                $lines[] = [$label, $type, $lang, $result['score'], $result['verdict'], mb_strimwidth($text, 0, 70, '…')];
            }

            $this->newLine();
            $this->info(strtoupper(str_replace('_', ' ', $name)));
            $table = [];
            foreach (['scam' => 'Scams caught', 'legit' => 'Normal messages wrongly flagged'] as $label => $title) {
                if ($totals[$label][1] === 0) {
                    continue;
                }
                $langs = collect($byLang[$label] ?? [])->map(fn ($v, $l) => "{$l} {$v[0]}/{$v[1]}")->implode(', ');
                $table[] = [$title, "{$totals[$label][0]}/{$totals[$label][1]}", $langs];
            }
            $this->table(['', 'Count', 'By language'], $table);

            if ($this->option('show')) {
                $this->table(['Label', 'Type', 'Lang', 'Score', 'Verdict', 'Message'], $lines);
            }
        }

        $set = require base_path('tests/Fixtures/brunei-messages.php');
        if (! $this->argument('file')) {
            $fake = collect($set['fake_domains'])->filter(fn ($d) => $engine->checkUrlSyntax('https://'.$d)['flagged'])->count();
            $real = collect($set['real_domains'])->filter(fn ($d) => $engine->checkUrlSyntax('https://'.$d)['flagged'])->count();
            $this->newLine();
            $this->info('WEB ADDRESSES (address check only)');
            $this->table(['', 'Count'], [
                ['Look-alike addresses flagged', "{$fake}/".count($set['fake_domains'])],
                ['Real Brunei sites wrongly flagged', "{$real}/".count($set['real_domains'])],
            ]);
        }

        $this->newLine();
        $this->line('The built-in samples are made up, so these are not real-world accuracy figures. Run with a file of real Brunei messages for that.');

        return self::SUCCESS;
    }

    /** @return array<string, array<int, array>>|null */
    private function loadGroups(): ?array
    {
        $path = $this->argument('file');
        if (! $path) {
            $set = require base_path('tests/Fixtures/brunei-messages.php');

            return ['tuning' => $set['tuning'], 'held_out' => $set['held_out'], 'normal_extra' => $set['normal_extra']];
        }

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return null;
        }

        $cases = json_decode((string) file_get_contents($path), true);
        if (! is_array($cases) || $cases === []) {
            $this->error('The file is not a JSON list of messages.');

            return null;
        }

        $rows = [];
        foreach ($cases as $case) {
            $label = in_array(strtolower((string) ($case['label'] ?? '')), ['scam', 'phishing', 'bad'], true) ? 'scam' : 'legit';
            $rows[] = [$label, (string) ($case['type'] ?? '-'), (string) ($case['lang'] ?? '-'), (string) ($case['text'] ?? '')];
        }

        return [basename($path) => $rows];
    }
}