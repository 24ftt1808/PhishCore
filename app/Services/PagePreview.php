<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Optional "Show page preview": asks urlscan.io (a security sandbox) to open the scanned link and
 * keeps its screenshot for a few days. Display only: it never changes the score.
 *
 * PRIVACY: the link is sent to urlscan.io. Scans are submitted as "unlisted" (or "private"), never
 * "public", so they do not appear in urlscan's public search.
 */
class PagePreview
{
    public const SUBMIT_URL = 'https://urlscan.io/api/v1/scan/';

    public const RESULT_URL = 'https://urlscan.io/api/v1/result/';

    public const SCREENSHOT_URL = 'https://urlscan.io/screenshots/';

    public const DIRECTORY = 'previews';

    public const MAX_IMAGE_BYTES = 3000000;

    public function enabled(): bool
    {
        return (bool) config('services.page_preview.enabled') && filled(config('services.page_preview.key'));
    }

    /**
     * Ask urlscan.io to open the link. The picture is not ready yet: call check() with the uuid.
     *
     * @return array{status: 'started'|'limit'|'invalid'|'unavailable', uuid?: string}
     */
    public function start(string $url): array
    {
        if (! $this->isCapturable($url)) {
            return ['status' => 'invalid'];
        }

        if (! $this->takeDailySlot()) {
            return ['status' => 'limit'];
        }

        try {
            $response = Http::withHeaders(['API-Key' => (string) config('services.page_preview.key')])
                ->timeout(15)
                ->acceptJson()
                ->post(self::SUBMIT_URL, ['url' => $url, 'visibility' => $this->visibility()]);
        } catch (Throwable) {
            // The message can contain the request address, so it is never logged.
            return $this->unavailable('request_failed');
        }

        if ($response->status() === 429) {
            return $this->unavailable('http_429', 'limit');
        }

        $uuid = $response->successful() ? $response->json('uuid') : null;

        if (! is_string($uuid) || ! $this->isUuid($uuid)) {
            return $this->unavailable('http_'.$response->status(), detail: $response->json('message'));
        }

        return ['status' => 'started', 'uuid' => $uuid];
    }

    /**
     * Has urlscan.io finished? When it has, the screenshot is downloaded and stored.
     *
     * @return array{status: 'pending'|'ready'|'unavailable', path?: string}
     */
    public function check(string $uuid): array
    {
        if (! $this->isUuid($uuid)) {
            return ['status' => 'unavailable'];
        }

        $headers = ['API-Key' => (string) config('services.page_preview.key')];

        try {
            $result = Http::withHeaders($headers)->timeout(15)->acceptJson()->get(self::RESULT_URL.$uuid.'/');
        } catch (Throwable) {
            return $this->unavailable('request_failed');
        }

        // 404 means "still scanning" (it also stays 404 if the scan itself failed, so the caller applies a time limit).
        if ($result->status() === 404) {
            return ['status' => 'pending'];
        }

        if (! $result->successful()) {
            return $this->unavailable('http_'.$result->status());
        }

        try {
            $picture = Http::withHeaders($headers)->timeout(20)->get(self::SCREENSHOT_URL.$uuid.'.png');
        } catch (Throwable) {
            return $this->unavailable('request_failed');
        }

        // The result can be ready a moment before the picture is: keep waiting (the caller applies a time limit).
        if ($picture->status() === 404) {
            return ['status' => 'pending'];
        }

        if (! $picture->successful()) {
            return $this->unavailable('no_image');
        }

        if (! $this->isPng($picture->body())) {
            return $this->unavailable('not_an_image');
        }

        $path = self::DIRECTORY.'/'.Str::random(40).'.png';

        if (! Storage::disk('public')->put($path, $picture->body())) {
            return $this->unavailable('not_saved');
        }

        return ['status' => 'ready', 'path' => $path];
    }

    /** Never "public": a scanned link must not show up in urlscan.io's public search. */
    public function visibility(): string
    {
        return config('services.page_preview.visibility') === 'private' ? 'private' : 'unlisted';
    }

    public function isUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    /** A real, reasonably small PNG (not HTML or anything else dressed up as one). */
    private function isPng(string $body): bool
    {
        if ($body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
            return false;
        }

        $info = @getimagesizefromstring($body);

        return $info !== false && $info['mime'] === 'image/png' && $info[0] >= 1 && $info[1] >= 1;
    }

    /** Only ordinary web links to public addresses are sent out. */
    public function isCapturable(string $url): bool
    {
        if ($url === '' || strlen($url) > 2000) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''), '[]');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    /** One shared allowance for the whole site, so a burst of clicks cannot use up the free quota. */
    private function takeDailySlot(): bool
    {
        $key = 'page-preview:day:'.now()->format('Ymd');
        $cap = (int) config('services.page_preview.daily_cap');

        if (RateLimiter::tooManyAttempts($key, $cap)) {
            return false;
        }

        RateLimiter::hit($key, 86400);

        return true;
    }

    /**
     * @param  'unavailable'|'limit'  $status
     * @return array{status: string}
     */
    private function unavailable(string $reason, string $status = 'unavailable', mixed $detail = null): array
    {
        // urlscan.io's own short explanation (e.g. why it refused a link) helps the developer; it never contains the key.
        $context = is_string($detail) && $detail !== ''
            ? ['reason' => $reason, 'detail' => Str::limit($detail, 150)]
            : ['reason' => $reason];

        Log::warning('Page preview unavailable', $context);

        return ['status' => $status];
    }

    /** A site-relative address, so the picture still loads if the domain or http/https setting changes. */
    public function publicUrl(string $path): string
    {
        return '/storage/'.$path;
    }

    /** The disk path behind a saved picture address, or null if it is not one of ours. */
    public function storedPath(?string $address): ?string
    {
        if ($address === null || ! preg_match('#/storage/('.self::DIRECTORY.'/[A-Za-z0-9]{40}\.png)$#', $address, $match)) {
            return null;
        }

        return $match[1];
    }

    public function exists(?string $address): bool
    {
        $path = $this->storedPath($address);

        return $path !== null && Storage::disk('public')->exists($path);
    }

    /** Deletes pictures older than $days and clears the links to them. Returns how many were deleted. */
    public function prune(int $days): int
    {
        $disk = Storage::disk('public');
        $cutoff = now()->subDays($days)->getTimestamp();
        $deleted = 0;

        foreach ($disk->files(self::DIRECTORY) as $file) {
            if ($disk->lastModified($file) >= $cutoff) {
                continue;
            }

            $disk->delete($file);
            Report::where('screenshot_path', 'like', '%/storage/'.$file)->update(['screenshot_path' => null]);
            $deleted++;
        }

        return $deleted;
    }
}
