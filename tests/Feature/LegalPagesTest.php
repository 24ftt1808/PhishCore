<?php

test('the Terms of Use and Privacy Policy are public pages', function () {
    $this->get(route('terms'))->assertOk()->assertSee('Terms of Use')->assertSee('not a guarantee', false);
    $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy')->assertSee('Brevo');
});

test('the register page links to both pages instead of a dead link', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('href="'.route('terms').'"', false)
        ->assertSee('href="'.route('privacy').'"', false);
});

test('the footer on public pages links to both pages', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('href="'.route('terms').'"', false)
        ->assertSee('href="'.route('privacy').'"', false);
});
