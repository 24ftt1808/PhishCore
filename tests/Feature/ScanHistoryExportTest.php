<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;

function historyScan(User $owner, string $verdict, int $score, array $attributes = []): Report
{
    $report = Report::factory()->create($attributes + [
        'user_id' => $owner->id,
        'type' => 'url',
        'url' => 'http://history-'.uniqid().'.test/',
        'status' => 'completed',
    ]);

    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict, 'risk_score' => $score]);

    return $report;
}

/** @return array<int, array<int, string|null>> */
function exportedRows(string $csv): array
{
    $lines = array_filter(explode("\n", trim(ltrim($csv, "\xEF\xBB\xBF"))));

    return array_map(fn (string $line) => str_getcsv($line, ',', '"', ''), array_values($lines));
}

test('a guest cannot download a scan history', function () {
    $this->get(route('scan.history.export'))->assertRedirect(route('login'));
});

test('the export holds only the signed-in user\'s scans, with a header row and readable verdicts', function () {
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    historyScan($me, 'phishing', 91, ['url' => 'http://mine-bad.test/']);
    historyScan($me, 'clean', 4, ['url' => 'http://mine-good.test/']);
    historyScan($someoneElse, 'phishing', 88, ['url' => 'http://not-mine.test/']);

    $response = $this->actingAs($me)->get(route('scan.history.export'));

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
    expect($response->headers->get('content-disposition'))->toContain('phishcore-scan-history-');

    $csv = $response->streamedContent();
    $rows = exportedRows($csv);

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($rows[0])->toBe(['Reference', 'Type', 'Reported item', 'Scan result', 'Risk score', 'Date', 'Time'])
        ->and($rows)->toHaveCount(3)
        ->and($csv)->not->toContain('not-mine.test')
        ->and(collect($rows)->pluck(2)->all())->toContain('http://mine-bad.test/', 'http://mine-good.test/')
        ->and(collect($rows)->pluck(3)->all())->toContain('Phishing', 'Safe');
});

test('the export follows the filters on the history page and covers every page of results', function () {
    $me = User::factory()->create();

    foreach (range(1, 12) as $i) {
        historyScan($me, 'phishing', 80, ['url' => "http://bad-{$i}.test/"]);
    }
    historyScan($me, 'clean', 2, ['url' => 'http://fine.test/']);

    $phishing = exportedRows($this->actingAs($me)->get(route('scan.history.export', ['status' => 'phishing']))->streamedContent());
    $searched = exportedRows($this->actingAs($me)->get(route('scan.history.export', ['search' => 'fine']))->streamedContent());

    expect($phishing)->toHaveCount(13)
        ->and($searched)->toHaveCount(2)
        ->and($searched[1][2])->toBe('http://fine.test/');
});

test('an item that looks like a spreadsheet formula is exported as plain text', function () {
    $me = User::factory()->create();

    historyScan($me, 'suspicious', 40, ['type' => 'email', 'url' => null, 'sender_email' => '=HYPERLINK("http://evil.test","click")']);

    $rows = exportedRows($this->actingAs($me)->get(route('scan.history.export'))->streamedContent());

    expect($rows[1][2])->toStartWith("'=HYPERLINK");
});

test('a phone number keeps its plus sign and is not changed', function () {
    $me = User::factory()->create();

    historyScan($me, 'phishing', 70, ['type' => 'phone', 'url' => null, 'phone_number' => '+6737654321']);

    $rows = exportedRows($this->actingAs($me)->get(route('scan.history.export'))->streamedContent());

    expect($rows[1][2])->toBe('+6737654321');
});

test('the history page links to the export, keeping the current filters', function () {
    $me = User::factory()->create();
    historyScan($me, 'phishing', 90);

    $this->actingAs($me)
        ->get(route('scan.history', ['status' => 'phishing']))
        ->assertOk()
        ->assertSee(route('scan.history.export', ['status' => 'phishing']), false);
});

test('scans that need a manual review get their own counter and filter, and are labelled in the export', function () {
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    historyScan($me, 'review', 45, ['url' => 'http://unsure.test/']);
    historyScan($me, 'review', 50);
    historyScan($me, 'clean', 2);
    historyScan($someoneElse, 'review', 50);

    $page = $this->actingAs($me)->get(route('scan.history'))->assertOk();
    $filtered = $this->actingAs($me)->get(route('scan.history', ['status' => 'review']))->assertOk();
    $rows = exportedRows($this->actingAs($me)->get(route('scan.history.export', ['status' => 'review']))->streamedContent());

    expect($page->viewData('stats')['review'])->toBe(2)
        ->and($page->viewData('stats')['total'])->toBe(3)
        ->and($filtered->viewData('reports')->total())->toBe(2)
        ->and($rows)->toHaveCount(3)
        ->and($rows[1][3])->toBe('Needs review');
});
