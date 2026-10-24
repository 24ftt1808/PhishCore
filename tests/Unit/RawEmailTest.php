<?php

use App\Support\RawEmail;

function rawEmailFixtureAlternative(): string
{
    return "Delivered-To: me@gmail.com\n"
        ."Authentication-Results: mx.google.com; dkim=pass header.i=@bank.example; spf=pass smtp.mailfrom=a@bank.example; dmarc=pass header.from=bank.example\n"
        ."From: \"Bank Support\" <Support@Bank.example>\n"
        ."Subject: =?UTF-8?B?VXJnZW50OiBWZXJpZnkgeW91ciBhY2NvdW50?=\n"
        ."MIME-Version: 1.0\n"
        ."Content-Type: multipart/alternative; boundary=\"BND1\"\n\n"
        ."--BND1\nContent-Type: text/plain; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n"
        ."Dear customer=2C verify now at https://bank.example/verify=\n123 today.\n\n"
        ."--BND1\nContent-Type: text/html; charset=\"UTF-8\"\n\n<html><body><p>HTML version</p></body></html>\n--BND1--\n";
}

test('the sender, decoded subject, message and header block are separated', function () {
    $email = RawEmail::parse(rawEmailFixtureAlternative());

    expect($email['from'])->toBe('support@bank.example')
        ->and($email['subject'])->toBe('Urgent: Verify your account')
        ->and($email['body'])->toBe('Dear customer, verify now at https://bank.example/verify123 today.')
        ->and($email['headers'])->toContain('Authentication-Results')->not->toContain('Dear customer');
});

test('Windows line endings are read the same way', function () {
    expect(RawEmail::parse(str_replace("\n", "\r\n", rawEmailFixtureAlternative()))['from'])->toBe('support@bank.example');
});

test('a web-page message becomes text, and the address behind every link is kept', function () {
    $html = '<html><head><style>p{}</style></head><body><p>Please <a href="https://evil.example/login?x=1&amp;y=2">sign in to PayPal</a> now.</p><a href="mailto:a@b.c">mail</a><script>x()</script></body></html>';
    $raw = "Received: from x\nFrom: a@evil.example\nSubject: Hi\nMIME-Version: 1.0\nContent-Type: text/html; charset=utf-8\nContent-Transfer-Encoding: base64\n\n".chunk_split(base64_encode($html));

    expect(RawEmail::parse($raw)['body'])->toBe("Please sign in to PayPal https://evil.example/login?x=1&y=2 now.\nmail");
});

test('other character sets are converted, and attachments are skipped', function () {
    $latin = "Received: x\nFrom: a@x.example\nSubject: t\nContent-Type: text/plain; charset=ISO-8859-1\nContent-Transfer-Encoding: quoted-printable\n\nCaf=E9 verify\n";
    $attachment = "Received: x\nFrom: a@x.example\nSubject: t\nContent-Type: multipart/mixed; boundary=B\n\n--B\nContent-Type: text/plain\n\nSee attached.\n--B\nContent-Type: application/pdf\nContent-Disposition: attachment; filename=a.pdf\nContent-Transfer-Encoding: base64\n\nJVBERi0xLjQK\n--B--\n";

    expect(RawEmail::parse($latin)['body'])->toBe('Café verify')
        ->and(RawEmail::parse($attachment)['body'])->toBe('See attached.');
});

test('text that is not an email is not read as one', function () {
    expect(RawEmail::parse('Dear customer, please verify your account'))->toBeNull()
        // a forwarded message copied as plain text has a From line but none of the headers only mail software writes
        ->and(RawEmail::parse("From: Bob\nSent: Monday\nSubject: Hi\n\nHello"))->toBeNull()
        ->and(RawEmail::looksRaw('Dear customer, verify'))->toBeFalse()
        ->and(RawEmail::looksRaw("Note: hello there\nFrom: x"))->toBeFalse()
        ->and(RawEmail::looksRaw(rawEmailFixtureAlternative()))->toBeTrue();
});

test('a header block without a message gives the sender and an empty body', function () {
    $email = RawEmail::parse("Delivered-To: a@b.c\nReceived: from x\nFrom: s@s.example\nSubject: S");

    expect($email['from'])->toBe('s@s.example')->and($email['body'])->toBe('');
});

test('the message text is cut to what the scan form accepts', function () {
    $raw = "Received: x\nFrom: a@x.example\nContent-Type: text/plain\n\n".str_repeat('word ', 3000);

    expect(mb_strlen(RawEmail::parse($raw)['body']))->toBe(RawEmail::MAX_BODY);
});
