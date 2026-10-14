<?php

namespace App\Services;

use App\Models\ScamNews;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

/**
 * Reads the RSS / Atom feeds in config/scamnews.php and saves new articles for the dashboard's
 * "Latest Scam News" box. Only the headline, link, source, date and (when the feed gives one) the
 * picture's address are kept; the article text is never copied.
 *
 * One broken feed never stops the others, and articles that are already saved are left alone.
 * It is run once a day by news:fetch and on demand by the "Fetch now" button on the Scam News page.
 */
class ScamNewsFetcher
{
    private const LAST_RUN_KEY = 'scamnews:last_run';

    private const MAX_FEED_BYTES = 3_000_000;

    private const NS_MEDIA = 'http://search.yahoo.com/mrss/';

    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    private const NS_DC = 'http://purl.org/dc/elements/1.1/';

    /**
     * Fetch every feed, save the new articles and delete the very old ones.
     *
     * @return array{at: string, added: int, pruned: int, feeds: list<array{name: string, ok: bool, added: int, error: string|null}>}
     */
    public function run(): array
    {
        $feeds = [];
        $added = 0;

        foreach ((array) config('scamnews.feeds', []) as $feed) {
            $result = $this->fetchFeed((array) $feed);
            $added += $result['added'];
            $feeds[] = $result;
        }

        $summary = [
            'at' => now()->toIso8601String(),
            'added' => $added,
            'pruned' => $this->prune(),
            'feeds' => $feeds,
        ];

        Cache::forever(self::LAST_RUN_KEY, $summary);

        return $summary;
    }

    /**
     * What the last run did (shown on the Scam News admin page and used for the "refreshed" label).
     *
     * @return array{at: string, added: int, pruned: int, feeds: list<array{name: string, ok: bool, added: int, error: string|null}>}|null
     */
    public static function lastRun(): ?array
    {
        $summary = Cache::get(self::LAST_RUN_KEY);

        return is_array($summary) ? $summary : null;
    }

    /** Delete automatically saved articles that are very old. Articles added by hand are kept. */
    public function prune(): int
    {
        return ScamNews::query()
            ->where('is_manual', false)
            ->where('published_at', '<', now()->subDays((int) config('scamnews.prune_days')))
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $feed
     * @return array{name: string, ok: bool, added: int, error: string|null}
     */
    private function fetchFeed(array $feed): array
    {
        $url = (string) ($feed['url'] ?? '');
        $name = (string) ($feed['name'] ?? $url);

        try {
            $response = Http::timeout((int) config('scamnews.timeout'))
                ->withHeaders([
                    'User-Agent' => 'PhishCore/1.0 (scam news reader)',
                    'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml',
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('The feed answered with HTTP '.$response->status().'.');
            }

            $body = $response->body();

            if (strlen($body) > self::MAX_FEED_BYTES) {
                throw new RuntimeException('The feed is too large.');
            }

            $items = $this->parse($body);
        } catch (Throwable $e) {
            Log::warning('Scam news feed failed: '.$name, ['error' => $e->getMessage()]);

            return ['name' => $name, 'ok' => false, 'added' => 0, 'error' => Str::limit($e->getMessage(), 160)];
        }

        $oldest = now()->subDays((int) config('scamnews.max_age_days'));

        $items = collect($items)
            ->filter(fn (array $item) => $item['published_at']->gte($oldest))
            ->when(! empty($feed['filter']), fn ($items) => $items->filter(
                fn (array $item) => $this->isAboutScams($item['title'].' '.$item['summary'])
            ))
            ->sortByDesc(fn (array $item) => $item['published_at']->getTimestamp())
            ->take((int) config('scamnews.per_feed'));

        $added = 0;

        foreach ($items as $item) {
            $hash = ScamNews::hashFor($item['url']);

            // Already saved (or hidden on purpose): leave it alone, and never open its page again.
            if (ScamNews::where('url_hash', $hash)->exists()) {
                continue;
            }

            $image = $item['image_url'];
            if ($image === null && ! empty($feed['page_image'])) {
                $image = $this->pageImage($item['url'], $url);
            }

            $article = ScamNews::firstOrCreate(
                ['url_hash' => $hash],
                [
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'source' => $feed['source'] ?? ($item['publisher'] ?: $name),
                    'image_url' => $image,
                    'published_at' => $item['published_at'],
                    'is_visible' => true,
                    'is_manual' => false,
                ]
            );

            if ($article->wasRecentlyCreated) {
                $added++;
            }
        }

        return ['name' => $name, 'ok' => true, 'added' => $added, 'error' => null];
    }

    /**
     * Turn the feed's XML into a plain list of articles.
     *
     * @return list<array{title: string, url: string, summary: string, publisher: string, image_url: string|null, published_at: Carbon}>
     */
    private function parse(string $body): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new RuntimeException('The feed is not valid XML.');
        }

        $items = [];

        if ($xml->getName() === 'feed') {
            foreach ($xml->entry as $entry) {
                $items[] = $this->atomItem($entry);
            }
        } else {
            // RSS 2.0 keeps its items inside <channel>; RSS 1.0 keeps them at the top.
            foreach ($xml->channel->item as $item) {
                $items[] = $this->rssItem($item);
            }
            foreach ($xml->item as $item) {
                $items[] = $this->rssItem($item);
            }
        }

        return array_values(array_filter($items));
    }

    /** @return array{title: string, url: string, summary: string, publisher: string, image_url: string|null, published_at: Carbon}|null */
    private function rssItem(SimpleXMLElement $item): ?array
    {
        $link = trim((string) $item->link);
        if ($link === '') {
            $link = trim((string) $item->guid);
        }

        $date = (string) $item->pubDate;
        if ($date === '') {
            $date = (string) $item->children(self::NS_DC)->date;
        }

        $description = (string) $item->description;
        $content = (string) $item->children(self::NS_CONTENT)->encoded;

        return $this->build(
            title: (string) $item->title,
            link: $link,
            summaryHtml: $description,
            imageHtml: $description.' '.$content,
            publisher: (string) $item->source,
            date: $date,
            image: $this->mediaImage($item),
        );
    }

    /** @return array{title: string, url: string, summary: string, publisher: string, image_url: string|null, published_at: Carbon}|null */
    private function atomItem(SimpleXMLElement $entry): ?array
    {
        $link = '';
        foreach ($entry->link as $candidate) {
            $rel = (string) ($candidate['rel'] ?? '');
            if ($rel === '' || $rel === 'alternate') {
                $link = (string) ($candidate['href'] ?? '');
                break;
            }
        }

        $date = (string) $entry->published;
        if ($date === '') {
            $date = (string) $entry->updated;
        }

        $summary = (string) $entry->summary;
        $content = (string) $entry->content;

        return $this->build(
            title: (string) $entry->title,
            link: $link,
            summaryHtml: $summary !== '' ? $summary : $content,
            imageHtml: $summary.' '.$content,
            publisher: '',
            date: $date,
            image: $this->mediaImage($entry),
        );
    }

    /** @return array{title: string, url: string, summary: string, publisher: string, image_url: string|null, published_at: Carbon}|null */
    private function build(string $title, string $link, string $summaryHtml, string $imageHtml, string $publisher, string $date, ?string $image): ?array
    {
        $url = $this->safeUrl($link);
        $publisher = $this->clean($publisher, 120);
        $title = $this->clean($title, 300);

        // Google News writes "Headline - Publisher"; the publisher is shown on its own.
        if ($publisher !== '' && Str::endsWith($title, ' - '.$publisher)) {
            $title = rtrim(Str::beforeLast($title, ' - '.$publisher));
        }

        if ($url === null || $title === '') {
            return null;
        }

        if ($image === null && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $imageHtml, $match) === 1) {
            $image = $this->safeUrl(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return [
            'title' => $title,
            'url' => $url,
            'summary' => $this->clean($summaryHtml, 600),
            'publisher' => $publisher,
            'image_url' => $image,
            'published_at' => $this->date($date),
        ];
    }

    /** A picture the feed hands over directly (media:thumbnail, media:content or an image enclosure). */
    private function mediaImage(SimpleXMLElement $item): ?string
    {
        $media = $item->children(self::NS_MEDIA);

        foreach ([$media->thumbnail, $media->content, $item->enclosure] as $nodes) {
            foreach ($nodes as $node) {
                // attributes() on purpose: for a tag read through children('...mrss/'), ['url'] would look
                // for the attribute inside that namespace and find nothing.
                $attributes = $node->attributes();
                $url = (string) ($attributes['url'] ?? '');
                $type = (string) ($attributes['type'] ?? '');
                $medium = (string) ($attributes['medium'] ?? '');

                if ($this->looksLikeImage($url, $type, $medium) && ($safe = $this->safeUrl($url)) !== null) {
                    return $safe;
                }
            }
        }

        return null;
    }

    /**
     * The preview picture of an article (its og:image tag), for feeds that give none. Only pages on the
     * feed's own site are opened and redirects are not followed, so a feed can never make the server
     * visit other addresses.
     */
    private function pageImage(string $articleUrl, string $feedUrl): ?string
    {
        if (! $this->isSameSite($articleUrl, $feedUrl)) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->withoutRedirecting()
                ->withHeaders(['User-Agent' => 'PhishCore/1.0 (scam news reader)', 'Accept' => 'text/html'])
                ->get($articleUrl);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        // The picture is always in the <head>, so only the start of the page is read.
        $html = substr($response->body(), 0, 300_000);

        if (! preg_match_all('/<meta\s[^>]*>/i', $html, $tags)) {
            return null;
        }

        foreach ($tags[0] as $tag) {
            $isPicture = preg_match('/(?:property|name)\s*=\s*["\'](?:og:image(?::secure_url|:url)?|twitter:image(?::src)?)["\']/i', $tag) === 1;

            if (! $isPicture || preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $tag, $content) !== 1) {
                continue;
            }

            $picture = html_entity_decode($content[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (($safe = $this->safeUrl($this->absoluteUrl($picture, $articleUrl))) !== null) {
                return $safe;
            }
        }

        return null;
    }

    /** The same site, ignoring a leading "www." */
    private function isSameSite(string $first, string $second): bool
    {
        $host = fn (string $url): string => (string) preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));

        return $host($first) !== '' && $host($first) === $host($second);
    }

    /** Complete "//host/pic.jpg" and "/pic.jpg" using the page they were found on. */
    private function absoluteUrl(string $url, string $pageUrl): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return parse_url($pageUrl, PHP_URL_SCHEME).'://'.parse_url($pageUrl, PHP_URL_HOST).$url;
        }

        return $url;
    }

    private function looksLikeImage(string $url, string $type, string $medium): bool
    {
        return str_starts_with($type, 'image/')
            || $medium === 'image'
            || preg_match('/\.(jpe?g|png|gif|webp|avif)(\?|$)/i', $url) === 1;
    }

    /** Only normal web addresses are kept (no javascript:, data: and so on). */
    private function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || strlen($url) > 2048 || preg_match('#^https?://#i', $url) !== 1) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
    }

    /** Plain text only: no tags, no leftover HTML entities, one line, cut to fit the column. */
    private function clean(string $text, int $max): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if (mb_strlen($text) > $max) {
            $text = rtrim(mb_substr($text, 0, $max - 1)).'…';
        }

        return $text;
    }

    private function date(string $raw): Carbon
    {
        try {
            $date = trim($raw) === '' ? now() : Carbon::parse($raw);
        } catch (Throwable) {
            $date = now();
        }

        // A wrong clock on the feed's side must not pin an article to the top forever.
        return $date->isFuture() ? now() : $date;
    }

    private function isAboutScams(string $text): bool
    {
        return Str::contains($text, (array) config('scamnews.keywords', []), ignoreCase: true);
    }
}
