<?php

use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('signed-in pages have the floating glass top bar for phones', function () {
    $this->actingAs(User::factory()->create())->get(route('scan.index'))
        ->assertOk()
        ->assertSee('dash-topbar', false)
        ->assertSee('backdrop-filter', false);
});

test('the top bar has the glass buttons and the notification panel has its glass look and animation', function () {
    $this->actingAs(User::factory()->create())->get(route('scan.index'))
        ->assertOk()
        ->assertSee('tb-btn', false)
        ->assertSee('aria-label="Notifications"', false)
        ->assertSee('aria-label="Open the menu"', false)
        ->assertSee('nb-panel', false)
        ->assertSee('nb-in', false)
        ->assertSee('fx-lite', false);
});

test('the public pages have a fourth glow in the animated background', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(preg_match('#<div class="hero-aurora"[^>]*>\s*(<span></span>\s*){4}</div>#', $html))->toBe(1);
});

test('the landing page scanner has the same sliding tab pill as the signed-in scanner', function () {
    $this->get('/')->assertOk()->assertSee('scan-pill-in', false)->assertSee('stretch(from, to)', false);
});