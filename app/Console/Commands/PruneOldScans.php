<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOldScans extends Command
{
    protected $signature = 'scans:prune-old';

    protected $description = 'Delete the scans (and their pictures) of people who chose to keep their scans for a limited time';

    public function handle(): int
    {
        $deleted = 0;

        foreach (User::RETENTION_CHOICES as $days) {
            Report::query()
                ->whereIn('user_id', User::where('scan_retention_days', $days)->select('id'))
                ->where('created_at', '<', now()->subDays($days))
                ->chunkById(200, function ($reports) use (&$deleted) {
                    foreach ($reports as $report) {
                        $this->deletePicture($report->screenshot_path);
                        $this->deletePicture($report->preview_path);
                        $report->delete(); // its analyses go with it
                        $deleted++;
                    }
                });
        }

        $this->info("Deleted {$deleted} old scan(s).");

        return self::SUCCESS;
    }

    /** Pictures are saved on the public disk and remembered as ".../storage/<path>". */
    private function deletePicture(?string $address): void
    {
        if ($address !== null && preg_match('#/storage/([A-Za-z0-9_\-./]+)$#', $address, $match) && ! str_contains($match[1], '..')) {
            Storage::disk('public')->delete($match[1]);
        }
    }
}
