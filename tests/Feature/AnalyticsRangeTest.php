<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;

function analyticsScan(User $owner, string $verdict, Carbon $when, array $report = [], int $score = 50): Analysis
{
    $created = Report::factory()->create($report + [
        'user_id' => $owner->id,
        'type' => 'url',
        'url' => 'http://analytics-'.uniqid().'.test/',
        'status' => 'completed',
        'created_at' => $when,
    ]);

    return Analysis::factory()->create([
        'report_id' => $created->id,
        'verdict' => $verdict,
        'risk_score' => $score,
        'created_at' => $when,
    ]);
}

/** @return array<int, array<int, string|null>> */
function analyticsCsv(string $csv): array
{
    $lines = array_filter(explode("\n", trim(ltrim($csv, "\xEF\xBB\xBF"))));

    return array_map(fn (string $line) => str_getcsv($line, ',', '"', ''), array_values($lines));
}

test('the page starts on the last 30 days and compares them with the 30 days before', function () {
    $me = User::factory()->create();
    analyticsScan($me, 'phishing', now()->subDays(5));
    analyticsScan($me, 'clean', now()->subDays(40));

    $response = $this->actingAs($me)->get(route('analytics'))->assertOk();

    expect($response->viewData('period'))->toBe('30')
        ->and($response->viewData('hasComparison'))->toBeTrue()
        ->and($response->viewData('stats')['total'])->toBe(1)
        ->and($response->viewData('previousStats')['total'])->toBe(1);
});

test('All Time counts every scan you have ever made and has nothing to compare with', function () {
    $me = User::factory()->create();
    analyticsScan($me, 'clean', now()->subDays(400));
    analyticsScan($me, 'phishing', now()->subDays(2));

    $response = $this->actingAs($me)->get(route('analytics', ['period' => 'all']))->assertOk();

    expect($response->viewData('rangeLabel'))->toBe('All Time')
        ->and($response->viewData('hasComparison'))->toBeFalse()
        ->and($response->viewData('stats')['total'])->toBe(2)
        ->and($response->viewData('stats')['total_change'])->toBe(0.0);
});

test('This Month and Last Month cover the calendar months, each compared with the days before it', function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

    $me = User::factory()->create();
    analyticsScan($me, 'phishing', Carbon::parse('2026-10-03 10:00:00'));
    analyticsScan($me, 'clean', Carbon::parse('2026-09-20 10:00:00'));
    analyticsScan($me, 'clean', Carbon::parse('2026-08-28 10:00:00'));

    $thisMonth = $this->actingAs($me)->get(route('analytics', ['period' => 'this_month']))->assertOk();
    $lastMonth = $this->actingAs($me)->get(route('analytics', ['period' => 'last_month']))->assertOk();

    expect($thisMonth->viewData('rangeLabel'))->toBe('This Month')
        ->and($thisMonth->viewData('stats')['total'])->toBe(1)
        ->and($lastMonth->viewData('rangeLabel'))->toBe('Last Month')
        ->and($lastMonth->viewData('stats')['total'])->toBe(1)
        ->and($lastMonth->viewData('previousStats')['total'])->toBe(1);
});

test('a custom range counts only the scans between the two dates, compared with the same number of days before', function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

    $me = User::factory()->create();
    analyticsScan($me, 'phishing', Carbon::parse('2026-10-05 09:00:00'));
    analyticsScan($me, 'clean', Carbon::parse('2026-10-01 09:00:00'));
    analyticsScan($me, 'clean', Carbon::parse('2026-10-10 09:00:00'));
    analyticsScan($me, 'clean', Carbon::parse('2026-09-28 09:00:00'));

    $response = $this->actingAs($me)->get(route('analytics', ['from' => '2026-10-02', 'to' => '2026-10-09']))->assertOk();

    expect($response->viewData('period'))->toBe('custom')
        ->and($response->viewData('rangeLabel'))->toBe('2 Oct – 9 Oct 2026')
        ->and($response->viewData('rangeNotice'))->toBeNull()
        ->and($response->viewData('stats')['total'])->toBe(1)
        ->and($response->viewData('previousStats')['total'])->toBe(2)
        ->and($response->viewData('chartDates'))->toHaveCount(8)
        ->and($response->viewData('exportParams'))->toBe(['from' => '2026-10-02', 'to' => '2026-10-09']);
});

test('dates in the wrong order are swapped, with a note', function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

    $me = User::factory()->create();
    analyticsScan($me, 'phishing', Carbon::parse('2026-10-05 09:00:00'));

    $response = $this->actingAs($me)->get(route('analytics', ['from' => '2026-10-09', 'to' => '2026-10-02']))->assertOk();

    expect($response->viewData('rangeNotice'))->toContain('swapped')
        ->and($response->viewData('stats')['total'])->toBe(1);
});

test('a custom range is limited to 12 months', function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

    $me = User::factory()->create();

    $response = $this->actingAs($me)->get(route('analytics', ['from' => '2024-01-01', 'to' => '2026-10-10']))->assertOk();

    expect($response->viewData('rangeNotice'))->toContain('limited to 12 months')
        ->and($response->viewData('rangeFrom'))->toBe('2025-10-10')
        ->and($response->viewData('chartDates'))->toHaveCount(366);
});

test('a half-filled, impossible or future range falls back to the last 30 days with a note', function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

    $me = User::factory()->create();

    foreach ([
        ['from' => '2026-10-02'],
        ['from' => '2026-02-31', 'to' => '2026-03-02'],
        ['from' => '2026-11-01', 'to' => '2026-11-05'],
    ] as $query) {
        $response = $this->actingAs($me)->get(route('analytics', $query))->assertOk();

        expect($response->viewData('period'))->toBe('30')
            ->and($response->viewData('rangeNotice'))->not->toBeNull();
    }
});

test('a guest cannot download analytics', function () {
    $this->get(route('analytics.export'))->assertRedirect(route('login'));
});

test('the analytics export has only your numbers, for the range you picked', function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));

    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    analyticsScan($me, 'phishing', Carbon::parse('2026-10-05 09:00:00'), score: 90);
    analyticsScan($me, 'clean', Carbon::parse('2026-10-12 09:00:00'), score: 5);
    analyticsScan($someoneElse, 'phishing', Carbon::parse('2026-10-05 09:00:00'));

    $response = $this->actingAs($me)->get(route('analytics.export', ['from' => '2026-10-02', 'to' => '2026-10-09']));

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
    expect($response->headers->get('content-disposition'))->toContain('phishcore-analytics-');

    $csv = $response->streamedContent();
    $rows = analyticsCsv($csv);
    $find = fn (string $section, string $item) => collect($rows)->first(fn ($row) => $row[0] === $section && $row[1] === $item);

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($rows[0])->toBe(['Section', 'Item', 'Value', 'Previous period', 'Change (%)'])
        ->and($find('Range', 'Period')[2])->toBe('2 Oct – 9 Oct 2026')
        ->and($find('Summary', 'Total reports')[2])->toBe('1')
        ->and($find('Results', 'Phishing')[2])->toBe('1')
        ->and(collect($rows)->where(0, 'Daily activity'))->toHaveCount(8);
});

test('the All Time export leaves the comparison columns empty', function () {
    $me = User::factory()->create();
    analyticsScan($me, 'phishing', now()->subDays(3));

    $rows = analyticsCsv($this->actingAs($me)->get(route('analytics.export', ['period' => 'all']))->streamedContent());
    $total = collect($rows)->first(fn ($row) => $row[0] === 'Summary' && $row[1] === 'Total reports');

    expect($total[2])->toBe('1')
        ->and($total[3])->toBe('')
        ->and($total[4])->toBe('');
});

test('a threat source that looks like a spreadsheet formula is exported as plain text', function () {
    $me = User::factory()->create();
    analyticsScan($me, 'phishing', now()->subDay(), ['type' => 'email', 'url' => null, 'sender_email' => 'x@=evil.test'], 95);

    $rows = analyticsCsv($this->actingAs($me)->get(route('analytics.export'))->streamedContent());
    $source = collect($rows)->first(fn ($row) => $row[0] === 'Top threat sources');

    expect($source[1])->toBe("'=evil.test");
});
