<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class PublicFeedController extends Controller
{
    private const MAX_ROWS = 5000;

    /**
     * Machine-readable download of flagged URLs from the public reports
     * (CSV, JSON or plain text), for other tools and researchers — in the
     * spirit of the OpenPhish / PhishTank feeds.
     *
     * Deliberately narrow: only link scans (never emails or phone numbers,
     * which are personal data), only http(s) URLs, no submitter information.
     * Defaults to confirmed-phishing verdicts only; ?verdict=suspicious or
     * ?verdict=all widens it.
     */
    public function __invoke(Request $request, string $format): Response|JsonResponse
    {
        $verdicts = match ($request->query('verdict', 'phishing')) {
            'all' => ['phishing', 'suspicious'],
            'suspicious' => ['suspicious'],
            default => ['phishing'],
        };

        $rows = $this->collectRows($verdicts);
        $stamp = now()->utc();

        return match ($format) {
            'json' => response()->json([
                'source' => 'PhishCore',
                'generated_at' => $stamp->toIso8601String(),
                'verdicts' => $verdicts,
                'count' => $rows->count(),
                'notice' => 'Automated verdicts that may contain errors. Verify before blocking.',
                'data' => $rows->all(),
            ], 200, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)->withHeaders($this->headers()),

            'txt' => response($rows->pluck('url')->implode("\n").($rows->isNotEmpty() ? "\n" : ''), 200, $this->headers() + [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]),

            default => response($this->toCsv($rows), 200, $this->headers() + [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="phishcore-feed-'.$stamp->format('Y-m-d').'.csv"',
            ]),
        };
    }

    /**
     * One row per distinct URL: the most severe verdict seen, the earliest
     * report date, and how many reports it has received.
     *
     * @param  array<int, string>  $verdicts
     */
    private function collectRows(array $verdicts): Collection
    {
        $severity = ['suspicious' => 1, 'phishing' => 2];

        return Report::with('analyses:id,report_id,verdict')
            ->where('status', 'completed')
            ->where('type', 'url')
            ->whereNotNull('url')
            ->whereHas('analyses', fn ($q) => $q->whereIn('verdict', $verdicts))
            ->latest()
            ->limit(self::MAX_ROWS * 4)
            ->get(['id', 'url', 'created_at'])
            ->filter(fn ($r) => preg_match('#^https?://#i', $r->url) === 1)
            ->groupBy(fn ($r) => trim($r->url))
            ->map(function (Collection $group, string $url) use ($severity, $verdicts) {
                $verdict = $group
                    ->map(fn ($r) => $r->analyses->first()?->verdict)
                    ->filter(fn ($v) => in_array($v, $verdicts, true))
                    ->sortByDesc(fn ($v) => $severity[$v] ?? 0)
                    ->first();

                return [
                    'url' => $url,
                    'domain' => strtolower(preg_replace('/^www\./i', '', (string) parse_url($url, PHP_URL_HOST))),
                    'verdict' => $verdict,
                    'first_reported' => $group->min('created_at')->utc()->toIso8601String(),
                    'reports' => $group->count(),
                ];
            })
            ->sortByDesc('first_reported')
            ->take(self::MAX_ROWS)
            ->values();
    }

    private function toCsv(Collection $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['url', 'domain', 'verdict', 'first_reported', 'reports'], ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, [$row['url'], $row['domain'], $row['verdict'], $row['first_reported'], $row['reports']], ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=60',
        ];
    }
}