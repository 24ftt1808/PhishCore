<?php

use App\Models\Analysis;
use App\Models\Report;
use Illuminate\Support\Facades\View;

/*
 * "What we checked" in the PDF report uses the same headlines and the same "looked at / found" lines as the
 * result page (both come from App\Support\CheckPresenter).
 */
function pdfChecksFor(array $flags): array
{
    $report = Report::factory()->create(['url' => 'http://example.test/', 'status' => 'completed']);
    Analysis::factory()->create(['report_id' => $report->id, 'verdict' => 'phishing', 'risk_score' => 70, 'flags' => $flags]);

    $holder = new stdClass;
    View::composer('scan.pdf', function ($view) use ($holder) {
        $holder->data = $view->getData();
    });

    $response = test()->withSession(['guest_report_ids' => [$report->id]])->get(route('scan.pdf', $report));
    $response->assertOk();

    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');

    return collect($holder->data['groups'])->keyBy('key')->all();
}

function pdfSampleFlags(): array
{
    return [
        ['name' => 'SSL Certificate', 'status' => 'SUSPICIOUS', 'points' => 20, 'message' => 'Website does not use HTTPS (no SSL encryption).'],
        ['name' => 'Page Content Analysis', 'status' => 'HIGH RISK', 'points' => 50, 'message' => 'Page contains a password input field. Page content references "Adobe" branding, but the domain ("dashboard.adobeexperience.com.br") does not belong to adobe — likely brand impersonation. Server responds as: nginx.'],
        ['name' => 'URL Structure', 'status' => 'SAFE', 'points' => 0, 'message' => 'No issues detected for this check.'],
        ['name' => 'Redirect Chain', 'status' => 'SAFE', 'points' => 0, 'message' => 'URL redirects to example.com; both are well-known, widely visited websites.'],
        ['name' => 'Domain Age', 'status' => 'UNKNOWN', 'points' => 0, 'message' => 'WHOIS data unavailable for this domain.'],
    ];
}

test('the pdf groups carry the same titles as the result page', function () {
    $groups = pdfChecksFor(pdfSampleFlags());

    expect($groups['warn']['title'])->toBe('Needs attention')
        ->and($groups['ok']['title'])->toBe('Passed')
        ->and($groups['info']['title'])->toBe('Unavailable or for information');
});

test('a warning in the pdf has a headline, the rest of the sentence and its evidence', function () {
    $warn = collect(pdfChecksFor(pdfSampleFlags())['warn']['checks'])->keyBy('name');

    expect($warn['SSL Certificate']['headline'])->toBe('No secure connection (HTTPS)')
        ->and($warn['SSL Certificate']['body'])->toBe('Information you enter could be intercepted.')
        ->and($warn['Page Content Analysis']['headline'])->toBe('Password field on a page that appears to imitate Adobe')
        ->and(collect($warn['Page Content Analysis']['facts'])->pluck('label')->all())->toContain('Password field', 'Page claims to be', 'Actual website', 'Server responds as');
});

test('a passed check in the pdf says what it looked at and what it found', function () {
    $ok = collect(pdfChecksFor(pdfSampleFlags())['ok']['checks'])->keyBy('name');

    expect($ok['URL Structure']['looked'])->toContain('Tricks in how the link is written')
        ->and($ok['URL Structure']['found'])->toBe('')
        ->and($ok['Redirect Chain']['found'])->toContain('both are well-known');
});

test('an unavailable check in the pdf shows its result', function () {
    $info = collect(pdfChecksFor(pdfSampleFlags())['info']['checks'])->keyBy('name');

    expect($info['Domain Age']['looked'])->toContain('How long ago the website was created')
        ->and($info['Domain Age']['found'])->toContain('could not find out');
});

test('a long sentence with no good place to cut stays whole in the pdf', function () {
    $message = 'It asks you to do something (pay, share a code or details, or contact someone) while putting pressure on you, which is the core pattern of a scam.';
    $card = pdfChecksFor([['name' => 'Message Content / Behavior Patterns', 'status' => 'HIGH RISK', 'points' => 40, 'message' => $message]])['warn']['checks'][0];

    expect($card['headline'])->toBeNull()
        ->and($card['body'])->toBe($message);
});