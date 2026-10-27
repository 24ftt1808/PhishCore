<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    $this->withoutVite();
});

function mismatchScan(User $owner, string $verdict, int $score, array $flags, string $type = 'url'): Report
{
    $report = Report::factory()->create([
        'user_id' => $owner->id,
        'type' => $type,
        'url' => 'https://garden-club.example/news',
        'sender_email' => $type === 'email' ? 'someone@garden-club.example' : null,
        'status' => 'completed',
    ]);

    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict, 'risk_score' => $score, 'flags' => $flags, 'duration_ms' => 3000]);

    return $report;
}

function aiPageFlag(int $points, string $status): array
{
    return ['name' => 'AI Page Review', 'status' => $status, 'points' => $points, 'message' => 'Our AI review judged that this page looks suspicious.'];
}

test('a clean link the AI page review flagged is not called safe on the result page', function () {
    $owner = User::factory()->create();
    $scan = mismatchScan($owner, 'clean', 18, [aiPageFlag(8, 'SUSPICIOUS')]);

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk()
        ->assertSee('Needs a manual review')
        ->assertDontSee('This appears safe')
        ->assertSee('the AI review of the page raised a concern');
});

test('only the wording changes: the stored verdict and score are untouched', function () {
    $owner = User::factory()->create();
    $scan = mismatchScan($owner, 'clean', 18, [aiPageFlag(8, 'SUSPICIOUS')]);

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk();

    $analysis = $scan->analyses()->first();
    expect($analysis->verdict)->toBe('clean')->and($analysis->risk_score)->toBe(18);
});

test('a clean link with a clean or skipped AI review keeps "This appears safe"', function (array $flag) {
    $owner = User::factory()->create();
    $scan = mismatchScan($owner, 'clean', 3, [$flag]);

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk()
        ->assertSee('This appears safe')
        ->assertDontSee('Needs a manual review')
        ->assertDontSee('data-ai-disagrees', false);
})->with([
    'AI found nothing' => [['name' => 'AI Page Review', 'status' => 'SAFE', 'points' => 0, 'message' => 'Nothing suspicious.']],
    'AI could not run' => [['name' => 'AI Page Review', 'status' => 'UNKNOWN', 'points' => 0, 'message' => 'Could not run.']],
]);

test('a suspicious or phishing link keeps its own wording, AI flag or not', function (string $verdict, int $score, string $headline) {
    $owner = User::factory()->create();
    $scan = mismatchScan($owner, $verdict, $score, [aiPageFlag(25, 'HIGH RISK')]);

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk()
        ->assertSee($headline)
        ->assertDontSee('Needs a manual review')
        ->assertDontSee('data-ai-disagrees', false);
})->with([
    ['suspicious', 40, 'This looks suspicious'],
    ['phishing', 80, 'Phishing detected'],
]);

test('an email scan is not affected', function () {
    $owner = User::factory()->create();
    $scan = mismatchScan($owner, 'clean', 10, [['name' => 'AI Message Review', 'status' => 'SUSPICIOUS', 'points' => 15, 'message' => 'The AI reader thinks this message looks suspicious.']], 'email');

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk()
        ->assertSee('No threats detected')
        ->assertDontSee('Needs a manual review');
});

test('the pdf report says the same as the result page', function (array $flags, string $headline, bool $hasCaveat) {
    $owner = User::factory()->create();
    $scan = mismatchScan($owner, 'clean', 18, $flags);

    $holder = new stdClass;
    View::composer('scan.pdf', function ($view) use ($holder) {
        $holder->data = $view->getData();
    });

    $this->actingAs($owner)->get(route('scan.pdf', $scan))->assertOk();

    expect($holder->data['verdict']['headline'])->toBe($headline)
        ->and(isset($holder->data['verdict']['caveat']))->toBe($hasCaveat);
})->with([
    'AI flagged' => [[['name' => 'AI Page Review', 'status' => 'SUSPICIOUS', 'points' => 8, 'message' => 'Looks suspicious.']], 'Needs a manual review', true],
    'AI clean' => [[['name' => 'AI Page Review', 'status' => 'SAFE', 'points' => 0, 'message' => 'Nothing.']], 'This appears safe', false],
]);