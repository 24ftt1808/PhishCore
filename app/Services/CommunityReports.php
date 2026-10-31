<?php

namespace App\Services;

use App\Models\Investigation;
use App\Models\NumberReport;
use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * The "Community reports" box on a scan result: what other PhishCore scans found for the same thing
 * (the same website, sender domain, phone number or file), the people who reported a number as a scam,
 * and how far the PhishCore team has taken it.
 *
 * Display only: nothing here changes a score (the engine's own "Previous reports" check does that).
 * Only counts and dates are given, never who scanned or reported anything. Worked out when the page
 * is opened, so the numbers are always current.
 */
class CommunityReports
{
    /** Investigation statuses from least to most advanced, with what the person sees. */
    public const TEAM_STATUS = [
        'active' => 'The PhishCore team is investigating this',
        'completed' => 'The PhishCore team has investigated this',
        'takedown_requested' => 'The PhishCore team has reported this to BruCERT',
        'takedown_confirmed' => 'BruCERT has confirmed it was taken down',
    ];

    /**
     * @return array{
     *     subject: ?string, subject_label: ?string, others: int, people: int, yours_before: bool, flagged: int, phishing: int,
     *     suspicious: int, clean: int, first_seen: ?Carbon, number_reports: int,
     *     top_category: ?string, team_status: ?string, team_label: ?string
     * }
     */
    public static function for(Report $report): array
    {
        [$subject, $label] = self::subjectOf($report);

        $result = [
            'subject' => $subject,
            'subject_label' => $label,
            'others' => 0,
            'people' => 0,
            'yours_before' => false,
            'flagged' => 0,
            'phishing' => 0,
            'suspicious' => 0,
            'clean' => 0,
            'first_seen' => null,
            'number_reports' => 0,
            'top_category' => null,
            'team_status' => null,
            'team_label' => null,
        ];

        if ($subject === null) {
            return $result;
        }

        $others = self::matchingReports($report, $subject)->load('analyses:id,report_id,verdict');

        $verdicts = $others->map(fn (Report $other) => $other->analyses->first()?->verdict);
        $result['others'] = $others->count();
        // People, not scans: each signed-in person counts once, each guest scan counts once. The person who made
        // this scan is not "another person", even if they checked the same thing before.
        $result['people'] = $others->pluck('user_id')->filter()->unique()
            ->reject(fn ($userId) => $report->user_id !== null && (int) $userId === (int) $report->user_id)->count()
            + $others->whereNull('user_id')->count();
        $result['yours_before'] = $report->user_id !== null
            && $others->contains(fn (Report $other) => (int) $other->user_id === (int) $report->user_id);
        $result['phishing'] = $verdicts->filter(fn ($v) => $v === 'phishing')->count();
        $result['suspicious'] = $verdicts->filter(fn ($v) => $v === 'suspicious')->count();
        $result['clean'] = $verdicts->filter(fn ($v) => $v === 'clean')->count();
        $result['flagged'] = $result['phishing'] + $result['suspicious'];
        $result['first_seen'] = $others->min('created_at');

        if ($report->type === 'phone') {
            $reports = NumberReport::where('phone', $subject)->get(['user_id', 'category']);
            $result['number_reports'] = $reports->pluck('user_id')->unique()->count();
            $top = $reports->countBy('category')->sortDesc()->keys()->first();
            $result['top_category'] = $top !== null ? (NumberReport::CATEGORIES[$top] ?? null) : null;
        }

        $statuses = Investigation::whereIn('report_id', $others->pluck('id')->push($report->id))->pluck('status');
        $order = array_keys(self::TEAM_STATUS);
        $furthest = $statuses->sortByDesc(fn ($status) => array_search($status, $order, true))->first();
        if ($furthest !== null && isset(self::TEAM_STATUS[$furthest])) {
            $result['team_status'] = $furthest;
            $result['team_label'] = self::TEAM_STATUS[$furthest];
        }

        return $result;
    }

    /**
     * What a scan is about, in the form other scans are compared on, and how to name it to the reader.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function subjectOf(Report $report): array
    {
        return match ($report->type) {
            'url', 'screenshot' => ($host = self::hostOf($report->url)) !== null ? [$host, "the website {$host}"] : [null, null],
            'email' => ($domain = self::senderDomain($report->sender_email)) !== null ? [$domain, "emails from {$domain}"] : [null, null],
            'phone' => filled($report->phone_number) ? [NumberReport::key((string) $report->phone_number), 'this phone number'] : [null, null],
            'file' => filled($report->file_sha256) ? [(string) $report->file_sha256, 'this file'] : [null, null],
            default => [null, null],
        };
    }

    /** Other finished scans about the same subject. A cheap database filter first, then an exact comparison. */
    private static function matchingReports(Report $report, string $subject): Collection
    {
        $query = Report::query()
            ->where('id', '!=', $report->id)
            ->where('status', 'completed')
            ->select(['id', 'user_id', 'type', 'url', 'sender_email', 'phone_number', 'file_sha256', 'created_at']);

        return match ($report->type) {
            'url', 'screenshot' => $query->whereIn('type', ['url', 'screenshot'])->where('url', 'like', '%'.$subject.'%')->get()
                ->filter(fn (Report $other) => self::hostOf($other->url) === $subject)->values(),
            'email' => $query->where('type', 'email')->where('sender_email', 'like', '%@%'.$subject)->get()
                ->filter(fn (Report $other) => self::senderDomain($other->sender_email) === $subject)->values(),
            'phone' => $query->where('type', 'phone')->where('phone_number', 'like', '%'.substr($subject, -4).'%')->get()
                ->filter(fn (Report $other) => filled($other->phone_number) && NumberReport::key((string) $other->phone_number) === $subject)->values(),
            'file' => $query->where('file_sha256', $subject)->get(),
            default => new Collection,
        };
    }

    /** The host of a link, lower case and without "www.", or null. */
    private static function hostOf(?string $url): ?string
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return $host === '' ? null : (preg_replace('/^www\./', '', $host) ?? $host);
    }

    /** The domain after the "@" of a sender address, or null. */
    private static function senderDomain(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return str_contains($email, '@') ? substr((string) strrchr($email, '@'), 1) ?: null : null;
    }
}