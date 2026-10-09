<?php

use App\Models\PlayProgress;
use App\Models\User;
use App\Services\PlayProgressSync;

test('saving progress needs a login', function () {
    $this->postJson(route('play.progress'), ['data' => ['xp' => '10']])->assertRedirect(route('login'));

    expect(PlayProgress::count())->toBe(0);
});

test('progress is saved for the signed-in user and comes back on the Play page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('play.progress'), ['data' => ['xp' => '250', 'rush' => '40', 'badges' => '["first"]']])
        ->assertOk();

    $row = PlayProgress::where('user_id', $user->id)->first();
    expect($row->xp)->toBe(250)
        ->and($row->data['rush'])->toBe('40');

    $this->actingAs($user)->get(route('play.index'))
        ->assertOk()
        ->assertSee('window.PL_SAVED', false)
        ->assertViewHas('saved', fn ($saved) => $saved->xp === '250' && $saved->rush === '40');
});

test('a player with nothing saved gets null, and each player only sees their own progress', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($a)->get(route('play.index'))->assertViewHas('saved', null);

    $this->actingAs($a)->postJson(route('play.progress'), ['data' => ['xp' => '999']])->assertOk();

    $this->actingAs($b)->get(route('play.index'))->assertViewHas('saved', null);
});

test('saving again merges, so a lower score from another device never wins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('play.progress'), ['data' => ['xp' => '500', 'rush' => '80']])->assertOk();
    $this->actingAs($user)->postJson(route('play.progress'), ['data' => ['xp' => '120', 'rush' => '95']])->assertOk();

    $row = PlayProgress::where('user_id', $user->id)->first();
    expect($row->xp)->toBe(500)
        ->and($row->data['rush'])->toBe('95')
        ->and(PlayProgress::count())->toBe(1);
});

test('the request must carry some data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('play.progress'), [])->assertStatus(422);
    $this->actingAs($user)->postJson(route('play.progress'), ['data' => 'xp'])->assertStatus(422);
});

test('unknown keys and bad values are dropped, not stored', function () {
    $clean = PlayProgressSync::clean([
        'xp' => '12abc',
        'rush' => '30',
        'is_admin' => '1',
        'rush-daily-20261009' => '55',
        'rush-daily-bad' => '5',
        'day-last' => 'yesterday',
        'sound' => '2',
        'survivor' => '{"a":"win","b":"cheated","c d":"win"}',
        'badges' => '["first","<script>"]',
        'wardrobe' => '{"hat":"crown","color":"red;x","water":"night"}',
        'side' => str_repeat('1', 5000),
    ]);

    expect($clean)->toBe([
        'rush' => '30',
        'rush-daily-20261009' => '55',
        'survivor' => '{"a":"win"}',
        'badges' => '["first"]',
        'wardrobe' => '{"hat":"crown","water":"night"}',
    ]);
});

test('merging never lowers a score and keeps the best story result and every badge', function () {
    $merged = PlayProgressSync::merge(
        ['xp' => '100', 'rush' => '50', 'survivor' => '{"a":"lose","b":"win"}', 'badges' => '["x","y"]'],
        ['xp' => '80', 'rush' => '70', 'survivor' => '{"a":"win","c":"meh"}', 'badges' => '["y","z"]'],
    );

    expect($merged['xp'])->toBe('100')
        ->and($merged['rush'])->toBe('70')
        ->and(json_decode($merged['survivor'], true))->toBe(['a' => 'win', 'b' => 'win', 'c' => 'meh'])
        ->and(json_decode($merged['badges'], true))->toBe(['x', 'y', 'z']);
});

test('the day streak follows the most recent day, and the newest look and sound settings win together', function () {
    $merged = PlayProgressSync::merge(
        ['day-last' => '20261008', 'day-n' => '5', 'day-best' => '5', 'sound' => '1', 'prefs-at' => '100', 'wardrobe' => '{"hat":"party"}'],
        ['day-last' => '20261009', 'day-n' => '1', 'day-best' => '1', 'sound' => '0', 'prefs-at' => '200', 'wardrobe' => '{"hat":"crown"}'],
    );

    expect($merged['day-last'])->toBe('20261009')
        ->and($merged['day-n'])->toBe('1')
        ->and($merged['day-best'])->toBe('5')
        ->and($merged['sound'])->toBe('0')
        ->and($merged['wardrobe'])->toBe('{"hat":"crown"}');

    $older = PlayProgressSync::merge(
        ['sound' => '1', 'prefs-at' => '300'],
        ['sound' => '0', 'prefs-at' => '200'],
    );
    expect($older['sound'])->toBe('1');
});

test('only the last two weeks of daily scores are kept', function () {
    $daily = [];
    for ($i = 1; $i <= 20; $i++) {
        $daily['rush-daily-202610'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)] = (string) $i;
    }

    $kept = PlayProgressSync::clean($daily);

    expect($kept)->toHaveCount(14)
        ->and($kept)->toHaveKey('rush-daily-20261020')
        ->and($kept)->not->toHaveKey('rush-daily-20261006');
});

test('the Play page saves and loads progress through the server', function () {
    $page = file_get_contents(resource_path('views/play/index.blade.php'));

    expect($page)->toContain('plSyncBoot()')
        ->and($page)->toContain('X-CSRF-TOKEN')
        ->and(substr_count($page, 'localStorage.setItem(PL_KEY('))->toBe(3);
});