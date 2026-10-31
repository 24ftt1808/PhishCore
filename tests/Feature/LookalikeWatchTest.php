<?php

use App\Models\LookalikeDomain;
use App\Models\LookalikeWatchRun;
use App\Models\User;
use App\Notifications\LookalikeWatchNotice;
use App\Services\AnalysisEngine;
use App\Services\LookalikeWatch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

/**
 * Look-alike Watch: new websites whose names copy a Brunei brand, read from the public certificate logs (crt.sh).
 */
beforeEach(function () {
    Sleep::fake();
    $this->withoutVite();
    // Never reach the real crt.sh or WhoisDS from a test: an unfaked address would really be downloaded.
    Http::preventStrayRequests();
});

function lookalikeTeamMember(): User
{
    return User::factory()->create(['is_team_member' => true]);
}

/** A crt.sh answer: one certificate per name, issued the given number of days ago. */
function crtShAnswer(array $namesByDaysAgo): array
{
    $rows = [];
    foreach ($namesByDaysAgo as $name => $daysAgo) {
        $rows[] = ['common_name' => $name, 'name_value' => $name."\nwww.".$name, 'not_before' => now()->subDays($daysAgo)->format('Y-m-d\TH:i:s')];
    }

    return $rows;
}

test('names that copy a Brunei brand are caught, including respelled ones', function (string $host, string $brand) {
    expect((new LookalikeWatch)->match($host))->toMatchArray(['brand' => $brand]);
})->with([
    ['bibd-login.top', 'bibd'],
    ['mybibd.xyz', 'bibd'],
    ['b1bd-online.com', 'bibd'],
    ['baidur1online.net', 'baiduri'],
    ['progresif-reward.shop', 'progresif'],
    ['dst-brunei-topup.com', 'dst'],
    ['bruneipost-track.top', 'post'],
    ['polis-brunei.online', 'polis'],
    ['bibd-pay.vercel.app', 'bibd'],
]);

test('genuine websites and everyday words are not listed', function (string $host) {
    expect((new LookalikeWatch)->match($host))->toBeNull();
})->with([
    'BIBD itself' => ['bibd.com.bn'],
    'a page on BIBD' => ['online.bibd.com.bn'],
    'DST itself' => ['dst.com.bn'],
    'a government site' => ['www.jpd.gov.bn'],
    'a word starting like TAIB' => ['taibah.edu.sa'],
    'a word containing dst' => ['dstore.com'],
    'brunei with no brand' => ['brunei-times.com'],
    'post without brunei' => ['postman.com'],
    'Progresif itself' => ['progresif.com'],
    'tarus inside another word' => ['cantarus.my'],
    'a parked German name' => ['staruss.de'],
    'the Russian town Tarusa' => ['arenda-spetstekhniki-tarusa.ru'],
    'Chinese pinyin that spells baiduri' => ['dongmanmanzheqiziqumanzhanbaiduriyu4.org.cn'],
    'letters and a number' => ['bdc8.lat'],
]);

test('the link check does not treat tarus inside another word, or a number read as a letter, as a Brunei brand', function (string $url) {
    expect(implode(' ', (new AnalysisEngine)->checkUrlSyntax($url)['reasons']))->not->toContain('Brunei brand');
})->with(['https://staruss.de', 'https://cantarus.my', 'https://bdc8.lat']);

test('the link check still catches real copies of tarus and BIBD', function (string $url) {
    expect(implode(' ', (new AnalysisEngine)->checkUrlSyntax($url)['reasons']))->toContain('Brunei brand');
})->with(['https://tarus-transfer-verify.top', 'https://b1bd-login.com']);

test('listings the tightened rules no longer call copies are removed on the next check', function () {
    Http::fake(['*' => Http::response([])]);
    LookalikeDomain::create(['domain' => 'cantarus.my', 'brand' => 'tarus']);
    LookalikeDomain::create(['domain' => 'bibd-login.top', 'brand' => 'bibd']);
    LookalikeDomain::create(['domain' => 'staruss.de', 'brand' => 'tarus', 'status' => 'dismissed']);

    (new LookalikeWatch)->run(1);

    expect(LookalikeDomain::pluck('domain')->sort()->values()->all())->toBe(['bibd-login.top', 'staruss.de']);
});

test('a check saves new look-alikes from recent certificates and tells the team', function () {
    Notification::fake();
    $team = lookalikeTeamMember();
    $regular = User::factory()->create();

    Http::fake(function ($request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return Http::response(match ($query['q'] ?? '') {
            '%bibd%' => crtShAnswer(['bibd-login.top' => 1, 'bibd.com.bn' => 2, 'bibd-old-scam.top' => 30]),
            '%brunei%' => crtShAnswer(['dst-brunei-topup.com' => 3, 'brunei-times.com' => 1]),
            default => [],
        });
    });

    $result = (new LookalikeWatch)->run(7);

    expect($result['ok'])->toBeTrue()
        ->and($result['new_found'])->toBe(2)
        ->and(LookalikeDomain::pluck('domain')->sort()->values()->all())->toBe(['bibd-login.top', 'dst-brunei-topup.com'])
        ->and(LookalikeDomain::where('domain', 'bibd-login.top')->value('brand'))->toBe('bibd')
        ->and(LookalikeWatchRun::latest('id')->first()->ok)->toBeTrue();

    Notification::assertSentTo($team, LookalikeWatchNotice::class);
    Notification::assertNotSentTo($regular, LookalikeWatchNotice::class);

    // The next check does not list the same websites again or notify again.
    Notification::fake();
    expect((new LookalikeWatch)->run(7)['new_found'])->toBe(0)
        ->and(LookalikeDomain::count())->toBe(2);
    Notification::assertNothingSent();
});

test('the daily list of newly registered domains is read too, so the watch works when crt.sh is busy', function () {
    $zipFile = tempnam(sys_get_temp_dir(), 'nrdtest');
    $zip = new ZipArchive;
    $zip->open($zipFile, ZipArchive::OVERWRITE);
    $zip->addFromString('domain-names.txt', "cheap-flights.com\nbaiduri-rewards.shop\nbibd.com.bn\nmy-garden.net\nposbrunei-delivery.top\n");
    $zip->close();
    $zipBytes = file_get_contents($zipFile);
    @unlink($zipFile);

    $yesterday = base64_encode(now()->subDay()->format('Y-m-d').'.zip');
    Http::fake([
        'crt.sh/*' => Http::response([]),
        "www.whoisds.com/whois-database/newly-registered-domains/{$yesterday}/nrd" => Http::response($zipBytes),
        'www.whoisds.com/*' => Http::response('Not found', 404),
    ]);

    $result = (new LookalikeWatch)->run(2);

    expect($result['new_found'])->toBe(2)
        ->and(LookalikeDomain::where('domain', 'baiduri-rewards.shop')->value('source'))->toBe('new-domains')
        ->and(LookalikeDomain::where('domain', 'posbrunei-delivery.top')->value('brand'))->toBe('pos')
        ->and($result['note'])->toContain('New-domain lists read: 5 domains over 1 day');
});

test('when the certificate logs are down, nothing breaks and the run is logged', function () {
    Http::fake([
        'crt.sh/*' => Http::response('Service Unavailable', 503),
        'www.whoisds.com/*' => Http::response('Service Unavailable', 503),
    ]);

    $result = (new LookalikeWatch)->run();

    expect($result['ok'])->toBeFalse()
        ->and(LookalikeDomain::count())->toBe(0)
        ->and(LookalikeWatchRun::latest('id')->first()->note)->toContain('did not answer');
});

test('the team page lists look-alikes as text, with scan and dismiss buttons', function () {
    LookalikeDomain::create(['domain' => 'bibd-login.top', 'brand' => 'bibd', 'certificate_issued_at' => now()->subDay()]);

    $this->actingAs(lookalikeTeamMember())->get(route('lookalike-watch.index'))
        ->assertOk()
        ->assertSee('Look-alike Watch')
        ->assertSee('bibd-login.top')
        ->assertSee('BIBD')
        ->assertSee('value="https://bibd-login.top"', false)
        ->assertDontSee('href="https://bibd-login.top', false)
        ->assertSee('Dismiss');
});

test('only team members and administrators can open the page', function () {
    $this->actingAs(User::factory()->create())->get(route('lookalike-watch.index'))->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('lookalike-watch.index'))->assertOk();
});

test('a dismissed website leaves the list and can be put back', function () {
    $team = lookalikeTeamMember();
    $site = LookalikeDomain::create(['domain' => 'bibdrewards.site', 'brand' => 'bibd']);

    $this->actingAs($team)->patch(route('lookalike-watch.dismiss', $site))->assertRedirect();
    expect($site->fresh()->status)->toBe('dismissed')->and($site->fresh()->dismissed_by)->toBe($team->id);
    $this->actingAs($team)->get(route('lookalike-watch.index')); // shows the "was dismissed" message once
    $this->actingAs($team)->get(route('lookalike-watch.index'))->assertDontSee('bibdrewards.site');

    $this->actingAs($team)->patch(route('lookalike-watch.restore', $site))->assertRedirect();
    expect($site->fresh()->status)->toBe('new');
});

test('Check now starts a check and says to refresh', function () {
    Http::fake(['crt.sh/*' => Http::response([]), 'www.whoisds.com/*' => Http::response('Not found', 404)]);

    $this->actingAs(lookalikeTeamMember())->post(route('lookalike-watch.check'))
        ->assertRedirect(route('lookalike-watch.index'))
        ->assertSessionHas('status', fn ($status) => str_contains($status, 'refresh'));
});

test('the bell notification opens the Look-alike Watch page', function () {
    $team = lookalikeTeamMember();
    $team->notify(new LookalikeWatchNotice(2, 'bibd-login.top'));
    $id = $team->notifications()->first()->id;

    $this->actingAs($team)->get(route('notifications.open', $id))->assertRedirect(route('lookalike-watch.index', absolute: false));
});

test('a scan of a watched website says the Look-alike Watch spotted it, without adding points', function () {
    LookalikeDomain::create(['domain' => 'bibd-login.top', 'brand' => 'bibd', 'certificate_issued_at' => now()->setDate(2026, 11, 1)]);
    $note = (new ReflectionMethod(AnalysisEngine::class, 'lookalikeWatchNote'))->invoke(new AnalysisEngine, 'https://www.bibd-login.top/verify');

    expect($note['name'])->toBe('Look-alike Watch')
        ->and($note['points'])->toBe(0)
        ->and($note['message'])->toContain('1 Nov 2026')->toContain('BIBD');
});