<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
});

function scanMade(User $user, int $daysAgo, array $extra = []): Report
{
    $report = Report::factory()->create(array_merge(['user_id' => $user->id, 'type' => 'url', 'status' => 'completed'], $extra));
    DB::table('reports')->where('id', $report->id)->update(['created_at' => now()->subDays($daysAgo)]);

    return $report;
}

test('new accounts start with the standard preferences', function () {
    $user = User::factory()->create()->fresh();

    expect($user->defaultScanTab())->toBe('url')
        ->and($user->scanRetentionDays())->toBeNull();
});

test('each preference saves on its own without touching the others', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.preferences.update'), ['default_scan_tab' => 'file'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'preferences-updated');
    $this->actingAs($user)->patch(route('profile.preferences.update'), ['scan_retention' => '30']);

    $user = $user->fresh();
    expect($user->defaultScanTab())->toBe('file')
        ->and($user->scanRetentionDays())->toBe(30)
        ->and($user->floating_chat_enabled)->toBeTrue();

    $this->actingAs($user)->patch(route('profile.preferences.update'), ['scan_retention' => 'forever']);
    expect($user->fresh()->scanRetentionDays())->toBeNull();
});

test('preferences reject values that are not on offer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.preferences.update'), ['default_scan_tab' => 'fax'])->assertSessionHasErrors('default_scan_tab');
    $this->actingAs($user)->patch(route('profile.preferences.update'), ['scan_retention' => '5'])->assertSessionHasErrors('scan_retention');
});

test('the preferences tab shows the two new options', function () {
    $this->actingAs(User::factory()->create())->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Default scan type')
        ->assertSee('Keep my scans');
});

test('both options are dropdowns that mark the current choice', function () {
    $user = User::factory()->create(['default_scan_tab' => 'phone', 'scan_retention_days' => 90]);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('aria-label="Default scan type"', false)
        ->assertSee('name="default_scan_tab" value="phone" role="option" aria-selected="true"', false)
        ->assertSee('name="default_scan_tab" value="url" role="option" aria-selected="false"', false)
        ->assertSee('aria-label="How long scans are kept"', false)
        ->assertSee('name="scan_retention" value="90" role="option" aria-selected="true"', false)
        ->assertSee('name="scan_retention" value="forever" role="option" aria-selected="false"', false);
});

test('the scan page opens on the tab the person chose', function () {
    $user = User::factory()->create(['default_scan_tab' => 'phone']);

    $this->actingAs($user)->get(route('scan.index'))->assertOk()->assertSee("tab: 'phone'", false);
    $this->actingAs(User::factory()->create())->get(route('scan.index'))->assertOk()->assertSee("tab: 'url'", false);
});

test('old scans are deleted only for people who chose a limit, and only past that limit', function () {
    Storage::fake('public');
    Storage::disk('public')->put('page-previews/old.png', 'x');

    $limited = User::factory()->create(['scan_retention_days' => 30]);
    $ninety = User::factory()->create(['scan_retention_days' => 90]);
    $forever = User::factory()->create();

    $old = scanMade($limited, 40, ['preview_path' => 'http://phishcore.test/storage/page-previews/old.png']);
    Analysis::factory()->create(['report_id' => $old->id]);
    $recent = scanMade($limited, 5);
    $middle = scanMade($ninety, 40);
    $ancient = scanMade($ninety, 120);
    $kept = scanMade($forever, 400);

    $this->artisan('scans:prune-old')->assertSuccessful();

    expect(Report::find($old->id))->toBeNull()
        ->and(Analysis::where('report_id', $old->id)->exists())->toBeFalse()
        ->and(Report::find($recent->id))->not->toBeNull()
        ->and(Report::find($middle->id))->not->toBeNull()
        ->and(Report::find($ancient->id))->toBeNull()
        ->and(Report::find($kept->id))->not->toBeNull();

    Storage::disk('public')->assertMissing('page-previews/old.png');
});