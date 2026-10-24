<?php

namespace App\Console\Commands;

use App\Services\ThreatFeeds;
use Illuminate\Console\Command;

class UpdateThreatFeeds extends Command
{
    protected $signature = 'feeds:update {--force : Also download the popular-sites list even if it is not due yet}';

    protected $description = 'Download the phishing lists and the popular-sites list used by the scan';

    public function handle(ThreatFeeds $feeds): int
    {
        $summary = $feeds->run((bool) $this->option('force'));

        foreach ($summary['feeds'] as $feed) {
            if ($feed['ok']) {
                $this->line("{$feed['name']}: {$feed['count']} saved");
            } else {
                $this->warn("{$feed['name']}: failed ({$feed['error']})");
            }
        }

        // One bad list is normal. Only a run where every list failed counts as a failure.
        return $summary['feeds'] !== [] && collect($summary['feeds'])->every(fn (array $feed) => ! $feed['ok'])
            ? self::FAILURE
            : self::SUCCESS;
    }
}
