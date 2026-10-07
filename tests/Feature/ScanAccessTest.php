<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;

function ownedScan(User $owner, array $report = [], string $verdict = 'phishing'): Report
{
    $scan = Report::factory()->create(array_merge([
        'user_id' => $owner->id,
        'url' => 'http://fake-bibd-login.example/verify',
        'type' => 'url',
        'status' => 'completed',
    ], $report));

    Analysis::factory()->create([
        'report_id' => $scan->id,
        'verdict' => $verdict,
        'risk_score' => 82,
        'duration_ms' => 3000,
        'flags' => [
            ['name' => 'URL Analysis', 'status' => 'HIGH RISK', 'message' => 'Looks like a BIBD lookalike.', 'points' => 40],
        ],
    ]);

    return $scan;
}

beforeEach(function () {
    $this->withoutVite();
});

// --- who can open a private scan ---

test('a stranger cannot open someone elses scan or its pdf', function () {
    $scan = ownedScan(User::factory()->create());

    $this->get(route('scan.show', $scan))->assertNotFound();
    $this->get(route('scan.pdf', $scan))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('scan.show', $scan))->assertNotFound();
});

test('the owner can open their own scan', function () {
    $owner = User::factory()->create();
    $scan = ownedScan($owner);

    $this->actingAs($owner)->get(route('scan.show', $scan))->assertOk();
});

test('team members and admins can open any scan', function () {
    $scan = ownedScan(User::factory()->create());

    $this->actingAs(User::factory()->create(['is_team_member' => true]))
        ->get(route('scan.show', $scan))
        ->assertOk();

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('scan.show', $scan))
        ->assertOk();
});

test('a guest can open the scan made in their own session', function () {
    $scan = ownedScan(User::factory()->create());

    $this->withSession(['guest_report_ids' => [$scan->id]])
        ->get(route('scan.show', $scan))
        ->assertOk();
});

// --- creating and revoking share links ---

test('the owner can create a share link for a finished link scan', function () {
    $owner = User::factory()->create();
    $scan = ownedScan($owner);

    $this->actingAs($owner)
        ->post(route('scan.share.store', $scan))
        ->assertRedirect(route('scan.show', $scan));

    expect($scan->fresh()->share_token)->toHaveLength(40);
});

test('someone else cannot create a share link', function () {
    $scan = ownedScan(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->post(route('scan.share.store', $scan))
        ->assertNotFound();

    expect($scan->fresh()->share_token)->toBeNull();
});

test('only link scans can be shared', function () {
    $owner = User::factory()->create();
    $scan = ownedScan($owner, ['type' => 'email', 'sender_email' => 'victim@example.com', 'url' => null]);

    $this->actingAs($owner)
        ->post(route('scan.share.store', $scan))
        ->assertStatus(422);
});

// --- the public shared page ---

test('a share link shows the result without personal details', function () {
    $owner = User::factory()->create(['name' => 'Secret Submitter']);
    $scan = ownedScan($owner);
    $scan->forceFill(['share_token' => str_repeat('a', 40)])->save();

    $response = $this->get(route('scan.shared', str_repeat('a', 40)));

    $response->assertOk()
        ->assertSee('fake-bibd-login.example')
        ->assertDontSee('Secret Submitter')
        ->assertDontSee(route('scan.pdf', $scan), false);

    expect($response->headers->get('X-Robots-Tag'))->toContain('noindex');
});

test('revoking a share link stops it working', function () {
    $owner = User::factory()->create();
    $scan = ownedScan($owner);
    $scan->forceFill(['share_token' => str_repeat('b', 40)])->save();

    $this->get(route('scan.shared', str_repeat('b', 40)))->assertOk();

    $this->actingAs($owner)->delete(route('scan.share.destroy', $scan))->assertRedirect();

    expect($scan->fresh()->share_token)->toBeNull();
    $this->get(route('scan.shared', str_repeat('b', 40)))->assertNotFound();
});

test('an unknown share token returns 404', function () {
    $this->get(route('scan.shared', str_repeat('z', 40)))->assertNotFound();
});

test('a share link for a non-link scan never works', function () {
    $owner = User::factory()->create();
    $scan = ownedScan($owner, ['type' => 'phone', 'phone_number' => '+6737654321', 'url' => null]);
    $scan->forceFill(['share_token' => str_repeat('c', 40)])->save();

    $this->get(route('scan.shared', str_repeat('c', 40)))->assertNotFound();
});