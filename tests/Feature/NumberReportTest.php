<?php

use App\Models\Analysis;
use App\Models\NumberReport;
use App\Models\Report;
use App\Models\User;
use App\Services\AnalysisEngine;

function phoneScan(User $owner, string $number = '+6738111346', string $type = 'phone'): Report
{
    $scan = Report::factory()->create([
        'user_id' => $owner->id,
        'type' => $type,
        'url' => $type === 'url' ? 'http://example.test/login' : null,
        'phone_number' => $type === 'phone' ? $number : null,
        'status' => 'completed',
    ]);

    Analysis::factory()->create([
        'report_id' => $scan->id,
        'verdict' => 'clean',
        'risk_score' => 5,
        'duration_ms' => 1200,
        'flags' => [
            ['name' => 'Phone Number Analysis', 'status' => 'SAFE', 'message' => 'Looks like a normal number.', 'points' => 0],
        ],
    ]);

    return $scan;
}

beforeEach(function () {
    $this->withoutVite();
});

test('a guest cannot report a number', function () {
    $scan = phoneScan(User::factory()->create());

    $this->post(route('number-report.store', $scan), ['category' => 'bank'])->assertRedirect(route('login'));

    expect(NumberReport::count())->toBe(0);
});

test('the owner of a phone scan can report the number and sees the box on the result page', function () {
    $user = User::factory()->create();
    $scan = phoneScan($user, '+673 811 1346');

    $this->actingAs($user)->get(route('scan.show', $scan))
        ->assertOk()
        ->assertSee('Is this number a scam?');

    $this->actingAs($user)->post(route('number-report.store', $scan), ['category' => 'bank'])
        ->assertRedirect(route('scan.show', $scan));

    $row = NumberReport::first();
    expect($row->user_id)->toBe($user->id)
        ->and($row->phone)->toBe('+6738111346')
        ->and($row->category)->toBe('bank');
});

test('reporting the same number again changes the kind of scam instead of adding a second report', function () {
    $user = User::factory()->create();
    $scan = phoneScan($user);

    $this->actingAs($user)->post(route('number-report.store', $scan), ['category' => 'bank']);
    $this->actingAs($user)->post(route('number-report.store', $scan), ['category' => 'parcel']);

    expect(NumberReport::count())->toBe(1)
        ->and(NumberReport::first()->category)->toBe('parcel');
});

test('only a real kind of scam is accepted', function () {
    $user = User::factory()->create();
    $scan = phoneScan($user);

    $this->actingAs($user)->post(route('number-report.store', $scan), ['category' => 'made-up'])
        ->assertSessionHasErrors('category');
    $this->actingAs($user)->post(route('number-report.store', $scan), [])
        ->assertSessionHasErrors('category');

    expect(NumberReport::count())->toBe(0);
});

test('someone else cannot report from your scan, and only phone scans can be reported', function () {
    $owner = User::factory()->create();
    $scan = phoneScan($owner);
    $linkScan = phoneScan($owner, type: 'url');

    $this->actingAs(User::factory()->create())
        ->post(route('number-report.store', $scan), ['category' => 'bank'])
        ->assertNotFound();

    $this->actingAs($owner)
        ->post(route('number-report.store', $linkScan), ['category' => 'bank'])
        ->assertStatus(422);

    expect(NumberReport::count())->toBe(0);
});

test('the public page lists a number only after two different people reported it', function () {
    [$a, $b, $c] = User::factory()->count(3)->create();

    NumberReport::create(['user_id' => $a->id, 'phone' => '+6737777001', 'category' => 'bank']);
    NumberReport::create(['user_id' => $b->id, 'phone' => '+6737777001', 'category' => 'bank']);
    NumberReport::create(['user_id' => $c->id, 'phone' => '+6737777001', 'category' => 'parcel']);
    NumberReport::create(['user_id' => $a->id, 'phone' => '+6738888002', 'category' => 'job']);

    $this->get(route('reports.public'))
        ->assertOk()
        ->assertSee('Most reported phone numbers')
        ->assertSee('+6737777001')
        ->assertSee('Pretending to be a bank')
        ->assertDontSee('+6738888002');
});

test('the public list shows one scam type when most reports agree and the top two when they do not', function () {
    $users = User::factory()->count(6)->create();

    // 3 of 4 say bank: a clear majority, so only bank is shown.
    foreach ([['bank', 0], ['bank', 1], ['bank', 2], ['job', 3]] as [$category, $i]) {
        NumberReport::create(['user_id' => $users[$i]->id, 'phone' => '+6737000001', 'category' => $category]);
    }

    // 1 bank, 1 job, 1 parcel: no majority, so two types plus "+1 more type" (ties follow the category order).
    foreach (['bank', 'job', 'parcel'] as $i => $category) {
        NumberReport::create(['user_id' => $users[$i]->id, 'phone' => '+6737000002', 'category' => $category]);
    }

    // 1 bank, 1 job: a tie, so both are shown and nothing is hidden.
    foreach (['bank', 'job'] as $i => $category) {
        NumberReport::create(['user_id' => $users[$i]->id, 'phone' => '+6737000003', 'category' => $category]);
    }

    $html = $this->get(route('reports.public'))->assertOk()->getContent();

    expect($html)
        ->toContain('Pretending to be a bank &middot; last reported')
        ->toContain('Pretending to be a bank · Parcel or delivery scam · +1 more type')
        ->toContain('Pretending to be a bank · Fake job offer &middot; last reported');
});

test('reports older than a year do not count towards the public list', function () {
    [$a, $b] = User::factory()->count(2)->create();

    foreach ([$a, $b] as $user) {
        $row = NumberReport::create(['user_id' => $user->id, 'phone' => '+6739999003', 'category' => 'prize']);
        $row->forceFill(['created_at' => now()->subDays(400)])->save();
    }

    $this->get(route('reports.public'))->assertOk()->assertDontSee('+6739999003');
});

test('people who reported a number make its next phone scan riskier', function () {
    $engine = app(AnalysisEngine::class);
    $number = '+6738111346';

    $before = $engine->analyze('phone', null, null, $number)['risk_score'];

    foreach (User::factory()->count(2)->create() as $user) {
        NumberReport::create(['user_id' => $user->id, 'phone' => $number, 'category' => 'bank']);
    }

    $after = $engine->analyze('phone', null, null, $number)['risk_score'];

    expect($after)->toBeGreaterThan($before);
});