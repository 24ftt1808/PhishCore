<?php

beforeEach(function () {
    $this->withoutVite();
});

test('the home page stat boxes for scans and threats are links', function () {
    $this->get('/')->assertOk()
        ->assertSee('aria-label="Scans performed: try the scanner"', false)
        ->assertSee('aria-label="Threats detected: see the public reports"', false)
        ->assertSee('href="'.route('reports.public').'"', false);
});