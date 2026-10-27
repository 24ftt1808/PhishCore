<?php

use App\Support\VerdictWording;

$aiFlagged = [['name' => 'AI Page Review', 'status' => 'SUSPICIOUS', 'points' => 8, 'message' => 'The AI reader thinks this page looks suspicious.']];
$aiClean = [['name' => 'AI Page Review', 'status' => 'SAFE', 'points' => 0, 'message' => 'Nothing suspicious.']];
$aiSkipped = [['name' => 'AI Page Review', 'status' => 'UNKNOWN', 'points' => 0, 'message' => 'Could not run.']];

test('a clean link scan that the AI page review flagged is shown as needing a review', function () use ($aiFlagged) {
    expect(VerdictWording::shown('clean', 'url', $aiFlagged))->toBe('review');
});

test('everything else keeps the stored verdict', function (string $verdict, string $type, array $checks, string $expected) {
    expect(VerdictWording::shown($verdict, $type, $checks))->toBe($expected);
})->with([
    'clean, AI found nothing' => ['clean', 'url', $aiClean, 'clean'],
    'clean, AI could not run' => ['clean', 'url', $aiSkipped, 'clean'],
    'clean, no AI check at all' => ['clean', 'url', [['name' => 'SSL Certificate', 'status' => 'SAFE', 'points' => 0]], 'clean'],
    'clean, nothing to look at' => ['clean', 'url', [], 'clean'],
    'suspicious stays suspicious' => ['suspicious', 'url', $aiFlagged, 'suspicious'],
    'phishing stays phishing' => ['phishing', 'url', $aiFlagged, 'phishing'],
    'review stays review' => ['review', 'url', $aiFlagged, 'review'],
    'an email is judged on its own wording' => ['clean', 'email', [['name' => 'AI Message Review', 'status' => 'SUSPICIOUS', 'points' => 15]], 'clean'],
    'a screenshot is left alone' => ['clean', 'screenshot', $aiFlagged, 'clean'],
]);

test('a missing verdict is treated as clean and odd check rows never break it', function () use ($aiFlagged) {
    expect(VerdictWording::shown(null, 'url', $aiFlagged))->toBe('review')
        ->and(VerdictWording::shown(null, 'url', []))->toBe('')
        ->and(VerdictWording::aiFlagged([null, 'text', 5, ['name' => 'AI Page Review']]))->toBeFalse();
});

test('the line shown with it names the AI Page Review', function () {
    expect(VerdictWording::CAVEAT)->toContain('AI review')->toContain('AI Page Review');
});