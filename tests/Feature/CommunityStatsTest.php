<?php

use App\Models\Analysis;
use App\Models\NumberReport;
use App\Models\Report;
use App\Models\User;
use App\Services\CommunityStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function communityScan(string $verdict, ?User $owner = null, ?string $url = null, array $flags = [], string $status = 'completed', $when = null): Report
{
    $report = Report::factory()->create([
        'user_id' => $owner?->id,
        'type' => 'url',
        'url' => $url ?? 'http://example-'.uniqid().'.test/',
        'status' => $status,
        'created_at' => $when ?? now(),
    ]);

    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict, 'flags' => $flags]);

    return $report;
}

test('the totals count every finished scan from every user and guest, and nothing else', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    communityScan('clean', $a);
    communityScan('clean', null);
    communityScan('suspicious', $b);
    communityScan('phishing', $a);
    communityScan('phishing', null, status: 'processing');

    $stats = CommunityStats::build();

    expect($stats['totals'])->toBe(['total' => 4, 'clean' => 2, 'suspicious' => 1, 'phishing' => 1]);
});

test('the week chart has seven days, oldest first, and today holds today\'s scans', function () {
    communityScan('phishing');
    communityScan('clean');
    communityScan('clean', when: now()->subDays(3));
    communityScan('clean', when: now()->subDays(20));

    $days = CommunityStats::build()['days'];

    expect($days)->toHaveCount(7)
        ->and($days[6])->toMatchArray(['clean' => 1, 'suspicious' => 0, 'phishing' => 1])
        ->and($days[3]['clean'])->toBe(1)
        ->and(array_sum(array_column($days, 'clean')))->toBe(2);
});

test('a link is only listed once two different people have scanned it', function () {
    $one = User::factory()->create();
    $two = User::factory()->create();

    foreach (['/a', '/b', '/c'] as $path) {
        communityScan('phishing', $one, 'http://lonely-scam.test'.$path);
    }
    communityScan('phishing', $one, 'http://shared-scam.test/x');
    communityScan('phishing', $two, 'https://www.shared-scam.test/y');
    communityScan('suspicious', $one, 'http://only-suspicious.test/');
    communityScan('suspicious', $two, 'http://only-suspicious.test/');

    $items = array_column(CommunityStats::build()['top'], 'item');

    expect($items)->toBe(['shared-scam.test']);
});

test('guests each count as one person and a known Brunei brand is named', function () {
    $flags = [['name' => 'URL', 'status' => 'HIGH RISK', 'points' => 30, 'message' => 'Domain imitates the Brunei brand "bibd" but is not its official domain']];

    communityScan('phishing', null, 'http://bibd-verify.test/1', $flags);
    communityScan('phishing', null, 'http://bibd-verify.test/2', $flags);

    $top = CommunityStats::build()['top'];

    expect($top)->toHaveCount(1)
        ->and($top[0])->toMatchArray(['kind' => 'link', 'item' => 'bibd-verify.test', 'type' => 'Imitates BIBD', 'count' => 2, 'verb' => 'scanned']);
});

test('phone numbers reported by two people are mixed into the list, most reported first, and nobody is named', function () {
    $people = User::factory()->count(3)->create();

    foreach ($people as $person) {
        NumberReport::create(['user_id' => $person->id, 'phone' => '+6738111111', 'category' => 'bank']);
    }
    NumberReport::create(['user_id' => $people[0]->id, 'phone' => '+6738222222', 'category' => 'job']);
    NumberReport::create(['user_id' => $people[1]->id, 'phone' => '+6738222222', 'category' => 'job']);
    NumberReport::create(['user_id' => $people[0]->id, 'phone' => '+6738333333', 'category' => 'job']);

    communityScan('phishing', $people[0], 'http://pair.test/a');
    communityScan('phishing', $people[1], 'http://pair.test/b');

    $top = CommunityStats::build()['top'];

    expect(array_column($top, 'item'))->toBe(['+6738111111', 'pair.test', '+6738222222'])
        ->and($top[0])->toMatchArray(['kind' => 'phone', 'type' => 'Pretending to be a bank', 'count' => 3, 'verb' => 'reported'])
        ->and(json_encode($top))->not->toContain($people[0]->email);
});

test('the list stops at ten rows', function () {
    $x = User::factory()->create();
    $y = User::factory()->create();

    foreach (range(1, 12) as $i) {
        communityScan('phishing', $x, "http://scam{$i}.test/a");
        communityScan('phishing', $y, "http://scam{$i}.test/b");
    }

    expect(CommunityStats::build()['top'])->toHaveCount(10);
});

test('the numbers are cached between page loads when nothing has changed', function () {
    Cache::forget(CommunityStats::CACHE_KEY);
    communityScan('clean');

    expect(CommunityStats::get()['totals']['total'])->toBe(1);

    // A change that bypasses the model events, as if it happened in another process.
    DB::table('reports')->update(['status' => 'processing']);
    expect(CommunityStats::get()['totals']['total'])->toBe(1);

    Cache::forget(CommunityStats::CACHE_KEY);
    expect(CommunityStats::get()['totals']['total'])->toBe(0);
});

test('a new scan shows up in the numbers straight away', function () {
    expect(CommunityStats::get()['totals']['total'])->toBe(0);

    $report = Report::factory()->create(['type' => 'url', 'url' => 'http://fresh.test/', 'status' => 'processing']);
    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => 'phishing', 'flags' => []]);

    // Not finished yet, so not counted, but the saved copy was thrown away.
    expect(CommunityStats::get()['totals']['total'])->toBe(0);

    $report->update(['status' => 'completed']);

    expect(CommunityStats::get()['totals'])->toBe(['total' => 1, 'clean' => 0, 'suspicious' => 0, 'phishing' => 1]);
});

test('a new number report and a removed scan refresh the Top 10 and totals straight away', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    expect(CommunityStats::get()['top'])->toBe([]);

    NumberReport::create(['user_id' => $a->id, 'phone' => '+6738444444', 'category' => 'bank']);
    NumberReport::create(['user_id' => $b->id, 'phone' => '+6738444444', 'category' => 'bank']);

    expect(array_column(CommunityStats::get()['top'], 'item'))->toBe(['+6738444444']);

    $scan = communityScan('clean');
    expect(CommunityStats::get()['totals']['total'])->toBe(1);

    $scan->delete();
    expect(CommunityStats::get()['totals']['total'])->toBe(0);
});

test('a regular user sees the community section on the analytics page, without anyone else\'s details', function () {
    $this->withoutVite();
    $me = User::factory()->create(['is_team_member' => false, 'role' => 'user']);
    $other = User::factory()->create();

    communityScan('phishing', $other, 'http://private-looking-scam.test/secret');
    communityScan('clean', $other);

    $this->actingAs($me)->get(route('analytics'))
        ->assertOk()
        ->assertSee('Brunei Scam Trends')
        ->assertSee('Top 10 most reported scams')
        ->assertSee('data-community-empty', false)
        ->assertDontSee('private-looking-scam.test')
        ->assertDontSee($other->email);
});

test('the analytics page lists a shared scam in the community Top 10', function () {
    $this->withoutVite();
    $me = User::factory()->create();
    $other = User::factory()->create();

    communityScan('phishing', $me, 'http://shared-scam.test/1');
    communityScan('phishing', $other, 'http://shared-scam.test/2');

    $this->actingAs($me)->get(route('analytics'))
        ->assertOk()
        ->assertSee('data-community-top', false)
        ->assertSee('shared-scam.test');
});

test('guests are sent to log in instead of seeing the community numbers', function () {
    $this->get(route('analytics'))->assertRedirect(route('login'));
});

test('the analytics page shows the week facts and a prompt to scan when the Top 10 is short', function () {
    $this->withoutVite();
    $me = User::factory()->create();

    communityScan('phishing', $me, 'http://one-off.test/a');
    communityScan('clean', $me);

    $this->actingAs($me)->get(route('analytics'))
        ->assertOk()
        ->assertSee('Busiest day')
        ->assertSee('High risk this week')
        ->assertSee('Scans per day')
        ->assertSee('data-community-cta', false)
        ->assertSee('Help this list grow');
});

test('the scan prompt goes away once the Top 10 has six or more rows', function () {
    $this->withoutVite();
    $x = User::factory()->create();
    $y = User::factory()->create();

    foreach (range(1, 6) as $i) {
        communityScan('phishing', $x, "http://six{$i}.test/a");
        communityScan('phishing', $y, "http://six{$i}.test/b");
    }

    $this->actingAs($x)->get(route('analytics'))
        ->assertOk()
        ->assertSee('data-community-top', false)
        ->assertDontSee('data-community-cta', false);
});

test('the page copes with a brand new site that has no scans at all', function () {
    $this->withoutVite();
    $me = User::factory()->create();

    $this->actingAs($me)->get(route('analytics'))
        ->assertOk()
        ->assertSee('Brunei Scam Trends')
        ->assertSee('data-community-empty', false)
        ->assertSee('data-community-cta', false);
});

test('the card shows the top five and a View all button opens the full list once there are more than five', function () {
    $this->withoutVite();
    $x = User::factory()->create();
    $y = User::factory()->create();

    foreach (range(1, 7) as $i) {
        communityScan('phishing', $x, "http://seven{$i}.test/a");
        communityScan('phishing', $y, "http://seven{$i}.test/b");
    }

    $html = $this->actingAs($x)->get(route('analytics'))
        ->assertOk()
        ->assertSee('data-community-viewall', false)
        ->assertSee('data-community-modal', false)
        ->assertSee('View all 7')
        ->getContent();

    // 5 rows on the card plus all 7 inside the overlay
    expect(substr_count($html, 'data-community-top'))->toBe(12);
});

test('there is no View all button when the list fits on the card', function () {
    $this->withoutVite();
    $x = User::factory()->create();
    $y = User::factory()->create();

    foreach (range(1, 5) as $i) {
        communityScan('phishing', $x, "http://five{$i}.test/a");
        communityScan('phishing', $y, "http://five{$i}.test/b");
    }

    $html = $this->actingAs($x)->get(route('analytics'))
        ->assertOk()
        ->assertDontSee('data-community-viewall', false)
        ->assertDontSee('data-community-modal', false)
        ->getContent();

    expect(substr_count($html, 'data-community-top'))->toBe(5);
});

test('the analytics page has My scans and Community tabs, and both panels are on the page', function () {
    $this->withoutVite();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('analytics'))
        ->assertOk()
        ->assertSee('data-analytics-tabs', false)
        ->assertSee('data-tab-mine', false)
        ->assertSee('data-tab-community', false)
        ->assertSee('id="panel-mine"', false)
        ->assertSee('id="panel-community"', false);
});