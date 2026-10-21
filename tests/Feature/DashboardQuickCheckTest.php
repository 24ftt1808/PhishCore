<?php

use App\Models\User;

test('the dashboard quick check posts a link to the scan and links to the full scan page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Check a link before you click it')
        ->assertSee('action="'.route('scan.store').'"', false)
        ->assertSee('name="url"', false)
        ->assertSee('aria-label="Link to check"', false)
        ->assertSee('href="'.route('scan.index').'" class="qc-more d-link"', false)
        ->assertDontSee('PHISHCORE ENGINE');
});

test('the quick check has styles for phone widths', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('.qc-form { margin-top: 1.1rem; flex-direction: column;', false);
});