<?php

use App\Models\Analysis;
use App\Models\Investigation;
use App\Models\InvestigationStatusLog;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\View;

function scannedReport(array $analysis = []): Report
{
    $report = Report::factory()->create([
        'url' => 'http://paypa1-secure-login.example/verify',
        'status' => 'completed',
    ]);

    Analysis::factory()->create(array_merge([
        'report_id' => $report->id,
        'verdict' => 'phishing',
        'risk_score' => 82,
        'duration_ms' => 4200,
        'flags' => [
            ['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a PayPal lookalike.', 'points' => 40],
            ['name' => 'SSL Certificate', 'status' => 'SAFE', 'message' => 'No issues detected for this check.', 'points' => 0],
        ],
    ], $analysis));

    return $report;
}

function investigationFor(Report $report): Investigation
{
    $teamMember = User::factory()->create(['name' => 'Case Handler', 'is_team_member' => true]);

    $investigation = Investigation::create([
        'report_id' => $report->id,
        'assigned_to' => $teamMember->id,
        'requested_by' => $teamMember->id,
        'status' => 'takedown_requested',
        'notes' => 'Registrar abuse desk contacted.',
    ]);

    InvestigationStatusLog::create([
        'investigation_id' => $investigation->id,
        'status' => 'takedown_requested',
        'changed_by' => $teamMember->id,
    ]);

    return $investigation;
}

/** Captures the data handed to the PDF view so we can check what each viewer is allowed to see. */
function capturePdfData(): object
{
    $holder = new stdClass;
    $holder->data = null;

    View::composer('scan.pdf', function ($view) use ($holder) {
        $holder->data = $view->getData();
    });

    return $holder;
}

test('anyone who can open a scan can download it as a pdf', function () {
    $report = scannedReport();

    $response = $this->withSession(['guest_report_ids' => [$report->id]])
        ->get(route('scan.pdf', $report));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')->toContain('PhishCore-PG-');
    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
});

test('a scan without an analysis cannot be exported', function () {
    $report = Report::factory()->create(['status' => 'failed']);

    $this->withSession(['guest_report_ids' => [$report->id]])
        ->get(route('scan.pdf', $report))
        ->assertNotFound();
});

test('the pdf uses the same reference id and verdict as the result page', function () {
    $report = scannedReport();
    $captured = capturePdfData();

    $this->withSession(['guest_report_ids' => [$report->id]])
        ->get(route('scan.pdf', $report))
        ->assertOk();

    $expectedRef = 'PG-'.$report->created_at->format('Y-md').'-'.strtoupper(substr(md5((string) $report->id), 0, 5));

    expect($captured->data['ref'])->toBe($expectedRef);
    expect($captured->data['verdict']['headline'])->toBe('Phishing detected');
    expect($captured->data['severity'])->toBe('CRITICAL');
    expect($captured->data['checks'][0]['name'])->toBe('URL Analysis');
});

test('guests get no investigation section in the pdf', function () {
    $report = scannedReport();
    investigationFor($report);
    $captured = capturePdfData();

    $this->withSession(['guest_report_ids' => [$report->id]])
        ->get(route('scan.pdf', $report))
        ->assertOk();

    expect($captured->data['investigation'])->toBeNull();
});

test('regular users see the investigation status but not names or notes', function () {
    $report = scannedReport();
    investigationFor($report);
    $captured = capturePdfData();

    $owner = User::factory()->create(['is_team_member' => false]);
    $report->update(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('scan.pdf', $report))
        ->assertOk();

    $investigation = $captured->data['investigation'];

    expect($investigation['status'])->toBe('Takedown requested');
    expect($investigation['assignee'])->toBeNull();
    expect($investigation['notes'])->toBeNull();
    expect($investigation['timeline'][0]['by'])->toBeNull();
});

test('team members also see who handled the case and the notes', function () {
    $report = scannedReport();
    investigationFor($report);
    $captured = capturePdfData();

    $this->actingAs(User::factory()->create(['is_team_member' => true]))
        ->get(route('scan.pdf', $report))
        ->assertOk();

    $investigation = $captured->data['investigation'];

    expect($investigation['assignee'])->toBe('Case Handler');
    expect($investigation['notes'])->toBe('Registrar abuse desk contacted.');
    expect($investigation['timeline'][0]['by'])->toBe('Case Handler');
});
test('a clean message scan says no threats detected with a caveat, a clean link keeps its wording', function () {
    foreach (['email', 'phone', 'screenshot'] as $type) {
        $report = scannedReport(['verdict' => 'clean', 'risk_score' => 0]);
        $report->update(['type' => $type]);
        $captured = capturePdfData();

        $this->withSession(['guest_report_ids' => [$report->id]])->get(route('scan.pdf', $report))->assertOk();

        expect($captured->data['verdict']['headline'])->toBe('No threats detected');
        expect($captured->data['verdict']['caveat'])->toContain('not a guarantee');
    }

    $report = scannedReport(['verdict' => 'clean', 'risk_score' => 0]);
    $report->update(['type' => 'url']);
    $captured = capturePdfData();
    $this->withSession(['guest_report_ids' => [$report->id]])->get(route('scan.pdf', $report))->assertOk();

    expect($captured->data['verdict']['headline'])->toBe('This appears safe');
});