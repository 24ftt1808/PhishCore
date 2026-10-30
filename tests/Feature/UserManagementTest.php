<?php

use App\Models\User;

test('only administrators can view user management', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('user-management.index'))->assertForbidden();
});

test('team member filter lists only team members', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_team_member' => false]);
    $teamMember = User::factory()->create(['name' => 'Team Person', 'role' => 'user', 'is_team_member' => true]);
    $regularUser = User::factory()->create(['name' => 'Regular Person', 'role' => 'user', 'is_team_member' => false]);

    $this->actingAs($admin)
        ->get(route('user-management.index', ['role' => 'team']))
        ->assertOk()
        ->assertSee($teamMember->name)
        ->assertDontSee($regularUser->name);
});

test('team member count is included in the stats', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(2)->create(['is_team_member' => true]);

    $this->actingAs($admin)
        ->get(route('user-management.index'))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats) => $stats['team'] === 2);
});

test('role filter still works for users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(2)->create(['role' => 'user']);

    $this->actingAs($admin)
        ->get(route('user-management.index', ['role' => 'user']))
        ->assertOk()
        ->assertViewHas('users', fn ($users) => $users->count() === 2 && $users->every(fn (User $user) => $user->role === 'user'));
});

test('the user list shows when each person last logged in', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['name' => 'Recent Person', 'last_login_at' => now()->subHours(3)]);
    User::factory()->create(['name' => 'Silent Person', 'last_login_at' => null]);

    $this->actingAs($admin)
        ->get(route('user-management.index'))
        ->assertOk()
        ->assertSee('LAST LOGIN')
        ->assertSee('3 hours ago')
        ->assertSee('Not recorded yet');
});

test('an administrator can suspend and then reactivate someone', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $person = User::factory()->create(['role' => 'user']);

    $this->actingAs($admin)->post(route('user-management.toggle-suspend', $person))
        ->assertRedirect(route('user-management.index'));
    expect($person->fresh()->isSuspended())->toBeTrue();

    $this->actingAs($admin)->post(route('user-management.toggle-suspend', $person))
        ->assertRedirect(route('user-management.index'));
    expect($person->fresh()->isSuspended())->toBeFalse();
});

test('an administrator cannot suspend their own account', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('user-management.toggle-suspend', $admin))
        ->assertRedirect(route('user-management.index'))
        ->assertSessionHas('error');

    expect($admin->fresh()->isSuspended())->toBeFalse();
});

test('regular users and team members cannot suspend anyone', function () {
    $target = User::factory()->create();

    $this->actingAs(User::factory()->create(['role' => 'user']))
        ->post(route('user-management.toggle-suspend', $target))->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'user', 'is_team_member' => true]))
        ->post(route('user-management.toggle-suspend', $target))->assertForbidden();

    expect($target->fresh()->isSuspended())->toBeFalse();
});

test('someone who is suspended while signed in is signed out on their next page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('notifications.index'))->assertOk();

    $user->forceFill(['suspended_at' => now()])->save();

    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => User::SUSPENDED_MESSAGE]);
    $this->assertGuest();
});

test('a suspended person whose page asks for JSON gets a 403 and is signed out', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->forceFill(['suspended_at' => now()])->save();

    $this->getJson(route('notifications.index'))
        ->assertForbidden()
        ->assertJson(['message' => User::SUSPENDED_MESSAGE]);
    $this->assertGuest();
});

test('a reactivated person can use the site again', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $user->forceFill(['suspended_at' => null])->save();

    $this->actingAs($user)->get(route('notifications.index'))->assertOk();
});
