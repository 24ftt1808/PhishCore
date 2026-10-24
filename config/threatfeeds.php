<?php

/*
|--------------------------------------------------------------------------
| Threat feeds and popular sites
|--------------------------------------------------------------------------
|
| feeds:update (scheduled in routes/console.php) downloads the lists below into the database, so a scan can
| look an address up instantly and without spending any outside quota.
|
|  - Phishing lists: addresses other people have already reported. An exact match is a strong sign; another
|    page on the same site is a weaker one, and is ignored for very popular sites (a phishing page on
|    docs.google.com does not make Google a scam site).
|  - Popular sites (Tranco): the most visited sites in the world. A well-known site is not a fresh scam site,
|    so it is exempt from the rules that only look at how a name is spelled (brand words, look-alikes).
|
| All of these are free. PhishTank and URLhaus also ask for a free key (PHISHTANK_APP_KEY and URLHAUS_AUTH_KEY
| in .env); until a key is set, that feed is simply left out. Each feed can be switched off by deleting its entry.
| A feed that fails to download is skipped and the older copy stays in the database.
|
| Each feed:
|   name   - shown to the visitor ("Listed on OpenPhish")
|   source - short key saved with every address
|   url    - where to download it
|   format - "lines" (one address per line, lines starting with # ignored), "phishtank" or "urlhaus" (their CSV files)
|
*/

return [
    'feeds' => array_values(array_filter([
        [
            'name' => 'OpenPhish',
            'source' => 'openphish',
            'url' => 'https://openphish.com/feed.txt',
            'format' => 'lines',
        ],
        // URLhaus now needs a free key (https://auth.abuse.ch/) in the address; left out until one is set.
        env('URLHAUS_AUTH_KEY') ? [
            'name' => 'URLhaus',
            'source' => 'urlhaus',
            'url' => 'https://urlhaus-api.abuse.ch/v2/files/exports/'.env('URLHAUS_AUTH_KEY').'/recent.csv',
            'format' => 'urlhaus',
        ] : null,
        // PhishTank needs a free application key (https://phishtank.org/api_info.php); left out until one is set.
        env('PHISHTANK_APP_KEY') ? [
            'name' => 'PhishTank',
            'source' => 'phishtank',
            'url' => 'https://data.phishtank.com/data/'.env('PHISHTANK_APP_KEY').'/online-valid.csv',
            'format' => 'phishtank',
        ] : null,
    ])),

    // Popular sites. The list changes slowly, so it is only downloaded again after this many days.
    'tranco' => [
        'url' => 'https://tranco-list.eu/top-1m.csv.zip',
        'keep' => (int) env('TRANCO_KEEP', 100000),
        'refresh_days' => 7,
    ],

    // A site this high in the ranking (1 = most visited) counts as well known.
    'popular_rank' => (int) env('POPULAR_RANK', 50000),

    // The look-alike rule also compares new addresses against the names of the sites this high in the ranking.
    'lookalike_rank' => (int) env('LOOKALIKE_RANK', 3000),

    // Never read more than this from one download.
    'max_bytes' => 60_000_000,
];