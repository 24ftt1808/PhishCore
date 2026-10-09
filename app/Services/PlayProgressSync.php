<?php

namespace App\Services;

/**
 * Cleans and merges Phish Lab progress. The page keeps progress in the browser as small key/value pairs, and this
 * keeps a copy per user on the server. Merging never lowers a score, so playing on two devices cannot lose progress.
 *
 * The same rules are written in JavaScript on the Play page (plMerge). Keep the two in step.
 */
class PlayProgressSync
{
    private const NUMBERS = ['xp', 'rush', 'rush-hard', 'quiz-streak', 'day-n', 'day-best', 'perfect', 'daily-runs'];

    private const PREFS = ['side', 'sound', 'wardrobe'];

    private const RANK = ['lose' => 1, 'meh' => 2, 'win' => 3];

    private const KEEP_DAILY = 14;

    private const MAX_NUMBER = 100000000;

    /** Keeps only the keys the page uses, with safe values. Anything else is dropped. */
    public static function clean(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (! is_string($key) || ! is_string($value) || strlen($value) > 4000) {
                continue;
            }

            if (in_array($key, self::NUMBERS, true) || preg_match('/^rush-daily-\d{8}$/', $key)) {
                if (preg_match('/^\d{1,9}$/', $value)) {
                    $out[$key] = (string) min((int) $value, self::MAX_NUMBER);
                }
            } elseif ($key === 'day-last') {
                if (preg_match('/^\d{8}$/', $value)) {
                    $out[$key] = $value;
                }
            } elseif ($key === 'prefs-at') {
                if (preg_match('/^\d{1,15}$/', $value)) {
                    $out[$key] = $value;
                }
            } elseif ($key === 'side' || $key === 'sound') {
                if ($value === '0' || $value === '1') {
                    $out[$key] = $value;
                }
            } elseif ($key === 'survivor') {
                $out[$key] = json_encode(self::cleanSurvivor(json_decode($value, true)));
            } elseif ($key === 'badges') {
                $out[$key] = json_encode(self::cleanBadges(json_decode($value, true)));
            } elseif ($key === 'wardrobe') {
                if ($look = self::cleanWardrobe(json_decode($value, true))) {
                    $out[$key] = json_encode($look);
                }
            }
        }

        return self::prune($out);
    }

    /** Combines what the server has with what a device sent. */
    public static function merge(array $old, array $new): array
    {
        $old = self::clean($old);
        $new = self::clean($new);
        $out = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $a = $old[$key] ?? null;
            $b = $new[$key] ?? null;

            if ($a === null || $b === null) {
                $out[$key] = $a ?? $b;
            } elseif (in_array($key, self::NUMBERS, true) || str_starts_with($key, 'rush-daily-')) {
                $out[$key] = (string) max((int) $a, (int) $b);
            } elseif ($key === 'survivor') {
                $out[$key] = json_encode(self::mergeSurvivor(json_decode($a, true), json_decode($b, true)));
            } elseif ($key === 'badges') {
                $out[$key] = json_encode(array_values(array_unique([...json_decode($a, true), ...json_decode($b, true)])));
            }
        }

        // The streak counter belongs with the day it was last earned, so the two always travel together.
        [$last, $days] = self::pickStreak($old, $new);
        if ($last !== null) {
            $out['day-last'] = $last;
            $out['day-n'] = (string) $days;
        }

        // Look and sound settings: whichever device changed them most recently wins as a group.
        $newer = (int) ($new['prefs-at'] ?? 0) >= (int) ($old['prefs-at'] ?? 0) ? $new : $old;
        $other = $newer === $new ? $old : $new;
        foreach (array_merge(self::PREFS, ['prefs-at']) as $key) {
            $value = $newer[$key] ?? $other[$key] ?? null;
            if ($value !== null) {
                $out[$key] = $value;
            }
        }

        return self::prune($out);
    }

    private static function pickStreak(array $old, array $new): array
    {
        $lastA = $old['day-last'] ?? null;
        $lastB = $new['day-last'] ?? null;

        if ($lastA === null && $lastB === null) {
            return [null, 0];
        }
        if ($lastA === null || ($lastB !== null && $lastB > $lastA)) {
            return [$lastB, (int) ($new['day-n'] ?? 0)];
        }
        if ($lastB === null || $lastA > $lastB) {
            return [$lastA, (int) ($old['day-n'] ?? 0)];
        }

        return [$lastA, max((int) ($old['day-n'] ?? 0), (int) ($new['day-n'] ?? 0))];
    }

    private static function cleanSurvivor(mixed $v): array
    {
        $out = [];
        foreach (is_array($v) ? $v : [] as $id => $result) {
            if (is_string($id) && is_string($result) && isset(self::RANK[$result]) && preg_match('/^[a-z0-9_-]{1,40}$/', $id) && count($out) < 60) {
                $out[$id] = $result;
            }
        }

        return $out;
    }

    private static function mergeSurvivor(array $a, array $b): array
    {
        foreach ($b as $id => $result) {
            if (! isset($a[$id]) || self::RANK[$result] > self::RANK[$a[$id]]) {
                $a[$id] = $result;
            }
        }

        return $a;
    }

    private static function cleanBadges(mixed $v): array
    {
        $out = [];
        foreach (is_array($v) ? $v : [] as $id) {
            if (is_string($id) && preg_match('/^[a-z0-9_-]{1,30}$/', $id) && count($out) < 40) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    private static function cleanWardrobe(mixed $v): array
    {
        $out = [];
        foreach (['hat', 'color', 'water'] as $part) {
            if (is_array($v) && isset($v[$part]) && is_string($v[$part]) && preg_match('/^[a-z0-9_-]{1,20}$/', $v[$part])) {
                $out[$part] = $v[$part];
            }
        }

        return $out;
    }

    /** Daily scores are only useful for a short while, so older days are dropped. */
    private static function prune(array $data): array
    {
        $daily = array_filter(array_keys($data), fn ($k) => str_starts_with($k, 'rush-daily-'));
        rsort($daily);

        foreach (array_slice($daily, self::KEEP_DAILY) as $key) {
            unset($data[$key]);
        }

        return $data;
    }
}