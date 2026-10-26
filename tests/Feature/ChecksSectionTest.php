<?php

/*
 * "What we checked" on the scan result (resources/views/scan/_checks.blade.php).
 * The partial only reads $analysis->flags, so it is rendered directly with sample checks written the way
 * AnalysisEngine writes them. No database, network or scan is needed.
 */

function renderChecks(array $flags): string
{
    return view('scan._checks', ['analysis' => (object) ['flags' => $flags]])->render();
}

/** The visible text of the section, tags removed and whitespace squeezed to single spaces. */
function checksText(string $html): string
{
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))));
}

function sampleChecks(): array
{
    return [
        ['name' => 'SSL Certificate', 'status' => 'SUSPICIOUS', 'points' => 15, 'message' => 'Website does not use HTTPS (no SSL encryption)'],
        ['name' => 'Blacklist Database', 'status' => 'DETECTED', 'points' => 40, 'message' => "URL is flagged on Google Safe Browsing's threat list"],
        ['name' => 'VirusTotal / CTI Check', 'status' => 'DETECTED', 'points' => 30, 'message' => '4 out of 93 security vendors on VirusTotal flagged this URL as malicious or suspicious'],
        ['name' => 'Page Content Analysis', 'status' => 'HIGH RISK', 'points' => 50, 'message' => 'Page contains a password input field. Page content references "Adobe" branding, but the domain ("dashboard.adobeexperience.com.br") does not belong to adobe — likely brand impersonation. Server responds as: nginx.'],
        ['name' => 'URL Structure', 'status' => 'SAFE', 'points' => 0, 'message' => 'No issues detected for this check.'],
        ['name' => 'Redirect Chain', 'status' => 'SAFE', 'points' => 0, 'message' => 'URL redirects to example.com; both are well-known, widely visited websites.'],
        ['name' => 'Domain Age', 'status' => 'UNKNOWN', 'points' => 0, 'message' => 'WHOIS data unavailable for this domain.'],
    ];
}

test('nothing is drawn when a scan has no checks', function () {
    expect(checksText(renderChecks([])))->toBe('');
});

test('the heading and the count pills count the checks the same way the groups do', function () {
    $text = checksText(renderChecks(sampleChecks()));

    expect($text)->toContain('What we checked')
        ->toContain('7 checks ran on this scan')
        ->toContain('3 Serious 1 Caution 2 Passed 1 Unavailable')
        ->toContain('Needs attention · 4')
        ->toContain('Passed · 2')
        ->toContain('Unavailable or for information · 1');
});

test('each count pill jumps to its section', function () {
    $html = renderChecks(sampleChecks());

    expect($html)->toContain('href="#ck-attention"')->toContain('href="#ck-passed"')->toContain('href="#ck-unavailable"')
        ->and($html)->toContain('id="ck-attention"')->toContain('id="ck-passed"')->toContain('id="ck-unavailable"');
});

test('the count pills only list kinds of result that exist', function () {
    $text = checksText(renderChecks([
        ['name' => 'URL Structure', 'status' => 'SAFE', 'points' => 0, 'message' => 'No issues detected for this check.'],
    ]));

    expect($text)->toContain('1 Passed')->not->toContain('Serious')->not->toContain('Caution')->not->toContain('Unavailable ');
});

test('the worst finding is the featured card, with its evidence listed beside it', function () {
    $text = checksText(renderChecks(sampleChecks()));

    expect($text)->toContain('Password field on a page that appears to imitate Adobe')
        ->toContain('Page claims to be')
        ->toContain('Adobe')
        ->toContain('dashboard.adobeexperience.com.br')
        ->toContain('Server responds as')
        ->toContain('nginx');
});

test('warning cards open with a bold headline taken from the engine sentence', function () {
    $text = checksText(renderChecks(sampleChecks()));

    expect($text)->toContain('Flagged by Google Safe Browsing')
        ->toContain('4 of 93 vendors flagged this link')
        ->toContain('No secure connection (HTTPS)');
});

test('the vendor card shows a meter that matches the count', function () {
    $html = renderChecks(sampleChecks());

    expect($html)->toContain('4 of 93 vendors flagged this link')
        ->toContain('width: 4.3');
});

test('a short sentence with no special rule still becomes the headline and nothing is lost', function () {
    $html = renderChecks([
        ['name' => 'Domain Age', 'status' => 'SUSPICIOUS', 'points' => 10, 'message' => 'Domain is relatively new (12 days old)'],
    ]);

    expect(checksText($html))->toContain('The website is fairly new (12 days old)');
});

test('a long sentence with no good place to cut is shown whole, not shortened', function () {
    $message = 'It asks you to do something (pay, share a code or details, or contact someone) while putting pressure on you, which is the core pattern of a scam.';
    $html = renderChecks([
        ['name' => 'Message Content / Behavior Patterns', 'status' => 'HIGH RISK', 'points' => 40, 'message' => $message],
    ]);

    expect(checksText($html))->toContain($message);
});

test('passed checks are one compact row each: what was found, and what was looked at inside the info panel', function () {
    $html = renderChecks(sampleChecks());
    $text = checksText($html);

    expect(substr_count($html, 'class="ck-card ck-row'))->toBe(3)   // 2 passed + 1 unavailable
        ->and($text)->toContain('Looked at: Tricks in how the link is written')
        ->toContain('Looked at: Where the link really leads once you open it')
        ->toContain('URL redirects to example.com; both are well-known, widely visited websites.')
        ->toContain('No problems found')
        ->not->toContain('No problems were found');
});

test('an unavailable check shows what happened in plain words', function () {
    $text = checksText(renderChecks(sampleChecks()));

    expect($text)->toContain('Website age')
        ->toContain('Unavailable')
        ->toContain('Unavailable. We could not find out when this website was created.');
});

test('every check is explained by an info button that opens its own panel, and the old dropdown is gone', function () {
    $html = renderChecks(sampleChecks());

    expect($html)->not->toContain('What does this check mean?')
        ->not->toContain('<details');

    preg_match_all('/aria-controls="([^"]+)"/', $html, $buttons);
    preg_match_all('/<div id="([^"]+)" class="ck-pop"/', $html, $panels);

    expect($buttons[1])->not->toBeEmpty()
        ->and($buttons[1])->toBe($panels[1])
        ->and(array_unique($buttons[1]))->toHaveCount(count($buttons[1]))
        ->and($html)->toContain('Technical name: SSL Certificate');
});

test('text coming from a scanned page is escaped, never run', function () {
    $html = renderChecks([
        ['name' => 'Page Content Analysis', 'status' => 'HIGH RISK', 'points' => 50, 'message' => 'Page content references "<script>alert(1)</script>" branding, but the domain ("<img src=x onerror=alert(2)>") does not belong to it — likely brand impersonation. Server responds as: <b>nginx</b>.'],
        ['name' => 'Redirect Chain', 'status' => 'SAFE', 'points' => 0, 'message' => 'URL redirects to <script>alert(3)</script>.'],
    ]);

    expect($html)->not->toContain('<script>alert')
        ->not->toContain('<img src=x')
        ->not->toContain('<b>nginx</b>');
});

test('a check the page has never seen before is still shown', function () {
    $text = checksText(renderChecks([
        ['name' => 'Some Future Check', 'status' => 'SUSPICIOUS', 'points' => 5, 'message' => 'Something looked odd.'],
        ['name' => 'Another Future Check', 'status' => 'SAFE', 'points' => 0, 'message' => 'No issues detected for this check.'],
    ]));

    expect($text)->toContain('Some Future Check')->toContain('Something looked odd')
        ->toContain('Another Future Check');
});

test('a scan with only passed checks has no warning section', function () {
    $text = checksText(renderChecks([
        ['name' => 'URL Structure', 'status' => 'SAFE', 'points' => 0, 'message' => 'No issues detected for this check.'],
    ]));

    expect($text)->toContain('Passed · 1')->not->toContain('Needs attention');
});

test('a lone warning card fills its row', function () {
    $html = renderChecks([
        ['name' => 'SSL Certificate', 'status' => 'SUSPICIOUS', 'points' => 15, 'message' => 'Website does not use HTTPS (no SSL encryption)'],
        ['name' => 'URL Structure', 'status' => 'SAFE', 'points' => 0, 'message' => 'No issues detected for this check.'],
    ]);

    // not high risk, so there is no featured card: the one warning spans both columns on a tablet and the full row on a desktop
    expect($html)->toContain('lg:col-span-6')->toContain('sm:col-span-2');
});

test('the wording fixes in CheckExplainer hold', function () {
    expect(\App\Services\CheckExplainer::plain('Number is a VoIP/internet-based line, inexpensive'))->toBe('Number is an internet phone line, inexpensive')
        ->and(\App\Services\CheckExplainer::plain('the submitted domain is on PhishCore\'s official-domain list'))->toBe('the submitted domain is on PhishCore\'s official-domain list')
        ->and(\App\Services\CheckExplainer::plain('No prior reports found for "example.com" on PhishCore'))->toBe('No one has reported "example.com" to PhishCore before');
});