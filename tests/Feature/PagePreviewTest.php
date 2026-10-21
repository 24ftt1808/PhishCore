<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Services\AnalysisEngine;
use App\Services\PagePreview;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

const PREVIEW_UUID = '0a1b2c3d-4e5f-4a6b-8c7d-9e0f1a2b3c4d';

/** A real 8x8 PNG. */
function previewPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgGB4AAADIAAH4XVuUAAAAAElFTkSuQmCC');
}

function previewOn(array $settings = []): void
{
    Storage::fake('public');
    Cache::flush();
    config(['services.page_preview' => array_merge([
        'enabled' => true,
        'key' => 'test-key',
        'visibility' => 'unlisted',
        'daily_cap' => 50,
        'keep_days' => 7,
    ], $settings)]);
}

function previewScan(User $owner, array $report = []): Report
{
    $scan = Report::factory()->create(array_merge([
        'user_id' => $owner->id,
        'url' => 'http://fake-bibd-login.example/verify',
        'type' => 'url',
        'status' => 'completed',
    ], $report));

    Analysis::factory()->create([
        'report_id' => $scan->id,
        'verdict' => 'phishing',
        'risk_score' => 82,
        'duration_ms' => 3000,
        'flags' => [
            ['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a BIBD lookalike.', 'points' => 40],
        ],
    ]);

    return $scan;
}

/** urlscan.io accepts the link, then (a moment later) has the result and the picture. */
function urlscanFinished(): array
{
    return [
        'urlscan.io/api/v1/scan/' => Http::response(['uuid' => PREVIEW_UUID, 'visibility' => 'unlisted'], 200),
        'urlscan.io/api/v1/result/*' => Http::response(['task' => ['uuid' => PREVIEW_UUID]], 200),
        'urlscan.io/screenshots/*' => Http::response(previewPng(), 200, ['Content-Type' => 'image/png']),
    ];
}

beforeEach(function () {
    $this->withoutVite();
});

// --- off by default ---

test('the preview is off by default: no button, the route is a 404 and nothing is sent', function () {
    Http::fake();
    config(['services.page_preview.enabled' => false, 'services.page_preview.key' => 'test-key']);
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk()->assertDontSee('Show page preview');
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertNotFound();

    Http::assertNothingSent();
});

test('it also stays off when no key is set', function () {
    Http::fake();
    config(['services.page_preview.enabled' => true, 'services.page_preview.key' => null]);
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner)))->assertNotFound();

    Http::assertNothingSent();
});

test('the result page offers the button to the owner when it is switched on', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();

    $this->actingAs($owner)->get(route('scan.show', previewScan($owner)))
        ->assertOk()
        ->assertSee('Show page preview');
});

test('the result page has a loading skeleton for the picture, hidden from screen readers, with the wait message', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();

    $this->actingAs($owner)->get(route('scan.show', previewScan($owner)))
        ->assertOk()
        ->assertSee('<div class="pv-skel" aria-hidden="true">', false)
        ->assertSee('Taking the picture. This usually takes 10 to 30 seconds...');
});

test('the loading state shows rotating phishing tips', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();

    $this->actingAs($owner)->get(route('scan.show', previewScan($owner)))
        ->assertOk()
        ->assertSee('Did you know?')
        ->assertSee('never ask for your password or OTP', false)
        ->assertSee('startTips()', false)
        ->assertSee('stopTips()', false);
});

test('the enlarged viewer is on the page for the owner but not on a public share link', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('Enlarge picture')
        ->assertSee('id="preview-pan"', false)
        ->assertSee('aria-label="Zoom in"', false);

    $token = Str::random(40);
    $scan->forceFill(['share_token' => $token])->save();
    $this->get(route('scan.shared', $token))->assertOk()->assertDontSee('Enlarge picture')->assertDontSee('preview-pan');
});

test('the button is not offered on a public share link', function () {
    previewOn();
    Http::fake();
    $scan = previewScan(User::factory()->create());
    $token = Str::random(40);
    $scan->forceFill(['share_token' => $token])->save();

    $this->get(route('scan.shared', $token))->assertOk()->assertDontSee('Show page preview');
});

// --- taking the picture ---

test('the first click sends the link to urlscan.io as an unlisted scan and reports pending', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))
        ->assertOk()
        ->assertJsonPath('status', 'pending');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'urlscan.io/api/v1/scan/')
        && $request->method() === 'POST'
        && $request->header('API-Key') === ['test-key']
        && $request['url'] === $scan->url
        && $request['visibility'] === 'unlisted');
    Http::assertSentCount(1);
});

test('the next click finds it finished, saves the picture under a random name and remembers it', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'pending');
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))
        ->assertOk()
        ->assertJsonPath('status', 'ready');

    $stored = app(PagePreview::class)->storedPath($scan->fresh()->screenshot_path);

    expect($stored)->not->toBeNull()->and($stored)->toEndWith('.png');
    Storage::disk('public')->assertExists($stored);
});

test('it keeps saying pending while urlscan.io is still scanning', function () {
    previewOn();
    Http::fake([
        'urlscan.io/api/v1/scan/' => Http::response(['uuid' => PREVIEW_UUID], 200),
        'urlscan.io/api/v1/result/*' => Http::response(['status' => 404], 404),
    ]);
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    foreach (range(1, 3) as $click) {
        $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'pending');
    }

    expect($scan->fresh()->screenshot_path)->toBeNull();
});

test('a finished scan whose picture is not there yet keeps waiting, then gives up', function () {
    previewOn();
    Http::fake([
        'urlscan.io/api/v1/scan/' => Http::response(['uuid' => PREVIEW_UUID], 200),
        'urlscan.io/api/v1/result/*' => Http::response([], 200),
        'urlscan.io/screenshots/*' => Http::response('', 404),
    ]);
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'pending');
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'pending');
    $this->travel(2)->minutes();
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'unavailable');
});

test('the saved picture address is site-relative', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan));
    $url = $this->actingAs($owner)->postJson(route('scan.preview', $scan))->json('url');

    expect($url)->toStartWith('/storage/previews/')->and($scan->fresh()->screenshot_path)->toBe($url);
});

test('it gives up when urlscan.io never finishes', function () {
    previewOn();
    Http::fake([
        'urlscan.io/api/v1/scan/' => Http::response(['uuid' => PREVIEW_UUID], 200),
        'urlscan.io/api/v1/result/*' => Http::response(['status' => 404], 404),
    ]);
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'pending');
    $this->travel(2)->minutes();
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'unavailable');
});

test('once saved, later clicks reuse the picture and send nothing', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan));
    $first = $this->actingAs($owner)->postJson(route('scan.preview', $scan))->json('url');
    Http::fake();
    $second = $this->actingAs($owner)->postJson(route('scan.preview', $scan))->json('url');

    expect($second)->toBe($first);
    Http::assertNothingSent();

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk()->assertSee(basename($first), false);
});

test('if the saved file is gone, a click starts a new scan', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan));
    $this->actingAs($owner)->postJson(route('scan.preview', $scan));
    Storage::disk('public')->delete(app(PagePreview::class)->storedPath($scan->fresh()->screenshot_path));

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'pending');
});

test('private visibility can be chosen, and anything else falls back to unlisted, never public', function () {
    previewOn(['visibility' => 'private']);
    expect(app(PagePreview::class)->visibility())->toBe('private');

    config(['services.page_preview.visibility' => 'public']);
    expect(app(PagePreview::class)->visibility())->toBe('unlisted');
});

// --- failing quietly ---

test('urlscan.io refusing the link reports unavailable and saves nothing', function () {
    previewOn();
    Http::fake(['urlscan.io/api/v1/scan/' => Http::response(['message' => 'DNS Error', 'status' => 400], 400)]);
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))
        ->assertOk()
        ->assertJsonPath('status', 'unavailable');

    expect($scan->fresh()->screenshot_path)->toBeNull()->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('when urlscan.io refuses a link, its explanation is logged for the developer', function () {
    previewOn();
    Log::spy();
    Http::fake(['urlscan.io/api/v1/scan/' => Http::response(['message' => 'Scan prevented: this domain is not scannable', 'status' => 400], 400)]);
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner)))->assertJsonPath('status', 'unavailable');

    Log::shouldHaveReceived('warning')
        ->with('Page preview unavailable', ['reason' => 'http_400', 'detail' => 'Scan prevented: this domain is not scannable'])
        ->once();
});

test('urlscan.io rate limiting reports limit', function () {
    previewOn();
    Http::fake(['urlscan.io/api/v1/scan/' => Http::response([], 429)]);
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner)))->assertJsonPath('status', 'limit');
});

test('a picture that is not really a PNG is never saved', function () {
    previewOn();
    Http::fake([
        'urlscan.io/api/v1/scan/' => Http::response(['uuid' => PREVIEW_UUID], 200),
        'urlscan.io/api/v1/result/*' => Http::response([], 200),
        'urlscan.io/screenshots/*' => Http::response('<script>alert(1)</script>', 200),
    ]);
    $owner = User::factory()->create();
    $scan = previewScan($owner);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan));
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertJsonPath('status', 'unavailable');

    expect($scan->fresh()->screenshot_path)->toBeNull()->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('a connection failure is reported as unavailable and the key never reaches the reply', function () {
    previewOn();
    Http::fake(['urlscan.io/*' => fn () => throw new ConnectionException('timed out: API-Key test-key for http://fake-bibd-login.example')]);
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner)));

    $response->assertOk()->assertJsonPath('status', 'unavailable');
    expect($response->getContent())->not->toContain('test-key')->and($response->getContent())->not->toContain('fake-bibd-login');
});

test('links to private addresses are refused without sending anything', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();
    $scan = previewScan($owner, ['url' => 'http://192.168.1.10/login']);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertOk()->assertJsonPath('status', 'invalid');

    Http::assertNothingSent();
});

// --- who and what ---

test('a stranger cannot take a picture for someone elses scan', function () {
    previewOn();
    Http::fake();
    $scan = previewScan(User::factory()->create());

    $this->postJson(route('scan.preview', $scan))->assertNotFound();
    $this->actingAs(User::factory()->create())->postJson(route('scan.preview', $scan))->assertNotFound();

    Http::assertNothingSent();
});

test('a guest can use it for the scan made in their own session, and the team for any scan', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $scan = previewScan(User::factory()->create());

    $this->withSession(['guest_report_ids' => [$scan->id]])
        ->postJson(route('scan.preview', $scan))
        ->assertOk()
        ->assertJsonPath('status', 'pending');

    $this->actingAs(User::factory()->create(['is_team_member' => true]))
        ->postJson(route('scan.preview', previewScan(User::factory()->create())))
        ->assertOk()
        ->assertJsonPath('status', 'pending');
});

test('only finished link scans can have a preview', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner, ['type' => 'phone'])))->assertNotFound();
    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner, ['status' => 'active'])))->assertNotFound();

    Http::assertNothingSent();
});

// --- limits ---

test('the daily allowance stops further scans being sent', function () {
    previewOn(['daily_cap' => 1]);
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner)))->assertJsonPath('status', 'pending');
    $this->actingAs($owner)->postJson(route('scan.preview', previewScan($owner)))->assertJsonPath('status', 'limit');

    Http::assertSentCount(1);
});

// --- housekeeping ---

test('only addresses of saved previews are recognised', function () {
    $name = Str::random(40);
    $service = app(PagePreview::class);

    expect($service->storedPath("https://phishcore.example/storage/previews/{$name}.png"))->toBe("previews/{$name}.png")
        ->and($service->storedPath("https://phishcore.example/storage/screenshots/{$name}.png"))->toBeNull()
        ->and($service->storedPath('https://phishcore.example/storage/previews/../.env'))->toBeNull()
        ->and($service->storedPath("https://phishcore.example/storage/previews/{$name}.php"))->toBeNull()
        ->and($service->storedPath(null))->toBeNull();
});

test('pruning deletes old pictures, keeps recent ones and clears the links to the deleted ones', function () {
    previewOn();
    $owner = User::factory()->create();
    $old = 'previews/'.Str::random(40).'.png';
    $recent = 'previews/'.Str::random(40).'.png';
    Storage::disk('public')->put($old, previewPng());
    Storage::disk('public')->put($recent, previewPng());
    touch(Storage::disk('public')->path($old), now()->subDays(10)->getTimestamp());
    $oldScan = previewScan($owner, ['screenshot_path' => asset('storage/'.$old)]);
    $recentScan = previewScan($owner, ['screenshot_path' => asset('storage/'.$recent)]);

    $this->artisan('previews:prune')->assertSuccessful();

    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($recent);
    expect($oldScan->fresh()->screenshot_path)->toBeNull()
        ->and($recentScan->fresh()->screenshot_path)->toContain($recent);
});

// --- screenshot and email scans ---

test('a screenshot scan with a link in it offers the preview, and the box is hidden when there is no link', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();
    $withLink = previewScan($owner, ['type' => 'screenshot', 'url' => 'https://steam-gift-rewards.com/claim', 'screenshot_path' => '/storage/screenshots/up.png']);
    $withoutLink = previewScan($owner, ['type' => 'screenshot', 'url' => null, 'screenshot_path' => '/storage/screenshots/up2.png']);

    $this->actingAs($owner)->get(route('scan.show', $withLink))->assertOk()
        ->assertSee('Show page preview')
        ->assertSee('the link in your screenshot')
        ->assertSee('steam-gift-rewards.com');
    $this->actingAs($owner)->get(route('scan.show', $withoutLink))->assertOk()->assertDontSee('Show page preview');
    $this->actingAs($owner)->postJson(route('scan.preview', $withoutLink))->assertNotFound();
});

test('a screenshot preview is saved separately and never replaces the uploaded image', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner, ['type' => 'screenshot', 'url' => 'https://steam-gift-rewards.com/claim', 'screenshot_path' => '/storage/screenshots/up.png']);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertOk()->assertJson(['status' => 'pending']);
    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertOk()->assertJson(['status' => 'ready']);

    $scan->refresh();
    expect($scan->screenshot_path)->toBe('/storage/screenshots/up.png')
        ->and($scan->preview_path)->toStartWith('/storage/previews/');
    Storage::disk('public')->assertExists(app(PagePreview::class)->storedPath($scan->preview_path));
});

test('an email scan only offers the preview when a link was found in it', function () {
    previewOn();
    Http::fake();
    $owner = User::factory()->create();
    $withLink = previewScan($owner, ['type' => 'email', 'url' => null, 'preview_url' => 'http://bn-parcel-track.com/app']);
    $withoutLink = previewScan($owner, ['type' => 'email', 'url' => 'http://not-used-for-email.example', 'preview_url' => null]);

    $this->actingAs($owner)->get(route('scan.show', $withLink))->assertOk()
        ->assertSee('Show page preview')
        ->assertSee('the link in your email');
    $this->actingAs($owner)->get(route('scan.show', $withoutLink))->assertOk()->assertDontSee('Show page preview');
    $this->actingAs($owner)->postJson(route('scan.preview', $withoutLink))->assertNotFound();
});

test('an email preview sends the link that was found in the email', function () {
    previewOn();
    Http::fake(urlscanFinished());
    $owner = User::factory()->create();
    $scan = previewScan($owner, ['type' => 'email', 'url' => null, 'preview_url' => 'http://bn-parcel-track.com/app']);

    $this->actingAs($owner)->postJson(route('scan.preview', $scan))->assertOk()->assertJson(['status' => 'pending']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'urlscan.io/api/v1/scan') && $request['url'] === 'http://bn-parcel-track.com/app');
});

test('pruning also clears the saved picture of screenshot and email scans', function () {
    previewOn();
    $owner = User::factory()->create();
    $old = 'previews/'.Str::random(40).'.png';
    Storage::disk('public')->put($old, previewPng());
    touch(Storage::disk('public')->path($old), now()->subDays(10)->getTimestamp());
    $scan = previewScan($owner, ['type' => 'screenshot', 'url' => 'https://x-example.com', 'screenshot_path' => '/storage/screenshots/up.png', 'preview_path' => '/storage/'.$old]);

    $this->artisan('previews:prune')->assertSuccessful();

    expect($scan->fresh()->preview_path)->toBeNull()
        ->and($scan->fresh()->screenshot_path)->toBe('/storage/screenshots/up.png');
});

test('the engine picks the most suspicious full link in an email and ignores messages without one', function () {
    $method = new ReflectionMethod(AnalysisEngine::class, 'pickLinkForPreview');
    $method->setAccessible(true);
    $engine = new AnalysisEngine;

    expect($method->invoke($engine, 'Hello, no link here at all.'))->toBeNull()
        ->and($method->invoke($engine, 'Visit https://www.google.com or http://bibd-secure-login.com/verify now'))->toBe('http://bibd-secure-login.com/verify');
});