<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Support\ScanReuse;
use Illuminate\Support\Facades\Http;

/** A finished scan of a link, with every check completed. */
function finishedScan(string $url, array $report = [], array $analysis = []): Report
{
    $created = Report::factory()->create($report + [
        'user_id' => User::factory()->create()->id,
        'type' => 'url',
        'url' => $url,
        'status' => 'completed',
    ]);

    Analysis::factory()->create($analysis + [
        'report_id' => $created->id,
        'verdict' => 'phishing',
        'risk_score' => 88,
        'flags' => [['name' => 'SSL', 'status' => 'SAFE', 'message' => 'Fine.', 'points' => 0]],
    ]);

    return $created;
}

beforeEach(function () {
    config(['scan.reuse_hours' => 6, 'scan.guest_per_hour' => 1, 'scan.guest_per_minute' => 100]);
});

test('a link scanned recently gets the earlier result instead of a new scan', function () {
    $earlier = finishedScan('http://reuse-me.test/');

    $response = $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/']);

    $copy = Report::where('id', '!=', $earlier->id)->first();

    $response->assertRedirect(route('scan.show', $copy))->assertSessionHas('info');
    expect(Report::count())->toBe(2)
        ->and($copy->status)->toBe('completed')
        ->and($copy->analyses->first()->verdict)->toBe('phishing')
        ->and($copy->analyses->first()->risk_score)->toBe(88);
});

test('a reused result does not use up the guest scan allowance', function () {
    finishedScan('http://reuse-me.test/');

    foreach (range(1, 3) as $i) {
        $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/'])->assertSessionHasNoErrors();
    }

    expect(Report::count())->toBe(4);
});

test('the copy belongs to the person who asked, and the original is left alone', function () {
    $earlier = finishedScan('http://reuse-me.test/');
    $me = User::factory()->create();

    $this->actingAs($me)->post(route('scan.store'), ['url' => 'http://reuse-me.test/']);

    $copy = Report::where('user_id', $me->id)->first();

    expect($copy)->not->toBeNull()
        ->and($copy->id)->not->toBe($earlier->id)
        ->and($earlier->fresh()->user_id)->not->toBe($me->id);
});

test('a guest can open the result they were given', function () {
    finishedScan('http://reuse-me.test/');

    $this->post(route('scan.store'), ['url' => 'http://reuse-me.test/']);
    $copy = Report::latest('id')->first();

    $this->get(route('scan.show', $copy))->assertOk()->assertSee('so you are seeing that result');
});

test('a signed-in person also sees the note that the result is from an earlier check', function () {
    finishedScan('http://reuse-me.test/');
    $me = User::factory()->create();

    $this->actingAs($me)->followingRedirects()->post(route('scan.store'), ['url' => 'http://reuse-me.test/'])
        ->assertOk()->assertSee('so you are seeing that result');
});

test('an earlier scan is only reused when it is recent, complete and of the same link', function () {
    $fresh = finishedScan('http://fresh.test/');
    finishedScan('http://old.test/', ['created_at' => now()->subHours(7)]);
    finishedScan('http://partial.test/', [], ['flags' => [['name' => 'Safe Browsing', 'status' => 'UNKNOWN', 'message' => 'Could not run.', 'points' => 0]]]);
    finishedScan('http://failed.test/', ['status' => 'failed']);

    expect(ScanReuse::find('http://fresh.test/')?->id)->toBe($fresh->id)
        ->and(ScanReuse::find(' http://fresh.test/ ')?->id)->toBe($fresh->id)
        ->and(ScanReuse::find('http://old.test/'))->toBeNull()
        ->and(ScanReuse::find('http://partial.test/'))->toBeNull()
        ->and(ScanReuse::find('http://failed.test/'))->toBeNull()
        ->and(ScanReuse::find('http://never-scanned.test/'))->toBeNull();
});

test('reusing results can be switched off', function () {
    finishedScan('http://fresh.test/');
    config(['scan.reuse_hours' => 0]);

    expect(ScanReuse::find('http://fresh.test/'))->toBeNull();
});

test('a robot that fills in the hidden trap field is dropped without a scan or a message', function () {
    finishedScan('http://reuse-me.test/');

    $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/', 'contact_fax' => 'spam'])
        ->assertRedirect('/')->assertSessionHasNoErrors();

    expect(Report::count())->toBe(1);
});

test('the scan forms carry the hidden trap field', function () {
    $this->get(route('welcome'))->assertOk()->assertSee('name="contact_fax"', false);
});

test('without Turnstile keys there is no human check', function () {
    config(['services.turnstile.site_key' => null, 'services.turnstile.secret_key' => null]);

    $this->get(route('welcome'))->assertDontSee('cf-turnstile', false);
});

test('with Turnstile keys, guests see the check and signed-in people do not', function () {
    config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);

    $this->get(route('welcome'))->assertSee('cf-turnstile', false)->assertSee('data-sitekey="site-key"', false);
    $this->actingAs(User::factory()->create())->get(route('welcome'))->assertDontSee('cf-turnstile', false);
});

test('a guest who fails the human check is sent back and nothing is scanned', function () {
    config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
    finishedScan('http://reuse-me.test/');

    $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/', 'cf-turnstile-response' => 'bad'])
        ->assertRedirect('/')->assertSessionHasErrors('url');

    expect(Report::count())->toBe(1);
});

test('a guest who passes the human check is trusted for a while and does not repeat it', function () {
    config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
    finishedScan('http://reuse-me.test/');

    $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/', 'cf-turnstile-response' => 'good'])
        ->assertSessionHasNoErrors();
    $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/'])
        ->assertSessionHasNoErrors();

    Http::assertSentCount(1);
    expect(Report::count())->toBe(3);
});

test('a guest who sends no human check answer at all is turned back', function () {
    config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);
    Http::fake();

    $this->from('/')->post(route('scan.store'), ['url' => 'http://reuse-me.test/'])->assertSessionHasErrors('url');

    Http::assertNothingSent();
});
