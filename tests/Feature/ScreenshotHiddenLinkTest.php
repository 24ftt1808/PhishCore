<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * A screenshot of an email or chat shows a link's words ("[AD4308 ISM – Lecture Slides]"), not its address. The scan
 * says so instead of staying silent, and a "no threats" result introduces its one small warning sign as small.
 */
beforeEach(function () {
    Sleep::fake();
    $this->withoutVite();
    config(['services.ocr_space.key' => 'test-key', 'services.ai_text.enabled' => false]);
    $this->image = tempnam(sys_get_temp_dir(), 'shot').'.png';
    file_put_contents($this->image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
});

afterEach(function () {
    @unlink($this->image);
});

function hiddenLinkScan(string $image, string $text): array
{
    Http::fake(fn ($request) => str_contains($request->url(), 'ocr.space')
        ? Http::response(['IsErroredOnProcessing' => false, 'ParsedResults' => [['ParsedText' => $text]]])
        : Http::response('', 503));

    return (new AnalysisEngine)->analyze('screenshot', null, null, null, $image);
}

test('a message that points to a link whose address is not shown gets a plain note', function () {
    $result = hiddenLinkScan($this->image, "Dear students, due to the ongoing LMS maintenance, the lecture slides are available through the link below:\n[AD4308 ISM – Lecture Slides]");

    $note = collect($result['checks'])->firstWhere('name', 'Hidden Link');
    expect($note['points'])->toBe(0)
        ->and($note['message'])->toContain('Press and hold the link')
        ->and(collect($result['checks'])->firstWhere('name', 'Screenshot Text Extraction')['message'])->not->toContain('phishing-style language');
});

test('no note when the link address can be read', function () {
    $result = hiddenLinkScan($this->image, 'The slides are at the link below: https://lms.example.edu/ad4308');

    expect(collect($result['checks'])->firstWhere('name', 'Hidden Link'))->toBeNull();
});

test('a "no threats" result introduces its one warning sign as a small one', function () {
    $owner = User::factory()->create();
    $report = Report::factory()->create(['type' => 'screenshot', 'url' => null, 'status' => 'completed', 'user_id' => $owner->id]);
    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => 'clean',
        'risk_score' => 10,
        'flags' => [['name' => 'Message Content / Behavior Patterns', 'status' => 'SUSPICIOUS', 'message' => 'Suspicious call-to-action phrasing detected (click/follow link)', 'points' => 10]],
    ]);

    $this->actingAs($owner)->get(route('scan.show', $report))
        ->assertOk()
        ->assertSee('One small warning sign, not enough on its own to call this a scam: it urges you to click or follow a link.');
});

test('a genuine message whose only sign is "click the link" adds no points and is shown for information', function () {
    $result = hiddenLinkScan($this->image, "Dear students, the LMS is down, so please click the link to download the lecture slides.\n[AD4308 ISM - Lecture Slides]");

    $content = collect($result['checks'])->firstWhere('name', 'Message Content / Behavior Patterns');
    expect($result['risk_score'])->toBe(0)
        ->and($result['verdict'])->toBe('clean')
        ->and($content['status'])->toBe('INFO')
        ->and($content['message'])->toContain('Only everyday wording was found');
});

test('"click the link" still counts next to another warning sign', function () {
    $result = hiddenLinkScan($this->image, 'Your BIBD account is on hold. Please click the link to verify: https://bibd-secure-verify.top/login');

    $content = collect($result['checks'])->firstWhere('name', 'Message Content / Behavior Patterns');
    expect($content['points'])->toBeGreaterThan(0)
        ->and($result['verdict'])->not->toBe('clean');
});

test('an urgent message linking to a Brunei school website is not a pressure scam', function () {
    $engine = new AnalysisEngine;
    $pressure = (new ReflectionMethod(AnalysisEngine::class, 'checkLinkInPressureMessage'))
        ->invoke($engine, ['matched_categories' => 1], 'https://lms.pb.edu.bn/course/ad4308');

    expect($pressure['flagged'])->toBeFalse();
});

test('a "could not be delivered, click to reschedule" parcel message is flagged even without a visible link', function () {
    $result = hiddenLinkScan($this->image, 'Dear customer, your package could not be delivered. Click the link below to reschedule.');

    expect($result['verdict'])->toBe('suspicious');
});

test('sharing a link with "click the link" is not treated as pressure', function () {
    $result = hiddenLinkScan($this->image, 'Hi, please click the link to see the photos from the event yesterday: https://photos.app.goo.gl/AbC123');

    expect($result['verdict'])->toBe('clean')
        ->and(collect($result['checks'])->firstWhere('name', 'Link in Pressure Message'))->toBeNull();
});

test('an email bounce notice is not read as a parcel scam', function () {
    $rules = (new AnalysisEngine)->scoreMessageRules('This message was created automatically by mail delivery software. A message that you sent could not be delivered to all of its recipients.');

    expect(implode(' ', $rules['reasons']))->not->toContain('parcel');
});

test('"Brunei Post" is not read as a job offer, and "reactivate your wallet" counts as an account threat', function () {
    $engine = new AnalysisEngine;
    expect(implode(' ', $engine->scoreMessageRules('Brunei Post: your parcel is on hold. Pay the delivery fee BND 1.50 at bruneipost-redeliver.top')['reasons']))->not->toContain('job offer')
        ->and($engine->scoreMessageRules('Reactivate your wallet. Copy this to your browser: hxxp://ecocash-verify[.]top/login')['verdict'])->not->toBe('clean');
});

test('a Brunei government website is not flagged for writing about fines and payments', function () {
    $html = '<html><title>Pasukan Polis Diraja Brunei</title><body><a>Senarai Kesalahan Kompaun Trafik</a><a>Panduan Pembayaran Denda Kompaun (Kesalahan Trafik) Melalui Aplikasi BIBD</a><p>Bayar denda segera. Sila log masuk untuk semak saman anda.</p></body></html>';
    $engine = new AnalysisEngine;

    expect($engine->inspectPageHtml($html, 'www.polis.gov.bn')['points'])->toBe(0)
        ->and($engine->inspectPageHtml($html, 'polis-brunei-saman.top')['points'])->toBeGreaterThan(0);
});