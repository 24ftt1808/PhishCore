<?php

use App\Services\AnalysisEngine;

/**
 * Official Brunei bank, wallet and network domains added in October 2026. Each domain was read from the
 * organisation's own pages (Maybank Brunei, UOB Brunei, Standard Chartered Brunei, Progresif, DST, tarus,
 * UNN). BrandsConfigTest already checks that none of them is flagged as a link or an email sender.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

test('the real sites of these organisations are not flagged', function (string $url) {
    expect($this->engine->checkUrlSyntax($url)['points'])->toBe(0);
})->with([
    'https://www.uobgroup.com/brunei/index.page',
    'https://www.maybank.com/en/worldwide/brunei.page',
    'https://retail.sc.com/bn/ibank/foa/login.htm',
    'https://s2b.standardchartered.com/unifiedlogin/login/index.html',
    'https://www.progresif.com/ding',
    'https://mydst.dst.com.bn/login',
    'https://tarus.com.bn/',
    'https://unn.com.bn/about',
    'https://digital.baiduri.com.bn/BR/',
]);

test('look-alikes of the newly listed names are flagged', function (string $url) {
    expect($this->engine->checkUrlSyntax($url)['points'])->toBeGreaterThanOrEqual(25);
})->with([
    'https://tarus-transfer-verify.top',
    'https://mydst-bill-update.xyz',
    'https://progresif-ding-claim.online',
]);

test('a message in the name of UOB Brunei from a stranger is a brand mismatch, from UOB it is not', function () {
    $fake = $this->engine->scoreEmailRules('alerts@uob-brunei-secure.top', 'UOB Brunei account', 'UOB Brunei: your account has been suspended. Verify your account now to avoid deactivation.');
    $real = $this->engine->scoreEmailRules('ebanking@uobgroup.com', 'UOB Brunei statement', 'Your UOB Brunei statement for October is ready. Log in to view it.');

    expect($fake['score'])->toBeGreaterThanOrEqual(60)
        ->and($real['score'])->toBeLessThan(25);
});