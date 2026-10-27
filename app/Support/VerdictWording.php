<?php

namespace App\Support;

/**
 * What a scan is called on the result page and in the PDF.
 *
 * A link scan whose rules found little wrong can still have an AI page review that judged the page suspicious
 * or a scam, worth only a few points on its own. "This appears safe" would contradict that, so such a scan is
 * shown as "Needs a manual review" with a line explaining why. This is wording only: the stored verdict, the
 * score, the history, the statistics and the accuracy figures are not changed.
 */
final class VerdictWording
{
    public const CAVEAT = 'The built-in checks found little wrong, but the AI review of the page raised a concern. Please read the AI Page Review below before you trust this link.';

    /** The verdict to show: the stored one, or "review" when the AI page review disagrees with a clean link scan. */
    public static function shown(?string $verdict, string $type, iterable $checks): string
    {
        $verdict = (string) $verdict;
        $isClean = ! in_array($verdict, ['phishing', 'suspicious', 'review'], true);

        return $isClean && $type === 'url' && self::aiFlagged($checks) ? 'review' : $verdict;
    }

    /** True when the AI page review raised a concern (it added points). Skipped or clean reviews add none. */
    public static function aiFlagged(iterable $checks): bool
    {
        foreach ($checks as $check) {
            if (is_array($check) && ($check['name'] ?? null) === 'AI Page Review' && (int) ($check['points'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}