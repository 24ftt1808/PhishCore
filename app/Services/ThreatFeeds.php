<?php

namespace App\Services;

use App\Models\PopularDomain;
use App\Models\ThreatUrl;
use App\Support\RegistrableDomain;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Downloads the lists in config/threatfeeds.php into the database (threat_urls and popular_domains), so a
 * scan can look an address up without a network call:
 *
 *  - phishing / malware lists (OpenPhish, URLhaus, PhishTank): addresses other people have reported;
 *  - the Tranco list: the most visited sites in the world.
 *
 * One broken feed never stops the others, and a feed that fails keeps its older copy. It is run by
 * feeds:update (scheduled every few hours) and the work is split into small pure functions that read a
 * downloaded text, so they can be tested without the internet.
 */
class ThreatFeeds
{
    private const LAST_RUN_KEY = 'threatfeeds:last_run';

    private const TRANCO_KEY = 'threatfeeds:tranco_at';

    /**
     * Download everything that is due.
     *
     * @return array{at: string, feeds: list<array{name: string, ok: bool, count: int, error: string|null}>}
     */
    public function run(bool $force = false): array
    {
        $feeds = [];

        foreach ((array) config('threatfeeds.feeds', []) as $feed) {
            $feeds[] = $this->updateUrlFeed((array) $feed);
        }

        if ((array) config('threatfeeds.tranco') !== [] && ($force || $this->trancoIsDue())) {
            $feeds[] = $this->updateTranco();
        }

        $summary = ['at' => now()->toIso8601String(), 'feeds' => $feeds];
        Cache::forever(self::LAST_RUN_KEY, $summary);

        return $summary;
    }

    /** @return array{at: string, feeds: list<array{name: string, ok: bool, count: int, error: string|null}>}|null */
    public static function lastRun(): ?array
    {
        $summary = Cache::get(self::LAST_RUN_KEY);

        return is_array($summary) ? $summary : null;
    }

    /**
     * Download one phishing / malware list and replace the saved copy of it.
     *
     * @param  array{name?: string, source?: string, url?: string, format?: string}  $feed
     * @return array{name: string, ok: bool, count: int, error: string|null}
     */
    public function updateUrlFeed(array $feed): array
    {
        $name = (string) ($feed['name'] ?? $feed['source'] ?? 'feed');

        try {
            $body = $this->download((string) $feed['url']);
            $urls = match ($feed['format'] ?? 'lines') {
                'phishtank' => self::parsePhishTank($body),
                'urlhaus' => self::parseUrlhausCsv($body),
                default => self::parseUrlLines($body),
            };

            // An empty answer is far more likely a broken download than a list with nothing on it, and
            // replacing the saved copy with it would silently switch the check off.
            if ($urls === []) {
                throw new RuntimeException('the list was empty');
            }

            $count = $this->replaceUrls((string) $feed['source'], $urls);

            return ['name' => $name, 'ok' => true, 'count' => $count, 'error' => null];
        } catch (Throwable $e) {
            Log::warning("Threat feed {$name} failed: ".self::shortError($e));

            return ['name' => $name, 'ok' => false, 'count' => 0, 'error' => self::shortError($e)];
        }
    }

    /**
     * Save the addresses of one list and remove the ones that are no longer on it.
     *
     * @param  list<string>  $urls
     */
    public function replaceUrls(string $source, array $urls): int
    {
        // Whole seconds, because the database keeps whole seconds: "seen before this run" must not match rows saved by it.
        $now = now()->startOfSecond();
        $rows = [];

        foreach ($urls as $url) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $registrable = RegistrableDomain::of($host);

            if ($host === '' || $registrable === null) {
                continue;
            }

            $hash = sha1(self::normalizeUrl($url));
            $rows[$hash] = [
                'source' => $source,
                'url_hash' => $hash,
                'url' => mb_substr($url, 0, 2048),
                'host' => mb_substr($host, 0, 255),
                'registrable' => mb_substr($registrable, 0, 255),
                'seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            ThreatUrl::query()->upsert($chunk, ['source', 'url_hash'], ['url', 'host', 'registrable', 'seen_at', 'updated_at']);
        }

        ThreatUrl::query()->where('source', $source)->where('seen_at', '<', $now)->delete();

        return count($rows);
    }

    /** True when the popular-sites list was never downloaded or is older than refresh_days. */
    public function trancoIsDue(): bool
    {
        $at = Cache::get(self::TRANCO_KEY);

        return ! $at || Carbon::parse($at)->diffInDays(now()) >= (int) config('threatfeeds.tranco.refresh_days', 7);
    }

    /** @return array{name: string, ok: bool, count: int, error: string|null} */
    public function updateTranco(): array
    {
        $name = 'Tranco popular sites';

        try {
            if (! class_exists(ZipArchive::class)) {
                throw new RuntimeException('the PHP zip extension is not installed');
            }

            // The full list is a million lines, far more than the few that are kept, so it is read straight out
            // of the zip a line at a time and reading stops at the last site that is kept.
            $domains = self::parseTrancoZip($this->download((string) config('threatfeeds.tranco.url')), (int) config('threatfeeds.tranco.keep', 100000));

            if ($domains === []) {
                throw new RuntimeException('the list was empty');
            }

            $count = $this->replacePopular($domains);
            Cache::forever(self::TRANCO_KEY, now());

            return ['name' => $name, 'ok' => true, 'count' => $count, 'error' => null];
        } catch (Throwable $e) {
            Log::warning("Threat feed {$name} failed: ".self::shortError($e));

            return ['name' => $name, 'ok' => false, 'count' => 0, 'error' => self::shortError($e)];
        }
    }

    /** @param  array<string, int>  $domains  domain => position */
    public function replacePopular(array $domains): int
    {
        DB::transaction(function () use ($domains) {
            PopularDomain::query()->delete();

            $rows = [];
            foreach ($domains as $domain => $position) {
                $rows[] = ['domain' => $domain, 'position' => $position];
            }

            foreach (array_chunk($rows, 1000) as $chunk) {
                PopularDomain::query()->insert($chunk);
            }
        });

        return count($domains);
    }

    /** An error message cut short: a failed bulk insert would otherwise repeat every row it tried to save. */
    private static function shortError(Throwable $e): string
    {
        return mb_strimwidth($e->getMessage(), 0, 300, '...');
    }

    private function download(string $url): string
    {
        $response = Http::timeout(120)
            ->withHeaders(['User-Agent' => 'PhishCore/1.0 (+https://phishcore.tech)'])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('download failed with status '.$response->status());
        }

        $body = $response->body();

        if (strlen($body) > (int) config('threatfeeds.max_bytes', 60_000_000)) {
            throw new RuntimeException('the download is larger than expected');
        }

        return $body;
    }

    /**
     * The first $keep sites of the Tranco list, read straight out of the zip file (held in memory) a line at a
     * time, so the million-line list is never loaded whole.
     *
     * @return array<string, int> domain => position
     */
    public static function parseTrancoZip(string $zipBytes, int $keep): array
    {
        $path = tempnam(sys_get_temp_dir(), 'tranco');

        try {
            file_put_contents($path, $zipBytes);

            $zip = new ZipArchive;
            if ($zip->open($path) !== true || $zip->numFiles < 1) {
                throw new RuntimeException('the zip file could not be opened');
            }

            $stream = $zip->getStreamIndex(0);
            if ($stream === false) {
                $zip->close();
                throw new RuntimeException('the zip file is empty');
            }

            $domains = [];
            while (($line = fgets($stream)) !== false) {
                $entry = self::parseTrancoLine($line, $keep);

                if ($entry === false) {
                    break;
                }

                if ($entry !== null) {
                    $domains[$entry[0]] = $entry[1];
                }
            }

            fclose($stream);
            $zip->close();

            return $domains;
        } finally {
            @unlink($path);
        }
    }

    /**
     * One address per line; lines starting with # and anything that is not an http(s) address are ignored.
     *
     * @return list<string>
     */
    public static function parseUrlLines(string $text): array
    {
        $urls = [];

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! self::isWebAddress($line)) {
                continue;
            }

            $urls[$line] = true;
        }

        return array_keys($urls);
    }

    /**
     * The addresses in URLhaus's CSV export. Its columns are named in a "# id,dateadded,url,url_status,..." comment
     * line (or a plain header row); when none is found the address is taken from the third column. Addresses
     * marked offline are skipped.
     *
     * @return list<string>
     */
    public static function parseUrlhausCsv(string $csv): array
    {
        $urlColumn = 2;
        $statusColumn = null;
        $urls = [];

        foreach (preg_split('/\R/', $csv) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $isComment = str_starts_with($line, '#');
            $cells = array_map(fn (string $cell): string => trim($cell, " \t\""), $isComment ? explode(',', ltrim($line, "# \t")) : (str_getcsv($line, ',', '"', '') ?: []));
            $names = array_map('strtolower', $cells);

            if (in_array('url', $names, true)) {
                $urlColumn = (int) array_search('url', $names, true);
                $found = array_search('url_status', $names, true);
                $statusColumn = $found === false ? null : (int) $found;

                continue;
            }

            if ($isComment) {
                continue;
            }

            $url = $cells[$urlColumn] ?? '';

            if ($statusColumn !== null && strtolower($cells[$statusColumn] ?? '') === 'offline') {
                continue;
            }

            if (self::isWebAddress($url)) {
                $urls[$url] = true;
            }
        }

        return array_keys($urls);
    }

    /**
     * The addresses in PhishTank's CSV (column "url", only rows that are online when that column exists).
     *
     * @return list<string>
     */
    public static function parsePhishTank(string $csv): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csv);
        rewind($stream);

        $header = fgetcsv($stream, 0, ',', '"', '');
        $urlColumn = is_array($header) ? array_search('url', array_map('strtolower', $header), true) : false;
        $onlineColumn = is_array($header) ? array_search('online', array_map('strtolower', $header), true) : false;
        $urls = [];

        if ($urlColumn !== false) {
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $url = trim((string) ($row[$urlColumn] ?? ''));

                if ($onlineColumn !== false && strtolower(trim((string) ($row[$onlineColumn] ?? ''))) === 'no') {
                    continue;
                }

                if (self::isWebAddress($url)) {
                    $urls[$url] = true;
                }
            }
        }

        fclose($stream);

        return array_keys($urls);
    }

    /**
     * The Tranco list: "position,domain" per line, best site first. Only the first $keep sites are used, and
     * reading stops once the list has gone past them.
     *
     * @return array<string, int> domain => position
     */
    public static function parseTranco(string $csv, int $keep): array
    {
        $domains = [];
        $offset = 0;
        $length = strlen($csv);

        while ($offset < $length) {
            $end = strpos($csv, "\n", $offset);
            $end = $end === false ? $length : $end;
            $entry = self::parseTrancoLine(substr($csv, $offset, $end - $offset), $keep);
            $offset = $end + 1;

            if ($entry === false) {
                break;
            }

            if ($entry !== null) {
                $domains[$entry[0]] = $entry[1];
            }
        }

        return $domains;
    }

    /**
     * One line of the Tranco list: [domain, position], null for a line to skip, or false once the position is
     * past the sites that are kept (the list is in order, so nothing after it is needed).
     *
     * @return array{0: string, 1: int}|false|null
     */
    private static function parseTrancoLine(string $line, int $keep): array|false|null
    {
        $parts = explode(',', trim($line), 2);

        if (count($parts) !== 2 || ! ctype_digit($parts[0])) {
            return null;
        }

        $position = (int) $parts[0];
        $domain = strtolower(trim($parts[1]));

        if ($position > $keep) {
            return false;
        }

        if ($position < 1 || $domain === '' || ! preg_match('/^[a-z0-9.\-]+$/', $domain)) {
            return null;
        }

        return [$domain, $position];
    }

    /**
     * An address in a form where trivial differences do not matter: scheme and host in lower case, no
     * #fragment, no default port, an empty path written as "/".
     */
    public static function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));

        if ($parts === false || empty($parts['host'])) {
            return strtolower(trim($url));
        }

        $scheme = strtolower($parts['scheme'] ?? 'http');
        $port = $parts['port'] ?? null;
        $keepPort = $port !== null && ! (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443));

        return $scheme.'://'.strtolower($parts['host']).($keepPort ? ':'.$port : '')
            .(($parts['path'] ?? '') === '' ? '/' : $parts['path'])
            .(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private static function isWebAddress(string $value): bool
    {
        return strlen($value) <= 2048 && preg_match('#^https?://[^\s/]+#i', $value) === 1;
    }
}
