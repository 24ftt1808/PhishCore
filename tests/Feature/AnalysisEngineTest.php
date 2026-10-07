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
    config(['services.google_safe_browsing.key' => null]);

    $result = $this->engine->checkBlacklist('https://example.com');

    expect($result['flagged'])->toBeFalse();
    expect($result['unavailable'] ?? false)->toBeTrue();
});

test('checkVirusTotal returns unavailable when no API key is configured', function () {
    config(['services.virustotal.key' => null]);

    $result = $this->engine->checkVirusTotal('https://example.com');

    expect($result['flagged'])->toBeFalse();
    expect($result['unavailable'] ?? false)->toBeTrue();
});

// --- Brunei-specific detection ---

test('checkUrlSyntax does not flag official Brunei domains', function (string $url) {
    $result = $this->engine->checkUrlSyntax($url);

    expect($result['flagged'])->toBeFalse();
})->with([
    'https://www.bibd.com.bn/personal',
    'https://www.baiduri.com.bn',
    'https://www.bdcb.gov.bn',
    'https://www.customs.gov.bn',
    'https://www.flyroyalbrunei.com/en-bn/',
    'https://www.post.gov.bn',
]);

test('checkUrlSyntax flags Brunei brand lookalike domains', function (string $url) {
    $result = $this->engine->checkUrlSyntax($url);

    expect($result['flagged'])->toBeTrue();
})->with([
    'https://bibd-secure-login.com/verify',
    'https://baiduri-online.top',
    'https://bdcb-verify.net',
    'https://bruneipost-track.cc',
]);

test('checkUrlSyntax flags fake Brunei government and authority domains', function (string $url) {
    $result = $this->engine->checkUrlSyntax($url);

    expect($result['flagged'])->toBeTrue();
})->with([
    'https://brunei.gov.bn.claim-refund.xyz',
    'https://gov-bn-refund.com',
    'https://brunei-customs-fee.com',
]);

test('content patterns flag Malay and Brunei scam wording', function (string $text) {
    $method = new ReflectionMethod($this->engine, 'checkContentPatterns');
    $method->setAccessible(true);

    expect($method->invoke($this->engine, strtolower($text))['flagged'])->toBeTrue();
})->with([
    'Akaun anda akan disekat. Sila sahkan akaun anda sekarang.',
    'Tahniah! Anda telah memenangi hadiah. Tuntut hadiah anda.',
    'Notice of Involvement in Investigation. Royal Brunei Police and Interpol. Keep this matter confidential.',
    'Your parcel is held. Please pay the customs fee to release it.',
]);

test('content patterns do not flag ordinary text', function () {
    $method = new ReflectionMethod($this->engine, 'checkContentPatterns');
    $method->setAccessible(true);

    expect($method->invoke($this->engine, 'hello, meeting at 3pm tomorrow')['flagged'])->toBeFalse();
});

test('checkSslCertificate reports an incomplete certificate chain as unknown, not suspicious', function () {
    \Illuminate\Support\Facades\Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 60: SSL certificate OpenSSL verify result: unable to get local issuer certificate (20)');
    });

    $result = $this->engine->checkSslCertificate('https://www.bibd.com.bn');

    expect($result['flagged'])->toBeFalse();
    expect($result['points'])->toBe(0);
    expect($result['unavailable'])->toBeTrue();
});

test('checkSslCertificate still flags an expired certificate', function () {
    \Illuminate\Support\Facades\Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 60: SSL certificate problem: certificate has expired');
    });

    $result = $this->engine->checkSslCertificate('https://expired.example.com');

    expect($result['flagged'])->toBeTrue();
    expect($result['points'])->toBe(30);
});