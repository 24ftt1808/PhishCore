<?php

use App\Models\HiddenNumber;
use App\Models\NumberReport;
use App\Models\User;
use App\Services\AnalysisEngine;

function reportNumberTwiceForReportedNumbers(string $number, string $category = 'bank'): void
{
    foreach (User::factory()->count(2)->create() as $user) {
        NumberReport::create(['user_id' => $user->id, 'phone' => $number, 'category' => $category]);
    }
}

function reportedNumbersTeamMember(): User
{
    return User::factory()->create(['is_team_member' => true]);
}

function reportedNumbersAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

beforeEach(function () {
    $this->withoutVite();
});

test('the reported numbers page is for team members and administrators only', function () {
    $this->get(route('reported-numbers.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('reported-numbers.index'))->assertForbidden();

    $this->actingAs(reportedNumbersTeamMember())->get(route('reported-numbers.index'))->assertOk()->assertSee('Reported Numbers');
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('reported-numbers.index'))->assertOk();
});

test('the page lists reported numbers and can search them', function () {
    reportNumberTwiceForReportedNumbers('+6737100001');
    reportNumberTwiceForReportedNumbers('+6737200002', 'job');

    $this->actingAs(reportedNumbersTeamMember())->get(route('reported-numbers.index'))
        ->assertSee('+6737100001')
        ->assertSee('+6737200002');

    $this->actingAs(reportedNumbersTeamMember())->get(route('reported-numbers.index', ['search' => '71000']))
        ->assertSee('+6737100001')
        ->assertDontSee('+6737200002');
});

test('hiding a number takes it off the public list and showing it puts it back', function () {
    reportNumberTwiceForReportedNumbers('+6737300003');
    $member = reportedNumbersAdmin();

    $this->get(route('reports.public'))->assertSee('+6737300003');

    $this->actingAs($member)->post(route('reported-numbers.hide'), ['phone' => '+673 730 0003'])->assertRedirect();

    expect(HiddenNumber::pluck('phone')->all())->toBe(['+6737300003']);
    expect(HiddenNumber::first()->hidden_by)->toBe($member->id);
    $this->get(route('reports.public'))->assertDontSee('+6737300003');

    $this->actingAs($member)->post(route('reported-numbers.show'), ['phone' => '+6737300003'])->assertRedirect();

    expect(HiddenNumber::count())->toBe(0);
    $this->get(route('reports.public'))->assertSee('+6737300003');
});

test('only administrators can hide or show a number', function () {
    reportNumberTwiceForReportedNumbers('+6737400004');

    foreach ([User::factory()->create(), reportedNumbersTeamMember()] as $notAdmin) {
        $this->actingAs($notAdmin)->post(route('reported-numbers.hide'), ['phone' => '+6737400004'])->assertForbidden();
    }
    expect(HiddenNumber::count())->toBe(0);

    HiddenNumber::create(['phone' => '+6737400004']);
    foreach ([User::factory()->create(), reportedNumbersTeamMember()] as $notAdmin) {
        $this->actingAs($notAdmin)->post(route('reported-numbers.show'), ['phone' => '+6737400004'])->assertForbidden();
    }
    expect(HiddenNumber::count())->toBe(1);
});

test('team members see the page without the hide buttons, and administrators see them', function () {
    reportNumberTwiceForReportedNumbers('+6737450004');

    $this->actingAs(reportedNumbersTeamMember())->get(route('reported-numbers.index'))
        ->assertOk()
        ->assertSee('Only administrators can hide or show a number.')
        ->assertDontSee('>Hide</button>', false);

    $this->actingAs(reportedNumbersAdmin())->get(route('reported-numbers.index'))
        ->assertOk()
        ->assertDontSee('Only administrators can hide or show a number.')
        ->assertSee('>Hide</button>', false);
});

test('a hidden number shows who hid it', function () {
    reportNumberTwiceForReportedNumbers('+6737460004');
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Ada Admin']);
    HiddenNumber::create(['phone' => '+6737460004', 'hidden_by' => $admin->id]);

    $this->actingAs(reportedNumbersTeamMember())->get(route('reported-numbers.index'))
        ->assertOk()
        ->assertSee('Hidden')
        ->assertSee('by Ada Admin');
});

test('hiding the same number twice keeps one row', function () {
    $member = reportedNumbersAdmin();

    $this->actingAs($member)->post(route('reported-numbers.hide'), ['phone' => '+6737500005']);
    $this->actingAs($member)->post(route('reported-numbers.hide'), ['phone' => '+6737500005']);

    expect(HiddenNumber::count())->toBe(1);
});

test('a hidden number is treated as having no reports when it is scanned', function () {
    $number = '+6737600006';
    reportNumberTwiceForReportedNumbers($number);

    $before = collect(app(AnalysisEngine::class)->analyze('phone', null, null, $number)['checks'])->firstWhere('name', 'Previous Reports');
    expect($before['status'])->toBeIn(['SUSPICIOUS', 'HIGH RISK']);

    HiddenNumber::create(['phone' => $number]);

    $after = collect(app(AnalysisEngine::class)->analyze('phone', null, null, $number)['checks'])->firstWhere('name', 'Previous Reports');
    expect($after['status'])->toBe('INFO')->and($after['points'])->toBe(0);
});

test('the dashboard shows what the whole community did this week', function () {
    reportNumberTwiceForReportedNumbers('+6737700007');

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Across PhishCore this week')
        ->assertSee('+6737700007')
        ->assertSee('Reported by 2 people');
});

test('the dashboard leaves out hidden numbers and numbers only one person reported', function () {
    reportNumberTwiceForReportedNumbers('+6737800008');
    HiddenNumber::create(['phone' => '+6737800008']);
    NumberReport::create(['user_id' => User::factory()->create()->id, 'phone' => '+6737900009', 'category' => 'job']);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('+6737800008')
        ->assertDontSee('+6737900009')
        ->assertSee('No number has been reported by 2 or more people this week.');
});
