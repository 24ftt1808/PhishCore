<?php

use App\Services\AnalysisEngine;

/**
 * Brunei-focused checks on the message and address rules, using the made-up sample set in
 * tests/Fixtures/brunei-messages.php. Rules only: no network, phone or AI checks, so a real
 * scan can only score a scam higher.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
    $this->set = require base_path('tests/Fixtures/brunei-messages.php');
});

test('Brunei scam messages are rated suspicious or phishing', function () {
    $missed = [];
    foreach (['tuning', 'held_out'] as $group) {
        foreach ($this->set[$group] as $row) {
            [$label, , , $text] = $row;
            if ($label !== 'scam' || ($row[4] ?? false)) {
                continue;
            }
            if ($this->engine->scoreMessageRules($text)['score'] < 25) {
                $missed[] = $text;
            }
        }
    }

    expect($missed)->toBe([]);
});

test('normal Brunei messages and office email stay clean', function () {
    $flagged = [];
    foreach (['tuning', 'held_out', 'normal_extra'] as $group) {
        foreach ($this->set[$group] as [$label, , , $text]) {
            if ($label === 'legit' && $this->engine->scoreMessageRules($text)['score'] >= 25) {
                $flagged[] = $text;
            }
        }
    }

    expect($flagged)->toBe([]);
});

test('made-up Brunei look-alike addresses are flagged', function () {
    foreach ($this->set['fake_domains'] as $domain) {
        expect($this->engine->checkUrlSyntax('https://'.$domain)['flagged'])->toBeTrue($domain);
    }
});

test('real Brunei sites are not flagged by the address check', function () {
    foreach ($this->set['real_domains'] as $domain) {
        expect($this->engine->checkUrlSyntax('https://'.$domain)['points'])->toBe(0, $domain);
    }
});

test('a safety warning that quotes scam words is not treated as a scam', function () {
    $warnings = [
        'Polis Diraja Brunei mengingatkan orang ramai supaya berhati-hati dengan panggilan yang mengaku dari pihak polis. Jangan kongsi kod OTP atau maklumat bank dengan sesiapa.',
        'Your OTP is 482913. Never share this code with anyone, including bank staff. BIBD will never ask for your OTP.',
        'Police warn the public about job scams on Telegram that promise easy money for liking videos.',
    ];

    foreach ($warnings as $text) {
        expect($this->engine->scoreMessageRules($text)['score'])->toBeLessThan(25);
    }
});

test('a request alone stays clean, but a request with a pressure tactic is flagged', function () {
    $friend = $this->engine->scoreMessageRules('Can you transfer me BND 20 for the movie tickets? I paid for both of us last week.');
    $scam = $this->engine->scoreMessageRules('Hai ibu, ani nombor baru ku. Phone ku rosak. Boleh transfer BND 800 dulu? Urgent, nanti ku bayar balik.');

    expect($friend['score'])->toBeLessThan(25)
        ->and($scam['verdict'])->toBe('phishing');
});

test('short Brunei names only count in capitals', function () {
    $detect = function (string $text) {
        $m = new ReflectionMethod($this->engine, 'detectBrandInText');
        $m->setAccessible(true);

        return $m->invoke($this->engine, $text)['brand'];
    };

    expect($detect('DST: your line will be suspended'))->toBe('dst')
        ->and($detect('Tap the card on the reader to pay'))->toBeNull()
        ->and($detect('Bank Islam Brunei Darussalam: akaun anda disekat'))->toBe('bank islam brunei darussalam');
});