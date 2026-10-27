<?php

use App\Services\AnalysisEngine;

/*
 * A well-known name used as the start of an address that belongs to another website ("outlook.webaccess-alert.com").
 * It counts only together with a word scams use to sound official, because companies also run their own mail pages
 * at "outlook.theircompany.com". Counted as a brand-word rule, so a long-established clean site is calmed down.
 */

function subdomainSyntax(string $url): array
{
    return (new AnalysisEngine)->checkUrlSyntax($url);
}

test('a brand name in front of an unrelated site that also sounds official is suspicious', function (string $url, string $brand) {
    $result = subdomainSyntax($url);

    expect($result['points'])->toBeGreaterThanOrEqual(25)
        ->and($result['brand_rule_points'])->toBe(25)
        ->and(implode(' ', $result['reasons']))->toContain("brand name \"{$brand}\"")->toContain('different website');
})->with([
    ['https://office365.internal-alerts.com/i/abc123', 'office365'],
    ['https://outlook.webaccess-alert.com/s/abc/def', 'outlook'],
    ['https://dhl.parcel-update-center.com/track', 'dhl'],
    ['https://icloud.secure-login-help.net/', 'icloud'],
    ['https://secure.outlook.example-company.com/', 'outlook'],
]);

test('the same names are left alone when nothing else about the address sounds official', function (string $url) {
    $result = subdomainSyntax($url);

    expect($result['brand_rule_points'])->toBe(0)
        ->and(implode(' ', $result['reasons']))->not->toContain('different website');
})->with([
    'a company running its own mail page' => ['https://outlook.contoso.com/owa'],
    'a plain business site' => ['https://dhl.shop-example.com/'],
    'a company in Brunei' => ['https://outlook.company.com.bn/'],
    'a mail page on a big mail site' => ['https://outlook.office365.com/mail'],
]);

test('real brand sites and ordinary sub-pages are never touched', function (string $url) {
    $result = subdomainSyntax($url);

    expect($result['brand_rule_points'])->toBe(0)
        ->and($result['points'])->toBe(0);
})->with([
    'https://outlook.live.com/mail',
    'https://mail.google.com/',
    'https://support.microsoft.com/',
    'https://account.live.com/',
    'https://www.dhl.com/',
    'https://login.example.com/',
    'https://www.hsbc.com.bn/',
]);

test('a name that has no site in front of it is not a subdomain', function () {
    expect(subdomainSyntax('https://dhl.co.uk/')['brand_rule_points'])->toBe(0)
        ->and(subdomainSyntax('https://ups.com/')['brand_rule_points'])->toBe(0);
});

test('a brand-word rule that already fired is not counted a second time', function () {
    // "google" is already caught by the brand-word rule, so the new rule must not add a second brand reason.
    $result = subdomainSyntax('https://dhl.google-update.com/');

    expect(count(preg_grep('/brand name/', $result['reasons'])))->toBe(1)
        ->and($result['brand_rule_points'])->toBe(30);
});

test('a site listed as genuine in known_lookalikes is skipped', function () {
    config(['brands.known_lookalikes' => ['webaccess-alert.com']]);

    expect(subdomainSyntax('https://outlook.webaccess-alert.com/')['brand_rule_points'])->toBe(0);
});

test('an old site that nobody flags is calmed down, a young or flagged one keeps the points', function () {
    $engine = new AnalysisEngine;
    $syntax = subdomainSyntax('https://outlook.helpdesk-company.com/');

    expect($syntax['points'])->toBe(25);

    $calm = $engine->softenBrandWordForEstablishedSite($syntax, 0, ['domain_age_days' => 4000], ['malicious' => 0, 'total' => 90]);
    $young = $engine->softenBrandWordForEstablishedSite($syntax, 0, ['domain_age_days' => 30], ['malicious' => 0, 'total' => 90]);
    $flagged = $engine->softenBrandWordForEstablishedSite($syntax, 0, ['domain_age_days' => 4000], ['malicious' => 3, 'total' => 90]);

    expect($calm['points'])->toBe(0)->and($young['points'])->toBe(25)->and($flagged['points'])->toBe(25);
});

test('the three live links this rule was written for are no longer called clean on their address alone', function () {
    // Scores were 5 and 15 before the rule; the engine calls a link suspicious from 25 points.
    foreach (['https://office365.internal-alerts.com/i/d5b9af0d', 'https://outlook.webaccess-alert.com/s/63bz/584dd8/90eab167'] as $url) {
        expect(subdomainSyntax($url)['points'])->toBeGreaterThanOrEqual(25);
    }
});