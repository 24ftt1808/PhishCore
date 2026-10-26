<?php

use App\Support\PageInspector;
use App\Support\RegistrableDomain;

$shell = '<!doctype html><html><head><title>App</title><script src="/assets/index.js"></script></head>'
    .'<body><div id="root"></div><noscript>You need to enable JavaScript to run this app.</noscript></body></html>';

test('an empty page that JavaScript fills in is recognised', function () use ($shell) {
    expect(PageInspector::needsJavaScript($shell))->toBeTrue()
        ->and(PageInspector::visibleText($shell))->toBe('');
});

test('a page with a form, a lot of text, or no script at all is not an empty shell', function () {
    $form = '<html><body><script src="a.js"></script><form><input type="password" name="p"></form></body></html>';
    $article = '<html><body><script src="a.js"></script><p>'.str_repeat('Real words on a real page. ', 20).'</p></body></html>';

    expect(PageInspector::needsJavaScript($form))->toBeFalse()
        ->and(PageInspector::needsJavaScript($article))->toBeFalse()
        ->and(PageInspector::needsJavaScript('<html><body><p>Hello</p></body></html>'))->toBeFalse()
        ->and(PageInspector::needsJavaScript(''))->toBeFalse();
});

test('visible text leaves out scripts, styles, templates and the head', function () {
    $html = '<html><head><title>T</title><style>p{}</style></head><body><p>Hello <b>there</b> &amp; welcome</p><script>x()</script><template>no</template></body></html>';

    expect(PageInspector::visibleText($html))->toBe('Hello there & welcome');
});

test('security check pages are recognised', function (string $html, ?string $header, ?string $expected) {
    expect(PageInspector::challenge($html, $header))->toBe($expected);
})->with([
    'cloudflare title' => ['<html><head><title>Just a moment...</title></head><body></body></html>', null, 'Cloudflare'],
    'cloudflare header' => ['<html></html>', 'challenge', 'Cloudflare'],
    'cloudflare script' => ['<script src="/cdn-cgi/challenge-platform/h/b/orchestrate/x"></script>', null, 'Cloudflare'],
    'sucuri' => ['<html><body>Access Denied - Sucuri Website Firewall</body></html>', null, 'Sucuri'],
    'a normal page' => ['<html><head><title>School</title></head><body><h1>Welcome</h1></body></html>', null, null],
]);

test('a large page is never mistaken for a security check', function () {
    expect(PageInspector::challenge(str_repeat('x', 90000).'Just a moment', null))->toBeNull();
});

test('pages in other character sets come out as UTF-8', function () {
    $latin1 = mb_convert_encoding('<html><body>Café déjà vu</body></html>', 'ISO-8859-1', 'UTF-8');

    expect(PageInspector::toUtf8($latin1, 'text/html; charset=ISO-8859-1'))->toBe('<html><body>Café déjà vu</body></html>')
        ->and(PageInspector::toUtf8($latin1))->toBe('<html><body>Café déjà vu</body></html>')
        ->and(PageInspector::toUtf8('<p>Café</p>'))->toBe('<p>Café</p>');
});

test('a page on a shared platform is told apart from a domain of its own', function () {
    expect(RegistrableDomain::sharedPlatform('someone.github.io'))->toBe('github.io')
        ->and(RegistrableDomain::sharedPlatform('A.B.Netlify.app.'))->toBe('netlify.app')
        ->and(RegistrableDomain::sharedPlatform('github.io'))->toBeNull()
        ->and(RegistrableDomain::sharedPlatform('example.com'))->toBeNull()
        ->and(RegistrableDomain::sharedPlatform(null))->toBeNull();
});