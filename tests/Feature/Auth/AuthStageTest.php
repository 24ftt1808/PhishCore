<?php

test('login and register share the animated stage and start in the right mode', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Create your account')
        ->assertSee('Create an account');

    $this->get('/register')
        ->assertOk()
        ->assertSee('Join the')
        ->assertSee('Already have an account?');
});

test('the form for the current URL is the one switched on', function () {
    expect($this->get('/login')->getContent())->toMatch('/as-form is-on"[^>]*x-ref="login"/');
    expect($this->get('/register')->getContent())->toMatch('/as-form(?: as-tight)? is-on"[^>]*x-ref="register"/');
});

test('both forms still post to their own routes', function () {
    $html = $this->get('/login')->getContent();

    expect($html)
        ->toContain('action="'.route('login').'"')
        ->toContain('action="'.route('register').'"')
        ->toContain('name="password_confirmation"');
});

test('a failed register attempt shows its errors on the register form only', function () {
    $this->from('/register')->post('/register', ['name' => '', 'email' => 'nope'])
        ->assertRedirect('/register');

    $this->get('/register')->assertOk();
    $this->get('/login')->assertOk()->assertDontSee('The name field is required.');
});

test('forgot password uses the same stage with the form switched on', function () {
    $html = $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password?')->assertSee('Secure Account Recovery')->getContent();

    expect($html)
        ->toMatch('/as-form is-on"[^>]*x-ref="forgot"/')
        ->toContain('is-mid')
        ->toContain('action="'.route('password.email').'"');
});

test('only a mouse on a wide screen gets the pinned desktop stage and the auto-focused field', function () {
    foreach (['/login', '/register', '/forgot-password', '/reset-password/some-token?email=a@b.test'] as $url) {
        expect($this->get($url)->getContent())
            ->toContain('@media (min-width: 1024px) and (hover: hover) and (pointer: fine) {')
            ->toContain('(min-width: 1024px) and (pointer: coarse)')
            ->toContain("window.matchMedia('(min-width: 1024px) and (hover: hover) and (pointer: fine)')")
            ->not->toContain("window.matchMedia('(min-width: 1024px)')");
    }
});

test('the reset screen reached from the e-mail link uses the stage too', function () {
    $this->get('/reset-password/some-token?email=a@b.test')
        ->assertOk()
        ->assertSee('Set a new password')
        ->assertSee('name="token" value="some-token"', false)
        ->assertSee('action="'.route('password.store').'"', false);
});