<?php

use App\Services\AnalysisEngine;
use App\Services\ThreatFeeds;
use App\Support\ThreatLookup;

beforeEach(function () {
    config(['services.google_safe_browsing.key' => null]);
    app(ThreatFeeds::class)->replaceUrls('openphish', ['http://bad.example/login.php']);
});

test('the saved phishing lists are used unless they are switched off', function () {
    $result = (new AnalysisEngine)->checkThreatFeeds('http://bad.example/login.php');

    expect(ThreatLookup::listsInUse())->toBeTrue()
        ->and($result['flagged'])->toBeTrue()
        ->and($result['points'])->toBe(50);
});

test('switched off, the lists neither match an address nor a site, so only the rules decide', function () {
    config(['threatfeeds.lists_in_use' => false]);

    expect(ThreatLookup::listsInUse())->toBeFalse()
        ->and(ThreatLookup::exactMatch('http://bad.example/login.php'))->toBeNull()
        ->and(ThreatLookup::domainMatch('www.bad.example'))->toBeNull();

    $result = (new AnalysisEngine)->checkThreatFeeds('http://bad.example/login.php');

    expect($result['flagged'])->toBeFalse()->and($result['points'])->toBe(0);
});

test('the popular-sites ranking is not a phishing list and stays on', function () {
    app(ThreatFeeds::class)->replacePopular(['google.com' => 1]);
    config(['threatfeeds.lists_in_use' => false]);

    expect(ThreatLookup::isPopular('mail.google.com'))->toBeTrue();
});