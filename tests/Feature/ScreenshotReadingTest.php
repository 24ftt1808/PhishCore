<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * What the screenshot scan pulls out of OCR text: tracking and reference numbers are not phone numbers, an icon
 * read as "f@" is not an email address, and a cut-off "1.com" is not a link. Real phone numbers, addresses and
 * links are still found.
 */
beforeEach(function () {
    Sleep::fake();
    config(['services.ocr_space.key' => 'test-key', 'services.ai_text.enabled' => false]);

    $this->image = tempnam(sys_get_temp_dir(), 'shot').'.png';
    file_put_contents($this->image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
});

afterEach(function () {
    @unlink($this->image);
});

function scanScreenshotText(string $image, string $text): array
{
    Http::fake(fn ($request) => str_contains($request->url(), 'ocr.space')
        ? Http::response(['IsErroredOnProcessing' => false, 'ParsedResults' => [['ParsedText' => $text]]])
        : Http::response('', 503));

    return (new AnalysisEngine)->analyze('screenshot', null, null, null, $image);
}

test('tracking, reference and order numbers are not read as phone numbers', function (string $text) {
    expect(scanScreenshotText($this->image, $text)['extracted_phone'])->toBeNull();
})->with([
    'courier tracking' => "Shipment on hold\nTracking reference DHL-7623-8891-022\nPending clearance",
    'labelled reference' => 'Your payment was received. Ref: 4521 8873 0094. Thank you.',
    'order number' => 'Order no. 2026 1031 5521 has shipped.',
]);

test('real phone numbers are still found', function (string $text, string $number) {
    expect(scanScreenshotText($this->image, $text)['extracted_phone'])->toBe($number);
})->with([
    'international' => ['Call our agent on +673 712 3456 now', '+673 712 3456'],
    'local with spaces' => ['WhatsApp me at 712 3456 for the prize', '712 3456'],
]);

test('an icon read as "f@" next to a website is not an email address', function () {
    $result = scanScreenshotText($this->image, "Pay BND 6.50 now\nf@ dh1-bru.site");

    expect($result['extracted_email'])->toBeNull()
        ->and($result['extracted_url'])->toBe('https://dh1-bru.site');
});

test('an email address with stray OCR spaces is still found', function () {
    expect(scanScreenshotText($this->image, 'From: services @ paypal-accounts . com')['extracted_email'])->toBe('services@paypal-accounts.com');
});

test('a cut-off "1.com" is not read as a link, a numeric scam domain still is', function () {
    expect((new AnalysisEngine)->extractBareDomainsFromText('htt Slow to respond 1.com/ pelita.coffee'))->not->toContain('1.com')
        ->and((new AnalysisEngine)->extractBareDomainsFromText('Claim now at 2702735.cc'))->toBe(['2702735.cc']);
});