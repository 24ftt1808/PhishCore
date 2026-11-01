<?php

use App\Models\ThreatUrl;
use App\Services\ThreatFeeds;
use Illuminate\Support\Facades\Cache;

/**
 * A big list (URLhaus can hold 100,000 addresses) is saved in batches, so it never has to sit in memory as database
 * rows all at once. Every address is still saved once, and the addresses that left the list are removed.
 */
test('a list larger than one batch is saved completely, with repeats counted once', function () {
    $urls = array_map(fn (int $i) => "http://bad-site-{$i}.example.com/payload-{$i}.exe", range(1, 1234));
    $urls[] = 'http://bad-site-1.example.com/payload-1.exe'; // a repeat

    $saved = (new ThreatFeeds)->replaceUrls('urlhaus', $urls);

    expect($saved)->toBe(1234)
        ->and(ThreatUrl::where('source', 'urlhaus')->count())->toBe(1234);
});

test('addresses that left the list are removed on the next update', function () {
    $feeds = new ThreatFeeds;
    $feeds->replaceUrls('urlhaus', ['http://old.example.com/a', 'http://kept.example.com/b']);
    $this->travel(2)->seconds();
    $feeds->replaceUrls('urlhaus', ['http://kept.example.com/b']);

    expect(ThreatUrl::where('source', 'urlhaus')->pluck('url')->all())->toBe(['http://kept.example.com/b']);
});

test('URLhaus lines are read one at a time, with Windows line endings too', function () {
    $csv = "# id,dateadded,url,url_status\r\n\"1\",\"2026-10-31\",\"http://a.example.com/x\",\"online\"\r\n\"2\",\"2026-10-31\",\"http://b.example.com/y\",\"offline\"\r\n\"3\",\"2026-10-31\",\"http://c.example.com/z\",\"online\"";

    expect(ThreatFeeds::parseUrlhausCsv($csv))->toBe(['http://a.example.com/x', 'http://c.example.com/z']);
});

test('the popular-sites date is saved as text, and an old unreadable value does not crash the scheduled update', function () {
    $key = (new ReflectionClassConstant(ThreatFeeds::class, 'TRANCO_KEY'))->getValue();
    $feeds = new ThreatFeeds;

    // What the cache gives back for a date object it is not allowed to rebuild.
    Cache::forever($key, unserialize('O:22:"__PHP_Incomplete_Class":0:{}'));
    expect($feeds->trancoIsDue())->toBeTrue();

    Cache::forever($key, now()->subDay()->toIso8601String());
    expect($feeds->trancoIsDue())->toBeFalse();

    Cache::forever($key, 'not a date');
    expect($feeds->trancoIsDue())->toBeTrue();
});