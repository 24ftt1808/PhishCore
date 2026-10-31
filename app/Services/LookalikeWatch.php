<?php

namespace App\Services;

use App\Models\LookalikeDomain;
use App\Models\LookalikeWatchRun;
use App\Models\User;
use App\Notifications\LookalikeWatchNotice;
use App\Support\LookAlike;
use App\Support\RegistrableDomain;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

/**
 * Look-alike Watch: finds new websites whose names copy a Brunei brand, before anyone scans them.
 *
 * Every HTTPS website needs a security certificate, and every certificate is published in public logs. Once a day
 * this asks crt.sh (a free search of those logs) for recent certificates whose names contain the words in
 * config/brands.php "lookalike_watch", keeps the names that really copy a brand (match()), and lists them for the
 * team. A listing is a lead, not a verdict: the team scans it or dismisses it.
 *
 * crt.sh is free and sometimes slow or down. A word that fails is skipped and the run is logged, so the page can
 * say when the last check worked; the next run tries again.
 */
class LookalikeWatch
{
    /** Words a brand is often joined to in a fake name ("bibdlogin", "baiduribankverify"). */
    private const LURE_ENDINGS = 'login|online|bank|banking|secure|verify|verification|reward|rewards|app|pay|update|bn|brunei|promo|gift|claim|my|official|support|help|service|services|account|portal|digital|insurans|insurance|topup|bonus|free';

    /** @return array{anywhere: array<string, array<int, string>>, beside_brunei: array<string, array<int, string>>} */
    public function watchlist(): array
    {
        $list = config('brands.lookalike_watch', []);

        return ['anywhere' => $list['anywhere'] ?? [], 'beside_brunei' => $list['beside_brunei'] ?? []];
    }

    /**
     * Whether a website name copies a watched brand. Returns the brand word and whether the name respells it with
     * look-alike characters ("b1bd"), or null for a name that does not copy one, or is the brand's own website, or
     * is a Brunei government or school website.
     *
     * @return array{brand: string, respelled: bool}|null
     */
    public function match(string $host): ?array
    {
        $host = rtrim(strtolower(trim(LookAlike::decode(trim($host)))), '.');
        $host = (string) preg_replace('/^(\*\.)+/', '', $host);

        if ($host === '' || str_contains($host, ' ') || preg_match('/^[\d.]+$/', $host)) {
            return null;
        }
        if (preg_match('/(^|\.)(gov|edu)\.bn$/', $host) || $this->isOfficial($host)) {
            return null;
        }

        $registered = RegistrableDomain::of($host);
        if ($registered === null || ! str_contains($registered, '.')) {
            return null;
        }

        // The part a scammer chooses: everything before the ending (".com", ".com.bn", ".netlify.app").
        $suffix = substr($registered, strpos($registered, '.') + 1);
        $name = (string) preg_replace('/^www\./', '', substr($host, 0, -strlen($suffix) - 1));
        $watch = $this->watchlist();

        foreach ($this->spellings($name) as $spelling) {
            $tokens = preg_split('/[.\-_]+/', $spelling, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $squashed = implode('', $tokens);
            $respelled = $spelling !== $name;

            foreach (array_keys($watch['anywhere']) as $word) {
                // A respelled short name only counts in a name built from several words ("b1bd-online.com"):
                // on its own "bdc8" is just letters and a number.
                if ($respelled && strlen($word) <= 4 && ! str_contains($name, '-')) {
                    continue;
                }
                if ($this->brandWordIn($word, $tokens)) {
                    return ['brand' => $word, 'respelled' => $respelled];
                }
            }

            if (str_contains($squashed, 'brunei')) {
                foreach (array_keys($watch['beside_brunei']) as $word) {
                    if (in_array($word, $tokens, true) || str_contains($squashed, $word.'brunei') || str_contains($squashed, 'brunei'.$word)) {
                        return ['brand' => $word, 'respelled' => $respelled];
                    }
                }
            }
        }

        return null;
    }

    /**
     * One check: ask the certificate logs, keep the names that copy a brand, save the new ones and tell the team.
     *
     * @return array{ok: bool, names_checked: int, new_found: int, note: ?string}
     */
    public function run(int $days = 7): array
    {
        $since = now()->subDays($days);
        $words = [...array_keys($this->watchlist()['anywhere']), 'brunei'];
        $names = [];
        $sourceOf = [];
        $failed = [];

        $found = [];
        $collect = function (array $certificates, string $word) use ($since, &$names, &$found): void {
            $found[$word] = count($certificates);
            foreach ($certificates as $certificate) {
                $issued = isset($certificate['not_before']) ? Carbon::parse($certificate['not_before']) : null;
                if ($issued !== null && $issued->lt($since)) {
                    continue;
                }
                foreach (preg_split('/\s+/', (string) ($certificate['name_value'] ?? '')) ?: [] as $one) {
                    // "www." is the same website, so it is listed once.
                    $one = (string) preg_replace('/^((\*|www)\.)+/', '', strtolower(trim($one)));
                    if ($one !== '' && (! isset($names[$one]) || ($issued && $issued->lt($names[$one])))) {
                        $names[$one] = $issued;
                    }
                }
            }
        };

        foreach ($words as $i => $word) {
            if ($i > 0) {
                Sleep::for(2)->seconds(); // be gentle with a free service
            }

            $certificates = $this->searchCertificateLogs($word, 45);
            $certificates === null ? $failed[] = $word : $collect($certificates, $word);
        }

        // crt.sh is often busy for a moment: the words that timed out get one more, longer try at the end.
        foreach ($failed as $j => $word) {
            Sleep::for(5)->seconds();
            $certificates = $this->searchCertificateLogs($word, 90);
            if ($certificates !== null) {
                $collect($certificates, $word);
                unset($failed[$j]);
            }
        }
        $failed = array_values($failed);

        // Second source: the free daily lists of newly registered domains (WhoisDS). crt.sh often drops wildcard
        // searches when it is busy, so these lists are what keeps the watch working on those days.
        $registeredBy = $this->newlyRegisteredDomains(min($days, 7));
        foreach ($registeredBy['names'] as $one => $date) {
            if (! isset($names[$one])) {
                $names[$one] = $date;
                $sourceOf[$one] = 'new-domains';
            }
        }

        // Listings from before a matching rule was tightened, which the rules now say are not copies, are removed
        // (only ones nobody has dismissed; dismissed ones stay as a record).
        LookalikeDomain::where('status', 'new')->get()
            ->filter(fn (LookalikeDomain $d) => $this->match($d->domain) === null)
            ->each->delete();

        $engine = new AnalysisEngine;
        $new = [];
        foreach ($names as $name => $issued) {
            $match = $this->match($name);
            if ($match === null || LookalikeDomain::where('domain', $name)->exists()) {
                continue;
            }
            $new[] = LookalikeDomain::create([
                'domain' => $name,
                'brand' => $match['brand'],
                'source' => $sourceOf[$name] ?? 'crt.sh',
                'certificate_issued_at' => $issued,
                'address_points' => (int) ($engine->checkUrlSyntax('https://'.$name)['points'] ?? 0),
            ]);
        }

        $ok = count($failed) < count($words) || $registeredBy['days'] > 0;
        $read = collect($found)->map(fn (int $count, string $word) => "{$word} {$count}")->implode(', ');
        $note = trim(($registeredBy['days'] > 0 ? 'New-domain lists read: '.number_format($registeredBy['total']).' domains over '.$registeredBy['days'].' '.($registeredBy['days'] === 1 ? 'day' : 'days').'. ' : 'The new-domain lists could not be downloaded. ')
            .($read !== '' ? "Certificates found (all dates): {$read}. " : '')
            .($failed === [] ? '' : 'The certificate logs did not answer for: '.implode(', ', $failed).'. These are tried again next time.'));
        $note = $note === '' ? null : mb_substr($note, 0, 500);
        LookalikeWatchRun::create(['ok' => $ok, 'names_checked' => count($names), 'new_found' => count($new), 'note' => $note]);

        if ($new !== []) {
            $this->tellTheTeam($new);
        }

        return ['ok' => $ok, 'names_checked' => count($names), 'new_found' => count($new), 'note' => $note];
    }

    /**
     * Recent certificates whose names contain the word, or null when crt.sh is down, slow or answers with something
     * that is not a list.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function searchCertificateLogs(string $word, int $timeout = 45): ?array
    {
        try {
            $response = Http::timeout($timeout)->acceptJson()->get('https://crt.sh/', [
                'q' => '%'.$word.'%',
                'output' => 'json',
                'exclude' => 'expired',
                'deduplicate' => 'Y',
            ]);
        } catch (\Throwable $e) {
            Log::info('Look-alike Watch: crt.sh did not answer', ['word' => $word, 'error' => $e->getMessage()]);

            return null;
        }

        $data = $response->successful() ? $response->json() : null;
        if (! is_array($data) || ($data !== [] && ! array_is_list($data))) {
            Log::info('Look-alike Watch: crt.sh gave no list', ['word' => $word, 'status' => $response->status()]);

            return null;
        }

        return $data;
    }

    /**
     * Names from the free daily lists of newly registered domains (WhoisDS: one zip file per day, a plain list of every
     * new domain under the common endings such as .com, .net, .top and .xyz). Only names that copy a watched brand are
     * kept, with the day they were registered. A day whose list is not out yet, or fails, is skipped.
     *
     * @return array{names: array<string, Carbon>, days: int, total: int}
     */
    private function newlyRegisteredDomains(int $days): array
    {
        $names = [];
        $daysRead = 0;
        $total = 0;

        for ($ago = 1; $ago <= max(1, $days); $ago++) {
            $date = now()->subDays($ago)->startOfDay();
            $lines = $this->downloadNewDomainList($date->format('Y-m-d'));
            if ($lines === null) {
                continue;
            }
            $daysRead++;
            $total += count($lines);

            foreach ($lines as $line) {
                $line = strtolower(trim($line));
                // A quick word check first: most of the 100,000 names a day have no brand word at all.
                if ($line === '' || ! $this->mightCopyABrand($line)) {
                    continue;
                }
                if (! isset($names[$line]) && $this->match($line) !== null) {
                    $names[$line] = $date;
                }
            }
        }

        return ['names' => $names, 'days' => $daysRead, 'total' => $total];
    }

    /** @return array<int, string>|null one domain per line, or null when that day's list is not available */
    private function downloadNewDomainList(string $date): ?array
    {
        $url = 'https://www.whoisds.com/whois-database/newly-registered-domains/'.base64_encode($date.'.zip').'/nrd';

        try {
            $response = Http::timeout(60)->get($url);
        } catch (\Throwable $e) {
            Log::info('Look-alike Watch: new-domain list did not download', ['date' => $date, 'error' => $e->getMessage()]);

            return null;
        }

        $body = $response->body();
        if (! $response->successful() || ! str_starts_with($body, 'PK') || ! class_exists(\ZipArchive::class)) {
            return null;
        }

        $file = tempnam(sys_get_temp_dir(), 'nrd');
        file_put_contents($file, $body);
        $zip = new \ZipArchive;
        $text = null;
        if ($zip->open($file) === true) {
            $text = $zip->getFromName('domain-names.txt');
            if ($text === false && $zip->numFiles > 0) {
                $text = $zip->getFromIndex(0);
            }
            $zip->close();
        }
        @unlink($file);

        return is_string($text) && $text !== ''
            ? array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn (string $line): bool => $line !== ''))
            : null;
    }

    /** Whether the name contains any watched word, plainly or respelled ("b1bd"): a fast filter before match(). */
    private function mightCopyABrand(string $name): bool
    {
        $words = [...array_keys($this->watchlist()['anywhere']), 'brunei'];
        foreach ($this->spellings($name) as $spelling) {
            $squashed = str_replace(['-', '.', '_'], '', $spelling);
            foreach ($words as $word) {
                if (str_contains($squashed, $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The brand as a word of its own in the name ("bibd-login"), or joined to "my"/"e" in front or to lure words and
     * numbers behind ("mybibd", "bibdlogin", "baiduribankverify", "bibd2"). Never in the middle of another word:
     * "cantarus", "staruss" and Chinese pinyin that happens to spell "baiduri" are not copies.
     */
    private function brandWordIn(string $word, array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (preg_match('/^(my|e|i|the|official|secure)?'.preg_quote($word, '/').'(('.self::LURE_ENDINGS.')|\d{1,4})*$/', $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The name as written, plus the name with look-alike characters read back as letters ("b1bd" as "bibd",
     * "baidur1" as "baiduri", "rnaybank" as "maybank"). A "1" can stand for "i" or "l", so both are tried.
     *
     * @return array<int, string>
     */
    private function spellings(string $name): array
    {
        $common = ['0' => 'o', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '8' => 'b', 'rn' => 'm', 'vv' => 'w'];
        $withI = strtr($name, $common + ['1' => 'i']);
        $withL = strtr($name, $common + ['1' => 'l']);

        return array_values(array_unique([$name, $withI, $withL]));
    }

    /** The real website of any watched or known Brunei brand, or a page on it. */
    private function isOfficial(string $host): bool
    {
        $official = [];
        foreach ($this->watchlist() as $group) {
            foreach ($group as $domains) {
                $official = [...$official, ...$domains];
            }
        }
        foreach (config('brands.brunei_brands', []) as $domains) {
            $official = [...$official, ...$domains];
        }

        foreach (array_unique($official) as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<int, LookalikeDomain>  $new */
    private function tellTheTeam(array $new): void
    {
        $recipients = User::whereNull('suspended_at')
            ->where(fn ($q) => $q->where('is_team_member', true)->orWhere('role', 'admin'))
            ->get();

        Notification::send($recipients, new LookalikeWatchNotice(count($new), $new[0]->domain));
    }
}