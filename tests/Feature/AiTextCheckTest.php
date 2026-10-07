<?php

use App\Services\AiTextCheck;
use App\Services\AnalysisEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function aiReply(string $verdict, int $confidence, string $reason = 'Because.'): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode(compact('verdict', 'confidence', 'reason'))]]]]]];
}

function aiOn(): void
{
    config(['services.ai_text.enabled' => true, 'services.ai_text.key' => 'test-key', 'services.ai_text.model' => 'gemini-2.5-flash']);
    Cache::flush();
}

test('the AI check is off by default and makes no request', function () {
    Http::fake();
    config(['services.ai_text.enabled' => false, 'services.ai_text.key' => 'test-key']);

    $result = app(AiTextCheck::class)->assess('You have won a prize');

    expect($result['available'])->toBeFalse()->and($result['points'])->toBe(0);
    Http::assertNothingSent();
});

test('a scam verdict adds points by confidence, a legitimate one adds none', function () {
    $ai = new AiTextCheck;

    expect($ai->toResult('scam', 90, 'x')['points'])->toBe(35);
    expect($ai->toResult('scam', 50, 'x')['points'])->toBe(20);
    expect($ai->toResult('suspicious', 80, 'x')['points'])->toBe(15);
    expect($ai->toResult('legitimate', 99, 'x')['points'])->toBe(0);
    expect($ai->toResult('nonsense', 99, 'x')['points'])->toBe(0);
});

test('a good reply is read, sent with the key in a header, and cached', function () {
    aiOn();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(aiReply('scam', 92, 'Fake lottery win.'))]);

    $first = app(AiTextCheck::class)->assess('Congratulations you won 12,000,000 dollars, send your bank details');
    $second = app(AiTextCheck::class)->assess('Congratulations you won 12,000,000 dollars, send your bank details');

    expect($first['available'])->toBeTrue()->and($first['points'])->toBe(35)->and($second['points'])->toBe(35);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key') && str_contains($request->url(), 'gemini-2.5-flash:generateContent'));
});

test('errors, rate limits and unreadable replies are reported as unavailable with 0 points', function () {
    aiOn();

    Http::fake(['*' => Http::response([], 429)]);
    $limited = app(AiTextCheck::class)->assess('one message');
    expect($limited['available'])->toBeFalse()->and($limited['points'])->toBe(0)->and($limited['reason'])->toContain('busy');

    Cache::flush();
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'not json']]]]]])]);
    expect(app(AiTextCheck::class)->assess('another message')['available'])->toBeFalse();

    Cache::flush();
    Http::fake(['*' => Http::response([], 500)]);
    expect(app(AiTextCheck::class)->assess('third message')['points'])->toBe(0);
});

test('the reply parser accepts fenced json and rejects unknown verdicts', function () {
    $ai = new AiTextCheck;

    expect($ai->parse("```json\n{\"verdict\":\"scam\",\"confidence\":80,\"reason\":\"r\"}\n```")['verdict'])->toBe('scam');
    expect($ai->parse('{"verdict":"safe","confidence":80,"reason":"r"}'))->toBeNull();
    expect($ai->parse('hello'))->toBeNull();
});

test('the email scan adds the AI points and shows an AI Message Review check', function () {
    aiOn();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(aiReply('scam', 90, 'Looks like a fake job offer.'))]);

    $result = app(AnalysisEngine::class)->analyze('email', null, 'someone@example.com', null, null, null, 'Hello', 'A very unusual new wording the rules do not know about.');
    $check = collect($result['checks'])->firstWhere('name', 'AI Message Review');

    expect($check)->not->toBeNull()->and($check['points'])->toBe(35)->and($check['status'])->toBe('HIGH RISK');
    expect($result['risk_score'])->toBeGreaterThanOrEqual(35);
});

test('the email scan still works when the AI service fails', function () {
    aiOn();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);

    $result = app(AnalysisEngine::class)->analyze('email', null, 'someone@example.com', null, null, null, 'Hello', 'See you at the meeting tomorrow.');
    $check = collect($result['checks'])->firstWhere('name', 'AI Message Review');

    expect($check['status'])->toBe('UNKNOWN')->and($check['points'])->toBe(0);
});

test('with the AI check off, the email scan has no AI check at all', function () {
    config(['services.ai_text.enabled' => false]);
    Http::fake();

    $result = app(AnalysisEngine::class)->analyze('email', null, 'someone@example.com', null, null, null, 'Hello', 'See you at the meeting tomorrow.');

    expect(collect($result['checks'])->firstWhere('name', 'AI Message Review'))->toBeNull();
    Http::assertNothingSent();
});