<?php

use App\Models\PopularDomain;
use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Http;

/**
 * Links written inside an email are checked with the same address rules as a link scan. The limits matter:
 * links to the sender's own site and to well-known sites must not add a warning, or every normal
 * email with a "view online" link would score higher.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
    config(['services.ai_text.enabled' => false]);
    Http::fake();
});

test('an email whose link is a look-alike of a bank or shop scores higher than the same email without it', function () {
    $without = $this->engine->scoreEmailRules('alerts@acc-verify.top', 'Account notice', 'Your PayPal account has a problem. Please log in to fix it.');
    $with = $this->engine->scoreEmailRules('alerts@acc-verify.top', 'Account notice', 'Your PayPal account has a problem. Please log in to fix it: https://paypa1-secure-login.top/verify');

    expect($with['score'])->toBeGreaterThan($without['score'])
        ->and(implode(' ', $with['reasons']))->toContain('A link in the email goes to "paypa1-secure-login.top"');
});

test('links to well-known sites add nothing', function () {
    PopularDomain::create(['domain' => 'wikipedia.org', 'position' => 8]);

    $result = $this->engine->scoreEmailRules('friend@example.org', 'Reading', 'Have a look at https://en.wikipedia.org/wiki/Brunei and https://www.google.com/maps.');

    expect($result['score'])->toBe(0);
});

test('a link to the sender\'s own site adds nothing', function () {
    $result = $this->engine->scoreEmailRules('news@shop-example.com', 'Our newsletter', 'Read more at https://shop-example.com/blog/2026/10/autumn-sale-with-a-very-long-address-that-goes-on-and-on-and-on');

    expect($result['score'])->toBeLessThan(25);
});

test('a strong warning on the sender\'s own domain still counts, because scams send from the address they link to', function () {
    $result = $this->engine->scoreEmailRules('support@paypa1-login.top', 'Account', 'Please verify here: https://paypa1-login.top/verify');

    expect($result['score'])->toBeGreaterThanOrEqual(25);
});

test('the email scan shows the link check only when the email has a link that was flagged', function () {
    $plain = $this->engine->analyze('email', null, 'a@shop.example', null, null, null, 'Hello', 'See you at the meeting tomorrow.');
    $linked = $this->engine->analyze('email', null, 'alerts@acc-verify.top', null, null, null, 'Account notice', 'Your PayPal account has a problem: https://paypa1-secure-login.top/verify');

    expect(collect($plain['checks'])->pluck('name'))->not->toContain('Links in the Email')
        ->and(collect($linked['checks'])->pluck('name'))->toContain('Links in the Email');
});