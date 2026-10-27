<?php

use App\Support\PageNote;

test('warning signs make a warning note with the findings as lines', function () {
    $note = PageNote::build([
        'points' => 30,
        'has_sensitive_field' => true,
        'reasons' => ['Page contains a password input field', 'The content refers to "Adobe" but the website does not belong to adobe', 'Server responds as: nginx'],
    ]);

    expect($note['tone'])->toBe('warn')
        ->and($note['headline'])->toBe('Warning signs on the page once it had loaded')
        ->and($note['lines'])->toHaveCount(2)
        ->and(implode(' ', $note['lines']))->not->toContain('Server responds');
});

test('a form with nothing else wrong is information, not a warning', function () {
    $note = PageNote::build(['points' => 0, 'has_sensitive_field' => true, 'reasons' => ['Page contains a password input field', 'Server responds as: nginx']]);

    expect($note['tone'])->toBe('info')->and($note['lines'])->toHaveCount(1);

    $bare = PageNote::build(['points' => 0, 'has_sensitive_field' => true, 'reasons' => []]);
    expect($bare['lines'])->toHaveCount(1)->and($bare['lines'][0])->toContain('form');
});

test('a clean page gets a calm note that explains what was looked for', function () {
    $note = PageNote::build([
        'points' => 0,
        'has_sensitive_field' => false,
        'reasons' => ['No login forms, sensitive-data requests, phishing-style language, brand imitation, hidden iframes, or obfuscated scripts detected in page content', 'Server responds as: nginx'],
    ]);

    expect($note['tone'])->toBe('ok')
        ->and($note['headline'])->toBe('No login form or warning signs on the loaded page')
        ->and($note['lines'])->toHaveCount(1)
        ->and($note['lines'][0])->toContain('sandbox');
});

test('the note tells whether the scan itself had looked at the page', function () {
    $clean = ['points' => 0, 'has_sensitive_field' => false, 'reasons' => []];

    expect(implode(' ', PageNote::build($clean, 'unread')['lines']))->toContain('could not read this page properly')
        ->and(implode(' ', PageNote::build($clean, 'unopened')['lines']))->toContain('did not open this link')
        ->and(implode(' ', PageNote::build($clean, null)['lines']))->not->toContain('The scan itself');
});

test('where the link led is added when it differs', function () {
    $clean = ['points' => 0, 'has_sensitive_field' => false, 'reasons' => []];

    expect(PageNote::build($clean, null, 'landing.example')['lines'])->toContain('The link led to landing.example.')
        ->and(implode(' ', PageNote::build($clean)['lines']))->not->toContain('The link led to');
});

test('at most five findings are kept and each is kept short', function () {
    $reasons = array_map(fn ($i) => "Finding number {$i} ".str_repeat('long ', 80), range(1, 9));

    $note = PageNote::build(['points' => 10, 'has_sensitive_field' => false, 'reasons' => $reasons]);

    expect($note['lines'])->toHaveCount(5)
        ->and(max(array_map('strlen', $note['lines'])))->toBeLessThanOrEqual(245);
});

test('missing keys and odd values never break it', function () {
    expect(PageNote::build([])['tone'])->toBe('ok')
        ->and(PageNote::build(['reasons' => [123, null, ['x']], 'points' => 0])['tone'])->toBe('ok');
});


test('a quiet reading never reads as all clear when the scan itself found a concern on the page', function () {
    $quiet = ['points' => 0, 'has_sensitive_field' => false, 'reasons' => []];

    $note = PageNote::build($quiet, null, null, true);

    expect($note['tone'])->toBe('info')
        ->and($note['headline'])->toBe('No login form found on the loaded page')
        ->and($note['headline'])->not->toContain('warning signs')
        ->and(implode(' ', $note['lines']))->toContain('does not make the page safe');

    expect(PageNote::build($quiet, null, null, false)['tone'])->toBe('ok');
});

test('findings and forms on the loaded page are reported the same way whatever the scan found', function () {
    $warn = PageNote::build(['points' => 20, 'has_sensitive_field' => false, 'reasons' => ['Page text contains phishing-style lure language']], null, null, true);
    $form = PageNote::build(['points' => 0, 'has_sensitive_field' => true, 'reasons' => []], null, null, true);

    expect($warn['tone'])->toBe('warn')->and($form['headline'])->toBe('The loaded page has a login or card form');
});