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
