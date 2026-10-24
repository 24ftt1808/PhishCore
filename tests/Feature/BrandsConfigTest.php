<?php

use App\Services\AnalysisEngine;

/**
 * config/brands.php is data the engine trusts: a wrong official domain would mark a real website as a scam.
 * These tests turn every entry into a check, so a typing mistake fails here and not on a real visitor.
 */
function allOfficialDomains(): array
{
    $domains = [];

    foreach (['official_domains', 'brunei_brands', 'brunei_acronyms'] as $group) {
        foreach (config('brands.'.$group) as $list) {
            foreach ($list as $domain) {
                $domains[$domain] = true;
            }
        }
    }

    return array_keys($domains);
}

test('the brand lists are loaded from config', function () {
    expect(config('brands.official_domains'))->toBeArray()->not->toBeEmpty()
        ->and(config('brands.official_domains.paypal'))->toBe(['paypal.com'])
        ->and(config('brands.brunei_brands.baiduri'))->toBe(['baiduri.com.bn'])
        ->and(config('brands.brunei_acronyms.DST'))->toBe(['dst.com.bn'])
        ->and(config('brands.brunei_gov_names'))->toContain('e-darussalam')
        ->and(config('brands.known_lookalikes'))->toBeArray();
});

test('every official domain looks like a plain lower-case domain name', function () {
    foreach (allOfficialDomains() as $domain) {
        expect($domain)->toMatch('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)+$/');
    }
});

test('every brand name is lower case, except the capital-letter Brunei acronyms', function () {
    foreach (array_keys(config('brands.official_domains') + config('brands.brunei_brands')) as $name) {
        expect($name)->toBe(strtolower($name));
    }

    foreach (array_keys(config('brands.brunei_acronyms')) as $name) {
        expect($name)->toBe(strtoupper($name));
    }
});

test('no official domain is marked as suspicious, as a link or as an email sender', function () {
    $engine = new AnalysisEngine;
    $problems = [];

    foreach (allOfficialDomains() as $domain) {
        foreach (['https://'.$domain, 'https://www.'.$domain, 'https://login.'.$domain.'/account/verify'] as $url) {
            $points = $engine->checkUrlSyntax($url)['points'];
            if ($points > 0) {
                $problems[] = "{$url} scored {$points}";
            }
        }

        $points = $engine->checkEmailDomain('service@'.$domain)['points'];
        if ($points > 0) {
            $problems[] = "service@{$domain} scored {$points}";
        }
    }

    expect($problems)->toBe([]);
});

test('a site listed under known_lookalikes is not treated as a copy of the brand', function () {
    $engine = new AnalysisEngine;

    expect($engine->checkUrlSyntax('https://gooogle.com')['points'])->toBeGreaterThanOrEqual(25);

    config(['brands.known_lookalikes' => ['gooogle.com']]);

    expect($engine->checkUrlSyntax('https://gooogle.com')['points'])->toBe(0);
});

test('a brand added to the config is protected without any code change', function () {
    $engine = new AnalysisEngine;

    expect($engine->checkUrlSyntax('https://zorrexx.com')['points'])->toBeLessThan(25);

    config(['brands.official_domains' => config('brands.official_domains') + ['zorrex' => ['zorrex.example']]]);

    expect($engine->checkUrlSyntax('https://zorrexx.com')['points'])->toBeGreaterThanOrEqual(25);
});

test('every brand name the engine looks for in pages and messages has official domains', function () {
    // Without an entry the engine would guess "name.com" and judge the brand's real site as a copy.
    $known = array_merge(
        config('brands.official_domains'),
        config('brands.brunei_brands'),
        array_change_key_case(config('brands.brunei_acronyms'), CASE_LOWER),
    );

    foreach (['page_brands', 'text_brands'] as $list) {
        foreach (config('brands.'.$list) as $name) {
            expect($known)->toHaveKey($name);
        }
    }
});

test('the parcel and e-wallet brands are present with their confirmed domains', function () {
    expect(config('brands.official_domains.pos laju'))->toContain('pos.com.my', 'poslaju.com.my')
        ->and(config('brands.official_domains.j&t express'))->toContain('jtexpress.com', 'jtexpress.my')
        ->and(config('brands.official_domains.ninja van'))->toBe(['ninjavan.co'])
        ->and(config('brands.official_domains.touchngo'))->toContain('tngdigital.com.my', 'touchngo.com.my');
});
