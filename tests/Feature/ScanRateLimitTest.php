<?php

use App\Models\Report;
use App\Models\User;

beforeEach(function () {
    config([
        'scan.guest_per_minute' => 100,
        'scan.guest_per_hour' => 2,
        'scan.user_per_minute' => 100,
        'scan.user_per_hour' => 5,
    ]);
});

/** An empty link fails validation, so nothing is scanned, but the attempt is still counted. */
function attemptScan(object $test, array $data = ['url' => ''], string $ip = '10.0.0.1')
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])->from('/')->post(route('scan.store'), $data);
}

test('a guest who keeps scanning is stopped with a friendly message that points to signing up', function () {
    attemptScan($this)->assertSessionHasErrors('url');
    attemptScan($this)->assertSessionHasErrors('url');

    $blocked = attemptScan($this, ['url' => 'http://example.test/']);

    $blocked->assertRedirect('/')->assertSessionHasErrors('url');
    expect(session('errors')->first('url'))->toContain('free guest scans')->toContain('Create a free account')
        ->and(Report::count())->toBe(0);
});

test('the limit is counted per IP address, so another visitor is not blocked', function () {
    attemptScan($this);
    attemptScan($this);
    attemptScan($this);

    $page = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->followingRedirects()->from('/')->post(route('scan.store'), ['url' => '']);

    $page->assertDontSee('free guest scans');
});

test('signed-in people get a larger allowance and a different message', function () {
    $me = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->actingAs($me)->from('/')->post(route('scan.store'), ['url' => ''])->assertSessionHasErrors('url');
        expect(session('errors')->first('url'))->not->toContain('scan limit');
    }

    $this->actingAs($me)->from('/')->post(route('scan.store'), ['url' => ''])->assertSessionHasErrors('url');

    expect(session('errors')->first('url'))->toContain('scan limit')->not->toContain('Create a free account');
});

test('scanning too fast within a minute asks the person to wait', function () {
    config(['scan.guest_per_minute' => 1]);

    attemptScan($this);

    $page = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->followingRedirects()->from('/')->post(route('scan.store'), ['url' => '']);

    $page->assertSee('scanning very quickly');
});

test('the message appears under the field of the scan that was sent', function () {
    attemptScan($this);
    attemptScan($this);

    attemptScan($this, ['email' => 'someone@example.test'])->assertSessionHasErrors('email');
});
