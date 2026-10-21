<?php

use App\Models\Analysis;
use App\Models\CtiLookup;
use App\Models\Report;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

/** A finished link scan with a VirusTotal lookup attached. */
function virusTotalScan(User $owner, array $stats, array $results): Report
{
    $scan = Report::factory()->create([
        'user_id' => $owner->id,
        'url' => 'http://fake-bibd-login.example/verify',
        'type' => 'url',
        'status' => 'completed',
    ]);

    Analysis::factory()->create([
        'report_id' => $scan->id,
        'verdict' => 'phishing',
        'risk_score' => 82,
        'duration_ms' => 3000,
        'flags' => [
            ['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a lookalike.', 'points' => 40],
        ],
    ]);

    CtiLookup::factory()->create([
        'report_id' => $scan->id,
        'threat_score' => 20.2,
        'raw_response' => ['data' => ['attributes' => ['last_analysis_stats' => $stats, 'last_analysis_results' => $results]]],
    ]);

    return $scan;
}

test('the VirusTotal card shows the chart, the counts and who flagged it', function () {
    $owner = User::factory()->create();
    $scan = virusTotalScan($owner, ['malicious' => 2, 'suspicious' => 1, 'harmless' => 3, 'undetected' => 1], [
        'Kaspersky' => ['category' => 'malicious', 'result' => 'phishing'],
        'Fortinet' => ['category' => 'malicious', 'result' => 'phishing'],
        'CRDF' => ['category' => 'suspicious', 'result' => 'suspicious'],
        'Sophos' => ['category' => 'harmless', 'result' => 'clean'],
        'ESET' => ['category' => 'harmless', 'result' => 'clean'],
        'Avira' => ['category' => 'harmless', 'result' => 'clean'],
        'Zillya' => ['category' => 'undetected', 'result' => null],
    ]);

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('3 / 7 FLAGGED')
        ->assertSee('class="vt-donut"', false)
        ->assertSee('FLAGGED BY')
        ->assertSee('showing 3 of 3, malicious first')
        ->assertSee('Kaspersky')
        ->assertSee('View all 7 security vendor results');
});

test('more than six flagging vendors collapse into a plus-more chip', function () {
    $owner = User::factory()->create();
    $results = [];
    foreach (range(1, 8) as $number) {
        $results["Vendor {$number}"] = ['category' => 'malicious', 'result' => 'malware'];
    }
    $scan = virusTotalScan($owner, ['malicious' => 8, 'suspicious' => 0, 'harmless' => 0, 'undetected' => 0], $results);

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('showing 6 of 8, malicious first')
        ->assertSee('+2 more');
});

test('when no vendor flagged it the card says so instead of listing vendors', function () {
    $owner = User::factory()->create();
    $scan = virusTotalScan($owner, ['malicious' => 0, 'suspicious' => 0, 'harmless' => 2, 'undetected' => 1], [
        'Sophos' => ['category' => 'harmless', 'result' => 'clean'],
        'ESET' => ['category' => 'harmless', 'result' => 'clean'],
        'Zillya' => ['category' => 'undetected', 'result' => null],
    ]);

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('0 / 3 FLAGGED')
        ->assertSee('No security vendor flagged this.')
        ->assertDontSee('FLAGGED BY');
});

test('a lookup with no vendor data still renders a calm card', function () {
    $owner = User::factory()->create();
    $scan = virusTotalScan($owner, [], []);

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('VirusTotal returned no vendor results for this lookup.')
        ->assertDontSee('class="vt-donut"', false);
});