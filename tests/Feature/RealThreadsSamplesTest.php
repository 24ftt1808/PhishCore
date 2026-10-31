<?php

use App\Services\AnalysisEngine;

/**
 * Real scam messages that people in Brunei shared publicly on Threads (the screenshots are in
 * scan-screenshots/real-03 to real-08, phone numbers blurred), and one genuine DST message. The text is
 * the message as it appears in each screenshot, without the phone numbers. Rules only: no network, phone
 * or AI checks. These samples were used to write the rules that catch them, so they show the rules work,
 * not a real-world accuracy figure.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

test('the real scams shared on Threads are flagged', function (string $text) {
    expect($this->engine->scoreMessageRules($text)['score'])->toBeGreaterThanOrEqual(25);
})->with([
    'PDRM parking ticket SMS' => 'PDRM Notice:You have an unpaid electronic parking ticket. If you have any outstanding fines, please verify the details and pay through official channels. Click here. pdrm.may2yw.com/okoqk4',
    'RAR statement on WhatsApp' => "FILE_20261027_145401_2848.rar 1.6 MB\nKindly forward the attached Statement of Account to your Finance Manager for review. Please open it on a computer, as all details may not be visible on a mobile device. Thank you.",
    'WhatsApp account verification' => 'Dear WhatsApp user, your account has been flagged as high-risk due to prolonged lack of verification. Please click the "Start Verification" button below to verify your account within the specified time. Failure to verify your account within the 12-hour timeframe will result in permanent suspension. Please be aware of this policy.',
    'Malay traffic-fine SMS' => 'Kesalahan trafik telah direkodkan bagi kenderaan anda. Sila semak butiran pelanggaran yang tersedia. https://lihi1.me/onV0o',
    'fake DHL customs page' => 'Customs clearance required · your shipment is being held at customs. Shipment on hold ACTION NEEDED To complete customs clearance, a handling fee of BND 6.50 must be paid. Pay BND 6.50 now dh1-bru.site',
    'ZIP statement on WhatsApp' => "20260922104622-36A89EA5CE676F.zip 7.4 MB · zip\nPlease forward your company's statement to the finance department for verification.",
]);

test('the genuine DST data message stays clean', function () {
    expect($this->engine->scoreMessageRules('Dear valued customer, you have used 50% of your add-on data. You can check your data balance through the MyDST app.')['score'])
        ->toBeLessThan(25);
});

test('a genuine supplier sending a PDF statement to finance is not flagged on that alone', function () {
    expect($this->engine->scoreMessageRules('Statement_Oct2026.pdf. Hi, please forward the attached statement to your finance department. Thank you, Siti (Accounts)')['score'])
        ->toBeLessThan(25);
});

test('Malay traffic-fine wording is read as a traffic-fine lure', function () {
    expect(implode(' ', $this->engine->scoreMessageRules('Kesalahan trafik telah direkodkan bagi kenderaan anda.')['reasons']))
        ->toContain('Traffic-fine');
});