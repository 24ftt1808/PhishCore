<?php

namespace App\Support;

/**
 * Accuracy figures for a batch of scans where the right answer is known.
 *
 * The positive class is "scam". A scan counts as flagged when its verdict is
 * anything other than "clean", so both "suspicious" and "phishing" count as
 * catching a scam, and both count as a false alarm on a legitimate site.
 * Scans that errored are left out of every figure and only counted apart.
 *
 * Each row is an array with: expected ("scam"|"legit"), category (string or
 * null), url, score (int or null) and verdict ("clean", "suspicious",
 * "phishing", "review" or "error").
 */
class BatchMetrics
{
    /** Verdicts shown in the breakdown table, in display order. */
    public const VERDICTS = ['clean', 'suspicious', 'phishing', 'review'];

    /** Risk-score cut-offs tried in the threshold table. 25 and 60 are the engine's own. */
    public const THRESHOLDS = [10, 20, 25, 30, 40, 50, 60, 70, 80];

    /** The engine calls a site suspicious from this score. */
    public const SUSPICIOUS_FROM = 25;

    /** Site Availability results that mean the page was already gone when scanned. */
    public const DEAD_STATUSES = ['OFFLINE', 'TAKEN DOWN'];

    /** Column key => name shown in the report, in display order. */
    public const CHECK_LABELS = [
        'ssl' => 'SSL certificate',
        'domain_age' => 'Domain age',
        'url_structure' => 'URL structure',
        'blacklist' => 'Blacklist database',
        'virustotal' => 'VirusTotal',
        'ip_reputation' => 'IP reputation',
        'redirect' => 'Redirect chain',
        'page_content' => 'Page content',
        'hosting' => 'Hosting platform',
        'previous_reports' => 'Previous reports',
    ];

    public static function scored(array $rows): array
    {
        return array_values(array_filter($rows, fn ($r) => ($r['verdict'] ?? 'error') !== 'error'));
    }

    public static function flagged(array $row): bool
    {
        return $row['verdict'] !== 'clean';
    }

    /** @return array{tp: int, fn: int, fp: int, tn: int} */
    public static function confusion(array $rows): array
    {
        $c = ['tp' => 0, 'fn' => 0, 'fp' => 0, 'tn' => 0];

        foreach (self::scored($rows) as $r) {
            $flag = self::flagged($r);

            if ($r['expected'] === 'scam') {
                $flag ? $c['tp']++ : $c['fn']++;
            } else {
                $flag ? $c['fp']++ : $c['tn']++;
            }
        }

        return $c;
    }

    /**
     * Ratios are returned as 0..1 floats, or null when there is nothing to
     * divide by (for example no legitimate sites in the batch).
     *
     * @param  array{tp: int, fn: int, fp: int, tn: int}  $c
     */
    public static function rates(array $c): array
    {
        $total = $c['tp'] + $c['fn'] + $c['fp'] + $c['tn'];
        $ratio = fn (int $n, int $d): ?float => $d > 0 ? $n / $d : null;

        $precision = $ratio($c['tp'], $c['tp'] + $c['fp']);
        $recall = $ratio($c['tp'], $c['tp'] + $c['fn']);

        return [
            'total' => $total,
            'accuracy' => $ratio($c['tp'] + $c['tn'], $total),
            'precision' => $precision,
            'recall' => $recall,
            'specificity' => $ratio($c['tn'], $c['tn'] + $c['fp']),
            'false_positive_rate' => $ratio($c['fp'], $c['fp'] + $c['tn']),
            'false_negative_rate' => $ratio($c['fn'], $c['fn'] + $c['tp']),
            'f1' => ($precision !== null && $recall !== null && ($precision + $recall) > 0)
                ? 2 * $precision * $recall / ($precision + $recall)
                : null,
        ];
    }

    /** @return array<int, array{category: string, scams: int, caught: int, legit: int, false_alarms: int}> */
    public static function byCategory(array $rows): array
    {
        $groups = [];

        foreach (self::scored($rows) as $r) {
            $name = ($r['category'] ?? '') !== '' ? $r['category'] : 'uncategorised';
            $groups[$name] ??= ['category' => $name, 'scams' => 0, 'caught' => 0, 'legit' => 0, 'false_alarms' => 0];

            if ($r['expected'] === 'scam') {
                $groups[$name]['scams']++;
                if (self::flagged($r)) {
                    $groups[$name]['caught']++;
                }
            } else {
                $groups[$name]['legit']++;
                if (self::flagged($r)) {
                    $groups[$name]['false_alarms']++;
                }
            }
        }

        ksort($groups);

        return array_values($groups);
    }

    /** @return array{scam: array<string, int>, legit: array<string, int>} */
    public static function verdictCounts(array $rows): array
    {
        $out = [
            'scam' => array_fill_keys(self::VERDICTS, 0),
            'legit' => array_fill_keys(self::VERDICTS, 0),
        ];

        foreach (self::scored($rows) as $r) {
            $class = $r['expected'] === 'scam' ? 'scam' : 'legit';
            $verdict = in_array($r['verdict'], self::VERDICTS, true) ? $r['verdict'] : 'review';
            $out[$class][$verdict]++;
        }

        return $out;
    }

    /**
     * What would happen at each risk-score cut-off: flag a site when its
     * score is at or above the threshold. Only scans that have a score are used.
     *
     * @return array<int, array{threshold: int, recall: ?float, false_positive_rate: ?float, precision: ?float}>
     */
    public static function thresholdSweep(array $rows, array $thresholds = self::THRESHOLDS): array
    {
        $withScore = array_values(array_filter(
            self::scored($rows),
            fn ($r) => isset($r['score']) && is_numeric($r['score'])
        ));

        $out = [];

        foreach ($thresholds as $t) {
            $c = ['tp' => 0, 'fn' => 0, 'fp' => 0, 'tn' => 0];

            foreach ($withScore as $r) {
                $flag = $r['score'] >= $t;

                if ($r['expected'] === 'scam') {
                    $flag ? $c['tp']++ : $c['fn']++;
                } else {
                    $flag ? $c['fp']++ : $c['tn']++;
                }
            }

            $rates = self::rates($c);
            $out[] = [
                'threshold' => $t,
                'recall' => $rates['recall'],
                'false_positive_rate' => $rates['false_positive_rate'],
                'precision' => $rates['precision'],
            ];
        }

        return $out;
    }

    /** True when the scanned page was already offline or taken down. Rows without a status count as live. */
    public static function isDead(array $row): bool
    {
        return in_array($row['site_status'] ?? '', self::DEAD_STATUSES, true);
    }

    /**
     * Detection of scams split by whether the page was still reachable.
     * Phishing feeds list many pages that are gone within hours, and a dead
     * page has nothing left for the page-content checks to read, so the rate on
     * live scams is the fairer figure for the checks themselves.
     *
     * @return array{live: int, live_caught: int, dead: int, dead_caught: int, live_rate: ?float}
     */
    public static function liveScams(array $rows): array
    {
        $o = ['live' => 0, 'live_caught' => 0, 'dead' => 0, 'dead_caught' => 0, 'live_rate' => null];

        foreach (self::scored($rows) as $r) {
            if ($r['expected'] !== 'scam') {
                continue;
            }

            $key = self::isDead($r) ? 'dead' : 'live';
            $o[$key]++;

            if (self::flagged($r)) {
                $o[$key.'_caught']++;
            }
        }

        $o['live_rate'] = $o['live'] > 0 ? $o['live_caught'] / $o['live'] : null;

        return $o;
    }

    /**
     * For every check: how often it added points to a scam and to a legitimate
     * site, and how many of those verdicts hinged on it (the site was flagged,
     * and would have been judged clean without that check's points). Reading
     * "depend" across the two columns shows what dropping or down-weighting a
     * check would cost in missed scams against what it would save in false
     * alarms. Rows without per-check points are skipped.
     *
     * @return array<int, array{check: string, scam_fired: int, scam_depend: int, legit_fired: int, legit_depend: int}>
     */
    public static function checkImpact(array $rows): array
    {
        $out = [];

        foreach (self::scored($rows) as $r) {
            if (! is_array($r['points'] ?? null)) {
                continue;
            }

            $side = $r['expected'] === 'scam' ? 'scam' : 'legit';

            foreach ($r['points'] as $key => $pts) {
                if ($pts <= 0) {
                    continue;
                }

                $out[$key] ??= ['check' => $key, 'scam_fired' => 0, 'scam_depend' => 0, 'legit_fired' => 0, 'legit_depend' => 0];
                $out[$key][$side.'_fired']++;

                if (self::flagged($r) && isset($r['score']) && $r['score'] - $pts < self::SUSPICIOUS_FROM) {
                    $out[$key][$side.'_depend']++;
                }
            }
        }

        $order = array_keys(self::CHECK_LABELS);
        uasort($out, fn ($a, $b) => [$b['legit_fired'], $b['scam_fired'], array_search($a['check'], $order)]
            <=> [$a['legit_fired'], $a['scam_fired'], array_search($b['check'], $order)]);

        return array_values($out);
    }

    /** Scams that got through and legitimate sites that were flagged. */
    public static function misclassified(array $rows): array
    {
        return array_values(array_filter(
            self::scored($rows),
            fn ($r) => ($r['expected'] === 'scam') !== self::flagged($r)
        ));
    }

    public static function pct(?float $value): string
    {
        return $value === null ? 'n/a' : sprintf('%.1f%%', $value * 100);
    }

    /** Makes a URL safe to paste into a report: it can no longer be clicked by accident. */
    public static function defang(string $url): string
    {
        return str_replace('.', '[.]', preg_replace('/^http/i', 'hxxp', $url));
    }

    /**
     * @param  array{generated?: string, mode?: string, source?: string}  $meta
     */
    public static function toMarkdown(array $rows, array $meta = []): string
    {
        $c = self::confusion($rows);
        $r = self::rates($c);
        $scams = $c['tp'] + $c['fn'];
        $legit = $c['fp'] + $c['tn'];
        $errors = count($rows) - count(self::scored($rows));
        $live = self::liveScams($rows);

        $md = "# PhishCore detection accuracy\n\n";
        $md .= '- Run: '.($meta['generated'] ?? 'unknown')."\n";
        $md .= '- Mode: '.($meta['mode'] ?? 'Full engine')."\n";
        $md .= '- List: '.($meta['source'] ?? 'unknown')."\n";
        $md .= "- Sample: {$scams} scam URLs, {$legit} legitimate URLs".($errors ? ", {$errors} scan(s) failed and are not counted" : '')."\n";
        if ($live['dead'] > 0) {
            $md .= "- {$live['dead']} of the {$scams} scam URLs were already offline or taken down when scanned.\n";
        }
        $md .= "- A site counts as flagged when its verdict is suspicious or phishing (anything other than clean).\n\n";

        $md .= "## Confusion matrix\n\n";
        $md .= "| | Flagged | Not flagged |\n|---|---:|---:|\n";
        $md .= "| Scam | {$c['tp']} (true positive) | {$c['fn']} (false negative) |\n";
        $md .= "| Legitimate | {$c['fp']} (false positive) | {$c['tn']} (true negative) |\n\n";

        $md .= "## Metrics\n\n| Metric | Value |\n|---|---:|\n";
        $md .= '| Accuracy | '.self::pct($r['accuracy'])." |\n";
        $md .= '| Detection rate (recall) | '.self::pct($r['recall'])." |\n";
        if ($live['dead'] > 0) {
            $md .= '| Detection rate, scams still online | '.self::pct($live['live_rate'])." ({$live['live_caught']} of {$live['live']}) |\n";
        }
        $md .= '| Precision | '.self::pct($r['precision'])." |\n";
        $md .= '| False positive rate | '.self::pct($r['false_positive_rate'])." |\n";
        $md .= '| False negative rate | '.self::pct($r['false_negative_rate'])." |\n";
        $md .= '| Specificity | '.self::pct($r['specificity'])." |\n";
        $md .= '| F1 score | '.($r['f1'] === null ? 'n/a' : sprintf('%.3f', $r['f1']))." |\n\n";

        $md .= "## By group\n\n| Group | Scams caught | Legitimate flagged |\n|---|---:|---:|\n";
        foreach (self::byCategory($rows) as $g) {
            $caught = $g['scams'] ? "{$g['caught']} of {$g['scams']} (".self::pct($g['caught'] / $g['scams']).')' : '-';
            $alarms = $g['legit'] ? "{$g['false_alarms']} of {$g['legit']} (".self::pct($g['false_alarms'] / $g['legit']).')' : '-';
            $md .= "| {$g['category']} | {$caught} | {$alarms} |\n";
        }

        $v = self::verdictCounts($rows);
        $md .= "\n## Verdicts given\n\n| Expected | ".implode(' | ', self::VERDICTS)." |\n|---|".str_repeat('---:|', count(self::VERDICTS))."\n";
        $md .= '| Scam | '.implode(' | ', $v['scam'])." |\n";
        $md .= '| Legitimate | '.implode(' | ', $v['legit'])." |\n\n";

        $md .= "## Risk-score threshold\n\n";
        $md .= "The engine calls a site suspicious from 25 and phishing from 60. This shows what other cut-offs would have done on the same sample.\n\n";
        $md .= "| Flag at score of | Detection rate | False positive rate | Precision |\n|---:|---:|---:|---:|\n";
        foreach (self::thresholdSweep($rows) as $t) {
            $label = $t['threshold'].($t['threshold'] === 25 ? ' (suspicious)' : ($t['threshold'] === 60 ? ' (phishing)' : ''));
            $md .= "| {$label} | ".self::pct($t['recall']).' | '.self::pct($t['false_positive_rate']).' | '.self::pct($t['precision'])." |\n";
        }

        $impact = self::checkImpact($rows);
        if ($impact !== []) {
            $md .= "\n## Which checks fired\n\n";
            $md .= "\"Depend\" counts the sites that were flagged only because of that check: without its points they would have been judged clean. Compare the scam and legitimate columns to see what changing a check would cost in missed scams against what it would save in false alarms.\n\n";
            $md .= "| Check | Scams it fired on | Scams that depend on it | Legitimate it fired on | False alarms that depend on it |\n|---|---:|---:|---:|---:|\n";
            foreach ($impact as $i) {
                $label = self::CHECK_LABELS[$i['check']] ?? $i['check'];
                $md .= "| {$label} | {$i['scam_fired']} | {$i['scam_depend']} | {$i['legit_fired']} | {$i['legit_depend']} |\n";
            }
        }

        $wrong = self::misclassified($rows);
        $md .= "\n## Misclassified\n\n";
        if ($wrong === []) {
            $md .= "None.\n";
        } else {
            $md .= "URLs are defanged so they cannot be clicked by accident.\n\n| Expected | Verdict | Score | Site status | URL |\n|---|---|---:|---|---|\n";
            foreach ($wrong as $w) {
                $md .= "| {$w['expected']} | {$w['verdict']} | ".($w['score'] ?? '-').' | '.(($w['site_status'] ?? '') !== '' ? $w['site_status'] : '-').' | '.self::defang($w['url'])." |\n";
            }
        }

        return $md;
    }
}