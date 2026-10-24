<?php

use App\Models\PopularDomain;
use App\Services\AnalysisEngine;

/**
 * The look-alike rule also protects the most visited sites of the world (the Tranco list in the database), with
 * no rule written per site. The limits matter as much as the catches: only long names, never a site that is
 * itself on the popular list, and never a name that is merely an ordinary word one letter from another.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;

    foreach (['wikipedia.org' => 8, 'stackoverflow.com' => 150, 'binance.com' => 300, 'github.com' => 20, 'rarelyseen.com' => 40000] as $domain => $position) {
        PopularDomain::create(['domain' => $domain, 'position' => $position]);
    }
});

test('a copy of a very popular site is flagged, whichever spelling trick it uses', function (string $url) {
    $result = $this->engine->checkUrlSyntax($url);

    expect($result['points'])->toBeGreaterThanOrEqual(25)
        ->and(implode(' ', $result['reasons']))->toContain('Wikipedia');
})->with([
    'i for l' => ['https://wikipedla.org'],
    'letter missing' => ['https://wikipedi.org'],
    'letters swapped' => ['https://wikipeida.org'],
    'doubled letter' => ['https://wikipediaa.org'],
    'behind a sub-domain' => ['https://login.wikipedla.org/account'],
]);

test('a long name from the popular list is protected the same way', function () {
    expect($this->engine->checkUrlSyntax('https://stackoverfiow.com')['points'])->toBeGreaterThanOrEqual(25);
});

test('the real site, its other endings and its sub-domains are not flagged', function (string $url) {
    expect($this->engine->checkUrlSyntax($url)['points'])->toBe(0);
})->with([
    'https://www.wikipedia.org',
    'https://en.wikipedia.org/wiki/Brunei',
    'https://wikipedia.com',
]);

test('a name one letter from a popular name that is an ordinary word is not flagged', function () {
    // finance is one letter from binance, and a real word. Only a dropped or swapped letter, or look-alike letters, count.
    expect($this->engine->checkUrlSyntax('https://finance.com')['points'])->toBe(0);
});

test('short popular names are not used, because many real sites sit one letter from them', function () {
    expect($this->engine->checkUrlSyntax('https://githb.com')['points'])->toBe(0);
});

test('a site that is itself on the popular list is never called a copy of another', function () {
    PopularDomain::create(['domain' => 'wikipediaa.org', 'position' => 70000]);

    expect($this->engine->checkUrlSyntax('https://wikipediaa.org')['points'])->toBe(0);
});

test('the look-alike names only come from the top of the ranking', function () {
    // rarelyseen.com is at 40000: popular enough to be real, too far down to be copied.
    expect($this->engine->checkUrlSyntax('https://rarelysen.com')['points'])->toBe(0);
});

test('an email from a copy of a popular site is flagged, and one from the real site is not', function () {
    $fake = $this->engine->checkEmailDomain('support@wikipedla.org');
    $real = $this->engine->checkEmailDomain('support@wikipedia.org');

    expect($fake['points'])->toBeGreaterThanOrEqual(25)->and($real['points'])->toBe(0);
});

test('with no popular list in the database the rule does nothing and nothing breaks', function () {
    PopularDomain::query()->delete();
    cache()->forget('popular_lookalike_names');

    expect($this->engine->checkUrlSyntax('https://wikipedla.org')['points'])->toBe(0);
});