<?php

use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Support\Facades\Schema;

/**
 * The public Reports page stays quick: its lookups are indexed, and a visitor cannot ask for a huge page.
 */
beforeEach(function () {
    $this->withoutVite();
});

test('the lookups the public Reports page makes are indexed', function () {
    expect(Schema::getIndexListing('analyses'))->toContain('analyses_report_verdict_index', 'analyses_verdict_report_index')
        ->and(Schema::getIndexListing('reports'))->toContain('reports_status_created_index', 'reports_type_status_index');
});

test('a visitor cannot ask for more than 50 rows per page', function () {
    foreach (range(1, 55) as $i) {
        $report = Report::factory()->create(['type' => 'url', 'url' => "https://scam{$i}.example/", 'status' => 'completed']);
        Analysis::factory()->create(['report_id' => $report->id, 'verdict' => 'phishing', 'risk_score' => 80]);
    }

    $this->get(route('reports.public', ['rows' => 100000]))
        ->assertOk()
        ->assertViewHas('reports', fn ($reports) => $reports->perPage() === 50);
});