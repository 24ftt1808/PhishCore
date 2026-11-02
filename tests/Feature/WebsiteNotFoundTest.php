<?php

use App\Http\Controllers\ScanController;
use App\Models\Analysis;
use App\Models\Report;
use App\Services\AnalysisEngine;

/**
 * A link scan must actually be about a website. "https://" in front of random text is refused at the form,
 * and an address that leads nowhere is shown as "Website not found", never "This appears safe".
 */
function engineWithSite(string $availability, int $blacklistPoints = 0): AnalysisEngine
{
    return new class($availability, $blacklistPoints) extends AnalysisEngine
    {
        public function __construct(private string $availability, private int $blacklistPoints) {}

        private function nothing(): array
        {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Unavailable.'], 'unavailable' => true];
        }

        public function checkDomainAge(string $urlOrHost): array { return $this->nothing() + ['domain_age_days' => null]; }
        public function checkSslCertificate(string $url): array { return $this->nothing(); }
        public function checkBlacklist(string $url): array
        {
            return $this->blacklistPoints > 0
                ? ['flagged' => true, 'points' => $this->blacklistPoints, 'reasons' => ['This exact address is on OpenPhish']]
                : ['flagged' => false, 'points' => 0, 'reasons' => ['Not on any list.']];
        }
        public function checkVirusTotal(string $url): array { return $this->nothing() + ['raw' => null, 'total' => 0, 'malicious' => 0]; }
        public function checkIpReputation(string $url): array { return $this->nothing() + ['ip' => null, 'summary' => null, 'country' => null]; }
        public function checkRedirectChain(string $url): array { return $this->nothing() + ['chain' => []]; }
        public function checkPageContent(string $url): array { return $this->nothing(); }
        public function checkSiteAvailability(string $url): array { return ['status_label' => $this->availability, 'reasons' => ['Status.']]; }
    };
}

test('an address that leads nowhere is "review" with a Website Not Found note, not clean', function () {
    $result = engineWithSite('OFFLINE')->analyze('url', 'https://stingy-typeface.biz');

    expect($result['verdict'])->toBe('review')
        ->and($result['checks'][0]['name'])->toBe('Website Not Found')
        ->and($result['checks'][0]['points'])->toBe(0);
});

test('a live website with nothing wrong is still clean', function () {
    $result = engineWithSite('LIVE')->analyze('url', 'https://stingy-typeface.biz');

    expect($result['verdict'])->toBe('clean')
        ->and(collect($result['checks'])->pluck('name'))->not->toContain('Website Not Found');
});

test('a taken-down site that is on a phishing list keeps its warning', function () {
    $result = engineWithSite('OFFLINE', 60)->analyze('url', 'https://stingy-typeface.biz');

    expect($result['verdict'])->toBe('phishing')
        ->and(collect($result['checks'])->pluck('name'))->not->toContain('Website Not Found');
});

test('the result page says "Website not found" instead of "This appears safe"', function () {
    $report = Report::factory()->create(['type' => 'url', 'url' => 'https://stingy-typeface.biz', 'status' => 'completed', 'user_id' => null]);
    Analysis::factory()->create([
        'report_id' => $report->id,
        'verdict' => 'review',
        'risk_score' => 0,
        'flags' => [['name' => 'Website Not Found', 'status' => 'INFO', 'message' => 'Leads nowhere.', 'points' => 0]],
    ]);
    session()->put('guest_report_ids', [$report->id]);

    $this->withoutVite()->get(route('scan.show', $report))
        ->assertOk()
        ->assertSee('Website not found')
        ->assertDontSee('This appears safe');
});

test('random text with https:// in front is not accepted as a web address', function (string $url) {
    expect(ScanController::looksLikeWebAddress($url))->toBeFalse();
})->with(['https://hello', 'https://asdf-qwer', 'https://hello.123', 'https://-bad-.com', 'https://a..com', 'https://']);

test('real web addresses are accepted', function (string $url) {
    expect(ScanController::looksLikeWebAddress($url))->toBeTrue();
})->with(['https://bibd.com.bn/login', 'http://www.gov.bn', 'https://stingy-typeface.biz', 'https://xn--bcher-kva.de', 'https://bücher.de', 'http://203.0.113.5/pay', 'https://sub.domain.co.uk/a?b=c']);

test('the scan form refuses an address that is not a web address', function () {
    $this->withoutVite()->post(route('scan.store'), ['type' => 'url', 'url' => 'https://hello'])
        ->assertSessionHasErrors('url');

    expect(Report::count())->toBe(0);
});