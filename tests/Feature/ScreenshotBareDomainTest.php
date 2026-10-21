<?php

use App\Services\AnalysisEngine;

beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

test('a domain written without https is found in screenshot text', function () {
    expect($this->engine->extractBareDomainsFromText("Go to: polydrop.top\nEnter promo code: BEAST"))
        ->toBe(['polydrop.top']);
});

test('the same domain is listed once, in lower case, without www', function () {
    expect($this->engine->extractBareDomainsFromText('Visit WWW.POLYDROP.TOP now. Banner: .polydrop.top'))
        ->toBe(['polydrop.top']);
});

test('a domain with a trailing full stop or an @handle after it is still found', function () {
    expect($this->engine->extractBareDomainsFromText('Claim at polydrop.top. Posted by polydrop.top @MrBeast'))
        ->toBe(['polydrop.top']);
});

test('multi-part endings such as com.bn are kept whole', function () {
    expect($this->engine->extractBareDomainsFromText('open www.bibd.com.bn now'))->toBe(['bibd.com.bn']);
});

test('a link that breaks after https:// onto the next line is read as one link', function () {
    $text = "Sila semak butiran pelanggaran yang tersedia. https://\neqncgov-bn.cfd/bn";

    expect($this->engine->scoreMessageRules($text)['url'])->toBe('https://eqncgov-bn.cfd/bn');
    expect($this->engine->extractBareDomainsFromText($text))->toBe([]);
});

test('scam endings such as .cfd are found in a bare domain', function () {
    expect($this->engine->extractBareDomainsFromText("Sila semak eqncgov-bn.cfd/bn\nTerima kasih"))
        ->toBe(['eqncgov-bn.cfd']);
});

test('things that only look like domains are ignored', function (string $text) {
    expect($this->engine->extractBareDomainsFromText($text))->toBe([]);
})->with([
    'email address' => 'mail me at someone@example.com please',
    'file names' => 'see photo.png and report.pdf and notes.txt and index.html',
    'abbreviations and numbers' => 'e.g. this, i.e. that, 10:15 2.5K v1.2.3 33.9M Mr.Beast',
    'full link already given' => 'Click https://evil.example.com/x now',
    'ordinary sentence' => 'You are receiving this email because we received a password reset request.',
    'empty text' => '',
]);