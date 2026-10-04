<?php

use App\Models\Analysis;
use App\Models\Report;

test('login screen shows live scan figures instead of fixed numbers', function () {
    $completed = Report::factory()->count(2)->create(['status' => 'completed']);
    Report::factory()->create(['status' => 'active']);
    Analysis::factory()->create(['report_id' => $completed[0]->id, 'verdict' => 'phishing', 'duration_ms' => 6000]);
    Analysis::factory()->create(['report_id' => $completed[1]->id, 'verdict' => 'clean', 'duration_ms' => 8000]);

    $this->get('/login')
        ->assertOk()
        ->assertSeeText('SCANS COMPLETED')
        ->assertSeeText('THREATS DETECTED')
        ->assertSeeText('7s')
        ->assertDontSeeText('97.4%')
        ->assertDontSeeText('489');
});

test('login screen still renders when nothing has been scanned yet', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSeeText('AVG SCAN TIME')
        ->assertDontSeeText('97.4%');
});
