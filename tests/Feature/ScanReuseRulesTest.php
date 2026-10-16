<?php

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Support\ScanReuse;
use Illuminate\Support\Facades\DB;

/** A finished scan of the link, saved the given number of hours ago. */
function scanFromHoursAgo(string $url, int $hours): Report
{
    $report = Report::factory()->create([
        'user_id' => User::factory()->create()->id,
        'type' => 'url',
        'url' => $url,
        'status' => 'completed',
    ]);

    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => 'suspicious',
        'risk_score' => 30,
        'flags' => [['name' => 'SSL', 'status' => 'SAFE', 'message' => 'Fine.', 'points' => 0]],
    ]);

    DB::table('reports')->where('id', $report->id)->update(['created_at' => now()->subHours($hours)]);

    return $report;
}

/** A stand-in for the rules file, last written the given number of hours ago. */
function rulesFileChanged(int $hoursAgo): string
{
    $path = tempnam(sys_get_temp_dir(), 'rules');
    touch($path, now()->subHours($hoursAgo)->getTimestamp());

    return $path;
}

beforeEach(function () {
    config(['scan.reuse_hours' => 6, 'scan.reuse_rules_check' => true]);
});

test('a result made after the rules last changed is still reused', function () {
    $scan = scanFromHoursAgo('http://rules-after.test/', 2);
    config(['scan.rules_file' => rulesFileChanged(3)]);

    expect(ScanReuse::find('http://rules-after.test/')?->id)->toBe($scan->id);
});

test('a result made before the rules last changed is not reused', function () {
    scanFromHoursAgo('http://rules-before.test/', 2);
    config(['scan.rules_file' => rulesFileChanged(1)]);

    expect(ScanReuse::find('http://rules-before.test/'))->toBeNull();
});

test('the rules check can be switched off', function () {
    $scan = scanFromHoursAgo('http://rules-off.test/', 2);
    config(['scan.rules_file' => rulesFileChanged(1), 'scan.reuse_rules_check' => false]);

    expect(ScanReuse::find('http://rules-off.test/')?->id)->toBe($scan->id);
});

test('a missing rules file does not stop results being reused', function () {
    $scan = scanFromHoursAgo('http://rules-missing.test/', 2);
    config(['scan.rules_file' => '/no/such/file.php']);

    expect(ScanReuse::find('http://rules-missing.test/')?->id)->toBe($scan->id);
});

test('the reuse window still applies when the rules are old', function () {
    scanFromHoursAgo('http://rules-window.test/', 8);
    config(['scan.rules_file' => rulesFileChanged(24)]);

    expect(ScanReuse::find('http://rules-window.test/'))->toBeNull();
});
