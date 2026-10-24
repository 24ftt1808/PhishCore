<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Page-preview pictures are only kept for a few days (PAGE_PREVIEW_KEEP_DAYS). Needs the usual
// "* * * * * php artisan schedule:run" cron entry on the server, or run previews:prune by hand.
Schedule::command('previews:prune')->daily();

// The dashboard's "Latest Scam News" box: fetch new articles from the RSS feeds every morning, Brunei time
// (same cron entry as above).
Schedule::command('news:fetch')->dailyAt('06:00')->timezone('Asia/Brunei')->withoutOverlapping();

// People who chose to keep their scans for 30 or 90 days only: remove the older ones every night, Brunei time
// (same cron entry as above).
Schedule::command('scans:prune-old')->dailyAt('03:30')->timezone('Asia/Brunei')->withoutOverlapping();

// The phishing lists (OpenPhish, URLhaus, PhishTank) and the popular-sites list used by the scan: refresh them
// every 6 hours (same cron entry as above). The popular-sites list is only downloaded again after a week.
Schedule::command('feeds:update')->everySixHours()->withoutOverlapping();
