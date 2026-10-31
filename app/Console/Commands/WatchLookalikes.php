<?php

namespace App\Console\Commands;

use App\Services\LookalikeWatch;
use Illuminate\Console\Command;

/** Look-alike Watch: search the public certificate logs for new websites copying a Brunei brand (runs daily). */
class WatchLookalikes extends Command
{
    protected $signature = 'lookalikes:watch {--days=7 : Only certificates issued in the last this many days}';

    protected $description = 'Find new websites whose names copy a Brunei brand, from the public certificate logs (crt.sh)';

    public function handle(LookalikeWatch $watch): int
    {
        $this->info('Searching the certificate logs. This can take a minute or two...');
        $result = $watch->run(max(1, (int) $this->option('days')));

        $this->line("Names read: {$result['names_checked']}");
        $this->line("New look-alikes: {$result['new_found']}");
        if ($result['note']) {
            $this->warn($result['note']);
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}