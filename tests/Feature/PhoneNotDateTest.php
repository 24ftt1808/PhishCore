<?php

use App\Services\AnalysisEngine;

/**
 * Dates and form codes in a screenshot are not phone numbers. A BDMD weather warning ends with
 * "FORM/12(01-2026-01-01)", which used to be read as a phone number, judged impossible, and scored 25.
 */
function phonesIn(string $text): array
{
    $engine = app(AnalysisEngine::class);
    $method = new ReflectionMethod($engine, 'extractAllPhonesFromText');

    return $method->invoke($engine, $text);
}

test('a form code with a date in brackets is not a phone number', function () {
    expect(phonesIn('Dikeluarkan oleh / Issued by: BDMD/MSCD/WSOS/WFC/FORM/12(01-2026-01-01)'))->toBe([]);
});

test('dates written with dashes or spaces are not phone numbers', function () {
    expect(phonesIn('Ends at 2026-01-01 10.00 pm'))->toBe([])
        ->and(phonesIn('Tarikh 01 10 2026'))->toBe([]);
});

test('real phone numbers are still found', function () {
    expect(phonesIn('Call 712 3456 now'))->toBe(['712 3456'])
        ->and(phonesIn('WhatsApp +673 712 3456'))->toBe(['+673 712 3456'])
        ->and(phonesIn('Hubungi 223-8181 atau 012-345 6789'))->toBe(['223-8181', '012-345 6789']);
});