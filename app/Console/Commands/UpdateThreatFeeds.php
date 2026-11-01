<?php

namespace App\Console\Commands;

use App\Services\ThreatFeeds;
use Illuminate\Console\Command;

class UpdateThreatFeeds extends Command
{
    protected $signature = 'feeds:update {--force : Also download the popular-sites list even if it is not due yet}';

    protected $description = 'Download the phishing lists and the popular-sites list used by the scan';

    /** "128M" as bytes; -1 (no limit) counts as unlimited. */
    private static function bytes(string $limit): int
    {
        if ($limit === '-1' || $limit === '') {
            return PHP_INT_MAX;
        }
        $number = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    public function handle(ThreatFeeds $feeds): int
    {
        // The lists are large (URLhaus alone can hold 100,000 addresses). PHP's usual 128 MB command-line limit is
        // raised for this command only, so a growing list cannot stop the update.
        if (self::bytes((string) ini_get('memory_limit')) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

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