<?php

use App\Services\AnalysisEngine;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/** An engine whose WHOIS lookup is replaced, so no test ever opens a real socket. */
function engineWithWhois(?int $answer = null): AnalysisEngine
{
    return new class($answer) extends AnalysisEngine
    {
        public function __construct(private ?int $answer) {}

        protected function whoisCreationDate(string $domain): ?int
        {
            return $this->answer;
        }
    };
}

function fakeRegistry(?string $createdAt, array $endings = ['com'], int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake([
        'data.iana.org/rdap/dns.json' => Http::response(['services' => [[$endings, ['https://rdap.registry.test/']]]]),
        'rdap.registry.test/*' => $createdAt === null
            ? Http::response([], $status)
            : Http::response(['events' => [['eventAction' => 'last changed', 'eventDate' => now()->toIso8601String()], ['eventAction' => 'registration', 'eventDate' => $createdAt]]]),
    ]);
}

test('the registry date is used and the source is named', function () {
    fakeRegistry(now()->subYears(5)->toIso8601String());

    $result = engineWithWhois()->checkDomainAge('https://example.com/login');

    expect($result['flagged'])->toBeFalse()
        ->and($result['unavailable'] ?? false)->toBeFalse()
        ->and($result['source'])->toBe('rdap')
        ->and($result['domain_age_days'])->toBeGreaterThan(1800)
        ->and($result['detail'])->toContain('5 years')->toContain('the domain registry');
});

test('the age of the main domain is asked for, not the age of the subdomain', function () {
    fakeRegistry(now()->subYears(3)->toIso8601String());

    $result = engineWithWhois()->checkDomainAge('https://login.shop.example.com/');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/domain/example.com'));
    expect($result['detail'])->toContain('main domain');
});

test('a very new domain is flagged', function () {
    fakeRegistry(now()->subDays(3)->toIso8601String());

    $result = engineWithWhois()->checkDomainAge('https://fresh.com');

    expect($result['flagged'])->toBeTrue()
        ->and($result['points'])->toBe(40)
        ->and($result['reasons'][0])->toContain('only 3 day');
});

test('a page on a shared platform gets a neutral answer and no lookup', function () {
    Http::preventStrayRequests();
    Http::fake();

    $result = engineWithWhois(strtotime('2008-01-01'))->checkDomainAge('https://someone.github.io/login');

    expect($result['neutral'])->toBeTrue()
        ->and($result['flagged'])->toBeFalse()
        ->and($result['points'])->toBe(0)
        ->and($result['domain_age_days'])->toBeNull()
        ->and($result['unavailable'] ?? false)->toBeFalse()
        ->and($result['reasons'][0])->toContain('github.io');
    Http::assertNothingSent();
});

test('an address made of numbers has no registration date', function () {
    Http::preventStrayRequests();
    Http::fake();

    $result = engineWithWhois()->checkDomainAge('http://192.168.0.1/');

    expect($result['unavailable'])->toBeTrue();
    Http::assertNothingSent();
});

test('WHOIS answers when the registry has no server for that ending', function () {
    fakeRegistry(null, ['net']);

    $result = engineWithWhois(now()->subYears(10)->getTimestamp())->checkDomainAge('example.com');

    expect($result['source'])->toBe('whois')
        ->and($result['detail'])->toContain('a WHOIS record');
});

test('VirusTotal answers when neither the registry nor WHOIS can', function () {
    config(['services.virustotal.key' => 'test-key']);
    fakeRegistry(null, ['net']);
    Http::fake([
        'data.iana.org/rdap/dns.json' => Http::response(['services' => [[['net'], ['https://rdap.registry.test/']]]]),
        'www.virustotal.com/api/v3/domains/example.com' => Http::response(['data' => ['attributes' => ['creation_date' => now()->subYears(8)->getTimestamp()]]]),
    ]);

    $result = engineWithWhois(null)->checkDomainAge('example.com');

    expect($result['source'])->toBe('virustotal')
        ->and($result['detail'])->toContain('VirusTotal');
});

test('no source knowing the date means unavailable, not a pass', function () {
    config(['services.virustotal.key' => null]);
    fakeRegistry(null, ['com'], 404);

    $result = engineWithWhois(null)->checkDomainAge('example.com');

    expect($result['unavailable'])->toBeTrue()
        ->and($result['flagged'])->toBeFalse()
        ->and($result['reasons'][0])->toBe('WHOIS data unavailable for this domain');
});

test('a date before the web existed or in the future is treated as bad data', function (string $date) {
    config(['services.virustotal.key' => null]);
    fakeRegistry($date);

    expect(engineWithWhois(null)->checkDomainAge('example.com')['unavailable'])->toBeTrue();
})->with([
    'epoch' => ['1970-01-01T00:00:00Z'],
    'future' => ['2999-01-01T00:00:00Z'],
]);

test('a registration date is looked up once and then remembered', function () {
    fakeRegistry(now()->subYears(2)->toIso8601String());
    $engine = engineWithWhois();

    $engine->checkDomainAge('example.com');
    $engine->checkDomainAge('https://www.example.com/other');

    Http::assertSentCount(2); // the list of registry servers, and the one domain lookup
});

test('a failing network never breaks the scan', function () {
    Http::preventStrayRequests();
    Http::fake(fn () => throw new \RuntimeException('network down'));

    $result = engineWithWhois(null)->checkDomainAge('example.com');

    expect($result['unavailable'])->toBeTrue();
});

test('a shared-platform answer is shown as information, not as a pass or a failure', function () {
    $engine = new AnalysisEngine;
    $age = $engine->checkDomainAge('https://someone.netlify.app');

    $build = new ReflectionMethod($engine, 'buildCheck');
    $check = $build->invoke($engine, 'Domain Age', $age);

    expect($check['status'])->toBe('INFO')->and($check['points'])->toBe(0);
});