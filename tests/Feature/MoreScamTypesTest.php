<?php

use App\Services\AnalysisEngine;

function moreScamResult(string $text, bool $forPage = false): array
{
    return (new ReflectionMethod(AnalysisEngine::class, 'checkContentPatterns'))->invoke(new AnalysisEngine, $text, $forPage);
}

function moreScamLabelFound(array $result, string $labelStart): bool
{
    return collect($result['reasons'])->contains(fn ($reason) => str_starts_with($reason, $labelStart));
}

dataset('marketplace_scam scam samples', [
    'hi is the item still available? please pay deposit to hold the item first',
    'bayar deposit dulu untuk simpan barang',
    'to receive the payment the seller must pay insurance fee rm50',
    'my courier will pick up the item, you pay the delivery fee first',
]);

dataset('marketplace_scam normal samples', [
    'we are holding the item for you until friday',
    'the item is still available, you can come and see it',
    'shipping is free on all orders',
    'thanks for buying, your parcel was delivered',
]);

test('marketplace scam wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Buying-and-selling scam wording detected'))->toBeTrue();
})->with('marketplace_scam scam samples');

test('marketplace scam wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Buying-and-selling scam wording detected'))->toBeFalse();
})->with('marketplace_scam normal samples');

dataset('romance_scam scam samples', [
    'i am a soldier deployed overseas, my love i need gift cards to come see you',
    'sayang, i found a good crypto investment, i will teach you',
    'i miss you my darling, i am stranded at the airport and customs want a fee',
]);

dataset('romance_scam normal samples', [
    'i love you mum, can you send me money for lunch',
    'miss you sayang, see you at dinner tonight',
    'i am an engineer, i worked overseas for two years before joining this company',
]);

test('romance scam wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Romance-scam wording detected (an affect'))->toBeTrue();
})->with('romance_scam scam samples');

test('romance scam wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Romance-scam wording detected (an affect'))->toBeFalse();
})->with('romance_scam normal samples');

dataset('rental_scam scam samples', [
    'pay the deposit before viewing to secure the room',
    'bayar deposit dulu sebelum tengok rumah',
    'the owner is abroad so i will send the key by courier after you transfer the deposit',
]);

dataset('rental_scam normal samples', [
    'viewing is on saturday at 3pm, the rent is bnd 600 a month',
    'the landlord will meet you at the house to hand over the key',
    'the owner lives overseas but his agent will show you the house',
]);

test('rental scam wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Rental-scam wording detected (a deposit '))->toBeTrue();
})->with('rental_scam scam samples');

test('rental scam wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Rental-scam wording detected (a deposit '))->toBeFalse();
})->with('rental_scam normal samples');

dataset('money_mule scam samples', [
    'we pay commission, just lend your bank account for transfers',
    'pinjam akaun bank anda, dapat upah rm100 sehari',
    'receive the money in your account then forward it to us and keep 10% commission',
]);

dataset('money_mule normal samples', [
    'we will use your bank account details to transfer your salary of rm2000',
    'please give your account number so we can refund you',
    'the bank account for the school fund is open to all parents',
]);

test('money mule wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Asks you to rent out or lend your bank a'))->toBeTrue();
})->with('money_mule scam samples');

test('money mule wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Asks you to rent out or lend your bank a'))->toBeFalse();
})->with('money_mule normal samples');

dataset('family_emergency scam samples', [
    'your son was in an accident, send money to this account now for the hospital',
    'anak awak kena tangkap, hantar duit bail sekarang',
    'i am in the police station, transfer bnd 500 for bail urgently',
]);

dataset('family_emergency normal samples', [
    'there was an accident on the road near the mall, avoid the area',
    'the suspect was arrested and the police thanked the public',
    'your accident claim has been received, we will contact you',
]);

test('family emergency wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Family-emergency wording detected (an ac'))->toBeTrue();
})->with('family_emergency scam samples');

test('family emergency wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Family-emergency wording detected (an ac'))->toBeFalse();
})->with('family_emergency normal samples');

dataset('remote_access scam samples', [
    'please install anydesk and give me the code so i can fix your account',
    'muat turun teamviewer dan bagi kod',
    'download ultraviewer now and send the id',
]);

dataset('remote_access normal samples', [
    'the weather today is sunny and warm',
    'i use teamviewer at work for the office printers',
    'remote work is allowed on fridays',
]);

test('remote access wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Asks you to install a remote-control app'))->toBeTrue();
})->with('remote_access scam samples');

test('remote access wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Asks you to install a remote-control app'))->toBeFalse();
})->with('remote_access normal samples');

dataset('tech_support scam samples', [
    'warning: your computer has been infected, call microsoft support now',
    'virus detected! do not close this window',
    'windows security alert: contact microsoft support',
]);

dataset('tech_support normal samples', [
    'we installed the latest windows update last night',
    'antivirus scans run every week at 2am',
    'how to remove malware from a phone: a guide for beginners',
]);

test('tech support wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Fake security-alert wording detected (a '))->toBeTrue();
})->with('tech_support scam samples');

test('tech support wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Fake security-alert wording detected (a '))->toBeFalse();
})->with('tech_support normal samples');

dataset('wrong_transfer scam samples', [
    'i sent money to your account by mistake, please send it back',
    'salah transfer ke akaun awak, tolong pulangkan balik',
    'please transfer back, i accidentally sent it to you',
]);

dataset('wrong_transfer normal samples', [
    'i transferred the money to your account for the rent',
    'your refund was processed to your original account',
    'the transfer was successful, thank you',
]);

test('wrong transfer wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Wrong-transfer wording detected ("I sent'))->toBeTrue();
})->with('wrong_transfer scam samples');

test('wrong transfer wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Wrong-transfer wording detected ("I sent'))->toBeFalse();
})->with('wrong_transfer normal samples');

dataset('malicious_app scam samples', [
    'download the apk to track your parcel',
    'sila muat turun parcel.apk dan benarkan pemasangan',
    'enable unknown sources and install the file',
]);

dataset('malicious_app normal samples', [
    'download the app from the app store',
    'our new app is on google play and the app store',
    'update your phone to the latest version',
]);

test('malicious app wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Mentions an APK file or installing from '))->toBeTrue();
})->with('malicious_app scam samples');

test('malicious app wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Mentions an APK file or installing from '))->toBeFalse();
})->with('malicious_app normal samples');

dataset('booking_scam scam samples', [
    'your hotel booking is confirmed, pay the deposit outside the platform by gift card',
    'tempahan homestay: bayar deposit melalui whatsapp akaun lain',
    'to get a discount, pay outside the app',
]);

dataset('booking_scam normal samples', [
    'you can pay directly at the hotel when you arrive',
    'your flight ticket is attached',
    'the booking was paid by credit card, thank you',
]);

test('booking scam wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Booking-scam wording detected (asks you '))->toBeTrue();
})->with('booking_scam scam samples');

test('booking scam wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Booking-scam wording detected (asks you '))->toBeFalse();
})->with('booking_scam normal samples');

dataset('investment_con scam samples', [
    'join our forex mentor vip group for daily signals',
    'earn 5% daily guaranteed',
    'pelaburan platform: to withdraw you must pay tax first',
    'weekly profit of 20%',
]);

dataset('investment_con normal samples', [
    'the stock market closed higher today',
    'savings account interest is 2% per year',
    'our annual report is available on the website',
]);

test('investment con wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Investment-scam wording detected (a trad'))->toBeTrue();
})->with('investment_con scam samples');

test('investment con wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Investment-scam wording detected (a trad'))->toBeFalse();
})->with('investment_con normal samples');

dataset('insurance_scam scam samples', [
    'your insurance policy will be cancelled, click the link to pay now',
    'takaful anda telah dibatalkan, klik pautan untuk bayar',
]);

dataset('insurance_scam normal samples', [
    'your policy renewal is due next month',
    'insurance claims are processed within 14 days',
    'thank you for your payment, your policy is active',
]);

test('insurance scam wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Insurance-cancellation wording detected '))->toBeTrue();
})->with('insurance_scam scam samples');

test('insurance scam wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Insurance-cancellation wording detected '))->toBeFalse();
})->with('insurance_scam normal samples');

dataset('boss_fraud scam samples', [
    'this is your ceo, i need gift cards bought urgently and keep it confidential',
    'bos nak awak transfer duit segera hari ini, rahsia',
    'are you available? i need a favour, buy gift cards',
]);

dataset('boss_fraud normal samples', [
    'the manager approved your leave request',
    'please pay the invoice by friday',
    'the director will give a talk on monday',
]);

test('boss fraud wording is picked up', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Boss or manager impersonation wording de'))->toBeTrue();
})->with('boss_fraud scam samples');

test('boss fraud wording is not raised for normal messages', function (string $text) {
    expect(moreScamLabelFound(moreScamResult($text), 'Boss or manager impersonation wording de'))->toBeFalse();
})->with('boss_fraud normal samples');

test('a single weak scam-type match stays below the suspicious line', function () {
    $result = moreScamResult('The owner is abroad so the key will come by courier.');

    expect(moreScamLabelFound($result, 'Rental-scam wording'))->toBeTrue()
        ->and($result['points'])->toBeLessThan(25);
});

test('an APK or unknown-sources request is rated suspicious on its own', function () {
    expect(moreScamResult('Download the apk to track your parcel')['points'])->toBeGreaterThanOrEqual(25);
});

test('a money-mule request is rated suspicious on its own', function () {
    expect(moreScamResult('Pinjam akaun bank anda, dapat upah RM100 sehari')['points'])->toBeGreaterThanOrEqual(25);
});

test('fake tech-support wording is also picked up on a web page', function () {
    expect(moreScamLabelFound(moreScamResult('Warning: your computer has been infected. Call Microsoft support now.', true), 'Fake security-alert wording'))->toBeTrue();
});

test('most of the new scam types are ignored on web pages, which are full of everyday words', function () {
    expect(moreScamLabelFound(moreScamResult('Your son was in an accident, send money to this account now for bail.', true), 'Family-emergency wording'))->toBeFalse();
    expect(moreScamLabelFound(moreScamResult('Download the apk to track your parcel', true), 'Mentions an APK'))->toBeFalse();
});

test('a fake rental page is picked up, but an ordinary listing is not', function () {
    expect(moreScamLabelFound(moreScamResult('The owner is abroad so the key will be sent by courier after you pay.', true), 'Rental-scam wording'))->toBeTrue();
    expect(moreScamLabelFound(moreScamResult('Pay deposit before viewing the room, WhatsApp us now.', true), 'Rental-scam wording'))->toBeTrue();
    expect(moreScamLabelFound(moreScamResult('Viewing is on Saturday. The deposit is one month rent. The owner lives overseas but his agent will show you the house.', true), 'Rental-scam wording'))->toBeFalse();
});

test('a fake marketplace or investment page is picked up, but ordinary fee wording is not', function () {
    expect(moreScamLabelFound(moreScamResult('The seller must pay insurance fee to receive the payment.', true), 'Buying-and-selling scam wording'))->toBeTrue();
    expect(moreScamLabelFound(moreScamResult('You must pay tax to withdraw your profit.', true), 'Investment-scam wording'))->toBeTrue();
    expect(moreScamLabelFound(moreScamResult('Seller fees apply when you sell an item. Withdrawal fee is 1% of the amount.', true), 'Buying-and-selling scam wording'))->toBeFalse();
    expect(moreScamLabelFound(moreScamResult('Savings interest is 2% per year. Withdrawal fee is 1% of the amount.', true), 'Investment-scam wording'))->toBeFalse();
});

test('a page that warns about scams is not treated as the scam it describes', function (string $text, string $label) {
    expect(moreScamLabelFound(moreScamResult($text, true), $label))->toBeFalse();
})->with([
    'rental warning' => ['Beware of fake landlords: the owner is abroad and sends the key by courier.', 'Rental-scam wording'],
    'investment guide' => ['How to spot an investment scam: you must pay tax to withdraw your profit.', 'Investment-scam wording'],
    'tech support guide' => ['How to avoid fake alerts such as: your computer has been infected, call Microsoft support now.', 'Fake security-alert wording'],
]);
