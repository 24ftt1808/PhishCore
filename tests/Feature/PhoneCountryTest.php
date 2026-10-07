<?php

use App\Models\Report;
use App\Support\PhoneCountries;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
});

// --- the country list ---

test('the country list covers the world with Brunei pinned first', function () {
    $all = PhoneCountries::all();

    expect(count($all))->toBeGreaterThan(230)
        ->and($all[0])->toBe(['code' => 'BN', 'name' => 'Brunei', 'dial' => '673'])
        ->and(PhoneCountries::codes())->toContain('MY', 'SG', 'ID', 'PH', 'US', 'GB', 'AU');

    $byCode = array_column($all, null, 'code');

    expect($byCode['MY']['dial'])->toBe('60')
        ->and($byCode['SG']['dial'])->toBe('65')
        ->and($byCode['US']['dial'])->toBe('1');
});

test('every country has a real name and a unique code', function () {
    $all = PhoneCountries::all();

    foreach ($all as $country) {
        expect($country['name'])->not->toBe($country['code']);
    }

    expect(array_unique(PhoneCountries::codes()))->toHaveCount(count($all));
});

// --- normalising what the user typed ---

test('a local number is read as belonging to the selected country', function () {
    expect(PhoneCountries::normalise('811 1346', 'BN'))->toBe('+673 811 1346')
        ->and(PhoneCountries::normalise('012-345 6789', 'MY'))->toBe('+60 12-345 6789')
        ->and(PhoneCountries::normalise('9123 4567', 'SG'))->toBe('+65 9123 4567');
});

test('with no or an unknown country selected, a local number is read as Brunei', function () {
    expect(PhoneCountries::normalise('8111346', null))->toBe('+673 811 1346')
        ->and(PhoneCountries::normalise('8111346', 'ZZ'))->toBe('+673 811 1346');
});

test('a number with its own country code keeps it whatever is selected', function () {
    expect(PhoneCountries::normalise('+673 811 1346', 'MY'))->toBe('+673 811 1346')
        ->and(PhoneCountries::normalise('00673 811 1346', 'MY'))->toBe('+673 811 1346');
});

test('input that is not a number is returned as typed so the engine can flag it', function () {
    expect(PhoneCountries::normalise('abc', 'BN'))->toBe('abc');
});

// --- the form ---

test('the scan page offers a country selector that defaults to Brunei', function () {
    $this->get(route('scan.index'))
        ->assertOk()
        ->assertSee('name="phone_country"', false)
        ->assertSee('Brunei (+673)')
        ->assertSee('Malaysia (+60)');
});

test('an unknown country is rejected', function () {
    $this->post(route('scan.store'), ['phone' => '8111346', 'phone_country' => 'ZZ'])
        ->assertSessionHasErrors('phone_country');

    expect(Report::count())->toBe(0);
});

// --- a full scan submission ---
// The phone reputation lookup is skipped when no API key is set, so these
// make no outside requests; preventStrayRequests() makes that a hard rule.

test('a scan stores the number in international form using the selected country', function () {
    Http::preventStrayRequests();
    config(['services.abstractapi_phone.key' => null]);

    $this->post(route('scan.store'), ['phone' => '012-345 6789', 'phone_country' => 'MY'])
        ->assertRedirect();

    $report = Report::latest('id')->first();

    expect($report->type)->toBe('phone')
        ->and($report->phone_number)->toBe('+60 12-345 6789')
        ->and($report->status)->toBe('completed');
});

test('a scan with no country selected is treated as a Brunei number', function () {
    Http::preventStrayRequests();
    config(['services.abstractapi_phone.key' => null]);

    $this->post(route('scan.store'), ['phone' => '8111346'])->assertRedirect();

    expect(Report::latest('id')->first()->phone_number)->toBe('+673 811 1346');
});

test('a number typed with a plus keeps its own country over the selection', function () {
    Http::preventStrayRequests();
    config(['services.abstractapi_phone.key' => null]);

    $this->post(route('scan.store'), ['phone' => '+65 9123 4567', 'phone_country' => 'BN'])->assertRedirect();

    expect(Report::latest('id')->first()->phone_number)->toBe('+65 9123 4567');
});

test('the front page scanner also offers the country selector', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('name="phone_country"', false)
        ->assertSee('Brunei (+673)');
});