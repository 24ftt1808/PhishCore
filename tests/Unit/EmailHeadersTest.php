<?php

use App\Support\EmailHeaders;

function gmailHeaders(string $results = 'dkim=pass header.i=@paypal.com header.s=pp1; spf=pass (google.com: domain of service@paypal.com designates 1.2.3.4 as permitted sender) smtp.mailfrom=service@paypal.com; dmarc=pass (p=REJECT sp=REJECT dis=NONE) header.from=paypal.com'): string
{
    return "Delivered-To: me@gmail.com\r\n"
        ."Authentication-Results: mx.google.com;\r\n       ".str_replace('; ', ";\r\n       ", $results)."\r\n"
        ."From: \"PayPal, Inc.\" <service@paypal.com>\r\n"
        ."Reply-To: help@gmail.com, Support <support@example.net>\r\n"
        ."Subject: Your account\r\n"
        ."\r\n"
        ."From: body-text@not-a-header.example\r\n";
}

test('a header block is read, folded lines are joined and the message body is ignored', function () {
    $headers = EmailHeaders::parse(gmailHeaders());

    expect($headers->hasHeaders())->toBeTrue()
        ->and($headers->first('subject'))->toBe('Your account')
        ->and($headers->first('Authentication-Results'))->toContain('dmarc=pass')
        ->and($headers->fromDomain())->toBe('paypal.com');
});

test('SPF, DKIM and DMARC results are read from Authentication-Results, comments ignored', function () {
    $auth = EmailHeaders::parse(gmailHeaders())->authentication();

    expect($auth)->toBe(['spf' => 'pass', 'dkim' => 'pass', 'dmarc' => 'pass', 'found' => true]);

    $failed = EmailHeaders::parse(gmailHeaders('dkim=fail header.i=@paypal.com; spf=softfail (domain owner discourages use) smtp.mailfrom=x@paypal.com; dmarc=fail (p=NONE) header.from=paypal.com'))->authentication();

    expect($failed['dkim'])->toBe('fail')->and($failed['spf'])->toBe('softfail')->and($failed['dmarc'])->toBe('fail');
});

test('the Outlook.com style, with no server name in front, is read too', function () {
    $raw = "Authentication-Results: spf=pass (sender IP is 1.2.3.4) smtp.mailfrom=bank.example; dkim=pass (signature was verified) header.d=bank.example;dmarc=pass action=none header.from=bank.example;compauth=pass reason=100\r\nFrom: a@bank.example\r\n";

    expect(EmailHeaders::parse($raw)->authentication())->toBe(['spf' => 'pass', 'dkim' => 'pass', 'dmarc' => 'pass', 'found' => true]);
});

test('one DKIM signature that checks out is enough when there are several', function () {
    $auth = EmailHeaders::parse(gmailHeaders('dkim=fail header.d=forwarder.example; dkim=pass header.d=bank.example; spf=pass smtp.mailfrom=a@bank.example; dmarc=pass header.from=bank.example'))->authentication();

    expect($auth['dkim'])->toBe('pass');
});

test('Received-SPF is used when there is no Authentication-Results', function () {
    $auth = EmailHeaders::parse("Received-SPF: Fail (protection.outlook.com: domain of x.example does not designate 9.9.9.9 as permitted sender)\r\nFrom: a@x.example\r\n")->authentication();

    expect($auth['spf'])->toBe('fail')->and($auth['dkim'])->toBeNull()->and($auth['found'])->toBeTrue();
});

test('only the top-most Authentication-Results header is trusted', function () {
    $raw = "Authentication-Results: mx.google.com; dmarc=fail header.from=bank.example\r\n"
        ."Authentication-Results: attacker.example; dmarc=pass header.from=bank.example\r\n"
        ."From: a@bank.example\r\n";

    expect(EmailHeaders::parse($raw)->authentication()['dmarc'])->toBe('fail');
});

test('headers without any verification results say so', function () {
    $auth = EmailHeaders::parse("From: a@example.com\r\nSubject: Hi\r\n")->authentication();

    expect($auth['found'])->toBeFalse()->and($auth['spf'])->toBeNull();
});

test('the From domain ignores the quoted display name, and Reply-To can list several domains', function () {
    $headers = EmailHeaders::parse("From: \"Bank help@other.example\" <service@bank.example>\r\nReply-To: a@gmail.com, B <b@Example.NET>\r\n");

    expect($headers->fromDomain())->toBe('bank.example')
        ->and($headers->replyToDomains())->toBe(['gmail.com', 'example.net']);
});

test('text that is not a header block is not read as one', function () {
    expect(EmailHeaders::parse('Dear customer, please verify your account today.')->hasHeaders())->toBeFalse()
        ->and(EmailHeaders::parse('')->hasHeaders())->toBeFalse();
});

test('an unreasonably long paste is cut short', function () {
    $headers = EmailHeaders::parse("Subject: x\n".str_repeat('X-Pad: '.str_repeat('a', 100)."\n", 2000));

    expect($headers->hasHeaders())->toBeTrue();
});
