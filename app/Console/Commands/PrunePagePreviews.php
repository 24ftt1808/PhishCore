<?php

namespace App\Console\Commands;

use App\Services\PagePreview;
use Illuminate\Console\Command;

class PrunePagePreviews extends Command
{
    protected $signature = 'previews:prune {--days= : Delete pictures older than this many days}';

    protected $description = 'Delete old page preview pictures';

    public function handle(PagePreview $preview): int
    {
        $days = (int) ($this->option('days') ?: config('services.page_preview.keep_days'));
        $deleted = $preview->prune($days);

        $this->info("Deleted {$deleted} page preview picture(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
