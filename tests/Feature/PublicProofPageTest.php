<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;

function proofReport(string $verdict, array $overrides = []): Report
{
    $report = Report::factory()->create(array_merge([
        'url' => 'http://fake-bibd-login.example/verify',
        'type' => 'url',
        'status' => 'completed',
    ], $overrides));

    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => $verdict,
        'risk_score' => $verdict === 'phishing' ? 82 : 40,
        'flags' => [
            ['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a bank lookalike.', 'points' => 40],
        ],
    ]);

    return $report;
}

beforeEach(function () {
    $this->withoutVite();
});

test('a flagged website opens a public proof page for anyone, without logging in', function () {
    $report = proofReport('phishing');

    $response = $this->get(route('reports.public.show', $report));

    $response->assertOk()
        ->assertSee('Public report.')
        ->assertSee('fake-bibd-login.example')
        ->assertDontSee('not confirmed');
    expect($response->headers->get('X-Robots-Tag'))->toContain('noindex');
});

test('a suspicious website is shown as not confirmed', function () {
    $report = proofReport('suspicious');

    $this->get(route('reports.public.show', $report))
        ->assertOk()
        ->assertSee('This result is not confirmed');
});

test('the proof page never shows who submitted the scan', function () {
    $owner = User::factory()->create(['name' => 'Zed Hiddenname']);
    $report = proofReport('phishing', ['user_id' => $owner->id]);

    $this->get(route('reports.public.show', $report))
        ->assertOk()
        ->assertDontSee('Zed Hiddenname')
        ->assertDontSee($owner->email);
});

test('only flagged, finished website reports have a proof page', function () {
    $clean = proofReport('clean');
    $unfinished = proofReport('phishing', ['status' => 'processing']);
    $email = proofReport('phishing', ['type' => 'email', 'url' => null, 'sender_email' => 'victim@example.com']);
    $phone = proofReport('phishing', ['type' => 'phone', 'url' => null, 'phone_number' => '+6737654321']);
    $file = proofReport('phishing', ['type' => 'file', 'url' => null, 'file_name' => 'private.pdf']);

    foreach ([$clean, $unfinished, $email, $phone, $file] as $report) {
        $this->get(route('reports.public.show', $report))->assertNotFound();
    }

    $this->get('/public-reports/999999')->assertNotFound();
});

test('on the public list only website rows link to a proof page', function () {
    $site = proofReport('phishing');
    $email = proofReport('phishing', ['type' => 'email', 'url' => null, 'sender_email' => 'victim@example.com']);

    $this->get(route('reports.public'))
        ->assertOk()
        ->assertSee('href="'.route('reports.public.show', $site).'"', false)
        ->assertDontSee('href="'.route('reports.public.show', $email).'"', false);
});

test('a link scanned many times is listed once, with its latest flagged scan', function () {
    $first = proofReport('phishing');
    $second = proofReport('phishing');
    $latest = proofReport('phishing');
    $other = proofReport('phishing', ['url' => 'http://another-scam.example/login']);

    $this->get(route('reports.public'))
        ->assertOk()
        ->assertSee('href="'.route('reports.public.show', $latest).'"', false)
        ->assertSee('href="'.route('reports.public.show', $other).'"', false)
        ->assertDontSee('href="'.route('reports.public.show', $first).'"', false)
        ->assertDontSee('href="'.route('reports.public.show', $second).'"', false);
});

test('emails and phone numbers are not grouped on the public list', function () {
    proofReport('phishing', ['type' => 'phone', 'url' => null, 'phone_number' => '+6737654321']);
    proofReport('phishing', ['type' => 'phone', 'url' => null, 'phone_number' => '+6737654321']);

    $html = $this->get(route('reports.public'))->assertOk()->getContent();

    expect(substr_count($html, '+6737654321'))->toBeGreaterThanOrEqual(2);
});

test('the proof page has a back button to the public reports list', function () {
    $report = proofReport('phishing');

    $this->get(route('reports.public.show', $report))
        ->assertOk()
        ->assertSee('data-back-link', false)
        ->assertSee('Back to Public Reports')
        ->assertSee('href="'.route('reports.public').'"', false);
});

test('the proof page back button returns to the same page of the list the person came from', function () {
    $report = proofReport('phishing');
    $list = route('reports.public', ['page' => 2]);

    $this->from($list)->get(route('reports.public.show', $report))
        ->assertOk()
        ->assertSee('href="'.$list.'"', false);
});

test('a scan result in the dashboard has a back button to scan history', function () {
    $user = User::factory()->create();
    $report = proofReport('phishing', ['user_id' => $user->id]);

    $this->actingAs($user)->get(route('scan.show', $report))
        ->assertOk()
        ->assertSee('data-back-link', false)
        ->assertSee('Back to Scan History');
});

test('an old public report keeps the saved-result note but has no scan again button', function () {
    $report = proofReport('phishing', ['created_at' => now()->subDays(2)]);

    $this->get(route('reports.public.show', $report))
        ->assertOk()
        ->assertSee('Saved result from')
        ->assertDontSee('Scan again');
});