<?php

namespace App\Support;

use App\Services\CheckExplainer;
use Illuminate\Support\Str;

/**
 * Turns the reading of a page that was loaded in the urlscan.io sandbox into the short note shown under the page
 * preview. Display only: nothing here is a check, and nothing here touches the score or the verdict.
 */
final class PageNote
{
    /** The lines the engine adds to every clean or informational reading: they say nothing about this page. */
    private const BOILERPLATE = ['Server responds as', 'No login forms, sensitive-data requests'];

    /**
     * @param  array  $result  what AnalysisEngine::inspectPageHtml() returned for the loaded page
     * @param  string|null  $earlier  how the scan itself related to this page: 'unread' (the scan could not read it)
     *                                or 'unopened' (the scan never opened this link, as with a link found in an email)
     * @param  string|null  $finalHost  where the link ended up, when that is not the address that was scanned
     * @param  bool  $scanFoundConcern  the scan's own page checks (page content, AI page review) raised a concern, so a
     *                                  quiet reading of the loaded page must not read as "all clear"
     * @return array{tone: string, headline: string, lines: list<string>}
     */
    public static function build(array $result, ?string $earlier = null, ?string $finalHost = null, bool $scanFoundConcern = false): array
    {
        $findings = [];
        foreach ((array) ($result['reasons'] ?? []) as $reason) {
            if (! is_string($reason) || Str::startsWith($reason, self::BOILERPLATE)) {
                continue;
            }

            $findings[] = Str::limit(CheckExplainer::plain($reason), 240);
        }
        $findings = array_slice($findings, 0, 5);

        if (($result['points'] ?? 0) > 0) {
            $tone = 'warn';
            $headline = 'Warning signs on the page once it had loaded';
            $lines = $findings;
        } elseif ($result['has_sensitive_field'] ?? false) {
            $tone = 'info';
            $headline = 'The loaded page has a login or card form';
            $lines = $findings ?: ['A form asking for a password or other private details appears once the page has loaded.'];
        } elseif ($scanFoundConcern) {
            // The sandbox reading found nothing, but the scan's own reading of this page did. One quiet reading
            // must not cancel that out, so the note stays neutral and points to the checks.
            $tone = 'info';
            $headline = 'No login form found on the loaded page';
            $lines = ['This does not make the page safe. The scan itself found warning signs on this page, so please read the checks below.'];
        } else {
            $tone = 'ok';
            $headline = 'No login form or warning signs on the loaded page';
            $lines = ['The page was opened in a sandbox and checked for login forms, pressure wording, brand imitation and hidden tricks.'];
        }

        if ($finalHost !== null) {
            $lines[] = 'The link led to '.$finalHost.'.';
        }

        if ($earlier === 'unread') {
            $lines[] = 'The scan itself could not read this page properly, so this is the first look at what is on it.';
        } elseif ($earlier === 'unopened') {
            $lines[] = 'The scan itself did not open this link, so this is the first look at what is on the page.';
        }

        return ['tone' => $tone, 'headline' => $headline, 'lines' => array_values($lines)];
    }
}