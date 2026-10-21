<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
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

test('a link written with PDF escapes or hex digits is still found', function () {
    $escaped = fileScanRun(fileScanPdf('1 0 obj << /URI (https\072\057\057bibd\055secure\055login\056com\057verify) >>'), 'letter.pdf');
    $hex = fileScanRun(fileScanPdf('1 0 obj << /URI <'.bin2hex('http://bibd-secure-login.com/verify').'> >>'), 'letter.pdf');

    expect($escaped['extracted_url'])->toBe('https://bibd-secure-login.com/verify')
        ->and($escaped['risk_score'])->toBeGreaterThanOrEqual(25)
        ->and($hex['extracted_url'])->toBe('http://bibd-secure-login.com/verify');
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

function fileScanTextPdf(string $operators, bool $compressed = true): string
{
    $data = $compressed ? gzcompress($operators) : $operators;
    $filter = $compressed ? ' /Filter /FlateDecode' : '';

    return fileScanPdf('1 0 obj << /Length '.strlen($data)."{$filter} >>\nstream\n{$data}\nendstream\nendobj\n");
}

test('a PDF written like a scam message is flagged, even when its text is compressed', function () {
    fileScanFake([]);
    $text = 'BT (URGENT: your account has been suspended. Verify your account now and send your OTP and PIN to avoid permanent closure.) Tj ET';

    foreach ([true, false] as $compressed) {
        $result = fileScanRun(fileScanTextPdf($text, $compressed), 'notice.pdf');
        $check = fileScanCheck($result, 'PDF Text');

        expect($check['points'])->toBeGreaterThan(0)
            ->and($result['verdict'])->not->toBe('clean');
    }
});

test('an ordinary PDF has no wording flagged', function () {
    fileScanFake([]);
    $result = fileScanRun(fileScanTextPdf('BT (Invoice 2041 for web hosting. Thank you for your business. Payment is due within 30 days.) Tj ET'), 'invoice.pdf');

    expect(fileScanCheck($result, 'PDF Text')['points'])->toBe(0)
        ->and($result['verdict'])->toBe('clean');
});

test('normal letters that mention deadlines, payments and bank details are not flagged', function () {
    fileScanFake([]);
    $texts = [
        'BT (Offer letter: we are pleased to offer you the position. Please sign and return within 7 days. Your bank details are required for payroll.) Tj ET',
        'BT (Tax invoice. Amount due BND 250. Please pay by bank transfer within 14 days. Late payment may incur a fee. Thank you.) Tj ET',
    ];

    foreach ($texts as $text) {
        $result = fileScanRun(fileScanTextPdf($text), 'letter.pdf');

        expect(fileScanCheck($result, 'PDF Text')['points'])->toBe(0)
            ->and($result['verdict'])->toBe('clean');
    }
});

test('a PDF with no readable text says so and adds no points', function () {
    fileScanFake([]);
    $result = fileScanRun(fileScanPdf('1 0 obj << /Type /Page >> endobj'), 'scan.pdf');
    $check = fileScanCheck($result, 'PDF Text');

    expect($check['status'])->toBe('UNKNOWN')
        ->and($check['points'])->toBe(0);
});

test('a PDF carrying a hidden file or a data-sending form is flagged', function () {
    fileScanFake([]);

    $hidden = fileScanRun(fileScanPdf('<< /Type /Filespec /EF << /F 5 0 R >> /Subtype /EmbeddedFile >>'), 'a.pdf');
    $form = fileScanRun(fileScanPdf('<< /S /SubmitForm /F (http://example.test/post) >>'), 'b.pdf');

    expect(fileScanCheck($hidden, 'PDF Contents')['points'])->toBe(20)
        ->and(fileScanCheck($form, 'PDF Contents')['points'])->toBe(20);
});

function fileScanPastScan(string $path, string $verdict, ?int $userId = null): void
{
    $report = Report::factory()->create([
        'type' => 'file',
        'status' => 'completed',
        'user_id' => $userId,
        'file_sha256' => hash_file('sha256', $path),
    ]);
    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict]);
}

test('a file already found to be phishing before is flagged again, even when nothing else is known', function () {
    fileScanFake([]);
    $path = fileScanPdf('hello, nothing suspicious in here');
    fileScanPastScan($path, 'phishing');

    $result = fileScanRun($path, 'letter.pdf');
    $check = fileScanCheck($result, 'Previous Reports (File)');

    expect($check['points'])->toBe(30)
        ->and($result['verdict'])->toBe('suspicious');
});

test('the same person scanning a bad file again and again counts once, different people add up', function () {
    fileScanFake([]);
    $path = fileScanPdf('hello again');
    $first = User::factory()->create();
    $second = User::factory()->create();

    fileScanPastScan($path, 'phishing', $first->id);
    fileScanPastScan($path, 'phishing', $first->id);
    expect(fileScanCheck(fileScanRun(copyOf($path), 'a.pdf'), 'Previous Reports (File)')['points'])->toBe(30);

    fileScanPastScan($path, 'phishing', $second->id);
    expect(fileScanCheck(fileScanRun(copyOf($path), 'a.pdf'), 'Previous Reports (File)')['points'])->toBe(50);
});

test('an earlier verdict older than 90 days no longer counts', function () {
    fileScanFake([]);
    $path = fileScanPdf('an old story');
    fileScanPastScan($path, 'phishing');
    Report::query()->update(['created_at' => now()->subDays(91)]);

    $check = fileScanCheck(fileScanRun($path, 'a.pdf'), 'Previous Reports (File)');

    expect($check['points'])->toBe(0);
});

test('an earlier suspicious verdict is only information, and a new file is not flagged', function () {
    fileScanFake([]);
    $path = fileScanPdf('a borderline file');
    fileScanPastScan($path, 'suspicious');

    $seen = fileScanCheck(fileScanRun(copyOf($path), 'a.pdf'), 'Previous Reports (File)');
    $new = fileScanCheck(fileScanRun(fileScanPdf('brand new content'), 'b.pdf'), 'Previous Reports (File)');

    expect($seen['points'])->toBe(0)->and($seen['status'])->toBe('INFO')
        ->and($new['points'])->toBe(0)->and($new['status'])->toBe('INFO');
});

function copyOf(string $path): string
{
    $copy = tempnam(sys_get_temp_dir(), 'fscopy');
    copy($path, $copy);

    return $copy;
}