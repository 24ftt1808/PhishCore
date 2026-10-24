<?php

use App\Services\ThreatFeeds;

test('a list with one address per line is read, ignoring comments, blanks, junk and repeats', function () {
    $text = "# URLhaus online list\r\n\r\nhttp://bad.example/login.php\nhttps://evil.example/a?b=1\nnot a url\nftp://files.example/x\nhttp://bad.example/login.php\n   https://spaced.example/x   \n";

    expect(ThreatFeeds::parseUrlLines($text))->toBe([
        'http://bad.example/login.php',
        'https://evil.example/a?b=1',
        'https://spaced.example/x',
    ]);
});

test('URLhaus CSV: the url column is found from the comment header, and addresses marked offline are skipped', function () {
    $csv = "################\n# URLhaus URL dump\n################\n"
        .'# id,dateadded,url,url_status,last_online,threat,tags,urlhaus_link,reporter'."\n"
        .'"1","2026-10-01","http://a.example/x.exe","online","2026-10-01","malware_download","exe","https://urlhaus.abuse.ch/url/1/","bot"'."\n"
        .'"2","2026-10-01","http://gone.example/y","offline","2026-10-01","malware_download","","https://urlhaus.abuse.ch/url/2/","bot"'."\n"
        .'"3","2026-10-01","not a url","online","2026-10-01","malware_download","","https://urlhaus.abuse.ch/url/3/","bot"'."\n";

    expect(ThreatFeeds::parseUrlhausCsv($csv))->toBe(['http://a.example/x.exe']);
    expect(ThreatFeeds::parseUrlhausCsv("id,dateadded,url,url_status\n1,2026-10-01,http://b.example/z,online\n"))->toBe(['http://b.example/z']);
    expect(ThreatFeeds::parseUrlhausCsv('"1","2026-10-01","http://c.example/q","online"'."\n"))->toBe(['http://c.example/q']);
});

test('PhishTank CSV: the url column is used, quoted commas are kept, and addresses marked offline are skipped', function () {
    $csv = "phish_id,url,phish_detail_url,submission_time,verified,verification_time,online,target\n"
        .'1,"http://a.example/x,y",http://phishtank/1,2026-10-01,yes,2026-10-01,yes,Other'."\n"
        .'2,http://gone.example/,http://phishtank/2,2026-10-01,yes,2026-10-01,no,Other'."\n"
        .'3,not-a-url,http://phishtank/3,2026-10-01,yes,2026-10-01,yes,Other'."\n";

    expect(ThreatFeeds::parsePhishTank($csv))->toBe(['http://a.example/x,y']);
    expect(ThreatFeeds::parsePhishTank("something,else\n1,2\n"))->toBe([]);
});

test('the Tranco list keeps only the top sites, with their position', function () {
    $csv = "1,google.com\n2,Facebook.com\n3,example.org\n4,bad line here\nx,notanumber.com\n5,sub.example.co.uk\n";

    expect(ThreatFeeds::parseTranco($csv, 3))->toBe(['google.com' => 1, 'facebook.com' => 2, 'example.org' => 3])
        ->and(ThreatFeeds::parseTranco($csv, 10))->toHaveKey('sub.example.co.uk', 5)
        ->and(ThreatFeeds::parseTranco('', 10))->toBe([]);
});

test('addresses that differ only in unimportant ways are the same address', function () {
    $same = [
        'http://Bad.Example/login',
        'http://bad.example:80/login',
        'HTTP://BAD.EXAMPLE/login#top',
    ];

    foreach ($same as $url) {
        expect(ThreatFeeds::normalizeUrl($url))->toBe('http://bad.example/login');
    }

    expect(ThreatFeeds::normalizeUrl('https://bad.example'))->toBe('https://bad.example/')
        ->and(ThreatFeeds::normalizeUrl('https://bad.example:8443/a?b=1'))->toBe('https://bad.example:8443/a?b=1')
        ->and(ThreatFeeds::normalizeUrl('https://bad.example/a'))->not->toBe(ThreatFeeds::normalizeUrl('https://bad.example/b'))
        ->and(ThreatFeeds::normalizeUrl('http://bad.example/a'))->not->toBe(ThreatFeeds::normalizeUrl('https://bad.example/a'));
});

test('the Tranco list is read straight from the zip and stops at the last site that is kept', function () {
    $path = tempnam(sys_get_temp_dir(), 'zt');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('top-1m.csv', "1,google.com\n2,facebook.com\n3,example.org\n4,fourth.example\n");
    $zip->close();
    $bytes = file_get_contents($path);
    unlink($path);

    expect(ThreatFeeds::parseTrancoZip($bytes, 3))->toBe(['google.com' => 1, 'facebook.com' => 2, 'example.org' => 3])
        ->and(count(ThreatFeeds::parseTrancoZip($bytes, 100)))->toBe(4)
        ->and(fn () => ThreatFeeds::parseTrancoZip('this is not a zip', 3))->toThrow(RuntimeException::class);
})->skip(! class_exists(ZipArchive::class), 'needs the zip extension');
