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
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push([], 429)
        ->push(['candidates' => [['content' => ['parts' => [['text' => 'not json']]]]]])
        ->push(['error' => ['message' => 'Model not found']], 404)
        ->push([], 500),
    ]);

    $limited = app(AiTextCheck::class)->assess('first message');
    expect($limited['available'])->toBeFalse()->and($limited['points'])->toBe(0)->and($limited['reason'])->toContain('busy');

    $unreadable = app(AiTextCheck::class)->assess('second message');
    expect($unreadable['available'])->toBeFalse()->and($unreadable['reason'])->toContain('could not be read');

    $missing = app(AiTextCheck::class)->assess('third message');
    expect($missing['available'])->toBeFalse()->and($missing['reason'])->toContain('404')->and($missing['reason'])->toContain('Model not found');

    $broken = app(AiTextCheck::class)->assess('fourth message');
    expect($broken['available'])->toBeFalse()->and($broken['points'])->toBe(0);
    Http::assertSentCount(4);
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

function aiPageCheck(string $host, array $content, int $otherPoints): ?array
{
    $method = new ReflectionMethod(AnalysisEngine::class, 'checkAiPage');

    return $method->invoke(app(AnalysisEngine::class), $host, $content, $otherPoints);
}

function aiPageContent(string $text = 'Verify your PayPal account now. Enter your password and card number to avoid suspension of your account.'): array
{
    return ['page_title' => 'Log in', 'page_text' => $text, 'has_sensitive_field' => true];
}

test('a web page verdict is worth less than the same verdict on a message', function () {
    $ai = new AiTextCheck;

    expect($ai->toResult('scam', 90, 'x', 'page')['points'])->toBe(25);
    expect($ai->toResult('scam', 50, 'x', 'page')['points'])->toBe(15);
    expect($ai->toResult('suspicious', 80, 'x', 'page')['points'])->toBe(8);
    expect($ai->toResult('legitimate', 99, 'x', 'page')['points'])->toBe(0);
    expect($ai->toResult('scam', 90, 'x', 'page')['reasons'][0])->toContain('this page');
    expect($ai->toResult('scam', 90, 'x')['points'])->toBe(35);
});

test('the AI page review sends the domain and page text and adds points', function () {
    aiOn();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(aiReply('scam', 95, 'Fake PayPal login on an unrelated domain.'))]);

    $result = aiPageCheck('paypal-secure-login.example', aiPageContent(), 10);

    expect($result['points'])->toBe(25)->and($result['verdict'])->toBe('scam');
    Http::assertSent(function ($request) {
        $text = (string) data_get($request->data(), 'contents.0.parts.0.text');

        return str_contains($text, 'WEB PAGE TO CHECK')
            && str_contains($text, 'Domain: paypal-secure-login.example')
            && str_contains($text, 'sensitive input field: yes');
    });
});

test('the AI page review is skipped when off, for official sites, for thin pages, and when the rules already say phishing', function () {
    Http::fake();

    config(['services.ai_text.enabled' => false]);
    expect(aiPageCheck('shady.example', aiPageContent(), 0))->toBeNull();

    aiOn();
    expect(aiPageCheck('www.paypal.com', aiPageContent(), 0))->toBeNull();
    expect(aiPageCheck('www.jpd.gov.bn', aiPageContent(), 0))->toBeNull();
    expect(aiPageCheck('shady.example', aiPageContent('Hello'), 0))->toBeNull();
    expect(aiPageCheck('shady.example', [], 0))->toBeNull();
    expect(aiPageCheck('shady.example', aiPageContent(), 60))->toBeNull();

    Http::assertNothingSent();
});

test('a page that the AI calls legitimate adds nothing, and a failing AI service never breaks the scan', function () {
    aiOn();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push(aiReply('legitimate', 90, 'A normal shop.'))
        ->push([], 500),
    ]);

    $shop = aiPageCheck('myshop.example', aiPageContent('Welcome to our shop. We sell shoes and bags. Free delivery over fifty dollars.'), 0);
    expect($shop['available'])->toBeTrue()->and($shop['points'])->toBe(0);

    $failed = aiPageCheck('myshop.example', aiPageContent('Welcome to our other shop. We sell hats and scarves. Free delivery over fifty dollars.'), 0);
    expect($failed['available'])->toBeFalse()->and($failed['points'])->toBe(0);
});

test('the scan page tells people about the outside AI service only when it is switched on', function () {
    $this->withoutVite();

    config(['services.ai_text.enabled' => false, 'services.ai_text.key' => 'test-key']);
    $this->get(route('scan.index'))->assertOk()->assertDontSee('Gemini');

    aiOn();
    $this->get(route('scan.index'))->assertOk()->assertSee('Gemini');
});

test('a result says it used the built-in rules only when the AI review could not run', function () {
    $this->withoutVite();
    $user = \App\Models\User::factory()->create();

    $make = function (array $flags) use ($user) {
        $scan = \App\Models\Report::factory()->create(['user_id' => $user->id, 'type' => 'email', 'sender_email' => 'a@b.test', 'status' => 'completed']);
        \App\Models\Analysis::factory()->create(['report_id' => $scan->id, 'verdict' => 'clean', 'risk_score' => 5, 'flags' => $flags]);

        return $scan;
    };

    $skipped = $make([['name' => 'AI Message Review', 'status' => 'UNKNOWN', 'message' => 'The AI service is busy right now (rate limit).', 'points' => 0]]);
    $ran = $make([['name' => 'AI Message Review', 'status' => 'SAFE', 'message' => 'Looks normal.', 'points' => 0]]);

    $this->actingAs($user)->get(route('scan.show', $skipped))->assertOk()->assertSee('built-in rules only');
    $this->actingAs($user)->get(route('scan.show', $ran))->assertOk()->assertDontSee('built-in rules only');
});