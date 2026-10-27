<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** An engine that never opens a real WHOIS socket, so the two ways of running can be compared on equal terms. */
function offlineEngine(): AnalysisEngine
{
    return new class extends AnalysisEngine
    {
        protected function whoisCreationDate(string $domain): ?int
        {
            return null;
        }
    };
}

function scanWithFakedWeb(): array
{
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('<html><head><title>Garden club</title></head><body><p>'.str_repeat('A calm page about gardening. ', 10).'</p></body></html>', 200)]);

    return offlineEngine()->analyze('url', 'https://garden-club.example/news');
}

beforeEach(function () {
    config(['services.virustotal.key' => null, 'services.scan.parallel' => false]);
});

test('by default the checks run one after the other and nothing is handed to the concurrency driver', function () {
    Concurrency::shouldReceive('run')->never();

    $result = scanWithFakedWeb();

    expect($result['checks'])->not->toBeEmpty()->and($result)->toHaveKeys(['risk_score', 'verdict', 'checks']);
});

test('running the checks side by side gives exactly the same scan as running them one by one', function () {
    $oneByOne = scanWithFakedWeb();

    config(['services.scan.parallel' => true, 'concurrency.default' => 'sync']);
    $sideBySide = scanWithFakedWeb();

    expect($sideBySide)->toEqual($oneByOne)
        ->and(array_column($sideBySide['checks'], 'name'))->toBe(array_column($oneByOne['checks'], 'name'));
});

test('the same eight checks are handed over, under their own names', function () {
    config(['services.scan.parallel' => true]);
    Concurrency::shouldReceive('run')->once()->withArgs(function (array $tasks) {
        expect(array_keys($tasks))->toBe(['age', 'ssl', 'blacklist', 'vt', 'ip', 'redirect', 'content', 'availability']);

        return true;
    })->andThrow(new RuntimeException('stop here'));

    scanWithFakedWeb();
});

test('if running side by side fails, the scan still completes, one by one, and says so in the log', function () {
    $expected = scanWithFakedWeb();

    config(['services.scan.parallel' => true]);
    Log::spy();
    Concurrency::shouldReceive('run')->once()->andThrow(new RuntimeException('process failed'));

    $result = scanWithFakedWeb();

    expect($result)->toEqual($expected);
    Log::shouldHaveReceived('warning')
        ->with('Scan checks could not run in parallel; running one by one', ['reason' => RuntimeException::class])
        ->once();
});

test('results that do not match the tasks are not trusted', function (array $badResults) {
    $expected = scanWithFakedWeb();

    config(['services.scan.parallel' => true]);
    Log::spy();
    Concurrency::shouldReceive('run')->once()->andReturn($badResults);

    expect(scanWithFakedWeb())->toEqual($expected);
    Log::shouldHaveReceived('warning')->with('Scan checks gave unexpected results when run in parallel; running one by one')->once();
})->with([
    'missing checks' => [['age' => ['flagged' => false]]],
    'not arrays' => [['age' => 1, 'ssl' => 1, 'blacklist' => 1, 'vt' => 1, 'ip' => 1, 'redirect' => 1, 'content' => 1, 'availability' => 1]],
    'empty' => [[]],
]);