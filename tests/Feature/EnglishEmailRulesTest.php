<?php

use App\Services\AnalysisEngine;

/**
 * English email rules (sender, wording and brand-vs-sender, no network or AI checks).
 * The examples are written for this test, modelled on common phishing seen in public
 * collections, so no real phishing email is stored in the repository.
 */
beforeEach(function () {
    $this->engine = new AnalysisEngine;
});

function emailScore(AnalysisEngine $engine, string $from, string $subject, string $body): int
{
    return $engine->scoreEmailRules($from, $subject, $body)['score'];
}

test('common English phishing emails are flagged', function () {
    $scams = [
        ['security@wallet-support-team.top', 'Your wallet access is temporarily suspended', 'Your wallet has been temporarily suspended for security reasons. Complete your verification before Friday or you will lose your assets. Click the button below to verify your recovery phrase.'],
        ['admin@mailbox-quota.online', 'Password Expired', 'The password to your mailbox has expired. Keep using your current password by clicking below, or your account will be locked within 24 hours.'],
        ['billing@cloud-storage-alerts.com', 'Your Cloud Storage Is Full', 'Your storage is 99% full. Your photos and videos will be permanently deleted. Update your payment method now to restore your access.'],
        ['director.remit@gmail.com', 'ATTENTION DEAR BENEFICIARY', 'Attention dear beneficiary, I am the director of the foreign remittance department. Your compensation fund of $4.5 million is ready for transfer. Reconfirm your home address and phone number urgently.'],
        ['orders@secure-receipts.net', 'Order Confirmation #88213', 'Your order has been successfully paid and processed. Amount: $499.99 for Total Protection renewal. If you did not authorise this transaction, call our support line at 1 808 555 0142 to cancel.'],
        ['notice@acc-alert.info', 'Important notice', 'Yоur wаllet hаs been tempоrаrily suspеndеd. Plеаsе vеrify yоur аccоunt tо аvоid lоss оf аssеts.'],
    ];

    foreach ($scams as [$from, $subject, $body]) {
        expect(emailScore($this->engine, $from, $subject, $body))->toBeGreaterThanOrEqual(25, $subject);
    }
});

test('ordinary emails that only mention a brand are not treated as brand impersonation', function () {
    $mailingList = 'Has anyone tried the new Outlook build? On my machine Microsoft changed the way the inbox sorts threads, and Google Calendar no longer syncs. Patches welcome.';

    expect(emailScore($this->engine, 'dev@lists.example.org', 'Re: sorting threads', $mailingList))->toBeLessThan(25);
});

test('the same brand claimed up front with a lure still counts against a wrong sender', function () {
    $fake = $this->engine->scoreEmailRules('alerts@acc-verify.top', 'Microsoft account unusual sign-in activity', 'Microsoft account. We detected unusual sign-in activity. Verify your account now to avoid suspension.');

    expect($fake['score'])->toBeGreaterThanOrEqual(60)
        ->and(implode(' ', $fake['reasons']))->toContain('does not belong to microsoft');
});

test('normal newsletters and office email stay clean', function () {
    $normal = [
        ['news@weekly.example.com', 'This week in tech', 'Read the full story online. Click here to read more. Last chance to register for our free webinar next week.'],
        ['finance@company.example.com', 'Expense claims', 'Please submit your expense claims before the end of the month. Reimbursements are processed within 10 days.'],
        ['it@company.example.com', 'Planned maintenance', 'The VPN will be unavailable on Saturday from 9 am. Please save your work before you log off.'],
        ['friend@gmail.com', 'Dinner', 'Can you transfer me $20 for dinner last night? Thanks!'],
    ];

    foreach ($normal as [$from, $subject, $body]) {
        expect(emailScore($this->engine, $from, $subject, $body))->toBeLessThan(25, $subject);
    }
});

test('hidden characters and spaced-out letters are seen through and flagged', function () {
    $spaced = $this->engine->scoreEmailRules('promo@loyal-rewards.top', 'Important', 'D e a r c u s t o m e r , y o u h a v e b e e n s e l e c t e d for our exclusive reward. Claim your gift now.');
    $hidden = $this->engine->scoreEmailRules('billing@off365-renew.top', 'Payment failed', "Your Of\u{00AD}fice365 renewal fai\u{00AD}led. Update your payment method to avoid suspension.");

    expect($spaced['score'])->toBeGreaterThanOrEqual(25)
        ->and($hidden['score'])->toBeGreaterThanOrEqual(25)
        ->and(implode(' ', $hidden['reasons']))->toContain('disguised');
});