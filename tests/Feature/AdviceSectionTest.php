<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\View;

function adviceScan(User $owner, string $type, string $verdict): Report
{
    $scan = Report::factory()->create([
        'user_id' => $owner->id,
        'type' => $type,
        'url' => $type === 'url' ? 'http://fake-bibd-login.example/verify' : null,
        'status' => 'completed',
    ]);

    Analysis::factory()->create([
        'report_id' => $scan->id,
        'verdict' => $verdict,
        'risk_score' => $verdict === 'clean' ? 0 : 82,
        'duration_ms' => 3000,
        'flags' => [
            ['name' => 'URL Structure', 'status' => $verdict === 'clean' ? 'SAFE' : 'HIGH RISK', 'message' => 'Check message.', 'points' => $verdict === 'clean' ? 0 : 40],
        ],
    ]);

    return $scan;
}

beforeEach(function () {
    $this->withoutVite();
});

test('a phishing result tells the user what to do and who to call in Brunei', function () {
    $owner = User::factory()->create();
    $scan = adviceScan($owner, 'url', 'phishing');

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('What you should do')
        ->assertSee('Already sent money or shared bank details?')
        ->assertSee('16993')
        ->assertSee('993');
});

test('the steps change with the kind of scan', function () {
    $owner = User::factory()->create();

    $link = adviceScan($owner, 'url', 'phishing');
    $email = adviceScan($owner, 'email', 'phishing');
    $phone = adviceScan($owner, 'phone', 'suspicious');
    $screenshot = adviceScan($owner, 'screenshot', 'phishing');

    $this->actingAs($owner)->get(route('scan.show', $link))->assertSee('Close the page');
    $this->actingAs($owner)->get(route('scan.show', $email))->assertSee('Report it as phishing in your mail app');
    $this->actingAs($owner)->get(route('scan.show', $phone))->assertSee('never share a one-time code');
    $this->actingAs($owner)->get(route('scan.show', $screenshot))->assertSee('Block the sender');
});

test('a clean result does not show the scam advice', function () {
    $owner = User::factory()->create();
    $scan = adviceScan($owner, 'url', 'clean');

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertDontSee('What you should do')
        ->assertDontSee('16993');
});

test('the pdf report carries the same advice for a scam and none for a clean result', function () {
    $owner = User::factory()->create();
    $captured = new stdClass;
    View::composer('scan.pdf', function ($view) use ($captured) {
        $captured->advice = $view->getData()['advice'];
    });

    $scam = adviceScan($owner, 'email', 'phishing');
    $this->actingAs($owner)->get(route('scan.pdf', $scam))->assertOk();

    expect($captured->advice)->not->toBeNull()
        ->and($captured->advice['steps'][1])->toContain('mail app')
        ->and(implode(' ', $captured->advice['affected']))->toContain('16993')->toContain('993');

    $clean = adviceScan($owner, 'url', 'clean');
    $this->actingAs($owner)->get(route('scan.pdf', $clean))->assertOk();

    expect($captured->advice)->toBeNull();
});

test('the advice text is shared, so the page and the pdf cannot drift apart', function () {
    expect(\App\Services\ScanAdvice::for('url', 'clean'))->toBeNull()
        ->and(\App\Services\ScanAdvice::for('url', 'review'))->toBeNull()
        ->and(\App\Services\ScanAdvice::for('url', 'phishing')['intro'])->toContain('looks like a scam')
        ->and(\App\Services\ScanAdvice::for('url', 'suspicious')['intro'])->toContain('could be a scam');
});