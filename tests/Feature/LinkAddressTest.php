<?php

use App\Services\AnalysisEngine;
use App\Support\RegistrableDomain;

/**
 * Address tricks found in public phishing link lists (OpenPhish, Phishing.Database) that the address check
 * missed, and the "@" and advance-fee rules narrowed after they flagged ordinary links and emails.
 * The addresses are written for this test in the shape of real phishing links; none is a live site.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

function addressReasons(AnalysisEngine $engine, string $url): string
{
    return implode(' ', $engine->checkUrlSyntax($url)['reasons']);
}

test('a brand name joined to other words on a free website builder or a cheap ending is flagged', function (string $url, string $brand) {
    expect(addressReasons($this->engine, $url))->toContain("brand name \"{$brand}\" on");
})->with([
    'shopee on blogspot' => ['https://shopeeakhirtahun172.blogspot.com/', 'shopee'],
    'comcast on weebly' => ['https://comcastxfiniylo.weebly.com/', 'comcast'],
    'dpd on a cheap ending' => ['https://mydpd.zkcma.xyz/track-ch', 'dpd'],
    'livelo on firebase' => ['https://pontos-livelo.web.app/', 'livelo'],
]);

test('a brand\'s own page and ordinary words that contain a short brand name are not flagged', function (string $url) {
    expect(addressReasons($this->engine, $url))->not->toContain('brand name');
})->with([
    'microsoft.github.io' => 'https://microsoft.github.io/',
    'pineapple' => 'https://pineapplefarm.blogspot.com/',
    'groups' => 'https://mygroups.top/',
    'purchase' => 'https://easypurchase.vercel.app/',
]);

test('a cheap domain ending counts as a small warning', function () {
    $result = $this->engine->checkUrlSyntax('https://2702735.cc/');

    expect($result['points'])->toBe(10)
        ->and(implode(' ', $result['reasons']))->toContain('".cc"');
});

test('an address that starts like a real ".com" site is flagged, a "www" hyphen name is not', function () {
    expect(addressReasons($this->engine, 'https://usps.com-tracklfi.top/us'))->toContain('starts like "usps.com"')
        ->and(addressReasons($this->engine, 'https://puroletor.com-payzpx.vip/ca'))->toContain('starts like "puroletor.com"')
        ->and($this->engine->checkUrlSyntax('http://www.net-watch.org/1.htm')['points'])->toBe(0);
});

test('a brand and a sign-in word in the page address of an unrelated site is flagged', function () {
    expect(addressReasons($this->engine, 'https://ahinsashoes.example/css/paypal/login.php'))->toContain('names "paypal" next to sign-in')
        ->and(addressReasons($this->engine, 'https://news.example/2002/10/paypal-ipo-shares'))->not->toContain('next to sign-in')
        ->and(addressReasons($this->engine, 'https://www.paypal.com/signin'))->not->toContain('next to sign-in');
});

test('Blogspot under a country ending is a free website builder', function () {
    $result = $this->engine->checkFreeHostingPlatform('https://5fgdff322d.blogspot.fi/');

    expect($result['flagged'])->toBeTrue()
        ->and($result['platform'])->toBe('blogspot.fi')
        ->and(RegistrableDomain::of('someone.blogspot.co.ke'))->toBe('someone.blogspot.co.ke');
});

test('an "@" in a page address is ordinary, such as a YouTube, TikTok or Medium handle', function (string $url) {
    expect($this->engine->checkUrlSyntax($url)['points'])->toBe(0);
})->with([
    'https://www.youtube.com/@MrBeast',
    'https://www.tiktok.com/@bruneitourism',
    'https://medium.com/@someone/how-i-learned-laravel',
]);

test('an "@" before the website name still hides where the link goes', function () {
    expect(addressReasons($this->engine, 'http://example.com@evil.example/login'))->toContain('"@" symbol');
});

test('an email address carried in a link is a smaller warning', function () {
    $result = $this->engine->checkUrlSyntax('https://am-t.example/library/index/?rand=13&email=someone%40mail.example');

    expect($result['points'])->toBe(15)
        ->and(implode(' ', $result['reasons']))->toContain('carries an email address');
});

test('"inheritance" and big sums in ordinary emails are not advance-fee wording', function (string $subject, string $body) {
    expect(implode(' ', $this->engine->scoreEmailRules('dev@lists.example', $subject, $body)['reasons']))
        ->not->toContain('Advance-fee');
})->with([
    'programming' => ['Re: class design', 'Multiple inheritance in C++ is messy, so prefer composition over inheritance here.'],
    'history' => ['Re: Roman Empire', 'The rules of inheritance under Roman law favoured the eldest son.'],
    'news' => ['Court news', 'The jury rejected the $700 million suit by an internet company that claimed the patent was stolen.'],
]);

test('a real inheritance scam is still advance-fee wording', function () {
    $body = 'I am contacting you as the next of kin. My late client left an inheritance fund of $4.5 million and I want to transfer it to you.';

    expect(implode(' ', $this->engine->scoreEmailRules('barrister.law@mail.example', 'Urgent reply', $body)['reasons']))
        ->toContain('Advance-fee');
});