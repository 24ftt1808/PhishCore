<?php

use App\Models\PopularDomain;
use App\Models\ThreatUrl;
use App\Services\AnalysisEngine;
use App\Services\ThreatFeeds;
use App\Support\ThreatLookup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->feeds = app(ThreatFeeds::class);
    $this->engine = new AnalysisEngine;

    config([
        'services.google_safe_browsing.key' => null,
        'threatfeeds.feeds' => [
            ['name' => 'OpenPhish', 'source' => 'openphish', 'url' => 'https://openphish.example/feed.txt', 'format' => 'lines'],
            ['name' => 'URLhaus', 'source' => 'urlhaus', 'url' => 'https://urlhaus.example/online.txt', 'format' => 'lines'],
        ],
        'threatfeeds.tranco' => ['url' => 'https://tranco.example/top.zip', 'keep' => 100000, 'refresh_days' => 7],
    ]);
});

function trancoZip(string $csv): string
{
    $path = tempnam(sys_get_temp_dir(), 'tz');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('top-1m.csv', $csv);
    $zip->close();
    $bytes = file_get_contents($path);
    unlink($path);

    return $bytes;
}

// --- saving the lists ---

test('a list is saved, and addresses that dropped off it are removed at the next run', function () {
    $this->feeds->replaceUrls('openphish', ['http://bad.example/a', 'http://worse.example/b']);
    $this->feeds->replaceUrls('urlhaus', ['http://other.example/z']);

    expect(ThreatUrl::count())->toBe(3);

    $this->travel(2)->seconds();
    $count = $this->feeds->replaceUrls('openphish', ['http://bad.example/a', 'http://new.example/c']);

    expect($count)->toBe(2)
        ->and(ThreatUrl::where('source', 'openphish')->pluck('url')->sort()->values()->all())->toBe(['http://bad.example/a', 'http://new.example/c'])
        ->and(ThreatUrl::where('source', 'urlhaus')->count())->toBe(1);
});

test('the same address with a different spelling is saved once, with its host and registered domain', function () {
    $this->feeds->replaceUrls('openphish', ['http://Login.Evil.example/a#x', 'http://login.evil.example:80/a']);

    $row = ThreatUrl::sole();

    expect($row->host)->toBe('login.evil.example')
        ->and($row->registrable)->toBe('evil.example')
        ->and($row->url_hash)->toBe(sha1('http://login.evil.example/a'));
});

test('feeds:update downloads each list and the popular sites', function () {
    Http::fake([
        'openphish.example/*' => Http::response("http://bad.example/a\nhttp://worse.example/b\n"),
        'urlhaus.example/*' => Http::response("# comment\nhttp://other.example/z\n"),
        'tranco.example/*' => Http::response(trancoZip("1,google.com\n2,facebook.com\n3,example.org\n")),
    ]);

    $this->artisan('feeds:update')
        ->expectsOutputToContain('OpenPhish: 2 saved')
        ->expectsOutputToContain('URLhaus: 1 saved')
        ->expectsOutputToContain('Tranco popular sites: 3 saved')
        ->assertSuccessful();

    expect(ThreatUrl::count())->toBe(3)
        ->and(PopularDomain::where('domain', 'google.com')->value('position'))->toBe(1);
})->skip(! class_exists(ZipArchive::class), 'needs the zip extension');

test('the popular-sites list is only downloaded again once it is due', function () {
    Http::fake([
        'openphish.example/*' => Http::response("http://bad.example/a\n"),
        'urlhaus.example/*' => Http::response("http://other.example/z\n"),
        'tranco.example/*' => Http::response(trancoZip("1,google.com\n")),
    ]);

    $this->feeds->run();
    $this->feeds->run();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'tranco.example'));
    expect(Http::recorded(fn ($request) => str_contains($request->url(), 'tranco.example')))->toHaveCount(1);

    $this->feeds->run(force: true);

    expect(Http::recorded(fn ($request) => str_contains($request->url(), 'tranco.example')))->toHaveCount(2);
})->skip(! class_exists(ZipArchive::class), 'needs the zip extension');

test('only the top sites asked for are kept', function () {
    config(['threatfeeds.tranco.keep' => 2]);
    Http::fake([
        'openphish.example/*' => Http::response("http://bad.example/a\n"),
        'urlhaus.example/*' => Http::response("http://other.example/z\n"),
        'tranco.example/*' => Http::response(trancoZip("1,google.com\n2,facebook.com\n3,example.org\n")),
    ]);

    $this->feeds->run(force: true);

    expect(PopularDomain::count())->toBe(2);
})->skip(! class_exists(ZipArchive::class), 'needs the zip extension');

// --- a broken download never wipes what is saved ---

test('a failed download keeps the older copy and the other lists still update', function () {
    $this->feeds->replaceUrls('openphish', ['http://old.example/a']);
    $this->travel(2)->seconds();

    Http::fake([
        'openphish.example/*' => Http::response('server error', 500),
        'urlhaus.example/*' => Http::response("http://other.example/z\n"),
        'tranco.example/*' => Http::response('broken', 500),
    ]);

    $summary = $this->feeds->run(force: true);

    expect(collect($summary['feeds'])->pluck('ok')->all())->toBe([false, true, false])
        ->and(ThreatUrl::where('source', 'openphish')->pluck('url')->all())->toBe(['http://old.example/a'])
        ->and(ThreatUrl::where('source', 'urlhaus')->count())->toBe(1);
});

test('an empty download is treated as a failure, not as "nothing is reported any more"', function () {
    $this->feeds->replaceUrls('openphish', ['http://old.example/a']);
    $this->travel(2)->seconds();
    Http::fake(['*' => Http::response("# nothing here\n")]);

    $result = $this->feeds->updateUrlFeed(config('threatfeeds.feeds.0'));

    expect($result['ok'])->toBeFalse()
        ->and(ThreatUrl::count())->toBe(1);
});

test('feeds:update fails only when every list failed', function () {
    Http::fake(['*' => Http::response('nope', 500)]);

    $this->artisan('feeds:update --force')->assertFailed();
});

// --- looking an address up ---

test('lookups find an exact address, another page on the same site, and a popular site', function () {
    $this->feeds->replaceUrls('openphish', ['http://bad.example/login.php']);
    $this->feeds->replacePopular(['google.com' => 1, 'example.org' => 70000]);

    expect(ThreatLookup::exactMatch('HTTP://bad.example:80/login.php#x'))->toBe(['source' => 'openphish', 'name' => 'OpenPhish'])
        ->and(ThreatLookup::exactMatch('http://bad.example/other.php'))->toBeNull()
        ->and(ThreatLookup::domainMatch('www.bad.example'))->toBe(['source' => 'openphish', 'name' => 'OpenPhish'])
        ->and(ThreatLookup::domainMatch('good.example'))->toBeNull()
        ->and(ThreatLookup::position('mail.google.com'))->toBe(1)
        ->and(ThreatLookup::isPopular('mail.google.com'))->toBeTrue()
        ->and(ThreatLookup::isPopular('example.org'))->toBeFalse()
        ->and(ThreatLookup::isPopular('unknown.example'))->toBeFalse();
});

test('a missing table or empty lists never break a lookup', function () {
    expect(ThreatLookup::exactMatch('http://bad.example/'))->toBeNull()
        ->and(ThreatLookup::isPopular('google.com'))->toBeFalse();

    Schema::drop('threat_urls');
    Schema::drop('popular_domains');

    expect(ThreatLookup::exactMatch('http://bad.example/'))->toBeNull()
        ->and(ThreatLookup::domainMatch('bad.example'))->toBeNull()
        ->and(ThreatLookup::isPopular('google.com'))->toBeFalse();
});

// --- what the scan does with them ---

test('an address that is exactly on a list is flagged in the blacklist check', function () {
    $this->feeds->replaceUrls('openphish', ['https://bad.example/login.php']);

    $result = $this->engine->checkBlacklist('https://bad.example/login.php');

    expect($result['flagged'])->toBeTrue()
        ->and($result['points'])->toBe(50)
        ->and($result['reasons'][0])->toContain('OpenPhish')
        ->and($result['unavailable'] ?? false)->toBeFalse();
});

test('another page on a listed site is a weaker warning, and is ignored for a popular site', function () {
    $this->feeds->replaceUrls('urlhaus', ['https://shady.example/payload.exe', 'https://docs.bigplatform.example/phish']);
    $this->feeds->replacePopular(['bigplatform.example' => 120]);

    $shady = $this->engine->checkBlacklist('https://shady.example/about');
    $big = $this->engine->checkBlacklist('https://docs.bigplatform.example/another-page');
    $bigExact = $this->engine->checkBlacklist('https://docs.bigplatform.example/phish');

    expect($shady['points'])->toBe(25)
        ->and($shady['reasons'][0])->toContain('Another page on this website')
        ->and($big['flagged'])->toBeFalse()
        ->and($bigExact['points'])->toBe(50);
});

test('with nothing on the lists the blacklist check answers exactly as before', function () {
    $result = $this->engine->checkBlacklist('https://example.com');

    expect($result['unavailable'] ?? false)->toBeTrue()
        ->and($result['points'])->toBe(0);
});

test('a well-known site is not treated as a copy of a brand just because of its spelling', function () {
    $url = 'https://netflix-fans.com';

    expect($this->engine->checkUrlSyntax($url)['points'])->toBeGreaterThanOrEqual(25);

    PopularDomain::create(['domain' => 'netflix-fans.com', 'position' => 900]);

    expect($this->engine->checkUrlSyntax($url)['points'])->toBe(0);
});

test('a site far down the ranking gets no such pass', function () {
    PopularDomain::create(['domain' => 'netflix-fans.com', 'position' => 900_000]);

    expect($this->engine->checkUrlSyntax('https://netflix-fans.com')['points'])->toBeGreaterThanOrEqual(25);
});

test('the popular-site pass does not cover a free-hosting page, whose owner is the subdomain', function () {
    PopularDomain::create(['domain' => 'github.io', 'position' => 300]);

    expect($this->engine->checkUrlSyntax('https://netflix-login.github.io')['points'])->toBeGreaterThanOrEqual(25);
});
