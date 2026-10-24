<?php

use App\Models\PopularDomain;
use App\Models\Report;
use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;

/**
 * The pasted-headers check (SPF, DKIM, DMARC and Reply-To). The rule behind every number here: real mail fails
 * these checks now and then, so a failure alone is a small signal, a failure on a well-known domain is a large
 * one, a pass never lowers a score, and headers alone can never reach the phishing verdict.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

function authHeaders(string $from, string $auth, ?string $replyTo = null): string
{
    return "Authentication-Results: mx.example; {$auth}\r\nFrom: Someone <{$from}>\r\n"
        .($replyTo ? "Reply-To: {$replyTo}\r\n" : '')
        ."Subject: Hello\r\n";
}

const ALL_PASS = 'spf=pass smtp.mailfrom=a@shop.example; dkim=pass header.d=shop.example; dmarc=pass header.from=shop.example';

test('an email that passes all three checks adds nothing, and says a pass is not proof', function () {
    $result = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', ALL_PASS), 'a@shop.example');

    expect($result['points'])->toBe(0)
        ->and($result['flagged'])->toBeFalse()
        ->and($result['reasons'][0])->toContain('passed sender verification')->toContain('does not prove');
});

test('one failed check is a small signal, below the suspicious line on its own', function () {
    $dkim = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'spf=pass; dkim=fail header.d=shop.example; dmarc=pass'));
    $softfail = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'spf=softfail; dkim=pass; dmarc=pass'));
    $spf = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'spf=fail; dkim=pass; dmarc=pass'));

    expect($dkim['points'])->toBe(5)
        ->and($softfail['points'])->toBe(5)
        ->and($spf['points'])->toBe(10);
});

test('DMARC failing, or several checks failing together, is a larger signal', function () {
    $dmarc = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'dmarc=fail header.from=shop.example'));
    $all = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'spf=fail; dkim=fail; dmarc=fail'));

    expect($dmarc['points'])->toBe(20)
        ->and($all['points'])->toBe(30)
        ->and($all['reasons'][0])->toContain('DMARC, SPF, DKIM');
});

test('a failure on a well-known domain is worth much more, because someone else is using its name', function () {
    PopularDomain::create(['domain' => 'bigshop.example', 'position' => 300]);

    $popular = $this->engine->checkEmailAuthentication(authHeaders('orders@bigshop.example', 'dmarc=fail header.from=bigshop.example'));
    $official = $this->engine->checkEmailAuthentication(authHeaders('service@paypal.com', 'dmarc=fail header.from=paypal.com'));
    $brunei = $this->engine->checkEmailAuthentication(authHeaders('info@bibd.com.bn', 'dmarc=fail header.from=bibd.com.bn'));

    expect($popular['points'])->toBe(40)
        ->and($popular['reasons'][1])->toContain('well-known')
        ->and($official['points'])->toBe(40)
        ->and($brunei['points'])->toBe(40);
});

test('a single SPF failure on a well-known domain stays small, as forwarding causes it', function () {
    $result = $this->engine->checkEmailAuthentication(authHeaders('service@paypal.com', 'spf=fail; dkim=pass; dmarc=pass'));

    expect($result['points'])->toBe(10)->and(implode(' ', $result['reasons']))->not->toContain('well-known');
});

test('a site outside the popular range gets no well-known boost', function () {
    PopularDomain::create(['domain' => 'tinyshop.example', 'position' => 900_000]);

    $result = $this->engine->checkEmailAuthentication(authHeaders('a@tinyshop.example', 'dmarc=fail'));

    expect($result['points'])->toBe(20);
});

test('replies going to a free mailbox are a small warning when a company writes, and only then', function () {
    $company = $this->engine->checkEmailAuthentication(authHeaders('service@paypal.com', 'dmarc=pass', 'seller@gmail.com'));
    $sameDomain = $this->engine->checkEmailAuthentication(authHeaders('service@paypal.com', 'dmarc=pass', 'help@paypal.com'));
    $unknownSender = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'dmarc=pass', 'seller@gmail.com'));
    $webmailSender = $this->engine->checkEmailAuthentication(authHeaders('a@gmail.com', 'dmarc=pass', 'b@outlook.com'));

    expect($company['points'])->toBe(10)
        ->and(implode(' ', $company['reasons']))->toContain('free mailbox')
        ->and($sameDomain['points'])->toBe(0)
        ->and($unknownSender['points'])->toBe(0)
        ->and($webmailSender['points'])->toBe(0);
});

test('a bulk-mail service that is merely popular is not held to the Reply-To rule', function () {
    // Real case: a password-reset email sent through Brevo, replies going to the customer's own mailbox.
    PopularDomain::create(['domain' => 'brevosend.com', 'position' => 5200]);

    $result = $this->engine->checkEmailAuthentication(authHeaders('someone@12368217.brevosend.com', 'spf=pass; dkim=pass; dmarc=pass', 'owner@gmail.com'));

    expect($result['points'])->toBe(0)->and($result['flagged'])->toBeFalse();
});

test('replies going to a different little-known domain are a small warning for a company sender', function () {
    $result = $this->engine->checkEmailAuthentication(authHeaders('service@paypal.com', 'dmarc=pass', 'help@paypal-help.example'));

    expect($result['points'])->toBe(10)->and(implode(' ', $result['reasons']))->toContain('paypal-help.example');
});

test('headers alone are capped at 50 points', function () {
    $result = $this->engine->checkEmailAuthentication(authHeaders('service@paypal.com', 'dmarc=fail', 'seller@gmail.com'));

    expect($result['points'])->toBe(50);
});

test('headers with no verification results, or no headers at all, are unavailable and cost nothing', function () {
    $none = $this->engine->checkEmailAuthentication("From: a@shop.example\r\nSubject: Hi\r\n");
    $junk = $this->engine->checkEmailAuthentication('Please verify your account right now.');

    expect($none['points'])->toBe(0)->and($none['unavailable'])->toBeTrue()
        ->and($junk['points'])->toBe(0)->and($junk['unavailable'])->toBeTrue()
        ->and($junk['reasons'][0])->toContain('No email headers could be read');
});

test('a sender that differs from the From line in the headers is pointed out without points', function () {
    $result = $this->engine->checkEmailAuthentication(authHeaders('a@shop.example', 'dmarc=pass'), 'b@other.example');

    expect($result['points'])->toBe(0)->and($result['reasons'][0])->toContain('shop.example')->toContain('other.example');
});

test('the email scan adds the check only when headers were pasted', function () {
    config(['services.ai_text.enabled' => false]);
    Http::fake();

    $without = $this->engine->analyze('email', null, 'a@shop.example', null, null, null, 'Hello', 'See you at the meeting tomorrow.');
    $with = $this->engine->analyze('email', null, 'a@shop.example', null, null, null, 'Hello', 'See you at the meeting tomorrow.', null, null, authHeaders('a@shop.example', 'spf=fail; dkim=fail; dmarc=fail'));

    expect(collect($without['checks'])->pluck('name'))->not->toContain('Email Authentication');

    $check = collect($with['checks'])->firstWhere('name', 'Email Authentication');

    expect($check)->not->toBeNull()
        ->and($check['points'])->toBe(30)
        ->and($check['status'])->toBe('HIGH RISK')
        ->and($with['risk_score'])->toBeGreaterThanOrEqual($without['risk_score'] + 30);
});

test('headers on their own cannot make a phishing verdict', function () {
    config(['services.ai_text.enabled' => false]);
    Http::fake();

    $result = $this->engine->analyze('email', null, 'service@paypal.com', null, null, null, null, null, null, null, authHeaders('service@paypal.com', 'dmarc=fail', 'seller@gmail.com'));
    $check = collect($result['checks'])->firstWhere('name', 'Email Authentication');

    expect($check['points'])->toBe(50)
        ->and($result['verdict'])->not->toBe('phishing');
});

test('the scan form accepts headers, and they are not saved with the report', function () {
    config(['services.ai_text.enabled' => false]);
    Http::fake();

    $headers = authHeaders('a@shop.example', 'spf=pass; dkim=pass; dmarc=pass');

    $this->post(route('scan.store'), ['email' => 'a@example.com', 'subject' => 'Hello', 'body' => 'See you tomorrow.', 'email_headers' => $headers])
        ->assertRedirect();

    $report = Report::latest('id')->first();

    expect($report->type)->toBe('email')
        ->and(json_encode($report->getAttributes()))->not->toContain('Authentication-Results');
});

test('headers that are far too long are refused', function () {
    $this->post(route('scan.store'), ['email' => 'a@shop.example', 'email_headers' => str_repeat('X-Pad: a'."\n", 5000)])
        ->assertSessionHasErrors('email_headers');
});
