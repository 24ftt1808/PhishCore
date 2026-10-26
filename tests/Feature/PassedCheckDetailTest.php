<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;

/*
 * A check that passes can say what it found ("detail"). The detail is only text for the result page:
 * it never changes a score, a flag or a status. These tests lock that in.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
    $this->buildCheck = fn (string $name, array $result, string $flagged = 'SUSPICIOUS') => (new ReflectionMethod(AnalysisEngine::class, 'buildCheck'))
        ->invoke($this->engine, $name, $result, $flagged);
});

test('a clean link says which tricks were looked for', function () {
    $result = $this->engine->checkUrlSyntax('https://example.com/about');

    expect($result['flagged'])->toBeFalse()
        ->and($result['points'])->toBe(0)
        ->and($result['detail'])->toContain('None of the common tricks were found');
});

test('a link with a trick has no passing detail', function () {
    $result = $this->engine->checkUrlSyntax('http://example.com@evil.com/login');

    expect($result['flagged'])->toBeTrue()
        ->and($result['detail'])->toBeNull();
});

test('a normal website says it is not on a free website builder', function () {
    $result = $this->engine->checkFreeHostingPlatform('https://example.com');

    expect($result['flagged'])->toBeFalse()
        ->and($result['detail'])->toContain('not on a free website builder');
});

test('a free website builder keeps its warning and gets no passing detail', function () {
    $result = $this->engine->checkFreeHostingPlatform('https://phishy-page.weebly.com');

    expect($result['flagged'])->toBeTrue()
        ->and($result)->not->toHaveKey('detail');
});

test('a website with a working certificate says so', function () {
    Http::fake(['*' => Http::response('', 200)]);

    $result = $this->engine->checkSslCertificate('https://example.com');

    expect($result['flagged'])->toBeFalse()
        ->and($result['points'])->toBe(0)
        ->and($result['detail'])->toContain('security certificate was accepted');
});

test('a website without HTTPS keeps its warning and gets no passing detail', function () {
    $result = $this->engine->checkSslCertificate('http://example.com');

    expect($result['flagged'])->toBeTrue()
        ->and($result)->not->toHaveKey('detail');
});

test('an address that is on no list says which lists were checked', function () {
    config(['services.google_safe_browsing.key' => 'test-key']);
    Http::fake(['safebrowsing.googleapis.com/*' => Http::response(['matches' => []], 200)]);

    $result = $this->engine->checkBlacklist('https://example.com');

    expect($result['flagged'])->toBeFalse()
        ->and($result['detail'])->toContain('Google Safe Browsing')
        ->toContain('OpenPhish');
});

test('an address Google flags keeps its warning and gets no passing detail', function () {
    config(['services.google_safe_browsing.key' => 'test-key']);
    Http::fake(['safebrowsing.googleapis.com/*' => Http::response(['matches' => [['threatType' => 'SOCIAL_ENGINEERING']]], 200)]);

    $result = $this->engine->checkBlacklist('https://example.com');

    expect($result['flagged'])->toBeTrue()
        ->and($result)->not->toHaveKey('detail');
});

test('an unavailable blacklist check does not claim the address is clean', function () {
    config(['services.google_safe_browsing.key' => null]);

    $result = $this->engine->checkBlacklist('https://example.com');

    expect($result['unavailable'] ?? false)->toBeTrue()
        ->and($result)->not->toHaveKey('detail');
});

test('a clean sender domain says what was looked for', function () {
    $result = $this->engine->checkEmailDomain('someone@example.com');

    expect($result['flagged'])->toBeFalse()
        ->and($result['detail'])->toContain('Nothing unusual');
});

test('a passing check shows its detail as the message', function () {
    $check = ($this->buildCheck)('SSL Certificate', ['flagged' => false, 'points' => 0, 'reasons' => [], 'detail' => 'It is fine.']);

    expect($check['status'])->toBe('SAFE')
        ->and($check['message'])->toBe('It is fine.')
        ->and($check['points'])->toBe(0);
});

test('reasons always win over the detail, and a flagged check never shows it', function () {
    $withReason = ($this->buildCheck)('Domain Age', ['flagged' => false, 'points' => 0, 'reasons' => ['Some reason'], 'detail' => 'Ignored.']);
    $flagged = ($this->buildCheck)('SSL Certificate', ['flagged' => true, 'points' => 20, 'reasons' => ['Website does not use HTTPS (no SSL encryption)'], 'detail' => 'Ignored.']);

    expect($withReason['message'])->toBe('Some reason.')
        ->and($flagged['status'])->toBe('SUSPICIOUS')
        ->and($flagged['message'])->not->toContain('Ignored');
});

test('without a detail the plain "nothing found" line is used, as before', function () {
    $check = ($this->buildCheck)('URL Structure', ['flagged' => false, 'points' => 0, 'reasons' => []]);

    expect($check['message'])->toBe('No issues detected for this check.');
});

test('an unavailable check never uses a detail', function () {
    $check = ($this->buildCheck)('Domain Age', ['flagged' => false, 'points' => 0, 'reasons' => ['WHOIS data unavailable for this domain'], 'unavailable' => true, 'detail' => 'Ignored.']);

    expect($check['status'])->toBe('UNKNOWN')
        ->and($check['message'])->toBe('WHOIS data unavailable for this domain.');
});