<?php

use App\Models\ScamNews;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('the dashboard shows the latest visible scam news', function () {
    ScamNews::factory()->create(['title' => 'Visible scam story', 'source' => 'Example Daily']);
    ScamNews::factory()->hidden()->create(['title' => 'Hidden scam story']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Latest Scam News')
        ->assertSee('Visible scam story')
        ->assertSee('Example Daily')
        ->assertDontSee('Hidden scam story');
});

test('the dashboard says so when there is no scam news yet', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No scam news yet');
});

test('the box shows the newest articles first and no more than the configured number', function () {
    config(['scamnews.display' => 2]);
    ScamNews::factory()->create(['title' => 'Oldest story', 'published_at' => now()->subDays(3)]);
    ScamNews::factory()->create(['title' => 'Middle story', 'published_at' => now()->subDays(2)]);
    ScamNews::factory()->create(['title' => 'Newest story', 'published_at' => now()->subDay()]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('scamNews', fn ($news) => $news->pluck('title')->all() === ['Newest story', 'Middle story']);
});

test('article text is escaped and links open safely in a new tab', function () {
    ScamNews::factory()->create(['title' => '<script>alert(1)</script>', 'url' => 'https://news.test/safe']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('href="https://news.test/safe"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

test('an article without a picture still renders', function () {
    ScamNews::factory()->create(['title' => 'No picture story', 'image_url' => null]);
    ScamNews::factory()->create(['title' => 'Picture story', 'image_url' => 'https://img.test/pic.jpg']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No picture story')
        ->assertSee('https://img.test/pic.jpg', false);
});

test('guests cannot see the dashboard news', function () {
    ScamNews::factory()->create(['title' => 'Members only story']);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
