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
