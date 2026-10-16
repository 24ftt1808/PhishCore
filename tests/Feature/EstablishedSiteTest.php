<?php

use App\Services\AnalysisEngine;

function brandSyntax(string $url): array
{
    return (new AnalysisEngine)->checkUrlSyntax($url);
}

function calmed(array $syntax, int $other = 0, ?int $days = 6711, array $vt = ['malicious' => 0, 'total' => 92]): array
{
    return (new AnalysisEngine)->softenBrandWordForEstablishedSite($syntax, $other, ['domain_age_days' => $days], $vt);
}

test('the brand word rule reports its own points', function () {
    expect(brandSyntax('https://www.garena-shop.com/')['brand_rule_points'])->toBe(30)
        ->and(brandSyntax('https://example.com/')['brand_rule_points'])->toBe(0);
});

test('an old site that nobody flags is not treated as a copy because of a brand word', function () {
    $result = calmed(brandSyntax('https://www.garena-shop.com/'));

    expect($result['points'])->toBe(0)
        ->and($result['flagged'])->toBeFalse()
        ->and($result['neutral'])->toBeTrue()
        ->and($result['reasons'][0])->toContain('established site')->toContain('18 years')->toContain('92 security companies');
});

test('a young site keeps every point', function () {
    expect(calmed(brandSyntax('https://www.garena-shop.com/'), days: 100)['points'])->toBe(30)
        ->and(calmed(brandSyntax('https://www.garena-shop.com/'), days: 729)['points'])->toBe(30)
        ->and(calmed(brandSyntax('https://www.garena-shop.com/'), days: 730)['points'])->toBe(0);
});

test('a site with an unknown age keeps every point', function () {
    expect(calmed(brandSyntax('https://www.garena-shop.com/'), days: null)['points'])->toBe(30);
});

test('a site that even one security company flags keeps every point', function () {
    expect(calmed(brandSyntax('https://www.garena-shop.com/'), vt: ['malicious' => 1, 'total' => 92])['points'])->toBe(30);
});

test('a site that too few security companies have looked at keeps every point', function () {
    expect(calmed(brandSyntax('https://www.garena-shop.com/'), vt: ['malicious' => 0, 'total' => 12])['points'])->toBe(30)
        ->and(calmed(brandSyntax('https://www.garena-shop.com/'), vt: ['malicious' => null, 'total' => null, 'unavailable' => true])['points'])->toBe(30);
});

test('any other warning in the scan keeps every point', function () {
    expect(calmed(brandSyntax('https://www.garena-shop.com/'), other: 10)['points'])->toBe(30);
});

test('other problems in the address itself keep every point', function () {
    $long = brandSyntax('https://www.garena-shop.com/'.str_repeat('a', 80));

    expect($long['points'])->toBeGreaterThan(30)
        ->and(calmed($long)['points'])->toBe($long['points']);
});

test('a digit-for-letter disguise is never calmed down', function () {
    $disguise = brandSyntax('https://www.paypa1.com/');

    expect($disguise['points'])->toBe(30)
        ->and($disguise['brand_rule_points'])->toBe(0)
        ->and(calmed($disguise)['points'])->toBe(30);
});

test('a result without a brand word is returned unchanged', function () {
    $plain = brandSyntax('https://example.com/');

    expect(calmed($plain))->toBe($plain);
});
