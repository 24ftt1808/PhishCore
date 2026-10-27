<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

$shell = '<!doctype html><html><head><title>My Bank</title><script src="/static/app.js"></script></head><body><div id="root"></div></body></html>';

test('a page that JavaScript draws is reported as such, not as clean', function () use ($shell) {
    Http::fake(['*' => Http::response($shell, 200)]);

    $result = $this->engine->checkPageContent('https://shell.example/');

    expect($result['neutral'])->toBeTrue()
        ->and($result['flagged'])->toBeFalse()
        ->and($result['points'])->toBe(0)
        ->and($result['unavailable'] ?? false)->toBeFalse()
        ->and($result['reasons'][0])->toContain('JavaScript')->not->toContain('No login forms')
        ->and($result['page_title'])->toBe('My Bank');
});

test('an empty shell is shown as information on the result page', function () use ($shell) {
    Http::fake(['*' => Http::response($shell, 200)]);
    $result = $this->engine->checkPageContent('https://shell.example/');

    $build = new ReflectionMethod($this->engine, 'buildCheck');

    expect($build->invoke($this->engine, 'Page Content Analysis', $result)['status'])->toBe('INFO');
});

test('a normal page with nothing wrong is still a clean pass', function () {
    Http::fake(['*' => Http::response('<html><head><title>Hello</title></head><body><p>'.str_repeat('A calm page about gardening. ', 12).'</p></body></html>', 200)]);

    $result = $this->engine->checkPageContent('https://garden.example/');

    expect($result['neutral'] ?? false)->toBeFalse()
        ->and($result['reasons'][0])->toContain('No login forms');
});

test('a login form in the HTML is still seen, even when scripts are present', function () {
    Http::fake(['*' => Http::response('<html><head><title>Sign in</title><script src="a.js"></script></head><body><form><input type="password" name="p"></form></body></html>', 200)]);

    $result = $this->engine->checkPageContent('https://school.example/');

    expect($result['neutral'] ?? false)->toBeFalse()
        ->and($result['has_sensitive_field'])->toBeTrue()
        ->and($result['reasons'][0])->toContain('password');
});

test('a security check page is not mistaken for the real page', function (int $status) {
    Http::fake(['*' => Http::response('<html><head><title>Just a moment...</title></head><body>Checking your browser before accessing</body></html>', $status)]);

    $result = $this->engine->checkPageContent('https://guarded.example/');

    expect($result['unavailable'])->toBeTrue()
        ->and($result['flagged'])->toBeFalse()
        ->and($result['reasons'][0])->toContain('security check')->toContain('Cloudflare');
})->with([403, 200, 503]);

test('an ordinary error page keeps its own message', function () {
    Http::fake(['*' => Http::response('<html><body>Forbidden</body></html>', 403)]);

    $result = $this->engine->checkPageContent('https://closed.example/');

    expect($result['unavailable'])->toBeTrue()->and($result['reasons'][0])->toContain('HTTP 403');
});

test('a page in another character set is read correctly', function () {
    $latin1 = mb_convert_encoding('<html><head><title>Café</title></head><body><p>Bienvenue au café, déjà ouvert.</p></body></html>', 'ISO-8859-1', 'UTF-8');
    Http::fake(['*' => Http::response($latin1, 200, ['Content-Type' => 'text/html; charset=ISO-8859-1'])]);

    $result = $this->engine->checkPageContent('https://cafe.example/');

    expect($result['page_title'])->toBe('Café')->and($result['page_text'])->toContain('déjà');
});

test('text hidden in the head, scripts and noscript is not read as page text', function () {
    Http::fake(['*' => Http::response('<html><head><title>Shop</title></head><body><script>var a="verify your account";</script><noscript>urgent verify</noscript><p>Welcome to our shop.</p></body></html>', 200)]);

    $result = $this->engine->checkPageContent('https://shop.example/');

    expect($result['page_text'])->toBe('Welcome to our shop.');
});
test('the same empty page read as a loaded page is not called a JavaScript shell', function () use ($shell) {
    $result = $this->engine->inspectPageHtml($shell, 'shell.example', null, true);

    expect($result['neutral'] ?? false)->toBeFalse()
        ->and($result['reasons'][0])->toContain('No login forms');

    // Read as a plain fetch it still is one.
    expect($this->engine->inspectPageHtml($shell, 'shell.example')['neutral'])->toBeTrue();
});

test('reading page HTML directly gives the same answer as fetching the page', function () {
    $html = '<html><head><title>Sign in</title></head><body><form><input type="password" name="p"></form></body></html>';
    Http::fake(['*' => Http::response($html, 200)]);

    $fetched = $this->engine->checkPageContent('https://school.example/');
    $direct = $this->engine->inspectPageHtml($html, 'school.example');

    expect($direct['points'])->toBe($fetched['points'])
        ->and($direct['has_sensitive_field'])->toBe($fetched['has_sensitive_field'])
        ->and($direct['flagged'])->toBe($fetched['flagged']);
});