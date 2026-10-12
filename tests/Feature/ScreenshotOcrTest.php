<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();
    config(['services.ocr_space.key' => 'test-key']);

    $this->image = tempnam(sys_get_temp_dir(), 'ocr').'.png';
    file_put_contents($this->image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
});

afterEach(function () {
    @unlink($this->image);
});

test('text read by the OCR service is returned', function () {
    Http::fake(['api.ocr.space/*' => Http::response([
        'IsErroredOnProcessing' => false,
        'ParsedResults' => [['ParsedText' => "Verify here https://bad.example/x\n"]],
    ])]);

    $result = (new AnalysisEngine)->checkScreenshotOcr($this->image);

    expect($result['success'])->toBeTrue()
        ->and($result['text'])->toBe('Verify here https://bad.example/x');
});

test('a throttled free key is reported as a temporary overload, after one retry', function () {
    Http::fake(['api.ocr.space/*' => Http::response([
        'error' => 'E571: Free OCR API overloaded currently, so your free ocr api key is throttled.',
        'details' => 'Temporary condition. Please retry in a few minutes.',
    ], 503)]);

    $result = (new AnalysisEngine)->checkScreenshotOcr($this->image);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('overloaded right now')
        ->and($result['error'])->not->toBe('OCR processing failed');

    Http::assertSentCount(2);
});

test('a processing error shows the reason the service gave', function () {
    Http::fake(['api.ocr.space/*' => Http::response([
        'IsErroredOnProcessing' => true,
        'ErrorMessage' => ['File failed validation.'],
    ])]);

    expect((new AnalysisEngine)->checkScreenshotOcr($this->image)['error'])->toContain('File failed validation.');
});

test('an answer that is not JSON keeps its HTTP status in the message', function () {
    Http::fake(['api.ocr.space/*' => Http::response('Forbidden by gateway', 403)]);

    expect((new AnalysisEngine)->checkScreenshotOcr($this->image)['error'])
        ->toContain('HTTP 403')
        ->toContain('Forbidden by gateway');
});

test('a large image is shrunk below 1 MB before it is sent', function () {
    if (! function_exists('imagecreatetruecolor')) {
        $this->markTestSkipped('GD is not installed.');
    }

    // 800 x 800 random pixels saved without compression is about 1.9 MB, over the limit, while
    // staying small enough to run inside PHP's default 128 MB test memory.
    $canvas = imagecreatetruecolor(800, 800);
    mt_srand(7);
    for ($y = 0; $y < 800; $y += 2) {
        for ($x = 0; $x < 800; $x += 2) {
            imagefilledrectangle($canvas, $x, $y, $x + 1, $y + 1, imagecolorallocate($canvas, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
    }
    $big = tempnam(sys_get_temp_dir(), 'big').'.png';
    imagepng($canvas, $big, 0);
    unset($canvas);
    expect(filesize($big))->toBeGreaterThan(1_000_000);

    Http::fake(['api.ocr.space/*' => Http::response([
        'IsErroredOnProcessing' => false,
        'ParsedResults' => [['ParsedText' => 'hello']],
    ])]);

    $result = (new AnalysisEngine)->checkScreenshotOcr($big);
    @unlink($big);

    expect($result['success'])->toBeTrue();

    Http::assertSent(fn ($request) => strlen($request->body()) < 1_100_000);
});