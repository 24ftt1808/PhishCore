<?php

/*
|--------------------------------------------------------------------------
| Scam news (dashboard "Latest Scam News" box)
|--------------------------------------------------------------------------
|
| news:fetch (scheduled daily in routes/console.php) reads the RSS feeds below and saves new articles
| in the scam_news table. RSS is free and needs no API key. To add, remove or swap a source, just
| edit the feeds list; nothing else in the code needs to change.
|
| Each feed:
|   name    - how it is shown on the Scam News admin page
|   url     - the RSS or Atom address
|   source  - the name shown on the article (leave out to use the publisher the feed gives, or "name")
|   filter  - true: keep only articles that mention one of the keywords below. Use it for general
|             security feeds; leave it off for a feed that is already a scam search.
|   page_image - true: when the feed gives no picture, open the article page and use its preview
|             picture. Only pages on the feed's own site are opened. Leave it off for Google News,
|             whose links are redirects with no picture behind them.
|
*/

return [
    'feeds' => [
        // Google News search for Brunei scam stories. Google matches loosely, so this feed is
        // filtered by the keywords below as well.
        [
            'name' => 'Google News: Brunei scams',
            'url' => 'https://news.google.com/rss/search?q=Brunei+(scam+OR+scams+OR+phishing+OR+fraud)+when:30d&hl=en-US&gl=US&ceid=US:en',
            'filter' => true,
        ],
        // Global security news: only the articles that are about scams, phishing and fraud.
        [
            'name' => 'The Hacker News',
            'url' => 'https://feeds.feedburner.com/TheHackersNews',
            'source' => 'The Hacker News',
            'filter' => true,
        ],
        [
            'name' => 'BleepingComputer',
            'url' => 'https://www.bleepingcomputer.com/feed/',
            'source' => 'BleepingComputer',
            'filter' => true,
            'page_image' => true,
        ],
    ],

    // For feeds with "filter" => true: an article is kept when its title or summary has one of these.
    'keywords' => [
        'phishing', 'smishing', 'vishing', 'quishing', 'scam', 'fraud', 'spoof',
        'social engineering', 'deepfake', 'fake website', 'fake site', 'lookalike', 'business email compromise',
        'fake call', 'fake parcel', 'fake job', 'bogus', 'swindl', 'ponzi', 'money mule', 'identity theft',
    ],

    // How many articles the dashboard box shows (newest first).
    'display' => 8,

    // At most this many articles are taken from one feed per run (newest first).
    'per_feed' => 15,

    // Articles older than this many days are not saved.
    'max_age_days' => 30,

    // Automatically saved articles older than this are deleted by news:fetch. Articles added by hand stay.
    // Keep this larger than max_age_days, so a deleted article is not saved again from the feed.
    'prune_days' => 90,

    // How long to wait for one feed before giving up on it, in seconds.
    'timeout' => 10,
];
