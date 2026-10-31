<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;

/**
 * Bulk scam email that used to slip through: sent from a throwaway Microsoft 365 address, with a body of
 * random filler and the real message in a picture, prize and casino lures, and links hidden behind short
 * links, cloud storage, free website builders and cheap domain endings. The examples are written for this
 * test, modelled on public phishing collections, so no real phishing email is stored in the repository.
 * Each signal is a supporting one: on its own it never makes an email "suspicious".
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
    config(['services.ai_text.enabled' => false]);
    Http::fake();
});

/** Random letters and digits, the way bulk scam mail pads its body. */
function bulkScamFiller(): string
{
    return 'ryVvybyLFGk9hB6mfIwXNke9YbYPp4hzPak7WFB0QhbDN87lbuijzIhvp7VAiDA3Xfkl7zHWfCXhk9QB3dRGf3hKPJGRWcv9fHsqPtzNqOJO0X9JYC18LmKFn9Z5jitXdY1TVuQDwOjd5ZArEhT6U7JmH2yElIqgjNcgr7lGYt';
}

test('a sender on a default Microsoft 365 address is a warning sign', function () {
    $result = $this->engine->checkEmailDomain('promo@kiofrozenkings2504.onmicrosoft.com');

    expect($result['points'])->toBe(20)
        ->and(implode(' ', $result['reasons']))->toContain('default address that comes with a new Microsoft 365 account');
});

test('Microsoft\'s own domain and Firebase app senders are not treated as throwaway addresses', function (string $sender) {
    expect(implode(' ', $this->engine->checkEmailDomain($sender)['reasons']))->not->toContain('Microsoft 365');
})->with([
    'the suffix itself' => 'someone@onmicrosoft.com',
    'microsoft.com' => 'account-security-noreply@accountprotection.microsoft.com',
    'firebase app' => 'noreply@my-school-app.firebaseapp.com',
]);

test('a throwaway sender alone does not make an ordinary email suspicious', function () {
    expect($this->engine->scoreEmailRules('it@contoso.onmicrosoft.com', 'Lunch', 'The team lunch is at 12:30 on Friday. See you there.')['score'])
        ->toBeLessThan(25);
});

test('random filler that makes up most of the message is detected', function () {
    $result = $this->engine->detectFillerText(bulkScamFiller());

    expect($result['flagged'])->toBeTrue()
        ->and($result['points'])->toBe(20);
});

test('ordinary text, long links, a single code and attached file data are not filler', function (string $body) {
    expect($this->engine->detectFillerText($body)['flagged'])->toBeFalse();
})->with([
    'plain words' => 'Hi Adam, the meeting moved to 3pm. The agenda is attached. Thanks, Siti',
    'tracking link' => 'Read the full story: https://click.example.com/?qs=831deb24786caa72aedb2cae209b159cb2dd7fe602c71e82923aB9xYz12QwErTy',
    'one API key in a normal email' => 'Your new API key is sk9LmQ2xR7vTpW4zA8bN3cK6dF1gH5jYu0eIo for the sandbox. Keep it private and rotate it every 90 days. Contact support if you need help setting up the integration.',
    'base64 attachment lines' => "Please find the file attached.\n".str_repeat("JVBERi0xLjQKJcOkw7zDtsOfCjIgMCBvYmoKPDwvTGVuZ3RoIDMgMCBSL0ZpbHRlci9GbGF0ZURlY29kZT4+\n", 5),
    'empty' => '',
]);

test('an image-only scam from a throwaway sender is flagged by its subject and filler', function () {
    $result = $this->engine->scoreEmailRules('noreply@bngamerousin01.onmicrosoft.com', 'A special gift is waiting in your account', bulkScamFiller());

    expect($result['score'])->toBeGreaterThanOrEqual(25);
});

test('the email scan shows the filler check only when it is found', function () {
    $filler = $this->engine->analyze('email', null, 'promo@deals.example', null, null, null, 'Your reward', bulkScamFiller());
    $plain = $this->engine->analyze('email', null, 'promo@deals.example', null, null, null, 'Hello', 'See you at the meeting tomorrow.');

    expect(collect($filler['checks'])->pluck('name'))->toContain('Hidden Filler Text')
        ->and(collect($plain['checks'])->pluck('name'))->not->toContain('Hidden Filler Text');
});

test('prize and casino lures that were missed are now read as prize wording', function (string $subject) {
    $reasons = implode(' ', $this->engine->scoreEmailRules('promo@deals.example', $subject, 'Open the link to continue.')['reasons']);

    expect($reasons)->toContain('Prize/reward language');
})->with([
    'Attention, your golden ticket is ready to be picked up',
    'You have an unclaimed reward',
    'Please check your Payout Verification',
    'A special gift is waiting in your account',
    'Check your account for a free gift',
    'Your new player pass is active: 200 free spins inside',
    '100 Balls. $5 No Deposit Required',
    'You have been selected to receive a cash prize',
]);

test('genuine messages that use similar words are not read as prize wording', function (string $subject) {
    $reasons = implode(' ', $this->engine->scoreEmailRules('hr@company.example', $subject, 'Please reply by Friday.')['reasons']);

    expect($reasons)->not->toContain('Prize/reward language');
})->with([
    'You have been selected for the second interview round',
    'Your deposit has been received',
    'Your gift order has shipped',
]);

test('a link hidden behind Outlook SafeLinks is judged by where it really goes', function () {
    $body = 'Protect your car today: https://na01.safelinks.protection.outlook.com/?url=https%3A%2F%2Ffriendandcircle.bond%2Foffer&data=05';
    $result = $this->engine->scoreEmailRules('no-reply@lnhaeup.com', 'Save on repairs', $body);

    expect(implode(' ', $result['reasons']))
        ->toContain('wrapped in a redirect, and really goes to "friendandcircle.bond"')
        ->toContain('".bond"');
});

test('short links count, except a short link from the site that owns it', function () {
    $stranger = $this->engine->scoreEmailRules('alerts@offers.example', 'Membership', 'Renew here https://t.co/SovxhLDYkV');
    $owner = $this->engine->scoreEmailRules('info@x.com', 'New post', 'See the post https://t.co/SovxhLDYkV');

    expect(implode(' ', $stranger['reasons']))->toContain('short link')
        ->and(implode(' ', $owner['reasons']))->not->toContain('short link');
});

test('a web page kept in cloud storage counts, a picture there does not', function () {
    $page = $this->engine->scoreEmailRules('news@offers.example', 'Find anyone', 'Open now https://storage.googleapis.com/educatione/report%20(2).html#abc');
    $picture = $this->engine->scoreEmailRules('news@offers.example', 'Our newsletter', 'Logo https://storage.googleapis.com/brand-assets/logo.png');

    expect(implode(' ', $page['reasons']))->toContain('stored as a file in cloud storage')
        ->and(implode(' ', $picture['reasons']))->not->toContain('cloud storage');
});

test('links with cheap endings count; a site\'s own link and a shared blog do not', function () {
    $cheap = $this->engine->scoreEmailRules('team@offers.example', 'Offer', 'Details at https://newtrustbridg.bond/cl/899');
    $own = $this->engine->scoreEmailRules('team@store.bond', 'Offer', 'Details at https://store.bond/sale');
    $blog = $this->engine->scoreEmailRules('friend@mail.example', 'My trip', 'Urgent, read my new post before Friday: https://monkeygirl.blogspot.com/');

    expect(implode(' ', $cheap['reasons']))->toContain('".bond"')
        ->and($own['reasons'])->toBe([])
        ->and($blog['score'])->toBeLessThan(25);
});

test('a digest that links many different sites is not judged by where each link goes', function () {
    $body = 'Updated weblogs: https://bit.ly/3xYzAb https://radio.weblogs.example/ https://blogs.salon.example/ '
        .'https://www.bunda.example/writings https://www.neuroprosthesis.example/ https://love-always.example/';

    expect(implode(' ', $this->engine->scoreEmailRules('webmaster@blo.example', 'updated weblogs', $body)['reasons']))
        ->not->toContain('short link');
});

test('typical bulk scam emails are now flagged', function (string $from, string $subject, string $body) {
    expect($this->engine->scoreEmailRules($from, $subject, $body)['score'])->toBeGreaterThanOrEqual(25, $subject);
})->with([
    'casino from a throwaway tenant' => ['promo@frostbornwarriors01.onmicrosoft.com', 'Unlock your special reward of a $150 FREE Welcome Bonus', bulkScamFiller()],
    'payout from a throwaway tenant' => ['info@apaksiasjdjxqd.onmicrosoft.com', 'Please check your payout verification!', bulkScamFiller()],
    'prize survey behind a short link' => ['yelek@cutynaledriite.com', 'Final notice for your gift', 'Congratulations! Your name came up for a customer survey. Your reward: a cookware set. https://t.co/loM5eS9RjY'],
]);