<?php

test('the home page introduces Phil and the Phish Lab games, and Cora and the AI chat', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('id="learn"', false)
        ->assertSee('Meet Phil')
        ->assertSee('Phish Lab')
        ->assertSee('Scam or Safe?')
        ->assertSee('Inbox Rush')
        ->assertSee('Scam Survivor')
        ->assertSee('Meet Cora')
        ->assertSee('AI Chat')
        ->assertSee('Not sure about a message or link?');
});

test('the Phil and Cora cards have no sign up buttons', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertDontSee('Create an account to play')
        ->assertDontSee('Create an account to chat');
});