<?php

use App\Models\Analysis;
use App\Models\Report;

function feedReport(string $url, string $verdict, array $overrides = []): Report
{
    $report = Report::factory()->create(array_merge([
        'url' => $url,
        'type' => 'url',
        'status' => 'completed',
    ], $overrides));

    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => $verdict]);

    return $report;
}

test('csv feed lists phishing urls and nothing else by default', function () {
    feedReport('http://fake-bibd-login.example/verify', 'phishing');
    feedReport('http://maybe-bad.example/page', 'suspicious');
    feedReport('https://www.bibd.com.bn/', 'clean');

    $response = $this->get(route('reports.feed', ['format' => 'csv']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');

    $body = $response->getContent();
    expect($body)->toContain('url,domain,verdict,first_reported,reports');
    expect($body)->toContain('http://fake-bibd-login.example/verify');
    expect($body)->not->toContain('maybe-bad.example');
    expect($body)->not->toContain('bibd.com.bn');
});

test('feed never includes emails or phone numbers', function () {
    feedReport('http://scam-link.example/a', 'phishing');
    feedReport('http://email-scam.example/b', 'phishing', ['type' => 'email', 'sender_email' => 'victim@example.com']);
    feedReport('http://phone-scam.example/c', 'phishing', ['type' => 'phone', 'phone_number' => '+6737654321']);

    $body = $this->get(route('reports.feed', ['format' => 'csv']))->getContent();

    expect($body)->toContain('scam-link.example');
    expect($body)->not->toContain('email-scam.example');
    expect($body)->not->toContain('phone-scam.example');
    expect($body)->not->toContain('victim@example.com');
    expect($body)->not->toContain('6737654321');
});

test('json feed has the expected structure and merges duplicate urls', function () {
    feedReport('http://dup-scam.example/login', 'phishing');
    feedReport('http://dup-scam.example/login', 'phishing');

    $response = $this->getJson(route('reports.feed', ['format' => 'json']));

    $response->assertOk()
        ->assertJsonPath('source', 'PhishCore')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('data.0.url', 'http://dup-scam.example/login')
        ->assertJsonPath('data.0.domain', 'dup-scam.example')
        ->assertJsonPath('data.0.verdict', 'phishing')
        ->assertJsonPath('data.0.reports', 2);
});

test('txt feed is one url per line', function () {
    feedReport('http://one.example/x', 'phishing');
    feedReport('http://two.example/y', 'phishing');

    $response = $this->get(route('reports.feed', ['format' => 'txt']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');

    $lines = array_filter(explode("\n", $response->getContent()));
    expect($lines)->toContain('http://one.example/x', 'http://two.example/y');
    expect(count($lines))->toBe(2);
});

test('verdict=all also includes suspicious urls', function () {
    feedReport('http://definitely-bad.example/', 'phishing');
    feedReport('http://maybe-bad.example/', 'suspicious');

    $body = $this->get(route('reports.feed', ['format' => 'txt', 'verdict' => 'all']))->getContent();

    expect($body)->toContain('definitely-bad.example');
    expect($body)->toContain('maybe-bad.example');
});

test('unknown feed formats return 404', function () {
    $this->get('/public-reports/feed.xml')->assertNotFound();
});

test('public reports page links to the feed downloads', function () {
    $this->get(route('reports.public'))
        ->assertOk()
        ->assertSee(route('reports.feed', ['format' => 'csv']), false)
        ->assertSee(route('reports.feed', ['format' => 'json']), false)
        ->assertSee(route('reports.feed', ['format' => 'txt']), false);
});