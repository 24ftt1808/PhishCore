<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('profile page shows the user\'s scan activity', function () {
    $user = User::factory()->create();
    $report = Report::factory()->create(['user_id' => $user->id]);
    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => 'phishing']);
    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => 'clean']);
    Analysis::factory()->create(['verdict' => 'phishing']);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertViewHas('activity', fn (array $a) => $a['scans'] === 2 && $a['phishing'] === 1 && $a['clean'] === 1);
});

test('profile page lists the devices signed in as the user', function () {
    $user = User::factory()->create();
    config(['session.driver' => 'database']);
    DB::table('sessions')->insert([
        ['id' => 'recent', 'user_id' => $user->id, 'ip_address' => '10.0.0.5', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0 Safari/537.36', 'payload' => '', 'last_activity' => now()->timestamp],
        ['id' => 'expired', 'user_id' => $user->id, 'ip_address' => '10.0.0.6', 'user_agent' => null, 'payload' => '', 'last_activity' => now()->subDays(3)->timestamp],
        ['id' => 'other', 'user_id' => User::factory()->create()->id, 'ip_address' => '10.0.0.7', 'user_agent' => null, 'payload' => '', 'last_activity' => now()->timestamp],
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertViewHas('devices', fn (array $devices) => count($devices) === 1
            && $devices[0]['label'] === 'Chrome on Windows'
            && $devices[0]['ip'] === '10.0.0.5')
        ->assertSee('Where you are signed in');
});

test('profile page hides the devices list when sessions are not stored in the database', function () {
    $this->actingAs(User::factory()->create())
        ->get('/profile')
        ->assertOk()
        ->assertViewHas('devices', null)
        ->assertDontSee('Where you are signed in');
});
