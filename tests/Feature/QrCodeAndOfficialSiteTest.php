<?php

use App\Services\AnalysisEngine;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;

/**
 * QR codes in screenshots (read with zbar when QR_READER_ENABLED is on), a copied government poster whose link goes
 * somewhere else, the "surprise money packet" lure, and wording split across lines by a phone screen.
 */
beforeEach(function () {
    Sleep::fake();
    config(['services.ocr_space.key' => 'test-key', 'services.ai_text.enabled' => false]);

    $this->image = tempnam(sys_get_temp_dir(), 'shot').'.png';
    file_put_contents($this->image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
});

afterEach(function () {
    @unlink($this->image);
});

function qrScreenshotScan(string $image, ?string $ocrText): array
{
    Http::fake(fn ($request) => str_contains($request->url(), 'ocr.space')
        ? ($ocrText === null
            ? Http::response(['error' => 'E571: Free OCR API overloaded'], 503)
            : Http::response(['IsErroredOnProcessing' => false, 'ParsedResults' => [['ParsedText' => $ocrText]]]))
        : Http::response('', 503));

    return (new AnalysisEngine)->analyze('screenshot', null, null, null, $image);
}

function checkNamed(array $result, string $name): ?array
{
    return collect($result['checks'])->firstWhere('name', $name);
}

test('the QR reader is off unless it is switched on', function () {
    config(['services.qr_reader.enabled' => false]);
    Process::fake();

    (new AnalysisEngine)->decodeQrCodes($this->image);

    Process::assertNothingRan();
});

test('zbar reads every QR code, and a website written without https:// is still a link', function () {
    config(['services.qr_reader.enabled' => true, 'services.qr_reader.binary' => 'zbarimg']);
    Process::fake(['*' => Process::result("https://www.polis.gov.bn/muat-turun/\nwww.yshhb.org.bn/kerjaya\nWIFI:S:Office;T:WPA;P:secret;;\n")]);

    expect((new AnalysisEngine)->decodeQrCodes($this->image))
        ->toBe(['https://www.polis.gov.bn/muat-turun/', 'https://www.yshhb.org.bn/kerjaya']);
    Process::assertRan(fn (PendingProcess $process) => ($process->command[0] ?? null) === 'zbarimg');
});

test('an image with no QR code gives no links', function () {
    config(['services.qr_reader.enabled' => true]);
    Process::fake(['*' => Process::result(output: '', exitCode: 4)]);

    expect((new AnalysisEngine)->decodeQrCodes($this->image))->toBe([]);
});

test('a QR code link is checked and shown, even when it is not printed in the text', function () {
    config(['services.qr_reader.enabled' => true]);
    Process::fake(['*' => Process::result("https://bibd-rewards.top/claim\n")]);

    $result = qrScreenshotScan($this->image, "BIBD Raya Rewards\nScan the QR code to claim your gift");

    expect($result['extracted_url'])->toBe('https://bibd-rewards.top/claim')
        ->and(checkNamed($result, 'QR Code')['message'])->toContain('The QR code opens https://bibd-rewards.top/claim')
        ->and($result['risk_score'])->toBeGreaterThanOrEqual(25);
});

test('when the text cannot be read, the QR code is still checked', function () {
    config(['services.qr_reader.enabled' => true]);
    Process::fake(['*' => Process::result("https://bibd-login.top/verify\n")]);

    $result = qrScreenshotScan($this->image, null);

    expect($result['verdict'])->not->toBe('review')
        ->and($result['extracted_url'])->toBe('https://bibd-login.top/verify')
        ->and(checkNamed($result, 'Screenshot Text Extraction')['message'])->toContain('only its QR code was checked');
});

test('a QR-code service link is explained, not flagged', function () {
    config(['services.qr_reader.enabled' => true]);
    Process::fake(['*' => Process::result("https://me-qr.com/NYQX5JKQ\n")]);

    $result = qrScreenshotScan($this->image, "KERJAYA\nPELUANG PEKERJAAN\nMaklumat lanjut mengenai kekosongan jawatan ada di\nwww.yshhb.org.bn/kerjaya");

    expect(checkNamed($result, 'QR Code')['message'])->toContain('QR-code service')
        ->and($result['verdict'])->toBe('clean');
});

test('a copied ministry poster whose link goes somewhere else is flagged', function () {
    $text = "KEMENTERIAN PENDIDIKAN MALAYSIA\nPENGAMBILAN GURU GANTI\nKELAYAKAN & KADAR UPAH\nSPM RM2,000\nwww.moe.gov.my\nhttps://register.net-ap1.com/guru-gov";

    $result = qrScreenshotScan($this->image, $text);

    expect($result['extracted_url'])->toBe('https://register.net-ap1.com/guru-gov')
        ->and(checkNamed($result, 'Official Website Mismatch')['message'])->toContain('moe.gov.my')
        ->and($result['risk_score'])->toBeGreaterThanOrEqual(25);
});

test('a government poster that links to its own website, a form or social media is not flagged', function (string $link) {
    $result = (new AnalysisEngine)->scoreMessageRules("PEMBUKAAN PENGAMBILAN PEGAWAI KADET\nPASUKAN POLIS DIRAJA BRUNEI\nWWW.POLIS.GOV.BN\n{$link}");

    expect(implode(' ', $result['reasons']))->not->toContain('government website')
        ->and($result['verdict'])->toBe('clean');
})->with([
    'own website' => ['https://www.polis.gov.bn/muat-turun/'],
    'another government site' => ['https://www.jpa.gov.bn/'],
    'Google Form' => ['https://forms.gle/AbCdEf123'],
    'Instagram' => ['https://www.instagram.com/upa.ppdb'],
    'short link' => ['https://bit.ly/ppdb2026'],
]);

test('a lone government-website mismatch stays below "suspicious"', function () {
    $result = (new AnalysisEngine)->scoreMessageRules('Health update from www.moh.gov.bn. Full story: https://borneobulletin.com.bn/health-update');

    expect($result['score'])->toBeLessThan(25);
});

test('a "surprise money packet" claim page is a prize lure', function () {
    $result = (new AnalysisEngine)->scoreMessageRules("A surprise Money Packet...\nclaimtng.ak-pmn.org\nDaripada\nTouch 'n Go Rewards\nBuka Sekarang");

    expect(implode(' ', $result['reasons']))->toContain('Prize/reward language')
        ->and($result['verdict'])->toBe('phishing');
});

test('wording split across lines by a phone screen is still read', function () {
    $result = (new AnalysisEngine)->scoreMessageRules("Your WhatsApp account has been\nflagged for a policy violation!\nPlease verify your identity within 6\nhours to avoid account\nsuspension: whtsapp-my.net");

    expect(implode(' ', $result['reasons']))->toContain('Urgency language')
        ->and($result['verdict'])->toBe('phishing');
});

test('zbar reads the QR codes on the real Brunei posters', function () {
    // Uses QR_READER_BINARY from .env, so a Windows install in "C:\Program Files (x86)\ZBar\bin" is found too.
    $binary = (string) config('services.qr_reader.binary', 'zbarimg');
    $installed = rescue(fn () => Process::run([$binary, '--version'])->successful(), false, false);
    if (! $installed) {
        $this->markTestSkipped('zbar is not installed on this computer (sudo apt install zbar-tools).');
    }
    config(['services.qr_reader.enabled' => true]);

    $engine = new AnalysisEngine;
    expect($engine->decodeQrCodes(base_path('scan-screenshots/legit-07-polis-recruitment-poster.jpg')))->toBe(['https://www.polis.gov.bn/muat-turun/'])
        ->and($engine->decodeQrCodes(base_path('scan-screenshots/legit-06-yshhb-job-poster.jpg')))->toBe(['https://me-qr.com/NYQX5JKQ']);
});