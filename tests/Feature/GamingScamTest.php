<?php

use App\Services\AnalysisEngine;

function gamingContent(string $text, bool $forPage = false): array
{
    $method = new ReflectionMethod(AnalysisEngine::class, 'checkContentPatterns');

    return $method->invoke(new AnalysisEngine, $text, $forPage);
}

function gamingLureFound(array $result): bool
{
    return collect($result['reasons'])->contains(fn ($reason) => str_contains($reason, 'Game giveaway'));
}

dataset('game scam messages', [
    'free robux' => 'Claim your free Robux now! Login with Roblox to get 10000 robux',
    'free fire diamonds, malay' => 'Dapat diamond percuma Free Fire! Tuntut sekarang',
    'login to claim, malay' => 'Dapat diamond percuma, login guna Steam untuk tuntut',
    'steam ban warning' => 'Your Steam account will be permanently banned unless you verify',
    'ban warning, malay' => 'Akaun game anda akan diban, sahkan sekarang',
    'ban warning, manglish' => 'Kena ban la bro, klik sini untuk appeal',
    'generator' => 'V-Bucks generator 2026 working, no survey',
    'lucky draw, malay' => 'Cabutan bertuah: menang skin MLBB percuma',
    'cheap top-up' => 'Topup murah diamond ML, bonus diamonds bila topup',
    'trade offer' => 'Steam trade offer from a friend, accept now',
    'nitro giveaway' => 'Free Discord Nitro giveaway, connect your Discord account to claim',
    'gift card generator' => 'Free steam wallet gift card generator',
]);

dataset('normal game and everyday messages', [
    'delivery notice for a legit top-up' => 'Your Free Fire diamond top-up order has been delivered',
    'steam receipt' => 'Welcome to Steam. Your purchase receipt is attached',
    'roblox maintenance' => 'Roblox maintenance tonight from 2am to 4am',
    'meeting' => 'The team meeting is at 3pm, please bring your laptop',
    'free shipping' => 'Free shipping on orders above $50',
    'skin care' => 'We offer free skin care advice for your face',
    'school minecraft' => 'Minecraft Education edition will be used in class this week',
]);

test('game scam wording in English and Malay is picked up in a message', function (string $text) {
    expect(gamingLureFound(gamingContent($text)))->toBeTrue();
})->with('game scam messages');

test('normal game and everyday messages are not flagged as a game lure', function (string $text) {
    expect(gamingLureFound(gamingContent($text)))->toBeFalse();
})->with('normal game and everyday messages');

test('a safety warning that quotes the scam wording is not scored as the scam', function () {
    $result = gamingContent('Beware of fake free robux generators. Never share your password with anyone.');

    expect(gamingLureFound($result))->toBeFalse();
});

test('a game lure on its own stays below the suspicious line', function () {
    $result = gamingContent('Free Robux for all Roblox players this month.');

    expect(gamingLureFound($result))->toBeTrue()
        ->and($result['points'])->toBeLessThan(25);
});

test('a game lure together with a request for a login scores as suspicious', function () {
    $result = gamingContent('Claim your free Robux now. Verify your account and enter your password to receive it.');

    expect(gamingLureFound($result))->toBeTrue()
        ->and($result['points'])->toBeGreaterThanOrEqual(25);
});

test('on a web page only blatant generator wording counts', function () {
    expect(gamingLureFound(gamingContent('Free Robux generator, no human verification', true)))->toBeTrue();
    expect(gamingLureFound(gamingContent('Gift cards and free shipping on skins and bundles in our store', true)))->toBeFalse();
});

test('a game lure with a link to the publisher is not penalised, but the same lure with a fake site is', function () {
    $content = gamingContent('Free Robux giveaway this weekend. Claim your free robux now.');
    $method = new ReflectionMethod(AnalysisEngine::class, 'checkLinkInPressureMessage');
    $engine = new AnalysisEngine;

    expect($method->invoke($engine, $content, 'https://www.roblox.com/giveaway')['points'])->toBe(0);
    expect($method->invoke($engine, $content, 'https://robux-claim-now.top/login')['points'])->toBe(15);
});

test('lookalike gaming addresses are flagged and the real ones are not', function (string $url, bool $flagged) {
    $reasons = implode(' ', (new AnalysisEngine)->checkUrlSyntax($url)['reasons']);

    expect(str_contains($reasons, 'brand name') || str_contains($reasons, 'mimic'))->toBe($flagged);
})->with([
    'fake roblox login' => ['https://roblox-login-verify.top/login', true],
    'fake discord gift' => ['https://discord-nitro-gift.com/claim', true],
    'fake steam gift' => ['https://steam-gift.top/free', true],
    'fake steam wallet' => ['https://steamwallet-login.xyz/', true],
    'fake epic games' => ['https://epicgames-free-vbucks.net/', true],
    'real roblox' => ['https://www.roblox.com/games', false],
    'real steam store' => ['https://store.steampowered.com/', false],
    'real steam community' => ['https://steamcommunity.com/id/someone', false],
    'real discord invite' => ['https://discord.gg/abcdef', false],
    'real epic games' => ['https://www.epicgames.com/store', false],
    'real xbox' => ['https://www.xbox.com/en-US/games', false],
    'a word that only contains steam' => ['https://steamed-buns-shop.com/menu', false],
]);

test('a page that warns about game scams is not treated as one', function () {
    $result = gamingContent('Scam alert: beware of free Robux generator sites that ask for your password.', true);

    expect(gamingLureFound($result))->toBeFalse();
});

test('a friend announcing a giveaway on their own channel is not flagged as a game scam', function () {
    $content = gamingContent('Channel aku buat giveaway Steam gift card $10 minggu depan. Join Discord server kami kalau berminat, nanti aku umum pemenang di live.');

    expect(gamingLureFound($content))->toBeFalse()
        ->and($content['points'])->toBeLessThan(25);
});
