<?php

use App\Services\AnalysisEngine;

/**
 * "One weak signal is not enough." Ordinary wording (a password reset, an invoice, a welcome email) uses the same
 * tactics scams copy: urgency, a request to verify, a link to click. Those tactics alone must stay below the
 * suspicious line, while scam-specific wording, or wording next to other evidence, still counts in full.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

test('ordinary account emails stay below the suspicious line, however many everyday tactics they use', function (string $subject, string $body) {
    $result = $this->engine->scoreEmailRules('no-reply@accounts.example.com', $subject, $body);

    expect($result['score'])->toBeLessThan(25);
})->with([
    'password reset with a deadline and a link' => ['Reset your password', 'We received a request to reset your password. This link expires in 24 hours. Click the link below to set a new password: https://accounts.example.com/reset?t=abc'],
    'welcome and verify' => ['Welcome! Please verify your email', 'Thanks for signing up. Please confirm your email address by clicking the button below to activate your account.'],
    'invoice reminder' => ['Your invoice is ready', 'Your invoice for October is attached. Payment is due within 14 days. Log in to your account to view or pay.'],
]);

test('the same everyday wording still counts when a scam-specific tactic comes with it', function () {
    $result = $this->engine->scoreEmailRules('support@paypa1-secure.example', 'Urgent: verify your account', 'Your account has been suspended. Verify your account within 24 hours or it will be closed. Click here to log in.');

    expect($result['score'])->toBeGreaterThanOrEqual(60);
});

test('a scam-specific request is not softened by the rule', function () {
    $result = $this->engine->scoreMessageRules('Hi, I sent my verification code to your number by mistake. Please send it back to me urgently.');

    expect($result['score'])->toBeGreaterThanOrEqual(25);
});

test('a lone weak signal is held below the suspicious line, but a strong one is not', function () {
    $weak = new ReflectionMethod($this->engine, 'requireCorroboration');
    $weak->setAccessible(true);

    expect($weak->invoke($this->engine, 30, ['content' => 30]))->toBe(24)
        ->and($weak->invoke($this->engine, 30, ['content' => 15, 'brand' => 15]))->toBe(30)
        ->and($weak->invoke($this->engine, 30, ['content' => 30], 1))->toBe(30)
        ->and($weak->invoke($this->engine, 40, ['content' => 40]))->toBe(40);
});

test('an email that delivers a one-time code is not a request for a code', function (string $body) {
    $result = $this->engine->scoreEmailRules('no-reply@accounts.example.com', 'Your code', $body);

    expect($result['score'])->toBeLessThan(25);
})->with([
    'Your verification code is 482913. It is valid for 5 minutes.',
    'Your one-time password is 123456. Never share your OTP with anyone.',
    'Security code: 998877. Enter it on the sign-in page to continue.',
]);

test('asking for the code to be handed over is still flagged', function (string $text) {
    expect($this->engine->scoreMessageRules($text)['score'])->toBeGreaterThanOrEqual(25);
})->with([
    'Your OTP is 123456. Reply with the code so we can unblock your account urgently.',
    'Your verification code is 482913. Please send me the code to confirm it is you.',
    'Hi, I sent my verification code to your number by mistake. Please send it back to me urgently.',
]);