<?php

use App\Services\AnalysisEngine;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

/** An APK is a zip file with a manifest and code. Permission names are stored two bytes per letter, like real ones. */
function fileScanApk(array $permissions, bool $wide = true): string
{
    $path = tempnam(sys_get_temp_dir(), 'fsapk');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);

    $manifest = "\x03\x00\x08\x00";
    foreach ($permissions as $permission) {
        $name = 'android.permission.'.$permission;
        $manifest .= $wide ? implode("\0", str_split($name))."\0\0A" : $name."\0A";
    }

    $zip->addFromString('AndroidManifest.xml', $manifest);
    $zip->addFromString('classes.dex', 'dex');
    $zip->close();

    return $path;
}

function fileScanPdf(string $body): string
{
    $path = tempnam(sys_get_temp_dir(), 'fspdf');
    file_put_contents($path, "%PDF-1.4\n".$body);

    return $path;
}

function fileScanRun(string $path, string $name): array
{
    $result = (new AnalysisEngine)->analyze('file', filePath: $path, fileName: $name);
    @unlink($path);

    return $result;
}

/** Starts again with a clean fake, because the first matching fake wins and the default one would hide the next. */
function fileScanFake(array $map): void
{
    Http::swap(new Factory);
    Http::fake($map);
}

function fileScanCheck(array $result, string $name): ?array
{
    return collect($result['checks'])->firstWhere('name', $name);
}

beforeEach(function () {
    config(['services.virustotal.key' => 'test-key']);
    fileScanFake(['virustotal.com/*' => Http::response([], 404)]);
});

test('a fake bank app asking for texts, accessibility and overlays is scored as phishing', function () {
    $result = fileScanRun(fileScanApk(['READ_SMS', 'RECEIVE_SMS', 'BIND_ACCESSIBILITY_SERVICE', 'SYSTEM_ALERT_WINDOW', 'INTERNET']), 'bibd-update.apk');

    expect($result['verdict'])->toBe('phishing')
        ->and($result['risk_score'])->toBeGreaterThanOrEqual(60)
        ->and($result['file_sha256'])->toHaveLength(64)
        ->and(fileScanCheck($result, 'App Permissions')['message'])->toContain('text messages');
});

test('permissions written as plain text are read too', function () {
    $result = fileScanRun(fileScanApk(['READ_SMS', 'BIND_DEVICE_ADMIN', 'REQUEST_INSTALL_PACKAGES'], wide: false), 'x.apk');

    expect($result['risk_score'])->toBeGreaterThanOrEqual(45);
});

test('an ordinary app is clean, and one weak permission alone is not flagged', function () {
    $normal = fileScanRun(fileScanApk(['INTERNET', 'CAMERA', 'ACCESS_NETWORK_STATE']), 'game.apk');
    $contacts = fileScanRun(fileScanApk(['READ_CONTACTS']), 'chat.apk');

    expect($normal['verdict'])->toBe('clean')->and($normal['risk_score'])->toBe(0)
        ->and($contacts['verdict'])->toBe('clean');
});

test('a file that is really an app but is named like a document is flagged', function () {
    $named = fileScanRun(fileScanApk(['INTERNET']), 'invoice.pdf');
    $double = fileScanRun(fileScanApk(['INTERNET']), 'invoice.pdf.apk');

    expect($named['risk_score'])->toBe(30)->and($named['verdict'])->toBe('suspicious')
        ->and($double['risk_score'])->toBe(25);
});

test('a PDF with a lookalike link is flagged and its link is kept for a page preview', function () {
    $result = fileScanRun(fileScanPdf('1 0 obj << /URI (http://bibd-secure-login.com/verify) >>'), 'letter.pdf');

    expect($result['risk_score'])->toBeGreaterThanOrEqual(25)
        ->and($result['extracted_url'])->toBe('http://bibd-secure-login.com/verify');
});

test('a PDF with a normal link, or no link, stays clean', function () {
    $linked = fileScanRun(fileScanPdf('1 0 obj << /URI (https://www.google.com) >>'), 'letter.pdf');
    $plain = fileScanRun(fileScanPdf('hello world'), 'letter.pdf');

    expect($linked['verdict'])->toBe('clean')->and($linked['extracted_url'])->toBe('https://www.google.com')
        ->and($plain['verdict'])->toBe('clean')->and($plain['extracted_url'])->toBeNull();
});

test('a PDF that launches another program is flagged', function () {
    $result = fileScanRun(fileScanPdf('<< /OpenAction << /S /Launch /F (cmd.exe) >> >>'), 'a.pdf');

    expect($result['risk_score'])->toBe(25);
});

test('VirusTotal flags change the score, and only the fingerprint is sent', function () {
    fileScanFake(['virustotal.com/*' => Http::response(['data' => ['attributes' => ['last_analysis_stats' => ['malicious' => 12, 'suspicious' => 1, 'harmless' => 50]]]], 200)]);
    $path = fileScanPdf('hello');
    $hash = hash_file('sha256', $path);

    $result = fileScanRun($path, 'a.pdf');

    expect($result['verdict'])->toBe('phishing')->and($result['risk_score'])->toBe(80);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->method() === 'GET' && str_ends_with($request->url(), '/api/v3/files/'.$hash));
});

test('one or two VirusTotal flags are only suspicious, and zero is clean', function () {
    fileScanFake(['virustotal.com/*' => Http::sequence()
        ->push(['data' => ['attributes' => ['last_analysis_stats' => ['malicious' => 1, 'harmless' => 70]]]], 200)
        ->push(['data' => ['attributes' => ['last_analysis_stats' => ['malicious' => 0, 'harmless' => 70]]]], 200)]);

    $one = fileScanRun(fileScanPdf('a'), 'a.pdf');
    $zero = fileScanRun(fileScanPdf('b'), 'b.pdf');

    expect($one['risk_score'])->toBe(35)->and($one['verdict'])->toBe('suspicious')
        ->and($zero['risk_score'])->toBe(0);
});

test('a file VirusTotal has not seen is not called safe', function () {
    $result = fileScanRun(fileScanPdf('hello'), 'a.pdf');
    $check = fileScanCheck($result, 'VirusTotal File Fingerprint');

    expect($check['status'])->toBe('INFO')
        ->and($check['message'])->toContain('does not mean it is safe');
});

test('a busy or missing VirusTotal is shown as unavailable and never adds points', function () {
    fileScanFake(['virustotal.com/*' => Http::response([], 429)]);
    $busy = fileScanRun(fileScanPdf('hello'), 'a.pdf');
    expect(fileScanCheck($busy, 'VirusTotal File Fingerprint')['status'])->toBe('UNKNOWN')->and($busy['risk_score'])->toBe(0);

    config(['services.virustotal.key' => null]);
    fileScanFake([]);
    $noKey = fileScanRun(fileScanPdf('hello'), 'a.pdf');
    expect(fileScanCheck($noKey, 'VirusTotal File Fingerprint')['status'])->toBe('UNKNOWN');
    Http::assertNothingSent();
});

test('a missing file is reviewed instead of crashing', function () {
    $result = (new AnalysisEngine)->analyze('file', filePath: '/nonexistent/file.apk', fileName: 'file.apk');

    expect($result['verdict'])->toBe('review')->and($result['file_sha256'])->toBeNull();
});
