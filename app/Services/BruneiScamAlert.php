<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Report;
use App\Models\User;
use App\Notifications\BruneiScamNotice;
use Illuminate\Support\Facades\Notification;

/**
 * Tells the team and the admins about two things: a phishing scan that looks Brunei-related (a known
 * Brunei brand, a .bn address, a +673 number or Brunei wording), and a possible scam campaign, where
 * several different people scan the same website, sender or number within a couple of days.
 * It reads the warnings the scan already produced, so it needs no extra checks or requests.
 */
class BruneiScamAlert
{
    /** A "campaign" is this many different people scanning the same thing within this many hours. */
    public const CAMPAIGN_PEOPLE = 3;
    public const CAMPAIGN_HOURS = 48;

    /** Regex => name shown in the notification. The first one that matches a warning wins. */
    private const BRANDS = [
        '/\bbibd\b/i' => 'BIBD',
        '/baiduri/i' => 'Baiduri',
        '/\bbdcb\b/i' => 'BDCB',
        '/progresif/i' => 'Progresif',
        '/brunei ?post|bruneipost/i' => 'Brunei Post',
        '/royal ?brunei ?airlines|royalbrunei/i' => 'Royal Brunei Airlines',
        '/polis diraja brunei|royal brunei police/i' => 'Royal Brunei Police',
        '/brunei customs|royal customs|kastam/i' => 'Brunei Customs',
        '/imigresen|immigration and national registration/i' => 'the Immigration Department',
        '/land transport|pengangkutan darat/i' => 'the Land Transport Department',
        '/electrical services|perkhidmatan elektrik/i' => 'the Electrical Services Department',
        '/\bdst\b|datastream digital/i' => 'DST',
        '/\btaib\b/i' => 'TAIB',
        '/imagine brunei/i' => 'Imagine',
        '/brunei government|brunei authority|gov\.bn|gov-bn/i' => 'a Brunei government body',
    ];

    /**
     * The Brunei brand a phishing scan imitates, or null when the scan is not phishing or
     * its warnings do not name a Brunei brand.
     *
     * @param  array<int, mixed>|null  $checks  The scan's checks (name, status, message, points).
     */
    public static function brandFor(?string $verdict, ?array $checks): ?string
    {
        if ($verdict !== 'phishing') {
            return null;
        }

        foreach ($checks ?? [] as $check) {
            if (! is_array($check) || (int) ($check['points'] ?? 0) <= 0) {
                continue;
            }

            foreach (self::BRANDS as $pattern => $label) {
                if (preg_match($pattern, (string) ($check['message'] ?? ''))) {
                    return $label;
                }
            }
        }

        return null;
    }

    /**
     * Why a scan counts as Brunei-related even when no known brand is named, or null.
     * Looks at the scanned link, sender email and phone number, then at the scan's own warnings.
     *
     * @param  array<int, mixed>|null  $checks
     */
    public static function bruneiSignalFor(?Report $report, ?array $checks): ?string
    {
        if ($report) {
            $host = self::hostOf($report->url);
            $emailDomain = $report->sender_email ? strtolower((string) substr(strrchr((string) $report->sender_email, '@') ?: '', 1)) : '';

            foreach ([$host, $emailDomain] as $domain) {
                if ($domain !== '' && ($domain === 'bn' || str_ends_with($domain, '.bn'))) {
                    return 'a Brunei (.bn) address';
                }
            }

            if (preg_match('/^\s*(\+|00)?673\d/', (string) $report->phone_number)) {
                return 'a Brunei phone number';
            }
        }

        foreach ($checks ?? [] as $check) {
            if (is_array($check) && (int) ($check['points'] ?? 0) > 0
                && preg_match('/\bbrunei\b|\bbnd\b|darussalam/i', (string) ($check['message'] ?? ''))) {
                return 'Brunei wording';
            }
        }

        return null;
    }

    /** The website name of a link without "www.", lowercase; empty when there is none. */
    public static function hostOf(?string $url): string
    {
        if (! $url) {
            return '';
        }

        $host = parse_url(str_contains($url, '://') ? $url : 'http://'.$url, PHP_URL_HOST);

        return preg_replace('/^www\./', '', strtolower((string) $host)) ?? '';
    }

    /**
     * How many different people scanned the same website, sender or phone number as this scan
     * in the last CAMPAIGN_HOURS, counting only scans that were not rated clean.
     * Each guest counts as one person, each signed-in user once.
     */
    public static function peopleScanning(Report $report): int
    {
        $since = now()->subHours(self::CAMPAIGN_HOURS);

        $query = Report::query()
            ->where('created_at', '>=', $since)
            ->whereHas('analyses', fn ($q) => $q->whereIn('verdict', ['phishing', 'suspicious']));

        if ($report->url) {
            $host = self::hostOf($report->url);
            $same = $host === '' ? collect() : (clone $query)->where('url', 'like', '%'.$host.'%')->get()
                ->filter(fn (Report $r) => self::hostOf($r->url) === $host);
        } elseif ($report->sender_email) {
            $same = (clone $query)->whereRaw('lower(sender_email) = ?', [strtolower($report->sender_email)])->get();
        } elseif ($report->phone_number) {
            $same = (clone $query)->where('phone_number', $report->phone_number)->get();
        } else {
            return 0;
        }

        $signedIn = $same->pluck('user_id')->filter()->unique()->count();
        $guests = $same->whereNull('user_id')->count();

        return $signedIn + $guests;
    }

    /** Called when a scan's analysis is saved. A failure here must never break the scan. */
    public static function notifyFor(Analysis $analysis): void
    {
        try {
            $report = $analysis->report;

            if (! $report) {
                return;
            }

            $brand = self::brandFor($analysis->verdict, $analysis->flags);
            $signal = $brand === null && $analysis->verdict === 'phishing'
                ? self::bruneiSignalFor($report, $analysis->flags)
                : null;

            if ($brand !== null) {
                self::send($report, 'High-risk scan imitating '.$brand, true);
            } elseif ($signal !== null) {
                self::send($report, 'High-risk scan with '.$signal, true);
            }

            if (in_array($analysis->verdict, ['phishing', 'suspicious'], true)
                && self::peopleScanning($report) === self::CAMPAIGN_PEOPLE) {
                self::send($report, 'Possible scam campaign: '.self::CAMPAIGN_PEOPLE.' people scanned the same thing', false);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Tell every active team member and admin; the scanner is skipped only for the "high-risk" kind. */
    private static function send(Report $report, string $headline, bool $skipOwner): void
    {
        $recipients = User::whereNull('suspended_at')
            ->where(fn ($q) => $q->where('is_team_member', true)->orWhere('role', 'admin'))
            ->when($skipOwner && $report->user_id, fn ($q) => $q->where('id', '!=', $report->user_id))
            ->get();

        Notification::send($recipients, new BruneiScamNotice($report, $headline));
    }
}