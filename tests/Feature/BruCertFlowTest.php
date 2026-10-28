<?php

use App\Models\Analysis;
use App\Models\Investigation;
use App\Models\Report;
use App\Models\User;

function brucertCase(string $status = 'active'): Report
{
    $report = Report::factory()->create([
        'type' => 'url',
        'url' => 'http://fake-bibd-login.example/verify',
        'status' => 'completed',
    ]);

    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => 'phishing',
        'risk_score' => 82,
        'flags' => [['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a bank lookalike.', 'points' => 40]],
    ]);

    Investigation::create(['report_id' => $report->id, 'status' => $status]);

    return $report;
}

beforeEach(function () {
    $this->withoutVite();
});

test('the team sees an Email BruCERT button with a safe, ready-written message and the evidence link', function () {
    config(['brucert.email' => 'cert@brucert.example']);
    $report = brucertCase();
    $team = User::factory()->create(['is_team_member' => true]);

    $html = $this->actingAs($team)->get(route('scan.show', $report))->assertOk()
        ->assertSee('Report to BruCERT')
        ->assertSee('Email BruCERT')
        ->assertSee(route('reports.public.show', $report), false)
        ->getContent();

    expect($html)->toContain('mailto:cert@brucert.example?subject=')
        // The link itself is written in a safe form, never as a clickable phishing address.
        ->and($html)->toContain(rawurlencode('hxxp://fake-bibd-login[.]example/verify'))
        ->and($html)->not->toContain(rawurlencode('http://fake-bibd-login.example/verify'));
});

test('without a BruCERT address the email still opens, and the team is told how to set it', function () {
    config(['brucert.email' => null]);
    $report = brucertCase();
    $team = User::factory()->create(['is_team_member' => true]);

    $this->actingAs($team)->get(route('scan.show', $report))->assertOk()
        ->assertSee('mailto:?subject=', false)
        ->assertSee('BRUCERT_EMAIL');
});

test('people outside the team do not get the BruCERT email button', function () {
    $report = brucertCase();
    $owner = User::factory()->create(['is_team_member' => false]);
    $report->update(['user_id' => $owner->id]);

    $this->actingAs($owner)->get(route('scan.show', $report))->assertOk()
        ->assertDontSee('Email BruCERT')
        ->assertDontSee('data-brucert-card', false);
});

test('the takedown statuses are worded as BruCERT\'s work, not PhishCore\'s', function () {
    brucertCase('takedown_requested');
    $team = User::factory()->create(['is_team_member' => true]);

    $this->actingAs($team)->get(route('investigations.index'))->assertOk()
        ->assertSee('REPORTED TO BRUCERT')
        ->assertDontSee('TAKEDOWN REQUESTED');
});

test('the public reports page counts what was reported to BruCERT and what was taken down', function () {
    brucertCase('active');
    brucertCase('takedown_requested');
    brucertCase('takedown_requested');
    brucertCase('takedown_confirmed');

    $html = $this->get(route('reports.public'))->assertOk()
        ->assertSee('BRUCERT TAKEDOWNS')
        ->getContent();

    // Three cases were passed to BruCERT (two waiting, one confirmed); one of them was taken down.
    expect($html)->toMatch('/data-brucert-reported>\s*3\s*</')
        ->and($html)->toMatch('/data-brucert-taken-down>\s*1\s*</');
});