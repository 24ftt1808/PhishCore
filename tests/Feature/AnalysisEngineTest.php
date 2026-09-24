<?php

use App\Services\AnalysisEngine;

/**
 * Baseline characterization tests for AnalysisEngine's pure-logic checks —
 * the ones that don't make real network calls, so they run fast and
 * reliably in CI. This deliberately does NOT cover checkDomainAge (real
 * WHOIS socket lookup), checkIpReputation, checkRedirectChain,
 * checkSiteAvailability, or checkPageContent (all real HTTP fetches) —
 * those would need Http::fake()/a fake WHOIS loader to test safely, which
 * is a follow-up. checkBlacklist and checkVirusTotal ARE covered here
 * because with no API key configured in the test environment, they return
 * immediately without touching the network.
 *
 * The goal of this file is to lock in current behavior before any refactor
 * (e.g. splitting AnalysisEngine into smaller classes) — if a future change
 * breaks one of these, a test here should fail.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

// --- checkUrlSyntax ---

test('checkUrlSyntax flags a raw IP address URL', function () {
    $result = $this->engine->checkUrlSyntax('http://192.168.1.1/login');

    expect($result['flagged'])->toBeTrue();
    expect($result['points'])->toBeGreaterThan(0);
});

test('checkUrlSyntax flags an "@" symbol in the URL', function () {
    $result = $this->engine->checkUrlSyntax('http://example.com@evil.com/login');

    expect($result['flagged'])->toBeTrue();
});

test('checkUrlSyntax flags a lookalike brand domain using character substitution', function () {
    $result = $this->engine->checkUrlSyntax('http://micros0ft.com/login');

    expect($result['flagged'])->toBeTrue();
    expect(implode(' ', $result['reasons']))->toContain('microsoft');
});

test('checkUrlSyntax does not flag the real brand domain', function () {
    $result = $this->engine->checkUrlSyntax('https://www.microsoft.com/en-us');

    expect($result['flagged'])->toBeFalse();
    expect($result['points'])->toBe(0);
});

test('checkUrlSyntax does not flag a plain, unremarkable URL', function () {
    $result = $this->engine->checkUrlSyntax('https://example.com/about');

    expect($result['flagged'])->toBeFalse();
});

// --- checkFreeHostingPlatform ---

test('checkFreeHostingPlatform flags a known free-hosting domain', function () {
    $result = $this->engine->checkFreeHostingPlatform('https://phishy-page.weebly.com');

    expect($result['flagged'])->toBeTrue();
    expect($result['platform'])->toBe('weebly.com');
});

test('checkFreeHostingPlatform does not flag a normal domain', function () {
    $result = $this->engine->checkFreeHostingPlatform('https://example.com');

    expect($result['flagged'])->toBeFalse();
    expect($result['platform'])->toBeNull();
});

// --- checkEmailDomain ---

test('checkEmailDomain rejects a string with no "@" symbol', function () {
    $result = $this->engine->checkEmailDomain('not-an-email');

    expect($result['flagged'])->toBeTrue();
    expect($result['domain'])->toBeNull();
});

test('checkEmailDomain flags a brand-mimicking sender domain', function () {
    $result = $this->engine->checkEmailDomain('security@paypa1-support.com');

    expect($result['flagged'])->toBeTrue();
});

test('checkEmailDomain does not flag the real brand domain', function () {
    $result = $this->engine->checkEmailDomain('service@paypal.com');

    expect($result['flagged'])->toBeFalse();
});

test('checkEmailDomain does not flag an unremarkable domain', function () {
    $result = $this->engine->checkEmailDomain('someone@example.com');

    expect($result['flagged'])->toBeFalse();
    expect($result['domain'])->toBe('example.com');
});

// --- checkPhoneNumber ---

test('checkPhoneNumber accepts a valid Brunei mobile number with no issues', function () {
    $result = $this->engine->checkPhoneNumber('+6737123456');

    expect($result['flagged'])->toBeFalse();
    expect($result['type'])->toBe('Mobile');
});

test('checkPhoneNumber flags an invalid number', function () {
    $result = $this->engine->checkPhoneNumber('123');

    expect($result['flagged'])->toBeTrue();
});

test('checkPhoneNumber flags a number with a repeated single digit', function () {
    $result = $this->engine->checkPhoneNumber('+6737777777');

    expect($result['flagged'])->toBeTrue();
    expect(implode(' ', $result['reasons']))->toContain('repeated');
});

// --- checkBlacklist / checkVirusTotal (no API key configured in tests) ---

test('checkBlacklist returns unavailable when no API key is configured', function () {
    $result = $this->engine->checkBlacklist('https://example.com');

    expect($result['flagged'])->toBeFalse();
    expect($result['unavailable'] ?? false)->toBeTrue();
});

test('checkVirusTotal returns unavailable when no API key is configured', function () {
    $result = $this->engine->checkVirusTotal('https://example.com');

    expect($result['flagged'])->toBeFalse();
    expect($result['unavailable'] ?? false)->toBeTrue();
});
