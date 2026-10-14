<?php

use App\Models\ScamNews;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
});

function scamNewsTeamMember(): User
{
    return User::factory()->create(['role' => 'user', 'is_team_member' => true]);
}

function scamNewsRegularUser(): User
{
    return User::factory()->create(['role' => 'user', 'is_team_member' => false]);
}

/** A one-article RSS feed, so these tests do not depend on helpers from another test file. */
function scamNewsAdminFeed(): string
{
    return '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Test feed</title>'
        .'<item><title>Fake bank texts hit phones</title><link>https://news.test/a1</link>'
        .'<pubDate>'.now()->subHours(3)->toRssString().'</pubDate><description>Scam texts.</description></item>'
        .'</channel></rss>';
}

function scamNewsPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Police warn of fake parcel texts',
        'url' => 'https://news.test/parcel-scam',
        'source' => 'Example Daily',
        'image_url' => '',
        'published_at' => '',
    ], $overrides);
}

test('guests are sent to the login page', function () {
    $this->get(route('scam-news.index'))->assertRedirect(route('login'));
});

test('regular users cannot open the scam news page', function () {
    $this->actingAs(scamNewsRegularUser())->get(route('scam-news.index'))->assertForbidden();
});

test('team members can open the page and see the articles', function () {
    ScamNews::factory()->create(['title' => 'A story from a feed']);
    ScamNews::factory()->manual()->hidden()->create(['title' => 'A hidden story added by hand']);

    $this->actingAs(scamNewsTeamMember())
        ->get(route('scam-news.index'))
        ->assertOk()
        ->assertSee('A story from a feed')
        ->assertSee('A hidden story added by hand');
});

test('administrators who are not on the team can open the page too', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_team_member' => false]);

    $this->actingAs($admin)->get(route('scam-news.index'))->assertOk();
});

test('the sidebar link only shows for people who can manage scam news', function () {
    $this->actingAs(scamNewsTeamMember())->get(route('dashboard'))->assertOk()->assertSee(route('scam-news.index'), false);
    $this->actingAs(scamNewsRegularUser())->get(route('dashboard'))->assertOk()->assertDontSee(route('scam-news.index'), false);
});

test('the page says which articles the dashboard is really showing', function () {
    config(['scamnews.display' => 1]);
    ScamNews::factory()->create(['title' => 'Newest story', 'published_at' => now()->subDay()]);
    ScamNews::factory()->create(['title' => 'Older story', 'published_at' => now()->subDays(5)]);

    $this->actingAs(scamNewsTeamMember())
        ->get(route('scam-news.index'))
        ->assertOk()
        ->assertSee('ON DASHBOARD')
        ->assertSee('OLDER, NOT SHOWN');
});

test('a team member can add an article by hand', function () {
    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->post(route('scam-news.store'), scamNewsPayload())
        ->assertRedirect(route('scam-news.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('scam_news', [
        'title' => 'Police warn of fake parcel texts',
        'url' => 'https://news.test/parcel-scam',
        'url_hash' => ScamNews::hashFor('https://news.test/parcel-scam'),
        'source' => 'Example Daily',
        'is_manual' => true,
        'is_visible' => true,
    ]);
});

test('a link that is not a web address is rejected', function () {
    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->post(route('scam-news.store'), scamNewsPayload(['url' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('url');

    $this->assertDatabaseCount('scam_news', 0);
});

test('a picture link that is not a web address is rejected', function () {
    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->post(route('scam-news.store'), scamNewsPayload(['image_url' => 'data:text/html;base64,AAAA']))
        ->assertSessionHasErrors('image_url');

    $this->assertDatabaseCount('scam_news', 0);
});

test('a date in the future is rejected', function () {
    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->post(route('scam-news.store'), scamNewsPayload(['published_at' => now()->addDays(3)->toDateString()]))
        ->assertSessionHasErrors('published_at');

    $this->assertDatabaseCount('scam_news', 0);
});

test('the same link cannot be added twice', function () {
    ScamNews::factory()->create(['url' => 'https://news.test/parcel-scam', 'url_hash' => ScamNews::hashFor('https://news.test/parcel-scam')]);

    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->post(route('scam-news.store'), scamNewsPayload())
        ->assertSessionHasErrors('url');

    $this->assertDatabaseCount('scam_news', 1);
});

test('a team member can hide an article and show it again', function () {
    $article = ScamNews::factory()->create();
    $member = scamNewsTeamMember();

    $this->actingAs($member)->from(route('scam-news.index'))->patch(route('scam-news.toggle', $article))
        ->assertRedirect(route('scam-news.index'));
    expect($article->fresh()->is_visible)->toBeFalse();

    $this->actingAs($member)->from(route('scam-news.index'))->patch(route('scam-news.toggle', $article))
        ->assertRedirect(route('scam-news.index'));
    expect($article->fresh()->is_visible)->toBeTrue();
});

test('a team member can delete an article', function () {
    $article = ScamNews::factory()->create();

    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->delete(route('scam-news.destroy', $article))
        ->assertRedirect(route('scam-news.index'));

    $this->assertModelMissing($article);
});

test('regular users cannot add, hide, delete or fetch', function () {
    $article = ScamNews::factory()->create();
    $user = scamNewsRegularUser();

    $this->actingAs($user)->post(route('scam-news.store'), scamNewsPayload())->assertForbidden();
    $this->actingAs($user)->patch(route('scam-news.toggle', $article))->assertForbidden();
    $this->actingAs($user)->delete(route('scam-news.destroy', $article))->assertForbidden();
    $this->actingAs($user)->post(route('scam-news.fetch'))->assertForbidden();

    expect($article->fresh()->is_visible)->toBeTrue();
    $this->assertDatabaseCount('scam_news', 1);
});

test('fetch now runs the feeds and reports the result', function () {
    config(['scamnews.feeds' => [['name' => 'Test feed', 'url' => 'https://feeds.test/rss', 'source' => 'Test Source', 'filter' => false]]]);
    Http::fake(['feeds.test/*' => Http::response(scamNewsAdminFeed())]);
    $member = scamNewsTeamMember();

    $this->actingAs($member)
        ->from(route('scam-news.index'))
        ->post(route('scam-news.fetch'))
        ->assertRedirect(route('scam-news.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseCount('scam_news', 1);

    $this->actingAs($member)->get(route('scam-news.index'))->assertOk()->assertSee('Test feed');
});

test('fetch now says so when every feed fails', function () {
    config(['scamnews.feeds' => [['name' => 'Test feed', 'url' => 'https://feeds.test/rss', 'filter' => false]]]);
    Http::fake(['feeds.test/*' => Http::response('', 500)]);

    $this->actingAs(scamNewsTeamMember())
        ->from(route('scam-news.index'))
        ->post(route('scam-news.fetch'))
        ->assertRedirect(route('scam-news.index'))
        ->assertSessionHas('error');
});

test('search and the filter chips narrow the list', function () {
    ScamNews::factory()->create(['title' => 'Parcel scam in the news']);
    ScamNews::factory()->hidden()->create(['title' => 'Bank phishing wave']);
    ScamNews::factory()->manual()->create(['title' => 'Hand picked warning']);
    $member = scamNewsTeamMember();

    $this->actingAs($member)->get(route('scam-news.index', ['search' => 'parcel']))
        ->assertViewHas('articles', fn ($articles) => $articles->total() === 1);

    $this->actingAs($member)->get(route('scam-news.index', ['filter' => 'hidden']))
        ->assertViewHas('articles', fn ($articles) => $articles->total() === 1);

    $this->actingAs($member)->get(route('scam-news.index', ['filter' => 'manual']))
        ->assertViewHas('articles', fn ($articles) => $articles->total() === 1);

    $this->actingAs($member)->get(route('scam-news.index', ['filter' => 'visible']))
        ->assertViewHas('articles', fn ($articles) => $articles->total() === 2);
});
