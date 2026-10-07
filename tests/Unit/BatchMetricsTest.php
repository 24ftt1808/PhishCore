<?php

use App\Support\BatchMetrics;

/**
 * 10 scams (8 flagged, 2 missed), 10 legitimate sites (1 false alarm) and one
 * failed scan that must be ignored. The expected figures below were worked
 * out by hand from these rows.
 */
function batchSampleRows(): array
{
    $rows = [];
    $add = function (string $expected, string $category, string $verdict, ?int $score) use (&$rows) {
        $rows[] = [
            'expected' => $expected,
            'category' => $category,
            'url' => 'https://'.$expected.count($rows).'.example/login',
            'score' => $score,
            'verdict' => $verdict,
        ];
    };

    foreach ([90, 85, 80, 75, 65] as $score) {
        $add('scam', 'global', 'phishing', $score);
    }
    foreach ([50, 40, 30] as $score) {
        $add('scam', 'brunei', 'suspicious', $score);
    }
    foreach ([10, 5] as $score) {
        $add('scam', 'brunei', 'clean', $score);
    }
    foreach ([0, 0, 5, 5, 10, 10, 15, 20, 24] as $score) {
        $add('legit', 'global', 'clean', $score);
    }
    $add('legit', 'brunei-other', 'suspicious', 27);

    $rows[] = ['expected' => 'scam', 'category' => 'global', 'url' => 'https://failed.example', 'score' => null, 'verdict' => 'error'];

    return $rows;
}

test('the confusion matrix counts scams caught and legitimate sites flagged, ignoring failed scans', function () {
    expect(BatchMetrics::confusion(batchSampleRows()))
        ->toBe(['tp' => 8, 'fn' => 2, 'fp' => 1, 'tn' => 9]);
});

test('the headline rates match the hand-worked figures', function () {
    $rates = BatchMetrics::rates(BatchMetrics::confusion(batchSampleRows()));

    expect($rates['total'])->toBe(20)
        ->and($rates['accuracy'])->toEqualWithDelta(0.85, 1e-9)
        ->and($rates['recall'])->toEqualWithDelta(0.8, 1e-9)
        ->and($rates['precision'])->toEqualWithDelta(8 / 9, 1e-9)
        ->and($rates['specificity'])->toEqualWithDelta(0.9, 1e-9)
        ->and($rates['false_positive_rate'])->toEqualWithDelta(0.1, 1e-9)
        ->and($rates['false_negative_rate'])->toEqualWithDelta(0.2, 1e-9)
        ->and($rates['f1'])->toEqualWithDelta(0.8421, 0.0001);
});

test('results are split by group', function () {
    $groups = array_column(BatchMetrics::byCategory(batchSampleRows()), null, 'category');

    expect($groups['brunei'])->toMatchArray(['scams' => 5, 'caught' => 3, 'legit' => 0])
        ->and($groups['global'])->toMatchArray(['scams' => 5, 'caught' => 5, 'legit' => 9, 'false_alarms' => 0])
        ->and($groups['brunei-other'])->toMatchArray(['legit' => 1, 'false_alarms' => 1]);
});

test('the verdicts given are counted for each kind of site', function () {
    $counts = BatchMetrics::verdictCounts(batchSampleRows());

    expect($counts['scam'])->toBe(['clean' => 2, 'suspicious' => 3, 'phishing' => 5, 'review' => 0])
        ->and($counts['legit'])->toBe(['clean' => 9, 'suspicious' => 1, 'phishing' => 0, 'review' => 0]);
});

test('the threshold table shows what other cut-offs would have done', function () {
    $sweep = array_column(BatchMetrics::thresholdSweep(batchSampleRows()), null, 'threshold');

    // 25 is the engine's own "suspicious" line, so it must agree with the verdicts.
    expect($sweep[25]['recall'])->toEqualWithDelta(0.8, 1e-9)
        ->and($sweep[25]['false_positive_rate'])->toEqualWithDelta(0.1, 1e-9)
        ->and($sweep[60]['recall'])->toEqualWithDelta(0.5, 1e-9)
        ->and($sweep[60]['false_positive_rate'])->toEqualWithDelta(0.0, 1e-9)
        ->and($sweep[10]['recall'])->toEqualWithDelta(0.9, 1e-9)
        ->and($sweep[10]['false_positive_rate'])->toEqualWithDelta(0.6, 1e-9);
});

test('missed scams and false alarms are listed, failed scans are not', function () {
    expect(BatchMetrics::misclassified(batchSampleRows()))->toHaveCount(3);
});

test('a batch with no legitimate sites gives n/a instead of crashing', function () {
    $rows = [['expected' => 'scam', 'category' => null, 'url' => 'https://a.example', 'score' => 70, 'verdict' => 'phishing']];
    $rates = BatchMetrics::rates(BatchMetrics::confusion($rows));

    expect($rates['false_positive_rate'])->toBeNull()
        ->and($rates['specificity'])->toBeNull()
        ->and($rates['recall'])->toEqualWithDelta(1.0, 1e-9)
        ->and(BatchMetrics::byCategory($rows)[0]['category'])->toBe('uncategorised');
});

test('a batch where every scan failed and an empty batch do not crash', function () {
    $failed = [['expected' => 'scam', 'category' => null, 'url' => 'https://a.example', 'score' => null, 'verdict' => 'error']];

    expect(BatchMetrics::rates(BatchMetrics::confusion($failed))['accuracy'])->toBeNull()
        ->and(BatchMetrics::toMarkdown([]))->toBeString();
});

test('urls are defanged so they cannot be clicked from a report', function () {
    expect(BatchMetrics::defang('https://evil.example.com/a.php'))->toBe('hxxps://evil[.]example[.]com/a[.]php');
});

test('the markdown report has every section and no clickable links', function () {
    $markdown = BatchMetrics::toMarkdown(batchSampleRows(), [
        'generated' => '2026-10-07 15:00 (Asia/Brunei)',
        'mode' => 'Full engine',
        'source' => 'scan-list.txt',
    ]);

    expect($markdown)
        ->toContain('## Confusion matrix', '## Metrics', '## By group', '## Verdicts given', '## Risk-score threshold', '## Misclassified')
        ->toContain('| 25 (suspicious) |', '| 60 (phishing) |', '1 scan(s) failed')
        ->not->toMatch('#https?://#i');
});

/**
 * Seven scored rows with per-check points and site status. Worked out by hand:
 * scams A (60, live), B (30, live), C (0, offline, missed), D (40, taken down);
 * legitimate E (25, false alarm), F (5), G (0).
 */
function batchCheckRows(): array
{
    $row = fn (string $expected, int $score, string $verdict, string $site, array $points) => [
        'expected' => $expected,
        'category' => null,
        'url' => "https://{$expected}{$score}.example",
        'score' => $score,
        'verdict' => $verdict,
        'site_status' => $site,
        'points' => $points,
    ];

    return [
        $row('scam', 60, 'phishing', 'LIVE', ['ip_reputation' => 20, 'page_content' => 40]),
        $row('scam', 30, 'suspicious', 'LIVE', ['ip_reputation' => 20, 'hosting' => 10]),
        $row('scam', 0, 'clean', 'OFFLINE', []),
        $row('scam', 40, 'suspicious', 'TAKEN DOWN', ['url_structure' => 40]),
        $row('legit', 25, 'suspicious', 'LIVE', ['ip_reputation' => 20, 'redirect' => 5]),
        $row('legit', 5, 'clean', 'LIVE', ['ip_reputation' => 5]),
        $row('legit', 0, 'clean', 'LIVE', []),
        ['expected' => 'scam', 'category' => null, 'url' => 'https://failed.example', 'score' => null, 'verdict' => 'error', 'site_status' => '', 'points' => []],
    ];
}

test('detection is also reported for scams that were still online', function () {
    expect(BatchMetrics::liveScams(batchCheckRows()))
        ->toMatchArray(['live' => 2, 'live_caught' => 2, 'dead' => 2, 'dead_caught' => 1])
        ->and(BatchMetrics::liveScams(batchCheckRows())['live_rate'])->toEqualWithDelta(1.0, 1e-9);
});

test('rows without a site status count as live, and a list with no scams gives n/a', function () {
    expect(BatchMetrics::liveScams(batchSampleRows()))->toMatchArray(['live' => 10, 'dead' => 0])
        ->and(BatchMetrics::liveScams([['expected' => 'legit', 'category' => null, 'url' => 'https://a.example', 'score' => 0, 'verdict' => 'clean']])['live_rate'])->toBeNull();
});

test('each check shows how often it fired and how many verdicts hinged on it', function () {
    $impact = array_column(BatchMetrics::checkImpact(batchCheckRows()), null, 'check');

    expect($impact['ip_reputation'])->toMatchArray(['scam_fired' => 2, 'scam_depend' => 1, 'legit_fired' => 2, 'legit_depend' => 1])
        ->and($impact['page_content'])->toMatchArray(['scam_fired' => 1, 'scam_depend' => 1, 'legit_fired' => 0, 'legit_depend' => 0])
        ->and($impact['hosting'])->toMatchArray(['scam_fired' => 1, 'scam_depend' => 1])
        ->and($impact['url_structure'])->toMatchArray(['scam_fired' => 1, 'scam_depend' => 1])
        ->and($impact['redirect'])->toMatchArray(['scam_fired' => 0, 'legit_fired' => 1, 'legit_depend' => 1])
        ->and(array_keys($impact))->toBe(['ip_reputation', 'redirect', 'url_structure', 'page_content', 'hosting']);
});

test('check figures are skipped for rows that carry no per-check points', function () {
    expect(BatchMetrics::checkImpact(batchSampleRows()))->toBe([]);
});

test('the report shows the live-scams rate and the check table when that data exists', function () {
    $markdown = BatchMetrics::toMarkdown(batchCheckRows());

    expect($markdown)
        ->toContain('2 of the 4 scam URLs were already offline or taken down')
        ->toContain('| Detection rate, scams still online | 100.0% (2 of 2) |')
        ->toContain('## Which checks fired', '| IP reputation | 2 | 1 | 2 | 1 |')
        ->not->toMatch('#https?://#i');

    $plain = BatchMetrics::toMarkdown(batchSampleRows());

    expect($plain)->not->toContain('## Which checks fired')->not->toContain('scams still online');
});