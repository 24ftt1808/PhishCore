<?php

namespace App\Console\Commands;

use App\Services\ScamNewsFetcher;
use Illuminate\Console\Command;

class FetchScamNews extends Command
{
    protected $signature = 'news:fetch';

    protected $description = 'Fetch the latest scam news from the RSS feeds for the dashboard';

    public function handle(ScamNewsFetcher $fetcher): int
    {
        $summary = $fetcher->run();

        foreach ($summary['feeds'] as $feed) {
            if ($feed['ok']) {
                $this->line("{$feed['name']}: {$feed['added']} new");
            } else {
                $this->warn("{$feed['name']}: failed ({$feed['error']})");
            }
        }

        $this->info("Saved {$summary['added']} new article(s) and deleted {$summary['pruned']} old one(s).");

        // Only a run where every feed failed counts as a failure. One bad feed is normal.
        return $summary['feeds'] !== [] && collect($summary['feeds'])->every(fn (array $feed) => ! $feed['ok'])
            ? self::FAILURE
            : self::SUCCESS;
    }
}
