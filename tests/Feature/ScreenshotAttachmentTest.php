<?php

use App\Services\AnalysisEngine;

function attachmentOf(string $text): array
{
    $method = new ReflectionMethod(AnalysisEngine::class, 'detectAttachment');

    return $method->invoke(new AnalysisEngine, $text);
}

test('an APK in a message is flagged and points to the file scan', function () {
    $result = attachmentOf('Sila install bank-update.apk untuk teruskan');

    expect($result['flagged'])->toBeTrue()
        ->and($result['points'])->toBe(30)
        ->and($result['file_name'])->toBe('bank-update.apk')
        ->and($result['reasons'][0])->toContain('Android app (APK)')->toContain('Scan File');
});

test('a program with a document name in front of it is flagged for its two endings', function () {
    $result = attachmentOf('Open Invoice_2026.pdf.exe now');

    expect($result['points'])->toBe(30)->and($result['reasons'][0])->toContain('two endings');
});

test('other risky file names keep their points', function (string $text, int $points) {
    expect(attachmentOf($text)['points'])->toBe($points);
})->with([
    'program installer' => ['Run setup.msi to continue', 25],
    'script' => ['open run.ps1', 25],
    'zip' => ['PDF_TOXGXO_2026102009 1958.zip', 15],
    'disk image' => ['download update.iso', 15],
    'macro file' => ['see sheet.xlsm', 15],
    'office file' => ['see report.docx', 10],
]);

test('a PDF in a message adds no points but tells the reader how to check it', function () {
    $result = attachmentOf('Please see statement.pdf attached');

    expect($result['flagged'])->toBeFalse()
        ->and($result['neutral'])->toBeTrue()
        ->and($result['points'])->toBe(0)
        ->and($result['reasons'][0])->toContain('Scan File');
});

test('a zip does not get the upload hint, because the file scan cannot look inside it', function () {
    expect(attachmentOf('invoice.zip')['reasons'][0])->not->toContain('Scan File');
});

test('text without a file name has no attachment result', function () {
    expect(attachmentOf('Your parcel is waiting, pay the fee')['flagged'])->toBeFalse()
        ->and(attachmentOf('Your parcel is waiting')['reasons'])->toBe([]);
});