<?php

use App\Models\User;
use App\Services\PlayContent;

test('the Play page needs a login', function () {
    $this->get(route('play.index'))->assertRedirect(route('login'));
});

test('the Play page shows all three games and a menu tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('play.index'))
        ->assertOk()
        ->assertSee('Scam or Safe?')
        ->assertSee('Inbox Rush')
        ->assertSee('Scam Survivor')
        ->assertSee('Your stats')
        ->assertSee('Safety tip of the day')
        ->assertDontSee('Red flag cheat sheet')
        ->assertSee('Cyber Guardian')
        ->assertSee('Start rush')
        ->assertSee('Daily')
        ->assertSee('Hard')
        ->assertSee('game sounds')
        ->assertSee('Tap to skip')
        ->assertSee('Badges')
        ->assertSee('wardrobe')
        ->assertSee('Next unlock', false)
        ->assertSee('Unlocked: ', false)
        ->assertSee('pl-hat-crown', false)
        ->assertSee('Start a streak')
        ->assertSee('LEVEL UP!')
        ->assertSee('New best')
        ->assertSee('plSfx', false)
        ->assertDontSee('pl-grip', false);

    $this->actingAs($user)->get(route('scan.history'))->assertOk()->assertSee('Phish Lab', false);
});

test('saved progress is kept per user, so two people on one browser do not share it', function () {
    $one = User::factory()->create();
    $two = User::factory()->create();

    $scopeOne = substr(hash('sha256', 'play|'.$one->id), 0, 16);
    $scopeTwo = substr(hash('sha256', 'play|'.$two->id), 0, 16);

    expect($scopeOne)->not->toBe($scopeTwo);
    $this->actingAs($one)->get(route('play.index'))->assertOk()->assertSee($scopeOne, false)->assertDontSee($scopeTwo, false);
    $this->actingAs($two)->get(route('play.index'))->assertOk()->assertSee($scopeTwo, false)->assertDontSee($scopeOne, false);
});

test('every practice message has what the games need and both kinds are present', function () {
    $messages = PlayContent::messages();

    expect(count($messages))->toBeGreaterThanOrEqual(50);
    foreach ($messages as $m) {
        expect($m['kind'])->not->toBe('')
            ->and($m['from'])->not->toBe('')
            ->and($m['text'])->not->toBe('')
            ->and($m['why'])->not->toBe('')
            ->and($m['scam'])->toBeBool();
    }
    expect(collect($messages)->where('scam', true)->count())->toBeGreaterThan(5)
        ->and(collect($messages)->where('scam', false)->count())->toBeGreaterThan(5);
});

test('every safe message says why it can be trusted, so the lesson is not just "everything is a scam"', function () {
    foreach (collect(PlayContent::messages())->where('scam', false) as $m) {
        expect($m['context'] ?? null)->not->toBeNull();
    }
});

test('every story can be followed from start to an ending, with a win and a loss to find', function () {
    $stories = PlayContent::stories();

    expect(count($stories))->toBeGreaterThanOrEqual(20);
    foreach ($stories as $story) {
        expect($story['title'])->not->toBe('')
            ->and($story['blurb'])->not->toBe('')
            ->and($story['flags'])->not->toBeEmpty()
            ->and($story['nodes'])->toHaveKey($story['start']);

        $seen = [];
        $queue = [$story['start']];
        while ($queue) {
            $key = array_pop($queue);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            expect($story['nodes'])->toHaveKey($key);
            $node = $story['nodes'][$key];

            if (isset($node['end'])) {
                expect($node['end']['kind'])->toBeIn(['win', 'meh', 'lose'])
                    ->and($node['end']['title'])->not->toBe('')
                    ->and($node['end']['text'])->not->toBe('');
                continue;
            }

            expect($node['say'])->not->toBeEmpty()
                ->and(count($node['choices']))->toBeGreaterThanOrEqual(2);
            foreach ($node['choices'] as $choice) {
                expect($choice['text'])->not->toBe('');
                if ($choice['tip'] !== null) {
                    expect($choice['tip'][0])->toBeIn(['flag', 'good']);
                }
                $queue[] = $choice['next'];
            }
        }

        // Nothing in the story is left unreachable, and there is a way to win and a way to lose.
        expect(count($seen))->toBe(count($story['nodes']));
        $kinds = collect($story['nodes'])->filter(fn ($n) => isset($n['end']))->map(fn ($n) => $n['end']['kind'])->unique()->all();
        expect($kinds)->toContain('win')->toContain('lose');
    }
});

test('every amount placeholder in a story has values to fill it with', function () {
    foreach (PlayContent::stories() as $story) {
        preg_match_all('/\{(\w+)\}/', json_encode($story['nodes']), $found);
        foreach (array_unique($found[1]) as $name) {
            expect($story['vars'] ?? [])->toHaveKey($name);
            expect(count($story['vars'][$name]))->toBeGreaterThanOrEqual(2);
        }
    }
});

test('the practice content gives only the real Brunei contacts', function () {
    $all = json_encode([PlayContent::messages(), PlayContent::stories()]);

    // Any run of 6 or more digits would be a phone number or similar. The only digits allowed are the OTP and order examples.
    preg_match_all('/\d{6,}/', $all, $found);
    expect(array_diff($found[0], ['482913']))->toBe([]);
});

test('every story choice has a real chat line, and only silent choices that end the story go without one', function () {
    foreach (PlayContent::stories() as $story) {
        foreach ($story['nodes'] as $id => $node) {
            foreach ($node['choices'] ?? [] as $choice) {
                expect($choice)->toHaveKey('say');

                if ($choice['say'] === null) {
                    // A silent choice (the player just goes quiet) has to lead straight to an ending.
                    expect($story['nodes'][$choice['next']])->toHaveKey('end');

                    continue;
                }

                expect(trim($choice['say']))->not->toBe('');
                expect($choice['say'])->not->toStartWith('You chose');

                preg_match_all('/\{(\w+)\}/', $choice['say'], $found);
                foreach ($found[1] as $placeholder) {
                    expect($story['vars'] ?? [])->toHaveKey($placeholder);
                }
            }
        }
    }
});

test('the generated messages change between visits, stay balanced and keep every rule of the curated ones', function () {
    $a = collect(PlayContent::generated())->pluck('text')->all();
    $b = collect(PlayContent::generated())->pluck('text')->all();
    expect($a)->not->toBe($b);

    $all = PlayContent::messages();
    expect(count($all))->toBeGreaterThanOrEqual(90)
        ->and(count($all))->toBe(count(array_unique(array_column($all, 'text'))));

    $scams = collect($all)->where('scam', true)->count();
    expect($scams / count($all))->toBeBetween(0.4, 0.6);

    foreach (PlayContent::generated() as $m) {
        expect($m['kind'])->not->toBe('')->and($m['from'])->not->toBe('')->and($m['text'])->not->toBe('')->and($m['why'])->not->toBe('');
        if (! $m['scam']) {
            expect($m['context'])->not->toBeNull();
        }
        expect($m['text'])->not->toMatch('/\{|\}/');
    }
});

test('every story has its own animated icon on the page and a unique id', function () {
    $ids = array_column(PlayContent::stories(), 'id');
    expect($ids)->toBe(array_values(array_unique($ids)));

    $page = file_get_contents(resource_path('views/play/index.blade.php'));
    foreach ($ids as $id) {
        expect($page)->toContain('id="pl-ic-'.$id.'"');
    }
});

test('what the player types in the stories is varied and does not start every line the same way', function () {
    $lines = [];
    foreach (PlayContent::stories() as $story) {
        foreach ($story['nodes'] as $node) {
            foreach ($node['choices'] ?? [] as $choice) {
                if ($choice['say'] !== null) {
                    $lines[] = $choice['say'];
                }
            }
        }
    }

    expect(count(array_unique($lines)))->toBeGreaterThanOrEqual((int) floor(count($lines) * 0.95));

    $ok = array_filter($lines, fn ($line) => str_starts_with($line, 'Ok,') || str_starts_with($line, 'Ok '));
    expect(count($ok))->toBeLessThanOrEqual(3);
});