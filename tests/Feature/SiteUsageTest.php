<?php

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

const SITE_USAGE_BROWSER = 'Mozilla/5.0 (Linux; Android 14) Chrome/130.0 Mobile Safari/537.36';

test('only administrators can see the usage page', function () {
    $user = User::factory()->create(['role' => 'user']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->get(route('usage.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('usage.index'))->assertOk()->assertSee('Website Usage');
});

test('guests are sent to log in instead of seeing the usage page', function () {
    $this->get(route('usage.index'))->assertRedirect(route('login'));
});

test('a page load from a browser is counted as one visitor', function () {
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/')->assertOk();

    expect(DB::table('site_visits')->count())->toBe(1);
    expect((int) DB::table('site_visits')->value('views'))->toBe(1);
    expect((bool) DB::table('site_visits')->value('is_member'))->toBeFalse();
});

test('the same visitor on the same day is counted once and their page views go up', function () {
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/')->assertOk();
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/')->assertOk();
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/')->assertOk();

    expect(DB::table('site_visits')->count())->toBe(1);
    expect((int) DB::table('site_visits')->value('views'))->toBe(3);
});

test('a different browser counts as a different visitor', function () {
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/')->assertOk();
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0) Safari/604.1')->get('/')->assertOk();

    expect(DB::table('site_visits')->count())->toBe(2);
});

test('search engine bots and tools are not counted', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/')->assertOk();
    $this->withHeader('User-Agent', 'curl/8.5.0')->get('/')->assertOk();
    $this->withHeader('User-Agent', 'python-requests/2.31')->get('/')->assertOk();

    expect(DB::table('site_visits')->count())->toBe(0);
});

test('requests without a browser name are not counted', function () {
    $this->withHeader('User-Agent', '')->get('/')->assertOk();

    expect(DB::table('site_visits')->count())->toBe(0);
});

test('json calls, errors and redirects are not counted', function () {
    $this->withHeaders(['User-Agent' => SITE_USAGE_BROWSER, 'Accept' => 'application/json'])->get('/')->assertOk();
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/this-page-does-not-exist')->assertNotFound();
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get(route('usage.index'))->assertRedirect();

    expect(DB::table('site_visits')->count())->toBe(0);
});

test('a signed-in visit is marked as a member', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withHeader('User-Agent', SITE_USAGE_BROWSER)->get(route('dashboard'))->assertOk();

    expect((bool) DB::table('site_visits')->value('is_member'))->toBeTrue();
});

test('no address or browser name is stored for a visit', function () {
    $this->withHeader('User-Agent', SITE_USAGE_BROWSER)->get('/')->assertOk();

    $row = (array) DB::table('site_visits')->first();
    $stored = json_encode($row);

    expect(array_keys($row))->toEqualCanonicalizing(['id', 'day', 'visitor_hash', 'is_member', 'views', 'created_at']);
    expect($stored)->not->toContain('127.0.0.1')->not->toContain('Mozilla');
    expect(strlen($row['visitor_hash']))->toBe(32);
});

test('the usage page shows visitors, scans and sign-ups from the last days', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    DB::table('site_visits')->insert([
        ['day' => now()->toDateString(), 'visitor_hash' => str_repeat('a', 32), 'is_member' => false, 'views' => 4, 'created_at' => now()],
        ['day' => now()->toDateString(), 'visitor_hash' => str_repeat('b', 32), 'is_member' => true, 'views' => 2, 'created_at' => now()],
        ['day' => now()->subDays(2)->toDateString(), 'visitor_hash' => str_repeat('c', 32), 'is_member' => false, 'views' => 1, 'created_at' => now()],
    ]);
    Report::factory()->count(2)->create(['status' => 'completed', 'user_id' => $admin->id]);
    Report::factory()->create(['status' => 'completed', 'user_id' => null]);
    Report::factory()->create(['status' => 'active', 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('usage.index'))
        ->assertOk()
        ->assertViewHas('summary', fn (array $s) => $s['visitors_today'] === 2
            && $s['visitors_7'] === 3
            && $s['views_7'] === 7
            && $s['scans_7'] === 3
            && $s['guest_scans_30'] === 1
            && $s['users_total'] === 1)
        ->assertViewHas('days', fn (array $days) => count($days) === 30 && end($days)['visitors'] === 2);
});

test('the usage page works before any visit has been counted', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    DB::table('site_visits')->delete();

    $this->actingAs($admin)->get(route('usage.index'))->assertOk()->assertSee('No visits have been counted yet');
});

test('the privacy page mentions the visit counts', function () {
    $this->get(route('privacy'))->assertOk()->assertSee('Visit counts');
});
