<?php

use App\Services\AnalysisEngine;

/**
 * Scam texts that hide from filters (full-width or "fancy" letters, digits in place of letters, words spelt
 * out letter by letter, "defanged" links) and scam types that were missing (a call to "confirm" a code, phone
 * dial codes that move money, "call our agent", job fees, SIM re-registration). The examples are written for
 * this test in the style of real scam texts, with Brunei names; none is a real message or number.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

function messageReasons(AnalysisEngine $engine, string $text): string
{
    return implode(' ', $engine->scoreMessageRules($text)['reasons']);
}

test('full-width letters are read as ordinary letters, link included', function () {
    $result = $this->engine->scoreMessageRules('ＢＩＢＤ： Ｙｏｕｒ　ａｃｃｏｕｎｔ　ｗｉｌｌ　ｂｅ　ｃｌｏｓｅｄ． Ｖｅｒｉｆｙ： ｈｔｔｐ：／／ｂｉｂｄ－ｖｅｒｉｆｙ．ｎｅｔ');

    expect($result['url'])->toBe('http://bibd-verify.net')
        ->and($result['score'])->toBeGreaterThanOrEqual(25)
        ->and(implode(' ', $result['reasons']))->toContain('Text is disguised');
});

test('"fancy" bold letters are read as ordinary letters', function () {
    expect(messageReasons($this->engine, '𝐘𝐎𝐔 𝐇𝐀𝐕𝐄 𝐖𝐎𝐍 a BND500 voucher'))->toContain('Prize/reward language');
});

test('Chinese and Japanese punctuation is not a disguise', function () {
    expect(messageReasons($this->engine, '会议明天上午十点开始！（请准时）'))->not->toContain('Text is disguised')
        ->and(messageReasons($this->engine, '明日の会議は十時からです！（よろしく）'))->not->toContain('Text is disguised');
});

test('digits used as letters are read back and count as a disguise', function () {
    $result = $this->engine->scoreMessageRules('D5T Alert: y0ur acc0unt is on h0ld. Re-activate at dst-he1p.net/unl0ck or lose your ba1ance.');

    expect(implode(' ', $result['reasons']))->toContain('Text is disguised')
        ->and($result['score'])->toBeGreaterThanOrEqual(25);
});

test('ordinary words with digits are not a disguise', function (string $text) {
    expect(messageReasons($this->engine, $text))->not->toContain('Text is disguised');
})->with([
    'music files' => 'Download the mp3s from the shared folder and cut them with mp3directcut.',
    'codes in links' => 'Read more: https://news.example/p1sylj1blfvqjrrr and https://news.example/a3bcd1efgh',
    'one product code' => 'Your order for the Galaxy S24 Ultra (model s1mx) has shipped.',
    'email addresses' => 'Write to support@paypal.example or help@b4nk.example for help.',
]);

test('a word spelt out letter by letter also joins short words in the same message', function () {
    expect(messageReasons($this->engine, 'C O N G R A T S ! You have w o n BND500 in the lucky draw. Claim before 6pm today.'))
        ->toContain('Prize/reward language')
        ->toContain('Text is disguised');
});

test('a "defanged" link is read as the real link', function () {
    expect($this->engine->scoreMessageRules('Reactivate your wallet. Copy this to your browser: hxxp://bibd-verify[.]top/login (remove brackets)')['url'])
        ->toBe('http://bibd-verify.top/login')
        ->and($this->engine->scoreMessageRules('Reactivate your wallet here: hxxps[:]//secure-bibd(.)com/x')['url'])
        ->toBe('https://secure-bibd.com/x');
});

test('a link that hides the real website after "@" is a strong warning', function () {
    $result = $this->engine->checkUrlSyntax('https://www.bibd.com.bn@secure-login-bn.ru/auth');

    expect($result['points'])->toBeGreaterThanOrEqual(45)
        ->and(implode(' ', $result['reasons']))->toContain('really opens "secure-login-bn.ru"');
});

test('an "@" without a website name in front only gets the ordinary warning', function () {
    $result = $this->engine->checkUrlSyntax('https://user@example.com/page');

    expect($result['points'])->toBe(25);
});

test('a promise that someone will call to confirm your code is flagged, in English and Malay', function (string $text) {
    expect(messageReasons($this->engine, $text))->toContain('will call you to confirm or collect the code');
})->with([
    'English' => 'BIBD: 448192 is your TAC. Do not share it with anyone. Our officer will now call you to confirm this TAC and secure your account.',
    'Malay' => 'Kod TAC anda 448192. Pegawai kami akan menghubungi anda untuk mengesahkan kod TAC ini.',
]);

test('a dial code with a PIN or someone\'s number is flagged; an ordinary data code is not', function () {
    expect(messageReasons($this->engine, 'To receive your BND250 prize, dial *123*1*7123456*250# and enter your PIN when asked. Offer ends today.'))
        ->toContain('dial a phone code')
        ->and(messageReasons($this->engine, 'Imagine: Get 2GB for BND2, valid 7 days. Dial *171# to buy. Reply STOP to opt out.'))
        ->not->toContain('dial a phone code');
});

test('"call our agent" about a payment is flagged; a bank\'s own number is not', function () {
    $scam = $this->engine->scoreMessageRules('SECURITY: A transfer of BND340 to an unknown number is pending. If this was not you call our agent on 7123456 within 10 minutes to cancel.');
    $bank = $this->engine->scoreMessageRules('BIBD: A payment of BND34.50 was made with your card. If you did not make this transaction, please call us at 2238181.');

    expect($scam['score'])->toBeGreaterThanOrEqual(25)
        ->and(implode(' ', $bank['reasons']))->not->toContain('agent')
        ->and($bank['score'])->toBeLessThan(25);
});

test('a job offer that asks for a fee first is flagged; an ordinary interview message is not', function () {
    expect($this->engine->scoreMessageRules('Dear applicant, you have been shortlisted for the Admin Clerk post. To confirm your interview slot pay the BND15 medical screening fee and send proof.')['score'])
        ->toBeGreaterThanOrEqual(25)
        ->and($this->engine->scoreMessageRules('Your interview is on Monday at 9am at our Gadong office. Please bring your IC and certificates.')['score'])
        ->toBeLessThan(25);
});

test('a SIM re-registration threat is flagged, in English and Malay; a maintenance notice is not', function () {
    expect($this->engine->scoreMessageRules('NOTICE: SIM cards not re-registered by 20 November will be permanently deactivated. Re-register here: https://sim-reg.verify-now.net/form')['score'])
        ->toBeGreaterThanOrEqual(25)
        ->and(messageReasons($this->engine, 'Kad SIM anda akan dinyahaktifkan jika tidak daftar semula sebelum Jumaat.'))
        ->toContain('re-register')
        ->and($this->engine->scoreMessageRules('Imagine: Your line may be slow tonight from 1am to 3am for network maintenance. Sorry for the trouble.')['score'])
        ->toBeLessThan(25);
});

test('a form asking for your IC and bank number is a request for your details', function () {
    expect(messageReasons($this->engine, 'Free food hampers for 200 families. Register your household using your IC and bank account number here: https://forms.gle/abc123'))
        ->toContain('Credential/verification request');
});