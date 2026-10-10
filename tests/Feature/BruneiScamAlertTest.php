<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Notifications\BruneiScamNotice;
use App\Services\BruneiScamAlert;

beforeEach(function () {
    $this->withoutVite();
});

function scanWithFlags(string $verdict, array $flags, ?User $owner = null, ?string $url = null): Analysis
{
    static $n = 0;

    $report = Report::factory()->create([
        'user_id' => $owner?->id,
        'url' => $url ?? 'http://bibd-secure-login'.(++$n).'.example/verify',
        'status' => 'completed',
    ]);

    return Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict, 'flags' => $flags]);
}

function bruneiFlag(int $points = 30): array
{
    return ['name' => 'URL Structure', 'status' => 'HIGH RISK', 'message' => 'Domain imitates the Brunei brand "bibd" but is not its official domain', 'points' => $points];
}

test('a phishing scan that imitates a Brunei brand tells the team and the admins, but not the scanner, suspended staff or regular users', function () {
    $scanner = User::factory()->create(['is_team_member' => true]);
    $colleague = User::factory()->create(['is_team_member' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_team_member' => false]);
    $suspended = User::factory()->create(['is_team_member' => true, 'suspended_at' => now()]);
    $regular = User::factory()->create(['is_team_member' => false]);

    $analysis = scanWithFlags('phishing', [bruneiFlag()], $scanner);

    expect($colleague->notifications)->toHaveCount(1)
        ->and($admin->notifications)->toHaveCount(1)
        ->and($scanner->notifications)->toHaveCount(0)
        ->and($suspended->notifications)->toHaveCount(0)
        ->and($regular->notifications)->toHaveCount(0);

    $data = $colleague->notifications->first()->data;
    expect($data['kind'])->toBe(BruneiScamNotice::KIND)
        ->and($data['message'])->toBe('High-risk scan imitating BIBD: '.$analysis->report->url)
        ->and($data['report_id'])->toBe($analysis->report_id);
});

test('a guest scan also tells the team', function () {
    $team = User::factory()->create(['is_team_member' => true]);

    scanWithFlags('phishing', [bruneiFlag()], null);

    expect($team->notifications)->toHaveCount(1);
});

test('nothing is sent for a suspicious scan, a scam with no Brunei brand, or a warning that scored nothing', function () {
    $team = User::factory()->create(['is_team_member' => true]);

    scanWithFlags('suspicious', [bruneiFlag()]);
    scanWithFlags('phishing', [['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a PayPal lookalike.', 'points' => 40]]);
    scanWithFlags('phishing', [bruneiFlag(0)]);
    scanWithFlags('phishing', []);

    expect($team->notifications)->toHaveCount(0);
});

test('the brand shown matches the warning, and an open alert goes to the scan', function () {
    $team = User::factory()->create(['is_team_member' => true]);

    $analysis = scanWithFlags('phishing', [[
        'name' => 'URL Structure', 'status' => 'HIGH RISK', 'points' => 30,
        'message' => 'Domain pretends to be a Brunei government site (contains "gov.bn") but is not under .gov.bn',
    ]]);

    expect($team->notifications->first()->data['message'])->toContain('imitating a Brunei government body');

    $this->actingAs($team)->get(route('notifications.open', $team->notifications->first()->id))
        ->assertRedirect(route('scan.show', $analysis->report_id));
});

test('the brand finder ignores ordinary words that only contain a brand name', function () {
    expect(BruneiScamAlert::brandFor('phishing', [['message' => 'Adstock marketing page', 'points' => 20]]))->toBeNull()
        ->and(BruneiScamAlert::brandFor('phishing', [['message' => 'Scam pretending to be your DST bill', 'points' => 20]]))->toBe('DST')
        ->and(BruneiScamAlert::brandFor('phishing', null))->toBeNull();
});

test('a phishing scan with no known brand still alerts the team when it has a Brunei sign', function () {
    $team = User::factory()->create(['is_team_member' => true]);

    scanWithFlags('phishing', [['name' => 'URL', 'status' => 'HIGH RISK', 'message' => 'Brand new site', 'points' => 30]], null, 'http://pay-now.com.bn/login');
    scanWithFlags('phishing', [['name' => 'URL', 'status' => 'HIGH RISK', 'message' => 'Asks you to pay in BND today', 'points' => 30]]);
    scanWithFlags('phishing', [['name' => 'URL', 'status' => 'HIGH RISK', 'message' => 'Brand new site', 'points' => 30]], null, 'http://pay-now.example/login');

    $messages = $team->notifications->pluck('data.message')->implode(' | ');

    expect($team->notifications)->toHaveCount(2)
        ->and($messages)->toContain('High-risk scan with Brunei wording')
        ->and($messages)->toContain('High-risk scan with a Brunei (.bn) address');
});

test('a Brunei phone number counts as a Brunei sign', function () {
    $team = User::factory()->create(['is_team_member' => true]);
    $report = Report::factory()->create(['type' => 'phone', 'url' => null, 'sender_email' => null, 'phone_number' => '+6738123456', 'status' => 'completed']);

    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => 'phishing', 'flags' => []]);

    expect($team->notifications->first()->data['message'])->toBe('High-risk scan with a Brunei phone number: +6738123456');
});

test('three different people scanning the same website raises one campaign alert', function () {
    $team = User::factory()->create(['is_team_member' => true]);
    $people = User::factory()->count(3)->create();
    $plain = [['name' => 'URL', 'status' => 'HIGH RISK', 'message' => 'Brand new site', 'points' => 30]];

    scanWithFlags('suspicious', $plain, $people[0], 'http://www.cheap-parcel.example/a');
    scanWithFlags('suspicious', $plain, $people[1], 'https://cheap-parcel.example/b');
    expect($team->notifications)->toHaveCount(0);

    scanWithFlags('phishing', $plain, $people[2], 'http://cheap-parcel.example/c');
    scanWithFlags('phishing', $plain, null, 'http://cheap-parcel.example/d');

    $alerts = $team->fresh()->notifications;

    expect($alerts)->toHaveCount(1)
        ->and($alerts->first()->data['message'])->toStartWith('Possible scam campaign: 3 people scanned the same thing');
});

test('one person scanning the same website again and again is not a campaign', function () {
    $team = User::factory()->create(['is_team_member' => true]);
    $person = User::factory()->create();
    $plain = [['name' => 'URL', 'status' => 'HIGH RISK', 'message' => 'Brand new site', 'points' => 30]];

    foreach (['a', 'b', 'c', 'd'] as $page) {
        scanWithFlags('suspicious', $plain, $person, 'http://cheap-parcel.example/'.$page);
    }

    expect($team->notifications)->toHaveCount(0);
});

test('clean scans and old scans do not count towards a campaign', function () {
    $team = User::factory()->create(['is_team_member' => true]);
    $plain = [['name' => 'URL', 'status' => 'HIGH RISK', 'message' => 'Brand new site', 'points' => 30]];

    scanWithFlags('clean', [], null, 'http://popular-shop.example/a');
    scanWithFlags('clean', [], null, 'http://popular-shop.example/b');
    scanWithFlags('suspicious', $plain, null, 'http://popular-shop.example/c');

    $old = scanWithFlags('suspicious', $plain, null, 'http://old-scam.example/a');
    $old->report->forceFill(['created_at' => now()->subDays(5)])->save();
    scanWithFlags('suspicious', $plain, null, 'http://old-scam.example/b');
    scanWithFlags('suspicious', $plain, null, 'http://old-scam.example/c');

    expect($team->notifications)->toHaveCount(0);
});