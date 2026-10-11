<?php

test('the home page has a contact section with the official email', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('id="contact"', false)
        ->assertSee('Get in Touch')
        ->assertSee('href="mailto:phishcorebn@gmail.com"', false);
});
