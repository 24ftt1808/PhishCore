<?php

use App\Models\ScamNews;
use App\Services\ScamNewsFetcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/** One <item> of an RSS feed. 'extra' is raw XML added inside the item (media tags, <source> and so on). */
function scamRssItem(array $item = []): string
{
    $item += [
        'title' => 'Fake bank texts hit phones',
        'link' => 'https://news.test/a1',
        'date' => now()->subHours(3),
        'description' => 'Police warn about scam texts.',
        'extra' => '',
    ];

    /** @var Carbon $date */
    $date = $item['date'];

    return '<item>'
        .'<title>'.htmlspecialchars($item['title'], ENT_XML1 | ENT_QUOTES).'</title>'
        .'<link>'.htmlspecialchars($item['link'], ENT_XML1 | ENT_QUOTES).'</link>'
        .'<pubDate>'.$date->toRssString().'</pubDate>'
        .'<description>'.htmlspecialchars($item['description'], ENT_XML1 | ENT_QUOTES).'</description>'
        .$item['extra']
        .'</item>';
}

function scamRssFeed(string ...$items): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"><channel><title>Test feed</title>'
        .implode('', $items)
        .'</channel></rss>';
}

/** Point news:fetch at one fake feed at https://feeds.test/rss. */
function scamFeedConfig(array $feed = []): void
{
    config(['scamnews.feeds' => [array_merge([
        'name' => 'Test feed',
        'url' => 'https://feeds.test/rss',
        'source' => 'Test Source',
        'filter' => false,
    ], $feed)]]);
}

test('news:fetch saves new articles and never saves the same article twice', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem(['link' => 'https://news.test/a1']),
        scamRssItem(['title' => 'Second story', 'link' => 'https://news.test/a2']),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();
    expect(ScamNews::count())->toBe(2);

    $this->artisan('news:fetch')->assertSuccessful();
    expect(ScamNews::count())->toBe(2);

    $article = ScamNews::where('url', 'https://news.test/a1')->first();
    expect($article->source)->toBe('Test Source')
        ->and($article->is_visible)->toBeTrue()
        ->and($article->is_manual)->toBeFalse();
});

test('a general security feed only keeps articles about scams', function () {
    scamFeedConfig(['filter' => true]);
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem(['title' => 'Phishing kit targets bank customers', 'link' => 'https://news.test/p1']),
        scamRssItem(['title' => 'Chip maker ships new GPU', 'link' => 'https://news.test/g1', 'description' => 'Faster graphics.']),
        scamRssItem(['title' => 'Weekly roundup', 'link' => 'https://news.test/s1', 'description' => 'A romance scam ring was arrested.']),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::pluck('url')->all())->toEqualCanonicalizing(['https://news.test/p1', 'https://news.test/s1']);
});

test('a feed that is already a scam search keeps every article', function () {
    scamFeedConfig(['filter' => false]);
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem(['title' => 'Chip maker ships new GPU', 'link' => 'https://news.test/g1', 'description' => 'Faster graphics.']),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::count())->toBe(1);
});

test('it picks up a picture from media:thumbnail, an image enclosure or an img tag', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem(['link' => 'https://news.test/m1', 'extra' => '<media:thumbnail url="https://img.test/thumb.jpg" />']),
        scamRssItem(['link' => 'https://news.test/m2', 'extra' => '<enclosure url="https://img.test/enc.png" length="1" type="image/png" />']),
        scamRssItem(['link' => 'https://news.test/m3', 'description' => '<p><img src="https://img.test/inline.webp" alt=""> text</p>']),
        scamRssItem(['link' => 'https://news.test/m4', 'extra' => '<enclosure url="https://cdn.test/podcast.mp3" length="1" type="audio/mpeg" />']),
        scamRssItem(['link' => 'https://news.test/m5']),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();

    $image = fn (string $id) => ScamNews::where('url', "https://news.test/{$id}")->value('image_url');

    expect($image('m1'))->toBe('https://img.test/thumb.jpg')
        ->and($image('m2'))->toBe('https://img.test/enc.png')
        ->and($image('m3'))->toBe('https://img.test/inline.webp')
        ->and($image('m4'))->toBeNull()
        ->and($image('m5'))->toBeNull();
});

test('Google News style items take the publisher from <source> and drop it from the headline', function () {
    scamFeedConfig(['source' => null]);
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem([
            'title' => 'Police warn of fake parcel texts - Example Daily',
            'link' => 'https://news.test/g-1',
            'extra' => '<source url="https://example.test">Example Daily</source>',
        ]),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();

    $article = ScamNews::first();
    expect($article->title)->toBe('Police warn of fake parcel texts')
        ->and($article->source)->toBe('Example Daily');
});

test('Atom feeds work too', function () {
    scamFeedConfig();
    $atom = '<?xml version="1.0" encoding="utf-8"?>'
        .'<feed xmlns="http://www.w3.org/2005/Atom"><title>Test</title>'
        .'<entry><title>Atom scam story</title><link rel="alternate" href="https://news.test/atom1"/>'
        .'<published>'.now()->subHour()->toAtomString().'</published><summary>About a scam.</summary></entry>'
        .'</feed>';
    Http::fake(['feeds.test/*' => Http::response($atom)]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::pluck('title')->all())->toBe(['Atom scam story']);
});

test('old articles and links that are not web addresses are skipped', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem(['link' => 'https://news.test/old', 'date' => now()->subDays(45)]),
        scamRssItem(['link' => 'javascript:alert(1)']),
        scamRssItem(['link' => 'ftp://files.test/story']),
        scamRssItem(['link' => 'https://news.test/fresh']),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::pluck('url')->all())->toBe(['https://news.test/fresh']);
});

test('headlines are saved as plain text', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(
        scamRssItem(['title' => '<b>Bold</b> &amp; <script>alert(1)</script>warning', 'link' => 'https://news.test/html']),
    ))]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::first()->title)->not->toContain('<')->and(ScamNews::first()->title)->toContain('Bold');
});

test('a broken feed does not stop the other feeds', function () {
    config(['scamnews.feeds' => [
        ['name' => 'Down', 'url' => 'https://down.test/rss', 'filter' => false],
        ['name' => 'Garbage', 'url' => 'https://garbage.test/rss', 'filter' => false],
        ['name' => 'Good', 'url' => 'https://good.test/rss', 'source' => 'Good Source', 'filter' => false],
    ]]);
    Http::fake([
        'down.test/*' => Http::response('', 500),
        'garbage.test/*' => Http::response('this is not xml at all', 200),
        'good.test/*' => Http::response(scamRssFeed(scamRssItem())),
    ]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::count())->toBe(1);

    $feeds = collect(ScamNewsFetcher::lastRun()['feeds']);
    expect($feeds->pluck('ok')->all())->toBe([false, false, true]);
});

test('news:fetch reports a failure when every feed is down', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response('', 503)]);

    $this->artisan('news:fetch')->assertFailed();

    expect(ScamNews::count())->toBe(0);
});

test('refetching never brings back a hidden article', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(scamRssItem()))]);

    $this->artisan('news:fetch')->assertSuccessful();
    ScamNews::first()->update(['is_visible' => false]);
    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::count())->toBe(1)
        ->and(ScamNews::first()->is_visible)->toBeFalse();
});

test('news:fetch deletes very old automatic articles but keeps ones added by hand', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed())]);

    ScamNews::factory()->create(['published_at' => now()->subDays(120)]);
    $manual = ScamNews::factory()->manual()->create(['published_at' => now()->subDays(120)]);
    $recent = ScamNews::factory()->create(['published_at' => now()->subDays(5)]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::pluck('id')->all())->toEqualCanonicalizing([$manual->id, $recent->id]);
});

test('the same link with a #fragment counts as the same article', function () {
    expect(ScamNews::hashFor('https://news.test/story#comments'))->toBe(ScamNews::hashFor(' https://news.test/story '));
});

test('a feed with page_image uses the preview picture of the article page', function () {
    scamFeedConfig(['url' => 'https://www.feeds.test/rss', 'page_image' => true]);
    Http::fake([
        'www.feeds.test/rss' => Http::response(scamRssFeed(scamRssItem(['link' => 'https://www.feeds.test/story-1']))),
        'www.feeds.test/story-1' => Http::response('<html><head><meta property="og:title" content="x"><meta content="https://img.test/cover.jpg?a=1&amp;b=2" property="og:image"></head></html>'),
    ]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::first()->image_url)->toBe('https://img.test/cover.jpg?a=1&b=2');
});

test('a relative preview picture is completed with the site address', function () {
    scamFeedConfig(['url' => 'https://feeds.test/rss', 'page_image' => true]);
    Http::fake([
        'feeds.test/rss' => Http::response(scamRssFeed(scamRssItem(['link' => 'https://feeds.test/story-1']))),
        'feeds.test/story-1' => Http::response('<meta name="twitter:image" content="/img/cover.png">'),
    ]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::first()->image_url)->toBe('https://feeds.test/img/cover.png');
});

test('article pages are not opened unless the feed asks for it', function () {
    scamFeedConfig();
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(scamRssItem()))]);

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertSentCount(1);
    expect(ScamNews::first()->image_url)->toBeNull();
});

test('a picture from the feed is kept and the article page is not opened', function () {
    scamFeedConfig(['page_image' => true]);
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(scamRssItem([
        'extra' => '<media:thumbnail url="https://img.test/feed.jpg"/>',
    ])))]);

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertSentCount(1);
    expect(ScamNews::first()->image_url)->toBe('https://img.test/feed.jpg');
});

test('articles on other sites are never opened to look for a picture', function () {
    scamFeedConfig(['page_image' => true]);
    Http::fake(['feeds.test/*' => Http::response(scamRssFeed(scamRssItem(['link' => 'https://elsewhere.test/story-1'])))]);

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertSentCount(1);
    expect(ScamNews::first()->image_url)->toBeNull();
});

test('an article page that fails or has no picture still saves the article', function () {
    scamFeedConfig(['page_image' => true]);
    Http::fake([
        'feeds.test/rss' => Http::response(scamRssFeed(
            scamRssItem(['link' => 'https://feeds.test/down']),
            scamRssItem(['title' => 'Other story', 'link' => 'https://feeds.test/bare']),
        )),
        'feeds.test/down' => Http::response('Server error', 500),
        'feeds.test/bare' => Http::response('<html><head><title>No picture</title></head></html>'),
    ]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::count())->toBe(2)
        ->and(ScamNews::whereNotNull('image_url')->count())->toBe(0);
});

test('a preview picture with an unsafe address is ignored', function () {
    scamFeedConfig(['page_image' => true]);
    Http::fake([
        'feeds.test/rss' => Http::response(scamRssFeed(scamRssItem(['link' => 'https://feeds.test/story-1']))),
        'feeds.test/story-1' => Http::response('<meta property="og:image" content="javascript:alert(1)">'),
    ]);

    $this->artisan('news:fetch')->assertSuccessful();

    expect(ScamNews::first()->image_url)->toBeNull();
});

test('an article that is already saved is not opened again', function () {
    scamFeedConfig(['page_image' => true]);
    ScamNews::factory()->create(['url' => 'https://feeds.test/story-1', 'image_url' => null, 'is_visible' => false]);
    Http::fake(['feeds.test/rss' => Http::response(scamRssFeed(scamRssItem(['link' => 'https://feeds.test/story-1'])))]);

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertSentCount(1);
    expect(ScamNews::count())->toBe(1);
});
