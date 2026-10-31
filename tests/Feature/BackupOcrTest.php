<?php

use App\Services\AnalysisEngine;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;

/**
 * The backup screenshot reader (Tesseract on the server), used when OCR.space is busy, down or has no key.
 * It is off unless OCR_BACKUP_ENABLED is set, so nothing changes for a setup without it.
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

function throttledOcrSpace(): void
{
    Http::fake(['*' => Http::response(['error' => 'E571: Free OCR API overloaded currently, so your free ocr api key is throttled.'], 503)]);
}

test('the backup reader is off unless it is switched on', function () {
    throttledOcrSpace();
    Process::fake();

    $result = (new AnalysisEngine)->checkScreenshotOcr($this->image);

    expect($result['success'])->toBeFalse();
    Process::assertNothingRan();
});

test('when OCR.space is busy, the backup reader reads the image', function () {
    config(['services.ocr_backup.enabled' => true]);
    throttledOcrSpace();
    Process::fake(['*' => Process::result("Your parcel is held at customs.\n\nPay the fee at https://bad.example/pay\n")]);

    $result = (new AnalysisEngine)->checkScreenshotOcr($this->image);

    expect($result['success'])->toBeTrue()
        ->and($result['reader'])->toBe('backup')
        ->and($result['text'])->toBe("Your parcel is held at customs.\nPay the fee at https://bad.example/pay");
    Process::assertRan(fn (PendingProcess $process) => ($process->command[0] ?? null) === 'tesseract');
});

test('the backup reader is not used when OCR.space works', function () {
    config(['services.ocr_backup.enabled' => true]);
    Http::fake(['*' => Http::response(['IsErroredOnProcessing' => false, 'ParsedResults' => [['ParsedText' => 'hello']]])]);
    Process::fake();

    expect((new AnalysisEngine)->checkScreenshotOcr($this->image)['text'])->toBe('hello');
    Process::assertNothingRan();
});

test('the backup reader is used when no OCR.space key is set', function () {
    config(['services.ocr_backup.enabled' => true, 'services.ocr_space.key' => null]);
    Process::fake(['*' => Process::result('Kesalahan trafik telah direkodkan')]);

    expect((new AnalysisEngine)->checkScreenshotOcr($this->image)['text'])->toBe('Kesalahan trafik telah direkodkan');
});

test('if the backup reader fails too, the OCR.space reason is kept', function () {
    config(['services.ocr_backup.enabled' => true]);
    throttledOcrSpace();
    Process::fake(['*' => Process::result(output: '', errorOutput: 'tesseract: command not found', exitCode: 127)]);

    $result = (new AnalysisEngine)->checkScreenshotOcr($this->image);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('overloaded right now');
});

test('a scan read by the backup reader says so', function () {
    config(['services.ocr_backup.enabled' => true]);
    throttledOcrSpace();
    Process::fake(['*' => Process::result('Brunei Post: pay the delivery fee now at https://bruneipost-redeliver.top')]);

    $result = (new AnalysisEngine)->analyze('screenshot', null, null, null, $this->image);
    $extraction = collect($result['checks'])->firstWhere('name', 'Screenshot Text Extraction');

    expect($extraction['message'])->toContain("PhishCore's backup reader read this image");
});

test('the real backup reader reads a real scam screenshot, where Tesseract is installed', function () {
    try {
        $installed = Process::run(['tesseract', '--version'])->successful();
    } catch (Throwable) {
        $installed = false;
    }
    if (! $installed) {
        $this->markTestSkipped('Tesseract is not installed on this computer.');
    }

    config(['services.ocr_backup.enabled' => true, 'services.ocr_space.key' => null]);

    $result = (new AnalysisEngine)->checkScreenshotOcr(base_path('scan-screenshots/real-06-traffic-fine-malay-sms.jpg'));

    expect($result['success'])->toBeTrue()
        ->and(strtolower($result['text']))->toContain('kesalahan trafik');
});