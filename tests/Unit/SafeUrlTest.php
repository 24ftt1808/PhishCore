<?php

use App\Support\SafeUrl;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

afterEach(fn () => SafeUrl::resolveUsing(null));

test('every spelling of an internal address is refused', function (string $host) {
    expect(SafeUrl::isSafe('http://'.$host.'/'))->toBeFalse();
})->with([
    '127.0.0.1',
    '2130706433',
    '0x7f.0.0.1',
    '0x7f000001',
    '017700000001',
    '0177.0.0.1',
    '127.1',
    '[::1]',
    '[::ffff:127.0.0.1]',
    '169.254.169.254',
    '10.0.0.5',
    '172.16.4.4',
    '192.168.1.1',
    '100.64.0.1',
    '0.0.0.0',
    '[fd00::1]',
    '[fe80::1]',
    'localhost',
    'printer.local',
    'wiki.internal',
]);

test('public addresses are allowed', function (string $host) {
    expect(SafeUrl::isSafe('https://'.$host.'/'))->toBeTrue();
})->with([
    '8.8.8.8',
    '1.1.1.1',
    '203.0.114.9',
    '[2606:4700:4700::1111]',
]);

test('only web addresses on normal ports are visited', function () {
    SafeUrl::resolveUsing(fn (string $host): array => ['93.184.216.34']);

    expect(SafeUrl::isSafe('https://example.com/'))->toBeTrue()
        ->and(SafeUrl::isSafe('http://example.com:8080/'))->toBeTrue()
        ->and(SafeUrl::isSafe('https://example.com:8443/'))->toBeTrue()
        ->and(SafeUrl::isSafe('https://example.com:22/'))->toBeFalse()
        ->and(SafeUrl::isSafe('https://example.com:6379/'))->toBeFalse()
        ->and(SafeUrl::isSafe('ftp://example.com/'))->toBeFalse()
        ->and(SafeUrl::isSafe('file:///etc/passwd'))->toBeFalse()
        ->and(SafeUrl::isSafe('not a url'))->toBeFalse();
});

test('a name that points to an internal address is refused, and one extra internal address is enough', function () {
    SafeUrl::resolveUsing(fn (string $host): array => match ($host) {
        'sneaky.example' => ['127.0.0.1'],
        'mixed.example' => ['93.184.216.34', '10.0.0.8'],
        'metadata.example' => ['169.254.169.254'],
        'six.example' => ['::1'],
        default => ['93.184.216.34'],
    });

    expect(SafeUrl::isSafe('https://sneaky.example/'))->toBeFalse()
        ->and(SafeUrl::isSafe('https://mixed.example/'))->toBeFalse()
        ->and(SafeUrl::isSafe('https://metadata.example/'))->toBeFalse()
        ->and(SafeUrl::isSafe('https://six.example/'))->toBeFalse()
        ->and(SafeUrl::isSafe('https://fine.example/'))->toBeTrue();
});

test('a name that does not resolve at all is allowed, so the normal "could not reach" result applies', function () {
    SafeUrl::resolveUsing(fn (string $host): array => []);

    expect(SafeUrl::isSafe('https://gone.example/'))->toBeTrue();
});

test('the reason is given in plain words, or null when the address is fine', function () {
    SafeUrl::resolveUsing(fn (string $host): array => []);

    expect(SafeUrl::problem('http://127.0.0.1/'))->toContain('internal or private')
        ->and(SafeUrl::problem('https://example.com:22/'))->toContain('unusual port')
        ->and(SafeUrl::problem('https://example.com/'))->toBeNull();
});

test('IP literals are read the way a browser reads them', function () {
    expect(SafeUrl::parseIpLiteral('2130706433'))->toBe('127.0.0.1')
        ->and(SafeUrl::parseIpLiteral('0x7f.0.0.1'))->toBe('127.0.0.1')
        ->and(SafeUrl::parseIpLiteral('0177.0.0.1'))->toBe('127.0.0.1')
        ->and(SafeUrl::parseIpLiteral('127.1'))->toBe('127.0.0.1')
        ->and(SafeUrl::parseIpLiteral('[::1]'))->toBe('::1')
        ->and(SafeUrl::parseIpLiteral('example.com'))->toBeNull()
        ->and(SafeUrl::parseIpLiteral('999.1.1.1'))->toBeNull()
        ->and(SafeUrl::parseIpLiteral('1.2.3.4.5'))->toBeNull();
});

test('the redirect guard lets public hops through and stops internal ones', function () {
    SafeUrl::resolveUsing(fn (string $host): array => []);

    $hook = SafeUrl::redirectOptions(3)['allow_redirects'];

    expect($hook['max'])->toBe(3)
        ->and($hook['protocols'])->toBe(['http', 'https']);

    $request = new Request('GET', 'https://example.com/');
    $response = new Response(302);

    $hook['on_redirect']($request, $response, new Uri('https://www.example.com/'));

    expect(fn () => $hook['on_redirect']($request, $response, new Uri('http://169.254.169.254/latest/meta-data/')))
        ->toThrow(RuntimeException::class, 'Redirect refused');
});
