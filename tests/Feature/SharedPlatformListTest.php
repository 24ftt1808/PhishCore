<?php

use App\Services\AnalysisEngine;
use App\Support\RegistrableDomain;

test('pages on free app-hosting platforms are recognised as pages on a shared platform', function (string $host, string $platform) {
    $result = app(AnalysisEngine::class)->checkFreeHostingPlatform("https://{$host}/");

    expect($result['flagged'])->toBeTrue()
        ->and($result['platform'])->toBe($platform)
        ->and(RegistrableDomain::sharedPlatform($host))->toBe($platform);
})->with([
    ['support-center-apple.wasmer.app', 'wasmer.app'],
    ['demo.streamlit.app', 'streamlit.app'],
    ['someone.pythonanywhere.com', 'pythonanywhere.com'],
    ['my-app.replit.app', 'replit.app'],
    ['my-app.deno.dev', 'deno.dev'],
]);

test('the platform itself and ordinary sites are not treated as a page on a shared platform', function () {
    foreach (['wasmer.app', 'wasmer.io', 'example.com'] as $host) {
        expect(RegistrableDomain::sharedPlatform($host))->toBeNull();
    }

    expect(app(AnalysisEngine::class)->checkFreeHostingPlatform('https://example.com/')['flagged'])->toBeFalse();
});