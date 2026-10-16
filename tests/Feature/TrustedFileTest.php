<?php

use App\Models\Report;
use App\Models\TrustedFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * A small APK asking for the permissions banking trojans ask for. The bytes are built once, because a
 * zip stamps the current time inside, so two builds a couple of seconds apart are different files.
 */
function trustedFileApkBytes(): string
{
    static $bytes = null;

    if ($bytes !== null) {
        return $bytes;
    }

    $path = tempnam(sys_get_temp_dir(), 'tfapk');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);

    $manifest = "\x03\x00\x08\x00";
    foreach (['READ_SMS', 'RECEIVE_SMS', 'BIND_ACCESSIBILITY_SERVICE', 'SYSTEM_ALERT_WINDOW'] as $permission) {
        $manifest .= implode("\0", str_split('android.permission.'.$permission))."\0\0A";
    }

    $zip->addFromString('AndroidManifest.xml', $manifest);
    $zip->addFromString('classes.dex', 'dex');
    $zip->close();

    $bytes = (string) file_get_contents($path);
    @unlink($path);

    return $bytes;
}

/** Scans the risky app as this user and returns the saved report. */
function trustedFileScan(User $user): Report
{
    test()->actingAs($user)->post(route('scan.store'), [
        'file' => UploadedFile::fake()->createWithContent('real-sms-app.apk', trustedFileApkBytes()),
    ]);

    return Report::latest('id')->firstOrFail();
}

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    config(['services.virustotal.key' => 'test-key']);
    Http::fake(['virustotal.com/*' => Http::response([], 404)]);
});

test('an administrator can mark a scanned file as safe, and the mark can be removed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $report = trustedFileScan($admin);

    $this->actingAs($admin)->post(route('scan.trust-file', $report))->assertRedirect();

    $trusted = TrustedFile::firstOrFail();
    expect($trusted->file_sha256)->toBe($report->file_sha256)
        ->and($trusted->file_name)->toBe('real-sms-app.apk')
        ->and($trusted->marked_by)->toBe($admin->id);

    $this->actingAs($admin)->delete(route('scan.untrust-file', $report))->assertRedirect();

    expect(TrustedFile::count())->toBe(0);
});

test('a file marked as safe is shown as safe on the next scan, whatever the other checks say', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $first = trustedFileScan($admin);
    expect($first->analyses->first()->verdict)->toBe('phishing');

    $this->actingAs($admin)->post(route('scan.trust-file', $first));

    $second = trustedFileScan($admin);
    $analysis = $second->analyses->first();

    expect($analysis->verdict)->toBe('clean')
        ->and($analysis->risk_score)->toBe(0)
        ->and(collect($analysis->flags)->pluck('name')->all())->toContain('Admin Review');
});

test('people who are not administrators cannot mark a file as safe', function () {
    $owner = User::factory()->create();
    $report = trustedFileScan($owner);

    $this->actingAs($owner)->post(route('scan.trust-file', $report))->assertForbidden();
    $this->actingAs(User::factory()->create(['is_team_member' => true]))->post(route('scan.trust-file', $report))->assertForbidden();

    expect(TrustedFile::count())->toBe(0);
});

test('guests cannot mark a file as safe', function () {
    $report = Report::factory()->create(['type' => 'file', 'file_sha256' => str_repeat('a', 64)]);

    $this->post(route('scan.trust-file', $report))->assertRedirect(route('login'));

    expect(TrustedFile::count())->toBe(0);
});

test('only file scans can be marked as safe', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $report = Report::factory()->create(['user_id' => $admin->id, 'type' => 'url']);

    $this->actingAs($admin)->post(route('scan.trust-file', $report))->assertNotFound();
});

test('the safe button shows to administrators on a file result, and to nobody else', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $report = trustedFileScan($admin);

    $this->actingAs($admin)->get(route('scan.show', $report))
        ->assertOk()
        ->assertSee('Mark this file as safe');

    $this->actingAs($admin)->post(route('scan.trust-file', $report));
    $this->actingAs($admin)->get(route('scan.show', $report))->assertSee('Remove the safe mark');

    $member = User::factory()->create();
    $memberReport = trustedFileScan($member);
    $this->actingAs($member)->get(route('scan.show', $memberReport))->assertOk()->assertDontSee('Mark this file as safe');
});

test('administrators see the safe files page, with a remove button, and can remove a mark', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Ada Admin']);
    $report = trustedFileScan($admin);
    $this->actingAs($admin)->post(route('scan.trust-file', $report));

    $this->actingAs($admin)->get(route('safe-files.index'))
        ->assertOk()
        ->assertSee('Safe Files')
        ->assertSee('real-sms-app.apk')
        ->assertSee('Ada Admin')
        ->assertSee('Remove');

    $this->actingAs($admin)->delete(route('safe-files.destroy', TrustedFile::firstOrFail()))->assertRedirect();

    expect(TrustedFile::count())->toBe(0);
    $this->actingAs($admin)->get(route('safe-files.index'))->assertSee('No file has been marked as safe yet.');
});

test('team members can see the safe files but cannot remove a mark', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->post(route('scan.trust-file', trustedFileScan($admin)));
    $member = User::factory()->create(['is_team_member' => true]);

    $this->actingAs($member)->get(route('safe-files.index'))
        ->assertOk()
        ->assertSee('real-sms-app.apk')
        ->assertDontSee('SAFE MARK');

    $this->actingAs($member)->delete(route('safe-files.destroy', TrustedFile::firstOrFail()))->assertForbidden();

    expect(TrustedFile::count())->toBe(1);
});

test('regular users cannot open the safe files page', function () {
    $this->actingAs(User::factory()->create())->get(route('safe-files.index'))->assertForbidden();
});

test('guests are sent to log in instead of seeing the safe files page', function () {
    $this->get(route('safe-files.index'))->assertRedirect(route('login'));
});

test('the safe files page can be searched by file name or fingerprint', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    TrustedFile::create(['file_sha256' => str_repeat('a', 64), 'file_name' => 'bank-app.apk', 'marked_by' => $admin->id]);
    TrustedFile::create(['file_sha256' => str_repeat('b', 64), 'file_name' => 'invoice.pdf', 'marked_by' => $admin->id]);

    $this->actingAs($admin)->get(route('safe-files.index', ['search' => 'bank']))
        ->assertSee('bank-app.apk')
        ->assertDontSee('invoice.pdf');

    $this->actingAs($admin)->get(route('safe-files.index', ['search' => str_repeat('b', 10)]))
        ->assertSee('invoice.pdf')
        ->assertDontSee('bank-app.apk');
});

test('the sidebar shows Safe Files to administrators and team members only', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('dashboard'))->assertSee(route('safe-files.index'));
    $this->actingAs(User::factory()->create(['is_team_member' => true]))->get(route('dashboard'))->assertSee(route('safe-files.index'));
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertDontSee(route('safe-files.index'));
});
