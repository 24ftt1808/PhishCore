<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Services\CheckExplainer;

/**
 * "Previous reports" that added no points is a note about PhishCore's own history, so it is listed under
 * "Unavailable or for information", not "Passed". Scores and stored results are unchanged.
 */
beforeEach(function () {
    $this->withoutVite();
});

test('a previous-reports check with no points is shown as information', function () {
    expect(CheckExplainer::shownStatus(['name' => 'Previous Reports (Domain)', 'status' => 'SAFE', 'points' => 0]))->toBe('INFO')
        ->and(CheckExplainer::shownStatus(['name' => 'Previous Reports (Phone)', 'status' => 'SAFE', 'points' => 0]))->toBe('INFO')
        ->and(CheckExplainer::shownStatus(['name' => 'Previous Reports (Domain)', 'status' => 'HIGH RISK', 'points' => 50]))->toBe('HIGH RISK')
        ->and(CheckExplainer::shownStatus(['name' => 'SSL Certificate', 'status' => 'SAFE', 'points' => 0]))->toBe('SAFE');
});

test('on the result page it sits under "for information", after the passed checks', function () {
    $owner = User::factory()->create();
    $report = Report::factory()->create(['type' => 'url', 'url' => 'https://dh1-bru.site/', 'status' => 'completed', 'user_id' => $owner->id]);
    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => 'clean',
        'risk_score' => 0,
        'flags' => [
            ['name' => 'SSL Certificate', 'status' => 'SAFE', 'message' => 'Valid certificate.', 'points' => 0],
            ['name' => 'Previous Reports (Domain)', 'status' => 'SAFE', 'message' => 'This domain has appeared in 1 other report on PhishCore.', 'points' => 0],
        ],
    ]);

    $this->actingAs($owner)->get(route('scan.show', $report))
        ->assertOk()
        ->assertSeeInOrder(['Passed · 1', 'Unavailable or for information · 1', 'Previous reports for this website']);
});

test('the legal pages do not name the school', function () {
    $this->get(route('privacy'))->assertOk()->assertDontSee('Politeknik Brunei');
    $this->get(route('terms'))->assertOk()->assertDontSee('Politeknik Brunei');
});