<?php

use App\Services\CheckExplainer;

it('gives every known check a plain question and falls back to the technical name', function () {
    expect(CheckExplainer::title('SSL Certificate'))->toBe('Secure connection');
    expect(CheckExplainer::title('Something New'))->toBe('Something New');
    expect(CheckExplainer::hasFriendlyTitle('Domain Age'))->toBeTrue();
    expect(CheckExplainer::hasFriendlyTitle('Something New'))->toBeFalse();
});

it('explains checks, including the ones with variable names', function () {
    expect(CheckExplainer::meaning('Domain Age'))->toContain('new website');
    expect(CheckExplainer::meaning('Previous Reports (Domain)'))->not->toBeNull();
    expect(CheckExplainer::meaning('Phone Reputation (AbstractAPI)'))->not->toBeNull();
    expect(CheckExplainer::meaning('AI Page Review'))->toContain('AI');
    expect(CheckExplainer::meaning('Unknown Check'))->toBeNull();
});

it('maps statuses to friendly labels and groups', function () {
    expect(CheckExplainer::statusLabel('SAFE'))->toBe('No issues found');
    expect(CheckExplainer::statusLabel('HIGH RISK'))->toBe('High risk');
    expect(CheckExplainer::statusLabel('UNKNOWN'))->toBe('Unavailable');
    expect(CheckExplainer::group('SUSPICIOUS'))->toBe('warn');
    expect(CheckExplainer::group('DETECTED'))->toBe('warn');
    expect(CheckExplainer::group('SAFE'))->toBe('ok');
    expect(CheckExplainer::group('UNKNOWN'))->toBe('info');
    expect(CheckExplainer::group('REVIEW'))->toBe('info');
});

it('rewrites technical wording into everyday words', function () {
    expect(CheckExplainer::plain('URL uses a raw IP address instead of a domain name'))->toContain('string of numbers');
    expect(CheckExplainer::plain('WHOIS data unavailable for this domain'))->not->toContain('WHOIS');
    expect(CheckExplainer::plain('3 out of 90 security vendors on VirusTotal flagged this URL'))->toContain('security companies');
    expect(CheckExplainer::plain('Nothing technical here'))->toBe('Nothing technical here');
    expect(CheckExplainer::plain('Urgency language detected (e.g. "urgent", "24 hours", "act now", "segera") Suspicious call-to-action phrasing detected (click/follow link)'))
        ->toBe('It pressures you to act quickly (for example "urgent", "within 24 hours", "act now" or "segera"). It urges you to click or follow a link.');
});

it('summarises the checks in one sentence', function () {
    $checks = collect([
        ['status' => 'HIGH RISK'], ['status' => 'SAFE'], ['status' => 'SAFE'], ['status' => 'UNKNOWN'],
    ]);

    expect(CheckExplainer::summary($checks))
        ->toBe('We ran 4 checks: 1 raised a warning, 2 found no issues and 1 was unavailable or for information only.');
});