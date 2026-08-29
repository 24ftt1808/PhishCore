<?php

namespace App\Console\Commands;

use App\Services\AnalysisEngine;
use Illuminate\Console\Command;

class TestContentCheck extends Command
{
    protected $signature = 'phishcore:test-content';
    protected $description = 'Batch-tests checkPageContent() against a curated list of URLs to catch false positives/negatives before relying on it in production scans.';

    /**
     * Each entry: [category label, URL, what we expect to happen]
     * Add more URLs here over time as you think of edge cases — this file
     * is meant to grow into your regression test set for this check.
     */
    protected array $testUrls = [
        // Category 1: Legitimate brand login pages — should NOT flag brand impersonation
        ['Brand login', 'https://accounts.google.com', 'No brand mismatch flag'],
        ['Brand login', 'https://www.facebook.com/login', 'No brand mismatch flag'],
        ['Brand login', 'https://www.amazon.com', 'No brand mismatch flag'],

        // Category 2: Informational pages about brands — should NOT flag at all
        ['Info about brand', 'https://en.wikipedia.org/wiki/PayPal', 'Clean — no password field present'],
        ['Info about brand', 'https://en.wikipedia.org/wiki/Amazon_(company)', 'Clean — no password field present'],
        ['Info about brand', 'https://en.wikipedia.org/wiki/Microsoft', 'Clean — no password field present'],

        // Category 3: Unrelated login forms — should NOT flag brand impersonation (no brand match)
        ['Unrelated login', 'https://github.com/login', 'No brand mismatch (github not in brand list)'],

        // Baseline clean sites — should always be fully clean
        ['Baseline clean', 'https://www.pb.edu.bn', 'Fully clean'],
        ['Baseline clean', 'https://www.wikipedia.org', 'Fully clean'],

        // Category 5: Real, currently-active phishing URLs targeting the
        // NEWLY ADDED brand categories, sourced from OpenPhish's public feed
        // on 2026-08-29. These change fast — some may already be dead by
        // the time you run this. Note: threat feeds occasionally include
        // GitHub Pages student coding-practice "clone" projects alongside
        // genuine phishing, so a FLAGGED result here is expected/correct
        // either way (the page really does impersonate the brand visually,
        // regardless of the original author's intent).
        ['Real phishing - WhatsApp', 'https://www.whtsapp-serve.chat/', 'Likely FLAG (WhatsApp brand + probable login form)'],
        ['Real phishing - Steam', 'https://satyansh-yadav0812.github.io/steam-web-clone/', 'Likely FLAG (Steam brand + login form) — may be a coding-practice clone, not confirmed malicious'],
        ['Real phishing - Coinbase (typosquat)', 'https://secure.zoinbase.live/', 'Likely FLAG (Coinbase brand text even though domain itself is a typosquat)'],
    ];

    public function handle(): int
    {
        $engine = new AnalysisEngine();
        $rows = [];
        $durations = [];

        foreach ($this->testUrls as [$category, $url, $expected]) {
            $this->line("Testing: {$url}...");

            $start = microtime(true);
            $result = $engine->checkPageContent($url);
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $durations[] = $durationMs;

            $status = $result['unavailable'] ?? false
                ? 'UNAVAILABLE'
                : ($result['flagged'] ? 'FLAGGED' : 'clean');

            $rows[] = [
                $category,
                $url,
                $status,
                $result['points'],
                "{$durationMs}ms",
                $expected,
                implode(' | ', $result['reasons']),
            ];

            // Small pause so we're not hammering these sites back-to-back —
            // polite scraping practice, and avoids tripping rate limits
            // that could taint results (a 429 looks identical to a real
            // fetch failure in our data).
            usleep(500000);
        }

        $this->table(
            ['Category', 'URL', 'Result', 'Points', 'Time', 'Expected', 'Reasons'],
            $rows
        );

        $avgMs = (int) round(array_sum($durations) / count($durations));
        $maxMs = max($durations);
        $minMs = min($durations);
        $totalMs = array_sum($durations);

        $this->newLine();
        $this->info('Performance summary across ' . count($this->testUrls) . ' URLs:');
        $this->line("  Average: {$avgMs}ms | Min: {$minMs}ms | Max: {$maxMs}ms | Total: {$totalMs}ms");

        return self::SUCCESS;
    }
}