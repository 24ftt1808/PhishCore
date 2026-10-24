<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * A whole email (a saved .eml file, or its source pasted into the message box) fills in the sender, subject,
 * message and headers, so nobody has to copy each part by hand.
 */
function wholeEmailSource(string $auth = 'spf=pass smtp.mailfrom=a@example.com; dkim=fail header.d=example.com; dmarc=fail header.from=example.com'): string
{
    return "Delivered-To: me@gmail.com\r\n"
        ."Authentication-Results: mx.google.com; {$auth}\r\n"
        ."Received: from mail.example.com\r\n"
        ."From: \"Example Support\" <support@example.com>\r\n"
        ."Subject: Your parcel is waiting\r\n"
        ."Content-Type: text/plain; charset=UTF-8\r\n\r\n"
        ."Hello, your parcel is waiting for collection.\r\n";
}

function wholeEmailChecks(): array
{
    return collect(Analysis::latest('id')->first()->flags)->keyBy('name')->all();
}

beforeEach(function () {
    config(['services.ai_text.enabled' => false]);
    Http::fake();
});

test('a saved .eml file alone is enough: the sender and headers are read from it', function () {
    $this->post(route('scan.store'), ['eml_file' => UploadedFile::fake()->createWithContent('parcel.eml', wholeEmailSource())])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $report = Report::latest('id')->first();
    $checks = wholeEmailChecks();

    expect($report->type)->toBe('email')
        ->and($report->sender_email)->toBe('support@example.com')
        ->and($checks)->toHaveKey('Email Authentication')
        ->and($checks['Email Authentication']['points'])->toBeGreaterThan(0)
        ->and($checks)->toHaveKey('Message Content / Behavior Patterns');
});

test('the whole email pasted into the message box is split up the same way', function () {
    $this->post(route('scan.store'), ['body' => wholeEmailSource()])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Report::latest('id')->first()->sender_email)->toBe('support@example.com')
        ->and(wholeEmailChecks())->toHaveKey('Email Authentication');
});

test('a sender typed in by the person is kept over the one in the email', function () {
    $this->post(route('scan.store'), ['email' => 'typed@example.org', 'body' => wholeEmailSource()])->assertRedirect();

    expect(Report::latest('id')->first()->sender_email)->toBe('typed@example.org');
});

test('a normal message that only mentions a From line is not taken for a whole email', function () {
    $this->post(route('scan.store'), ['email' => 'a@example.com', 'body' => "From: Bob\nSent: Monday\nPlease pay the invoice."])->assertRedirect();

    expect(wholeEmailChecks())->not->toHaveKey('Email Authentication');
});

test('a file that is not an email is refused with a plain message', function () {
    $this->post(route('scan.store'), ['eml_file' => UploadedFile::fake()->createWithContent('notes.eml', 'Just some notes, not an email.')])
        ->assertSessionHasErrors('eml_file');

    $this->post(route('scan.store'), ['eml_file' => UploadedFile::fake()->createWithContent('run.exe', wholeEmailSource())])
        ->assertSessionHasErrors('eml_file');
});

test('both scan forms offer the upload', function () {
    $this->get('/')->assertOk()->assertSee('name="eml_file"', false);
    $this->actingAs(User::factory()->create())->get(route('scan.index'))->assertOk()->assertSee('name="eml_file"', false);
});
