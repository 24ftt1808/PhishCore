<?php

use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('the scan page explains each check in one box and no longer repeats it below', function () {
    $response = $this->actingAs(User::factory()->create())->get('/scan');

    $response->assertOk()
        ->assertSee('WHAT THIS SCAN CHECKS')
        ->assertSee('Domains registered in the last 30 days are a strong phishing signal.')
        ->assertSee('whether other PhishCore users have reported it')
        ->assertDontSee('How PhishCore Checks Reports');
});

test('the checks box can be folded away on phones and only shows the checks for the chosen scan', function () {
    $this->actingAs(User::factory()->create())->get('/scan')
        ->assertOk()
        ->assertSee('sc-checks-toggle', false)
        ->assertSee('sc-checks-body', false)
        ->assertSee('sc-check-off', false)
        ->assertSee('checksOpen', false);
});
