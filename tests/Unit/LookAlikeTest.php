<?php

use App\Support\LookAlike;

function lookAlikeBrands(): array
{
    return ['paypal', 'google', 'amazon', 'microsoft', 'facebook', 'netflix', 'shopee', 'lazada', 'binance',
        'livelo', 'linkedin', 'instagram', 'dropbox', 'apple', 'maybank', 'citibank', 'metamask', 'whatsapp', 'spotify', 'twitter'];
}

test('names written to be mistaken for a brand are found', function (string $host, string $type, string $brand) {
    $found = LookAlike::find($host, lookAlikeBrands());

    expect($found)->not->toBeNull()
        ->and($found['type'])->toBe($type)
        ->and($found['brand'])->toBe($brand);
})->with([
    'repeated letter' => ['gooogle.com', 'lookalike', 'google'],
    'doubled last letter' => ['netflixx.com', 'lookalike', 'netflix'],
    'rn for m' => ['arnazon.com', 'lookalike', 'amazon'],
    'i for l' => ['paypai.com', 'lookalike', 'paypal'],
    'l for i' => ['linkedln.com', 'lookalike', 'linkedin'],
    'digit for letter' => ['dropb0x.com', 'lookalike', 'dropbox'],
    'hyphen inside the name' => ['pay-pal.com', 'lookalike', 'paypal'],
    'behind a sub-domain' => ['login.rnicrosoft.com', 'lookalike', 'microsoft'],
    'letter missing' => ['microsft.com', 'typo', 'microsoft'],
    'letters swapped' => ['googel.com', 'typo', 'google'],
    'swapped in a Brunei-used brand' => ['maybnak.com.my', 'typo', 'maybank'],
    'Cyrillic letters only' => ['аррӏе.com', 'homoglyph', 'apple'],
]);

test('a name that mixes alphabets is flagged even without a brand', function () {
    expect(LookAlike::find('аpple.com', lookAlikeBrands())['type'])->toBe('mixed')
        ->and(LookAlike::find('paypаl.com', lookAlikeBrands())['type'])->toBe('mixed')
        ->and(LookAlike::mixesAlphabets('pаypal'))->toBeTrue()
        ->and(LookAlike::mixesAlphabets('paypal'))->toBeFalse()
        ->and(LookAlike::mixesAlphabets('пример'))->toBeFalse();
});

test('punycode names are read back into their letters', function () {
    expect(LookAlike::find('xn--pple-43d.com', lookAlikeBrands())['type'])->toBe('mixed')
        ->and(LookAlike::find('xn--80ak6aa92e.com', lookAlikeBrands())['type'])->toBe('homoglyph');
})->skip(! function_exists('idn_to_utf8'), 'needs the intl extension');

test('ordinary words and real sites one letter away from a brand are not flagged', function (string $host) {
    expect(LookAlike::find($host, lookAlikeBrands()))->toBeNull();
})->with([
    'finance.com', 'lively.com', 'shoppe.com', 'goggle.com', 'goggles.com', 'googles.com', 'appie.nl',
    'apply.com', 'amazing.com', 'twitch.tv', 'paypay.ne.jp', 'bing.com', 'baidu.com', 'shopify.com',
    'wikipedia.org', 'github.com', 'cloudflare.com', 'politeknikbrunei.edu.bn', 'ubd.edu.bn', 'bruneitimes.com',
    'bücher.de', 'пример.рф', 'xn--bcher-kva.de',
]);

test('the brand itself, its sub-domains and short names are left to the other rules', function () {
    expect(LookAlike::find('paypal.com', lookAlikeBrands()))->toBeNull()
        ->and(LookAlike::find('www.google.com', lookAlikeBrands()))->toBeNull()
        ->and(LookAlike::find('ebay.com', lookAlikeBrands()))->toBeNull()
        ->and(LookAlike::find('203.0.113.9', lookAlikeBrands()))->toBeNull()
        ->and(LookAlike::find('[2001:db8::1]', lookAlikeBrands()))->toBeNull();
});

test('a real site that looks like a brand can be listed as known good', function () {
    expect(LookAlike::find('gooogle.com', lookAlikeBrands()))->not->toBeNull()
        ->and(LookAlike::find('gooogle.com', lookAlikeBrands(), ['gooogle.com']))->toBeNull()
        ->and(LookAlike::find('www.gooogle.com', lookAlikeBrands(), ['gooogle.com']))->toBeNull();
});

test('hyphens inside punycode names are not counted', function () {
    expect(LookAlike::hyphenCount('xn--pypal-4ve.com'))->toBe(0)
        ->and(LookAlike::hyphenCount('secure-login-bank.com'))->toBe(2)
        ->and(LookAlike::hyphenCount('a-b.xn--bcher-kva.de'))->toBe(1);
});
