<?php

use App\Models\Analysis;
use App\Models\Investigation;
use App\Models\NumberReport;
use App\Models\Report;
use App\Models\User;
use App\Services\CommunityReports;

/**
 * The "Community reports" box on a scan result: other scans of the same website, sender domain, phone number
 * or file, number reports, and the team's progress. Counts only, display only.
 */
beforeEach(function () {
    $this->withoutVite();
});

function communityReportScan(array $attributes, string $verdict = 'phishing', ?User $user = null): Report
{
    $report = Report::factory()->create(array_merge([
        'type' => 'url',
        'status' => 'completed',
        'user_id' => ($user ?? User::factory()->create())->id,
    ], $attributes));

    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => $verdict,
        'risk_score' => match ($verdict) {
            'phishing' => 80, 'suspicious' => 40, default => 5
        },
        'flags' => [['name' => 'URL Structure', 'status' => 'SAFE', 'message' => 'Nothing unusual.', 'points' => 0]],
    ]);

    return $report;
}

test('other scans of the same website are counted as people, with their results', function () {
    $repeat = User::factory()->create();
    communityReportScan(['url' => 'https://pdrm.may2yw.com/okoqk4'], 'phishing', $repeat);
    communityReportScan(['url' => 'https://pdrm.may2yw.com/other-path'], 'phishing', $repeat);
    communityReportScan(['url' => 'http://www.pdrm.may2yw.com/'], 'suspicious');
    communityReportScan(['url' => 'https://pdrm.may2yw.com.evil.example/'], 'phishing');
    $mine = communityReportScan(['url' => 'https://pdrm.may2yw.com/x']);

    $community = CommunityReports::for($mine);

    expect($community['subject'])->toBe('pdrm.may2yw.com')
        ->and($community['others'])->toBe(3)
        ->and($community['people'])->toBe(2)
        ->and($community['phishing'])->toBe(2)
        ->and($community['suspicious'])->toBe(1)
        ->and($community['flagged'])->toBe(3);
});

test('emails are matched on the sender domain and phone scans on the number, however it was typed', function () {
    communityReportScan(['type' => 'email', 'url' => null, 'sender_email' => 'alerts@bibd-secure.top']);
    $email = communityReportScan(['type' => 'email', 'url' => null, 'sender_email' => 'support@bibd-secure.top']);

    communityReportScan(['type' => 'phone', 'url' => null, 'phone_number' => '+673 712 3456']);
    $phone = communityReportScan(['type' => 'phone', 'url' => null, 'phone_number' => '+6737123456']);
    NumberReport::create(['user_id' => User::factory()->create()->id, 'phone' => '+6737123456', 'category' => 'parcel']);
    NumberReport::create(['user_id' => User::factory()->create()->id, 'phone' => '+6737123456', 'category' => 'parcel']);

    expect(CommunityReports::for($email)['others'])->toBe(1)
        ->and(CommunityReports::for($phone)['others'])->toBe(1)
        ->and(CommunityReports::for($phone)['number_reports'])->toBe(2)
        ->and(CommunityReports::for($phone)['top_category'])->toBe('Parcel or delivery scam');
});

test('the furthest team status on any scan of the same website is shown', function () {
    $earlier = communityReportScan(['url' => 'https://dh1-bru.site/pay']);
    Investigation::create(['report_id' => $earlier->id, 'status' => 'takedown_requested']);
    $mine = communityReportScan(['url' => 'https://dh1-bru.site/fee']);
    Investigation::create(['report_id' => $mine->id, 'status' => 'active']);

    expect(CommunityReports::for($mine)['team_label'])->toBe('The PhishCore team has reported this to BruCERT');
});

test('the result page shows the box with counts and the team status to the person who scanned', function () {
    $owner = User::factory()->create();
    $earlier = communityReportScan(['url' => 'https://dh1-bru.site/pay'], 'phishing');
    Investigation::create(['report_id' => $earlier->id, 'status' => 'takedown_requested']);
    $mine = communityReportScan(['url' => 'https://dh1-bru.site/fee'], 'phishing', $owner);

    $this->actingAs($owner)->get(route('scan.show', $mine))
        ->assertOk()
        ->assertSee('Community reports')
        ->assertSee('the website dh1-bru.site')
        ->assertSee('Checked by')
        ->assertSee('reported this to BruCERT');
});

test('a first sighting says so', function () {
    $owner = User::factory()->create();
    $mine = communityReportScan(['url' => 'https://brand-new-site.example/'], 'clean', $owner);

    $this->actingAs($owner)->get(route('scan.show', $mine))
        ->assertOk()
        ->assertSee('You are the first to check the website brand-new-site.example on PhishCore.');
});

test('the public shared page shows the counts but not the team status', function () {
    $earlier = communityReportScan(['url' => 'https://dh1-bru.site/pay']);
    Investigation::create(['report_id' => $earlier->id, 'status' => 'takedown_requested']);
    $mine = communityReportScan(['url' => 'https://dh1-bru.site/fee']);
    $mine->forceFill(['share_token' => str_repeat('a', 40)])->save();

    $this->get(route('scan.shared', str_repeat('a', 40)))
        ->assertOk()
        ->assertSee('Community reports')
        ->assertDontSee('reported this to BruCERT');
});

test('the box never names the people who scanned', function () {
    $other = User::factory()->create(['name' => 'Siti Rahmah']);
    communityReportScan(['url' => 'https://dh1-bru.site/pay'], 'phishing', $other);
    $owner = User::factory()->create();
    $mine = communityReportScan(['url' => 'https://dh1-bru.site/fee'], 'phishing', $owner);

    $this->actingAs($owner)->get(route('scan.show', $mine))
        ->assertOk()
        ->assertDontSee('Siti Rahmah');
});

test('the person\'s own earlier scans are not counted as other people', function () {
    $owner = User::factory()->create();
    communityReportScan(['url' => 'https://dh1-bru.site/pay'], 'phishing', $owner);
    $mine = communityReportScan(['url' => 'https://dh1-bru.site/fee'], 'phishing', $owner);

    $community = CommunityReports::for($mine);

    expect($community['others'])->toBe(1)
        ->and($community['people'])->toBe(0)
        ->and($community['yours_before'])->toBeTrue();

    $this->actingAs($owner)->get(route('scan.show', $mine))
        ->assertOk()
        ->assertSee('You have checked this before too.');
});

test('the "Why this score?" shares add up to 100% even when the checks add up to more than the score', function () {
    $owner = User::factory()->create();
    $report = Report::factory()->create(['type' => 'url', 'url' => 'https://dh1-bru.site/', 'status' => 'completed', 'user_id' => $owner->id]);
    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => 'phishing',
        'risk_score' => 100,
        'flags' => [
            ['name' => 'Message Content / Behavior Patterns', 'status' => 'HIGH RISK', 'message' => 'Fee.', 'points' => 75],
            ['name' => 'Brand / Domain Correlation', 'status' => 'HIGH RISK', 'message' => 'Brand.', 'points' => 50],
            ['name' => 'Link in Pressure Message', 'status' => 'SUSPICIOUS', 'message' => 'Link.', 'points' => 25],
        ],
    ]);

    $this->actingAs($owner)->get(route('scan.show', $report))
        ->assertOk()
        ->assertSeeInOrder(['50%', '33%', '17%'])
        ->assertDontSee('>75%<', false);
});