<?php

/**
 * The public landing page only claims what PhishCore actually does, and does not name the school
 * (public pages are for everyone).
 */
beforeEach(function () {
    $this->withoutVite();
});

test('the landing page does not claim things PhishCore does not do', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertDontSee('Machine learning models')
        ->assertDontSee('high accuracy')
        ->assertDontSee('registrar reputation')
        ->assertDontSee('Politeknik Brunei');
});

test('the landing page says scanning needs no account and counts suspicious results as threats', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('no account needed')
        ->assertSee('Flagged as phishing or suspicious')
        ->assertSee('pass confirmed threats to BruCERT');
});