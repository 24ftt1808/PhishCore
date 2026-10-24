<?php

use App\Models\HiddenNumber;
use App\Models\KnownScamNumber;
use App\Models\User;
use App\Services\AnalysisEngine;

function knownNumbersAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

beforeEach(function () {
    $this->withoutVite();
});

test('only an administrator can add a known scam number', function () {
    $payload = ['phone' => '8123456', 'phone_country' => 'BN', 'category' => 'bank', 'source' => 'Royal Brunei Police Force'];

    $this->post(route('reported-numbers.known.store'), $payload)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post(route('reported-numbers.known.store'), $payload)->assertForbidden();
    $this->actingAs(User::factory()->create(['is_team_member' => true]))->post(route('reported-numbers.known.store'), $payload)->assertForbidden();

    expect(KnownScamNumber::count())->toBe(0);

    $admin = knownNumbersAdmin();
    $this->actingAs($admin)->post(route('reported-numbers.known.store'), $payload)->assertRedirect();

    $known = KnownScamNumber::first();

    expect($known->phone)->toBe('+6738123456')
        ->and($known->source)->toBe('Royal Brunei Police Force')
        ->and($known->category)->toBe('bank')
        ->and($known->added_by)->toBe($admin->id);
});

test('the number is stored the same way however it is typed, and adding it again updates it', function () {
    $admin = knownNumbersAdmin();

    $this->actingAs($admin)->post(route('reported-numbers.known.store'), ['phone' => '+673 812 3456', 'category' => 'bank', 'source' => 'BDCB']);
    $this->actingAs($admin)->post(route('reported-numbers.known.store'), ['phone' => '812-3456', 'phone_country' => 'BN', 'category' => 'parcel', 'source' => 'The Star']);

    expect(KnownScamNumber::count())->toBe(1)
        ->and(KnownScamNumber::first()->category)->toBe('parcel')
        ->and(KnownScamNumber::first()->source)->toBe('The Star');
});

test('something that is not a phone number is refused', function () {
    $this->actingAs(knownNumbersAdmin())
        ->post(route('reported-numbers.known.store'), ['phone' => 'hello there', 'category' => 'bank', 'source' => 'BDCB'])
        ->assertSessionHasErrors('phone');

    expect(KnownScamNumber::count())->toBe(0);
});

test('a source and a kind of scam are required', function () {
    $this->actingAs(knownNumbersAdmin())
        ->post(route('reported-numbers.known.store'), ['phone' => '8123456', 'category' => 'nonsense'])
        ->assertSessionHasErrors(['source', 'category']);
});

test('a scan of a known scam number is flagged and says who named it', function () {
    KnownScamNumber::create(['phone' => '+6738123456', 'category' => 'bank', 'source' => 'Royal Brunei Police Force', 'note' => 'Police warning, October 2026']);

    $result = (new AnalysisEngine)->analyze('phone', null, null, '+673 812 3456');
    $check = collect($result['checks'])->firstWhere('name', 'Previous Reports');

    expect($check['points'])->toBe(60)
        ->and($check['message'])->toContain('Royal Brunei Police Force')->toContain('Pretending to be a bank')->toContain('Police warning, October 2026')
        ->and($result['verdict'])->toBe('phishing');
});

test('a number that is not on the list is not affected', function () {
    KnownScamNumber::create(['phone' => '+6738123456', 'category' => 'bank', 'source' => 'BDCB']);

    $result = (new AnalysisEngine)->analyze('phone', null, null, '+673 812 3457');

    expect(collect($result['checks'])->firstWhere('name', 'Previous Reports')['points'])->toBe(0);
});

test('a hidden number is treated as having no reports, a known one included', function () {
    KnownScamNumber::create(['phone' => '+6738123456', 'category' => 'bank', 'source' => 'BDCB']);
    HiddenNumber::create(['phone' => '+6738123456']);

    $result = (new AnalysisEngine)->analyze('phone', null, null, '+673 812 3456');

    expect(collect($result['checks'])->firstWhere('name', 'Previous Reports')['points'])->toBe(0);
});

test('an administrator can remove a known scam number, nobody else can', function () {
    $known = KnownScamNumber::create(['phone' => '+6738123456', 'category' => 'bank', 'source' => 'BDCB']);

    $this->actingAs(User::factory()->create(['is_team_member' => true]))->delete(route('reported-numbers.known.destroy', $known))->assertForbidden();
    expect(KnownScamNumber::count())->toBe(1);

    $this->actingAs(knownNumbersAdmin())->delete(route('reported-numbers.known.destroy', $known))->assertRedirect();
    expect(KnownScamNumber::count())->toBe(0);
});

test('the reported numbers page shows the known numbers, and the form only to administrators', function () {
    KnownScamNumber::create(['phone' => '+6738123456', 'category' => 'bank', 'source' => 'Royal Brunei Police Force']);

    $this->actingAs(User::factory()->create(['is_team_member' => true]))->get(route('reported-numbers.index'))
        ->assertOk()->assertSee('Known scam numbers')->assertSee('+6738123456')->assertSee('Royal Brunei Police Force')
        ->assertDontSee(route('reported-numbers.known.store'));

    $this->actingAs(knownNumbersAdmin())->get(route('reported-numbers.index'))
        ->assertOk()->assertSee(route('reported-numbers.known.store'));
});

test('a number that cannot exist gets a plain explanation', function () {
    $result = (new AnalysisEngine)->checkPhoneNumber('+673 812 345');

    expect($result['points'])->toBe(35)
        ->and($result['reasons'][0])->toContain('may have been typed wrongly')->toContain('spoofed');
});