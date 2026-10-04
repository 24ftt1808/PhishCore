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
