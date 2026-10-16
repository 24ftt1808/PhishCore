<?php

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** The bytes of a small APK (a zip with a manifest and code) that asks for risky permissions. */
function fileFlowApkBytes(array $permissions): string
{
    $path = tempnam(sys_get_temp_dir(), 'ffapk');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);

    $manifest = "\x03\x00\x08\x00";
    foreach ($permissions as $permission) {
        $manifest .= implode("\0", str_split('android.permission.'.$permission))."\0\0A";
    }

    $zip->addFromString('AndroidManifest.xml', $manifest);
    $zip->addFromString('classes.dex', 'dex');
    $zip->close();

    $bytes = (string) file_get_contents($path);
    @unlink($path);

    return $bytes;
}

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    config(['services.virustotal.key' => 'test-key']);
    Http::fake(['virustotal.com/*' => Http::response([], 404)]);
});

test('uploading a risky app scans it, keeps only its name and fingerprint, and never stores the file', function () {
    $user = User::factory()->create();
    $bytes = fileFlowApkBytes(['READ_SMS', 'RECEIVE_SMS', 'BIND_ACCESSIBILITY_SERVICE', 'SYSTEM_ALERT_WINDOW']);
    $upload = UploadedFile::fake()->createWithContent('bibd-update.apk', $bytes);

    $response = $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $report = Report::firstOrFail();
    $response->assertRedirect(route('scan.show', $report));

    expect($report->type)->toBe('file')
        ->and($report->file_name)->toBe('bibd-update.apk')
        ->and($report->file_sha256)->toBe(hash('sha256', $bytes))
        ->and($report->url)->toBeNull()
        ->and($report->status)->toBe('completed')
        ->and($report->analyses->first()->verdict)->toBe('phishing');
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('a file that is not an APK or a PDF by name is refused before any scan', function () {
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
    $upload = UploadedFile::fake()->createWithContent('bibd-update.apk', fileFlowApkBytes(['INTERNET']));
    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $this->actingAs($user)->get(route('scan.show', Report::firstOrFail()))
        ->assertOk()
        ->assertSee('UPLOADED FILE')
        ->assertSee('bibd-update.apk')
        ->assertSee('Scan Another File');
});

test('the scanner offers a File tab', function () {
    $this->get(route('scan.index'))->assertOk()->assertSee('UPLOAD AN APP OR PDF')->assertSee('Scan File');
});

test('a file scan is listed in the scan history with its name', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('parcel.apk', fileFlowApkBytes(['INTERNET']));
    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $this->actingAs($user)->get(route('scan.history'))->assertOk()->assertSee('parcel.apk');
});

test('the home page scanner has a File tab too', function () {
    $this->get('/')->assertOk()->assertSee('Upload an APK or PDF');
});

test('the public reports page lists a flagged file but never shows its name', function () {
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->createWithContent('adam-private-cv.apk', fileFlowApkBytes(['READ_SMS', 'RECEIVE_SMS', 'BIND_ACCESSIBILITY_SERVICE', 'SYSTEM_ALERT_WINDOW']));
    $this->actingAs($user)->post(route('scan.store'), ['file' => $upload]);

    $this->get(route('reports.public'))->assertOk()->assertSee('Uploaded file')->assertDontSee('adam-private-cv');
});

test('scan history can be searched by file name', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('scan.store'), ['file' => UploadedFile::fake()->createWithContent('parcel-tracker.apk', fileFlowApkBytes(['INTERNET']))]);
    $this->actingAs($user)->post(route('scan.store'), ['file' => UploadedFile::fake()->createWithContent('other-thing.apk', fileFlowApkBytes(['INTERNET']))]);

    $this->actingAs($user)->get(route('scan.history', ['search' => 'parcel']))
        ->assertOk()->assertSee('parcel-tracker.apk')->assertDontSee('other-thing.apk');
});
