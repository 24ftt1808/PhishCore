<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;

function reportWithVerdict(string $verdict, string $url): Report
{
    $report = Report::factory()->create(['type' => 'url', 'url' => $url, 'status' => 'completed']);
    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict, 'risk_score' => 40]);

    return $report;
}

function teamMember(): User
{
    return User::factory()->create(['role' => 'admin', 'is_team_member' => true]);
}

test('the total on the reports page counts needs-review scans so it matches the list', function () {
    reportWithVerdict('clean', 'http://safe-one.test/');
    reportWithVerdict('phishing', 'http://bad-one.test/');
    reportWithVerdict('review', 'http://unsure-one.test/');

    $this->actingAs(teamMember())->get(route('reports.index'))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 3
            && $stats['safe'] === 1
            && $stats['phishing'] === 1
            && $stats['suspicious'] === 0
            && $stats['review'] === 1)
        ->assertSee('Needs review');
});

test('the reports page can be filtered to needs-review scans', function () {
    reportWithVerdict('clean', 'http://safe-two.test/');
    reportWithVerdict('review', 'http://unsure-two.test/');

    $this->actingAs(teamMember())->get(route('reports.index', ['status' => 'review']))
        ->assertOk()
        ->assertSee('unsure-two.test')
        ->assertDontSee('safe-two.test');
});
