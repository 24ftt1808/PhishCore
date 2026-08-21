<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Analysis;
use App\Models\CtiLookup;
use App\Models\Investigation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Report::factory(15)->create()->each(function (Report $report) {
            Analysis::factory()->create(['report_id' => $report->id]);
            CtiLookup::factory()->create(['report_id' => $report->id]);
            Investigation::factory()->create(['report_id' => $report->id]);
        });
    }
}