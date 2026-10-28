<?php

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** The bytes of a small PDF. A risky one carries a link to a look-alike bank page, which the file scan flags. */
function fileFlowPdfBytes(bool $risky = false): string
{
    return "%PDF-1.4\n".($risky ? '1 0 obj << /URI (http://bibd-secure-login.com/verify) >>' : 'hello');
}

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    config(['services.virustotal.key' => 'test-key']);
    Http::fake(['virustotal.com/*' => Http::response([], 404)]);
});

test('uploading a risky PDF scans it, keeps only its name and fingerprint, and never stores the file', function () {
    $user = User::factory()->create();
    $bytes = fileFlowPdfBytes(risky: true);
    $upload = UploadedFile::fake()->createWithContent('bibd-update.pdf', $bytes);

    $response = $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $report = Report::firstOrFail();
    $response->assertRedirect(route('scan.show', $report));

    expect($report->type)->toBe('file')
        ->and($report->file_name)->toBe('bibd-update.pdf')
        ->and($report->file_sha256)->toBe(hash('sha256', $bytes))
        ->and($report->url)->toBeNull()
        ->and($report->status)->toBe('completed')
        ->and($report->analyses->first()->verdict)->toBeIn(['suspicious', 'phishing']);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('an app (APK) is refused with a clear message before any scan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/')->post(route('scan.store'), ['file' => UploadedFile::fake()->create('bibd-update.apk', 10)])
        ->assertSessionHasErrors(['file' => 'Only PDF files can be scanned. App (APK) files are not accepted.']);

    expect(Report::count())->toBe(0);
    Http::assertNothingSent();
});

test('a file that is not a PDF by name is refused before any scan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/')->post(route('scan.store'), ['file' => UploadedFile::fake()->create('notes.txt', 10)])
        ->assertSessionHasErrors('file');

    expect(Report::count())->toBe(0);
    Http::assertNothingSent();
});

test('a file over 20 MB is refused', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/')->post(route('scan.store'), ['file' => UploadedFile::fake()->create('big.pdf', 21 * 1024)])
        ->assertSessionHasErrors('file');

    expect(Report::count())->toBe(0);
});

test('a PDF with a link keeps the link so a page preview can be offered', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.4\n1 0 obj << /URI (http://bibd-secure-login.com/verify) >>");

    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    expect(Report::firstOrFail()->preview_url)->toBe('http://bibd-secure-login.com/verify');
});

test('a PDF with no link has no page preview link', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.4\nhello");

    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    expect(Report::firstOrFail()->preview_url)->toBeNull();
});

test('the result page for a file scan shows the file name and says what was scanned', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('bibd-update.pdf', fileFlowPdfBytes());
    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $this->actingAs($user)->get(route('scan.show', Report::firstOrFail()))
        ->assertOk()
        ->assertSee('UPLOADED FILE')
        ->assertSee('bibd-update.pdf')
        ->assertSee('Scan Another File')
        ->assertSee('the verdict is the loudest thing', false);
});

test('the scanner offers a File tab', function () {
    $this->get(route('scan.index'))->assertOk()->assertSee('UPLOAD A PDF')->assertSee('Scan File');
});

test('a file scan is listed in the scan history with its name', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('parcel.pdf', fileFlowPdfBytes());
    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $this->actingAs($user)->get(route('scan.history'))->assertOk()->assertSee('parcel.pdf');
});

test('the home page scanner has a File tab too', function () {
    $this->get('/')->assertOk()->assertSee('Upload a PDF')->assertDontSee('APK');
});

test('the public reports page lists a flagged file but never shows its name', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('adam-private-cv.pdf', fileFlowPdfBytes(risky: true));
    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $this->get(route('reports.public'))->assertOk()->assertSee('Uploaded file')->assertDontSee('adam-private-cv');
});

test('scan history can be searched by file name', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('scan.store'), ['file' => UploadedFile::fake()->createWithContent('parcel-tracker.pdf', fileFlowPdfBytes())]);
    $this->actingAs($user)->post(route('scan.store'), ['file' => UploadedFile::fake()->createWithContent('other-thing.pdf', fileFlowPdfBytes())]);

    $this->actingAs($user)->get(route('scan.history', ['search' => 'parcel']))
        ->assertOk()->assertSee('parcel-tracker.pdf')->assertDontSee('other-thing.pdf');
});