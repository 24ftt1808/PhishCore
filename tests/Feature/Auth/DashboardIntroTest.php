<?php

use App\Models\User;

test('the dashboard entrance animation plays once after signing in, not on later visits', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->get('/dashboard')->assertOk()->assertSee('data-intro="1"', false);
    $this->get('/dashboard')->assertOk()->assertDontSee('data-intro="1"', false);
});

test('registering also plays it once', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'intro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->get('/dashboard')->assertOk()->assertSee('data-intro="1"', false);
    $this->get('/dashboard')->assertOk()->assertDontSee('data-intro="1"', false);
});

test('opening the dashboard from another page does not replay it', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertOk()->assertDontSee('data-intro="1"', false);
});

test('a failed sign in does not trigger it', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

    $this->assertGuest();
    expect(session()->has('intro'))->toBeFalse();
});