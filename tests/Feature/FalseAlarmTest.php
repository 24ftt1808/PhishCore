<?php

use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;

/**
 * Regression tests for false alarms found by the accuracy run
 * (php artisan scan:batch). Each "still flagged" test makes sure the fix did
 * not open a way for a real scam to use the same exemption.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

function callPrivate(AnalysisEngine $engine, string $method, mixed ...$args): mixed
{
    $m = new ReflectionMethod($engine, $method);
    $m->setAccessible(true);

    return $m->invoke($engine, ...$args);
}

function hiddenIframePoints(AnalysisEngine $engine, string $html): int
{
    return callPrivate($engine, 'checkObfuscationPatterns', $html)['points'];
}

// --- hidden iframe vs Google Tag Manager ---

test('the standard Google Tag Manager noscript iframe is not flagged', function () {
    $html = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-ABC123" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

    expect(hiddenIframePoints($this->engine, $html))->toBe(0);
});

test('a lazy-loaded or first-party Tag Manager iframe is not flagged', function () {
    expect(hiddenIframePoints($this->engine, '<iframe data-src="https://www.googletagmanager.com/ns.html?id=GTM-ABC123" width="0" height="0"></iframe>'))->toBe(0)
        ->and(hiddenIframePoints($this->engine, '<iframe src="/gtm/ns.html?id=GTM-ABC123" width="0" height="0"></iframe>'))->toBe(0);
});

test('a hidden iframe to another site is still flagged', function () {
    expect(hiddenIframePoints($this->engine, '<iframe src="https://evil.example/x" width="0" height="0"></iframe>'))->toBe(20);
});

test('copying a Tag Manager id onto another host does not earn the exemption', function () {
    $evil = [
        '<iframe src="https://evil.example/ns.html?id=GTM-ABC123" width="0"></iframe>',
        '<iframe src="//evil.example/ns.html?id=GTM-ABC123" width="0"></iframe>',
        '<iframe src="https://googletagmanager.com.evil.example/ns.html?id=GTM-A" width="0"></iframe>',
        '<iframe src="https://evil.example/" data-src="https://www.googletagmanager.com/ns.html?id=GTM-A" width="0"></iframe>',
    ];

    foreach ($evil as $html) {
        expect(hiddenIframePoints($this->engine, $html))->toBe(20);
    }
});

test('the lazy-loaded Tag Manager iframe with a placeholder image src is not flagged', function () {
    $html = "<iframe \nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\" data-src=\"https://www.googletagmanager.com/ns.html?id=GTM-549FSBX\" class=\"lazyload\" src=\"data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==\">";

    expect(hiddenIframePoints($this->engine, $html))->toBe(0);
});

test('a hidden iframe carrying inline html is still flagged', function () {
    expect(hiddenIframePoints($this->engine, '<iframe width="0" src="data:text/html;base64,PHNjcmlwdD4=" data-src="https://www.googletagmanager.com/ns.html?id=GTM-A"></iframe>'))->toBe(20);
});

test('a harmless Tag Manager iframe does not hide a malicious one next to it', function () {
    $html = '<iframe src="https://www.googletagmanager.com/ns.html?id=GTM-A" width="0"></iframe>'
        .'<iframe src="https://evil.example/x" width="0"></iframe>';

    expect(hiddenIframePoints($this->engine, $html))->toBe(20);
});

// --- generic "bank" brand word on a brand's own domain ---

test('official bank domains are not flagged for containing the word bank', function () {
    expect($this->engine->checkUrlSyntax('https://www.maybank2u.com.my')['points'])->toBe(0)
        ->and($this->engine->checkUrlSyntax('https://www.citibank.com')['points'])->toBe(0);
});

test('lookalike domains using a brand word are still flagged', function () {
    foreach ([
        'https://secure-bank-login.xyz',
        'https://paypal.evil.com',
        'https://micros0ft.com',
        'https://google.com.evil.com',
        'https://maybank2u.com.my.evil.com',
    ] as $url) {
        expect($this->engine->checkUrlSyntax($url)['flagged'])->toBeTrue($url);
    }
});

// --- redirects between Brunei government sites ---

test('a redirect from one gov.bn site to another costs no points', function () {
    Http::preventStrayRequests();
    Http::fake([
        'www.mofe.gov.bn*' => Http::response('', 301, ['Location' => 'https://www.mof.gov.bn/']),
        'www.mof.gov.bn*' => Http::response('ok', 200),
    ]);

    $result = $this->engine->checkRedirectChain('https://www.mofe.gov.bn');

    expect($result['points'])->toBe(0)
        ->and($result['flagged'])->toBeFalse()
        ->and($result['reasons'][0])->toContain('gov.bn');
});

test('a redirect from a gov.bn site to a non-government site is still flagged', function () {
    Http::preventStrayRequests();
    Http::fake([
        'www.mofe.gov.bn*' => Http::response('', 302, ['Location' => 'https://evil.example/login']),
        'evil.example*' => Http::response('ok', 200),
    ]);

    expect($this->engine->checkRedirectChain('https://www.mofe.gov.bn')['points'])->toBe(20);
});

test('a lookalike that only ends in gov.bn is not treated as government', function () {
    expect(callPrivate($this->engine, 'isBruneiGovernmentHost', 'www.mof.gov.bn'))->toBeTrue()
        ->and(callPrivate($this->engine, 'isBruneiGovernmentHost', 'evilgov.bn'))->toBeFalse()
        ->and(callPrivate($this->engine, 'isBruneiGovernmentHost', 'www.gov.bn.evil.com'))->toBeFalse();
});

// --- site availability ---

test('a server that does not answer is reported as unknown, not offline', function () {
    Http::fake(fn () => throw new Illuminate\Http\Client\ConnectionException('cURL error 28: timed out'));

    expect($this->engine->checkSiteAvailability('https://example.com')['status_label'])->toBe('UNKNOWN');
})->skip(fn () => gethostbyname('example.com') === 'example.com', 'needs DNS to resolve example.com');

test('a live site is reported as live', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    expect($this->engine->checkSiteAvailability('https://example.com')['status_label'])->toBe('LIVE');
})->skip(fn () => gethostbyname('example.com') === 'example.com', 'needs DNS to resolve example.com');

test('a domain that does not resolve is still reported as offline', function () {
    expect($this->engine->checkSiteAvailability('https://no-such-domain-phishcore-test.invalid')['status_label'])->toBe('OFFLINE');
});

// --- IP reputation weight ---

test('a proxy flag on a shared platform address is not enough on its own to make a site suspicious', function () {
    Http::fake(['ip-api.com/*' => Http::response(['status' => 'success', 'country' => 'United States', 'isp' => 'GitHub, Inc.', 'proxy' => true, 'hosting' => true, 'query' => '1.2.3.4'])]);

    $result = $this->engine->checkIpReputation('https://example.com');

    // proxy (10) + datacenter (5) = 15, below the 25 where a site turns suspicious.
    expect($result['points'])->toBe(15)->and($result['points'])->toBeLessThan(25);
})->skip(fn () => gethostbyname('example.com') === 'example.com', 'needs DNS to resolve example.com');

test('a datacenter-only address still scores 5 and a clean address scores 0', function () {
    Http::fake(['ip-api.com/*' => Http::sequence()
        ->push(['status' => 'success', 'country' => 'US', 'isp' => 'AWS', 'proxy' => false, 'hosting' => true, 'query' => '1.2.3.4'])
        ->push(['status' => 'success', 'country' => 'BN', 'isp' => 'DST', 'proxy' => false, 'hosting' => false, 'query' => '1.2.3.4'])]);

    expect($this->engine->checkIpReputation('https://example.com')['points'])->toBe(5)
        ->and($this->engine->checkIpReputation('https://example.com')['points'])->toBe(0);
})->skip(fn () => gethostbyname('example.com') === 'example.com', 'needs DNS to resolve example.com');

// --- free hosting: brand in the path, official brand pages, shared IPs ---

test('a well-known brand in the path of a free-hosting page adds points, a plain page does not', function () {
    expect($this->engine->checkFreeHostingPlatform('https://someone.github.io/Amazon-Clone')['points'])->toBe(25)
        ->and($this->engine->checkFreeHostingPlatform('https://someone.github.io/DHL')['points'])->toBe(25)
        ->and($this->engine->checkFreeHostingPlatform('https://someone.github.io/spotify-clone')['points'])->toBe(25)
        ->and($this->engine->checkFreeHostingPlatform('https://someone.github.io/Tiktokshop-byte/de.html')['points'])->toBe(25)
        ->and($this->engine->checkFreeHostingPlatform('https://someone.github.io/')['points'])->toBe(10)
        ->and($this->engine->checkFreeHostingPlatform('https://example.com/Amazon-Clone')['points'])->toBe(0);
});

test('brand words inside longer words do not count as a brand in the path', function () {
    expect($this->engine->checkFreeHostingPlatform('https://someone.github.io/setups-and-groups')['points'])->toBe(10)
        ->and($this->engine->checkFreeHostingPlatform('https://someone.github.io/pineapple-recipes')['points'])->toBe(10);
});

test('the verified official github.io pages are recognised and fakes are not', function () {
    foreach (['microsoft.github.io', 'google.github.io', 'spotify.github.io', 'netflix.github.io', 'googleblog.blogspot.com', 'blog.google'] as $host) {
        expect(callPrivate($this->engine, 'isOfficialBrandDomain', $host))->toBeTrue($host);
    }

    foreach (['amazon.github.io', 'paypal.github.io', 'microsoft.github.io.evil.com', 'notgoogle.github.io'] as $host) {
        expect(callPrivate($this->engine, 'isOfficialBrandDomain', $host))->toBeFalse($host);
    }

    expect($this->engine->checkUrlSyntax('https://microsoft.github.io/')['points'])->toBe(0)
        ->and($this->engine->checkUrlSyntax('https://microsoft-support.github.io/')['flagged'])->toBeTrue()
        ->and($this->engine->checkUrlSyntax('https://paypal.github.io/login')['flagged'])->toBeTrue();
});

test('a meta-refresh on an official brand page is ignored but not on other sites', function () {
    $html = '<meta http-equiv="refresh" content="0; url=https://opensource.microsoft.com">';

    expect(callPrivate($this->engine, 'checkMetaRefresh', $html, 'microsoft.github.io')['points'])->toBe(0)
        ->and(callPrivate($this->engine, 'checkMetaRefresh', $html, 'evil.example')['points'])->toBe(15);
});

test('a redirect from an official brand page costs nothing, from other sites it still does', function () {
    Http::preventStrayRequests();
    Http::fake([
        'googleblog.blogspot.com*' => Http::response('', 302, ['Location' => 'https://blog.google/']),
        'swr.vercel.app*' => Http::response('', 302, ['Location' => 'https://vercel.com/oss/swr']),
        'evil.example*' => Http::response('', 302, ['Location' => 'https://paypal.com/']),
        '*' => Http::response('ok', 200),
    ]);

    expect($this->engine->checkRedirectChain('https://googleblog.blogspot.com/')['points'])->toBe(0)
        ->and($this->engine->checkRedirectChain('https://swr.vercel.app/')['points'])->toBe(20)
        ->and($this->engine->checkRedirectChain('https://evil.example/')['points'])->toBe(20);
});

test('the proxy flag is ignored on a shared free-hosting address but counts elsewhere', function () {
    Http::fake(['ip-api.com/*' => Http::response(['status' => 'success', 'country' => 'US', 'isp' => 'Shared', 'proxy' => true, 'hosting' => false, 'query' => '1.2.3.4'])]);

    expect($this->engine->checkIpReputation('https://octocat.github.io/')['points'])->toBe(0)
        ->and($this->engine->checkIpReputation('https://github.com/')['points'])->toBe(10);
})->skip(fn () => gethostbyname('octocat.github.io') === 'octocat.github.io' || gethostbyname('github.com') === 'github.com', 'needs DNS');

// --- brand mentions and iframe sizes on real pages (found on stgeorges.edu.bn) ---

test('iframes with marginwidth or marginheight of 0 are not treated as hidden', function () {
    $html = '<iframe id="web-visitor-counter" width="78px" height="16px" border="0" marginwidth="0" marginheight="0" hspace="0" vspace="0" frameborder="0" src="https://example.com/counter"></iframe>';

    expect(hiddenIframePoints($this->engine, $html))->toBe(0)
        ->and(hiddenIframePoints($this->engine, '<iframe src="https://evil.example/x" width="0" height="0"></iframe>'))->toBe(20);
});

test('a school page that lists Microsoft software and has a login box is not brand impersonation', function () {
    Http::fake(['*' => Http::response(
        '<html><head><title>St. George\'s School</title></head><body><h1>Welcome</h1>'
        .'<input id="login-box" type="password" name="waPassword" />'
        .'<p>The school has a Computer Laboratory and software applications, including Microsoft Paint, Microsoft PowerPoint, Microsoft Excel and GIMP for students.</p></body></html>',
        200
    )]);

    expect($this->engine->checkPageContent('https://school.example/')['points'])->toBe(0);
});

test('a login page that names the brand in its title, logo, or login prompt is still flagged', function () {
    $pages = [
        '<html><head><title>Netflix - Update payment</title></head><body><form><input type="password" name="p"></form></body></html>',
        '<html><head><title>Welcome</title></head><body><p>Please log in to your PayPal account to continue.</p><form><input type="password" name="p"></form></body></html>',
        '<html><head><title>Sign in</title></head><body><img src="logo.png" alt="Microsoft"><form><input type="password" name="p"></form><p>(c) Microsoft</p></body></html>',
    ];

    // One fake with a response per page: a second Http::fake() call would not override the first.
    $responses = Http::sequence();
    foreach ($pages as $html) {
        $responses->push($html, 200);
    }
    Http::fake(['*' => $responses]);

    foreach ($pages as $html) {
        expect($this->engine->checkPageContent('https://evil.example/login')['points'])->toBeGreaterThanOrEqual(30);
    }
});

test('a brand mention on its own official domain is still not flagged', function () {
    Http::fake(['*' => Http::response('<html><head><title>Sign in to PayPal</title></head><body><form><input type="password" name="p"></form></body></html>', 200)]);

    expect($this->engine->checkPageContent('https://www.paypal.com/signin')['points'])->toBe(0);
});