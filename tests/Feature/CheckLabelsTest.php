<?php

use App\Services\AnalysisEngine;
use App\Services\CheckExplainer;
use Illuminate\Support\Facades\Http;

test('a website that cannot be reached is unavailable for redirects, not "no issues found"', function () {
    Http::fake(fn () => throw new \RuntimeException('Could not resolve host'));

    $result = (new AnalysisEngine)->checkRedirectChain('https://offline-example.test/bn/');

    expect($result['unavailable'] ?? false)->toBeTrue()
        ->and($result['points'])->toBe(0)
        ->and($result['reasons'][0])->toContain('Could not reach');
});

test('a website that answers still gets a normal redirect result', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    $result = (new AnalysisEngine)->checkRedirectChain('https://example.test/');

    expect($result['unavailable'] ?? false)->toBeFalse()
        ->and($result['reasons'])->toContain('No redirects detected');
});

test('the picture text card is information, not a safety result', function () {
    expect(CheckExplainer::statusLabel('INFO'))->toBe('Information')
        ->and(CheckExplainer::group('INFO'))->toBe('info');
});