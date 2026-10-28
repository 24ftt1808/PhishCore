<?php

namespace App\Services;

use App\Models\HiddenNumber;
use App\Models\KnownScamNumber;
use App\Models\NumberReport;
use App\Models\Report;
use App\Services\Concerns\ScansFiles;
use App\Support\EmailHeaders;
use App\Support\LookAlike;
use App\Support\PageInspector;
use App\Support\Rdap;
use App\Support\RegistrableDomain;
use App\Support\SafeUrl;
use App\Support\ThreatLookup;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Iodev\Whois\Factory;
use Iodev\Whois\Loaders\SocketLoader;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Zxing\QrReader;

class AnalysisEngine
{
    use ScansFiles;

    /**
     * Brand data lives in config/brands.php (see the note at the top of that file), so adding a brand
     * is a data change, not a code change.
     *
     * @return array<string, array<int, string>> brand => its official domains
     */
    private function officialBrandDomains(): array
    {
        return config('brands.official_domains', []);
    }

    /** @return array<string, array<int, string>> Brunei brand (a name that is not an everyday word) => its official domains */
    private function bruneiBrands(): array
    {
        return config('brands.brunei_brands', []);
    }

    /** @return array<string, array<int, string>> Brunei acronym (written in capitals) => its official domains */
    private function bruneiAcronyms(): array
    {
        return config('brands.brunei_acronyms', []);
    }

    /** @return array<int, string> government names whose real home is any .gov.bn site */
    private function bruneiGovNames(): array
    {
        return config('brands.brunei_gov_names', []);
    }

    public function checkUrlSyntax(string $url): array
    {
        $reasons = [];
        $points = 0;
        // Points that come only from "a well-known brand name sits in this address". Kept apart so a scan
        // can calm that one rule down for an old, clean site (see softenBrandWordForEstablishedSite).
        $brandRulePoints = 0;

        if (preg_match('/^https?:\/\/(\d{1,3}\.){3}\d{1,3}/', $url)) {
            $reasons[] = 'URL uses a raw IP address instead of a domain name';
            $points += 25;
        }

        if (str_contains($url, '@')) {
            $reasons[] = 'URL contains an "@" symbol, which can mask the real destination';
            $points += 25;
        }

        $hyphenCount = LookAlike::hyphenCount(parse_url($url, PHP_URL_HOST) ?? '');
        if ($hyphenCount >= 2) {
            $reasons[] = "Domain contains {$hyphenCount} hyphens, which is unusually high";
            $points += 15;
        }

        if (strlen($url) > 75) {
            $reasons[] = 'URL is unusually long';
            $points += 10;
        }

        $brands = ['paypal', 'google', 'facebook', 'apple', 'microsoft', 'amazon', 'bank',
            // gaming platforms: only names that are not everyday words ("steam" is handled below)
            'roblox', 'discord', 'epicgames', 'riotgames', 'garena', 'minecraft', 'playstation', 'xbox', 'nintendo', 'steampowered', 'steamcommunity'];
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $normalizedHost = $this->normalizeForBrandMatch($host);
        // A brand's own site can contain a brand word without imitating
        // anything: "maybank2u.com.my" contains "bank" and is Maybank's real
        // domain. Exact official domains (and their subdomains) are skipped.
        // A very popular site (top of the Tranco ranking, see ThreatLookup) is treated the same way: it is
        // a well-known name, not a fresh copy of one, so the spelling rules below leave it alone.
        $isOfficialBrandSite = $this->isOfficialBrandDomain($host) || ThreatLookup::isPopular($host);
        foreach ($brands as $brand) {
            $matchesBrand = ! $isOfficialBrandSite && str_contains($normalizedHost, $brand);
            // The legit-domain exclusion must run on the ORIGINAL host, not the
            // normalized one — otherwise a lookalike like "micros0ft.com" would
            // normalize into looking identical to "microsoft.com" and get
            // incorrectly whitelisted instead of flagged.
            $isLegitDomain = $host === $brand.'.com' || str_ends_with($host, '.'.$brand.'.com');

            if ($matchesBrand && ! $isLegitDomain) {
                // An ordinary word ("bank") is not one organisation's name, so on its own it is only a mild
                // warning; it counts in full when something else about the address looks like a disguise.
                $isOrdinaryWord = in_array($brand, self::ORDINARY_WORD_BRANDS, true)
                    && ! $this->addressLooksDisguised($host, $brand);

                $reasons[] = str_contains($host, $brand)
                    ? ($isOrdinaryWord
                        ? "Contains the common word \"{$brand}\" in the domain, which is only a mild warning on its own"
                        : "Contains brand name \"{$brand}\" in a suspicious position within the domain")
                    : "Domain appears to mimic \"{$brand}\" using character substitution (e.g. a digit in place of a letter)";
                $weight = $isOrdinaryWord ? 10 : 30;
                $points += $weight;
                // only a plain brand word can be calmed down; a digit-for-letter disguise ("paypa1") never is
                $brandRulePoints = str_contains($host, $brand) ? $weight : 0;
                break;
            }
        }

        // "steam" is also an ordinary word (steamer, steamboat), so it only counts as the brand when it
        // stands alone in the address or is joined to a word scams use ("steam-gift", "steamwallet").
        if (! $isOfficialBrandSite && ! preg_grep('/brand name|mimic/', $reasons)
            && preg_match('/(^|[.\-])steam(-|community|powered|gift|wallet|trade|login|support|free|games?|keys?|offers?|rewards?|[.\-]|$)/', $host)) {
            $reasons[] = 'Contains brand name "steam" in a suspicious position within the domain';
            $points += 30;
            $brandRulePoints = 30;
        }

        // Brunei-specific lookalikes. Official domains differ from the
        // naive "brand.com" guess (e.g. bibd.com.bn), so they are checked
        // against an explicit allow-list rather than the loop above.
        if (! preg_grep('/brand name|mimic/', $reasons)) {
            $bnBrands = [
                'bibd' => ['bibd.com.bn'],
                'baiduri' => ['baiduri.com.bn'],
                'bdcb' => ['bdcb.gov.bn'],
                'progresif' => ['progresif.com', 'progresifevents.com'],
                'mydst' => ['dst.com.bn'],
                'tarus' => ['tarus.com.bn'],
                'bruneipost' => ['post.gov.bn'],
                'posbrunei' => ['post.gov.bn'],
                'posbn' => ['post.gov.bn'],
                'royalbrunei' => ['flyroyalbrunei.com'],
                'edarussalam' => ['gov.bn'],
                'shopee' => $this->officialBrandDomains()['shopee'],
                'lazada' => $this->officialBrandDomains()['lazada'],
            ];
            // Short names only count as a hyphenated word in the address ("dst-update.online", not
            // "midstream.com" or another country's "dst.com"); TAP also needs a pension word, as
            // "tap" is an everyday word.
            $bnWords = [
                'dst' => ['dst.com.bn'],
                'jpd' => ['jpd.gov.bn'],
                'rbpf' => ['polis.gov.bn', 'police.gov.bn'],
                'taib' => ['taib.com.bn', 'insuranstaib.com.bn'],
                'tap' => ['tap.com.bn'],
            ];
            // A "1" can stand for "i" as well as "l" ("b1bd"), so both readings are tried.
            $hostReadings = array_unique([$normalizedHost, strtr($host, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '8' => 'b']), str_replace('-', '', $normalizedHost)]);
            $imitated = null;
            foreach ($bnBrands as $bnBrand => $officialDomains) {
                // Shopee and Lazada run many real sites of their own (shopeepay, shopeefood), so
                // only a name joined with a hyphen ("shopee-buyer-pay", "shopee-jkt1777") counts.
                if (in_array($bnBrand, ['shopee', 'lazada'], true) && ! preg_match('/(^|[.\-])'.$bnBrand.'-|-'.$bnBrand.'([.\-]|$)/', $host)) {
                    continue;
                }
                foreach ($hostReadings as $reading) {
                    if (str_contains($reading, $bnBrand)) {
                        $imitated ??= [$bnBrand, $officialDomains];
                    }
                }
            }
            foreach ($bnWords as $word => $officialDomains) {
                if ($imitated === null && preg_match('/(^|[.\-])'.$word.'-|-'.$word.'([.\-]|$)/', $host)
                    && ($word !== 'tap' || preg_match('/spk|pencen|pension|caruman|amanah|withdraw|pengeluaran/', $host))) {
                    $imitated = [$word, $officialDomains];
                }
            }
            if ($imitated !== null) {
                [$bnBrand, $officialDomains] = $imitated;
                $isOfficial = false;
                foreach ($officialDomains as $official) {
                    if ($host === $official || str_ends_with($host, '.'.$official)) {
                        $isOfficial = true;
                        break;
                    }
                }
                if (! $isOfficial) {
                    $label = in_array($bnBrand, ['shopee', 'lazada'], true) ? 'the shopping site' : 'the Brunei brand';
                    $shown = isset($bnWords[$bnBrand]) ? strtoupper($bnBrand) : $bnBrand;
                    $reasons[] = "Domain imitates {$label} \"{$shown}\" but is not its official domain";
                    $points += 30;
                }
            }
        }

        // A distinctive brand name from config/brands.php inside the address ("netflix-billing.com",
        // "ninjavan-delivery.com"). The short fixed list above only covers a few brands; this one grows
        // with the config. Counted as a brand-word rule, so a clean, long-established site is calmed down
        // the same way (see softenBrandWordForEstablishedSite).
        if (! $isOfficialBrandSite && ! preg_grep('/brand name|mimic|imitates/', $reasons)
            && ! in_array(RegistrableDomain::of($host), array_map('strtolower', config('brands.known_lookalikes', [])), true)) {
            foreach ($this->distinctiveBrandNames() as $name) {
                if (str_contains($host, $name)) {
                    $reasons[] = "Contains brand name \"{$name}\" in a suspicious position within the domain";
                    $points += 30;
                    $brandRulePoints = 30;
                    break;
                }
            }
        }

        // A well-known name used as the start of an address that belongs to another website, together with a word
        // scams use to sound official ("outlook.webaccess-alert.com"). Only when no brand-word rule above has
        // already fired, and counted as a brand-word rule, so a clean, long-established site is calmed down the same way.
        if (! $isOfficialBrandSite && ! preg_grep('/brand name|mimic|imitates/', $reasons)
            && ($asSubdomain = $this->brandUsedAsSubdomain($host)) !== null) {
            $reasons[] = "The brand name \"{$asSubdomain['brand']}\" is used at the start of an address that belongs to a different website, and the address also contains a word scams use to sound official (\"{$asSubdomain['word']}\")";
            $points += 25;
            $brandRulePoints = 25;
        }

        // A name written to be mistaken for a known brand ("gooogle", "arnazon", a Cyrillic "а" in "аpple").
        // Only when no brand-word rule above has already fired, so one fake is not counted twice.
        if (! $isOfficialBrandSite && ! preg_grep('/brand name|mimic|imitates|looks like|alphabets/', $reasons)
            && ($lookAlike = $this->lookAlikeFinding($host)) !== null) {
            $reasons[] = $this->lookAlikeReason($lookAlike);
            $points += $lookAlike['type'] === 'typo' ? 25 : 30;
        }

        // Fake Brunei-government look: "gov.bn" / "gov-bn" used inside a
        // domain that does not actually end in .gov.bn.
        if (preg_match('/gov[.\-]?bn/', $host) && ! str_ends_with($host, '.gov.bn') && $host !== 'gov.bn') {
            $reasons[] = 'Domain pretends to be a Brunei government site (contains "gov.bn") but is not under .gov.bn';
            $points += 30;
        } elseif (
            preg_match('/brunei|(^|[.\-])bn([.\-]|$)/', $host)
            && preg_match('/customs|kastam|police|polis|ministry|kementerian|refund|fine|saman|gov|zakat|bantuan|relief|immigration|imigresen|cukai|tax/', $host)
            && ! str_ends_with($host, '.bn')
            && ! str_ends_with($host, 'flyroyalbrunei.com')
        ) {
            $reasons[] = 'Domain uses Brunei authority wording (customs, police, ministry, refund, aid) outside a .bn domain';
            $points += 20;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
            'brand_rule_points' => $brandRulePoints,
            // shown on the result page when the check passes (see buildCheck); never used for scoring
            'detail' => $reasons === [] ? 'None of the common tricks were found in how the link is written, such as a number in place of a name, a look-alike brand name or too many hyphens.' : null,
        ];
    }

    /** "Brands" that are ordinary words too, so a domain containing one is not necessarily imitating anyone. */
    private const ORDINARY_WORD_BRANDS = ['bank'];

    /** Words scams put in an address to sound official. */
    private const OFFICIAL_SOUNDING_WORDS = '/(secure|security|login|logon|signin|verify|verification|update|support|alert|confirm|unlock|billing|refund|claim|wallet|account|webaccess|helpdesk|notice|recover|validate)/';

    /**
     * A name from brands.subdomain_brands used as a whole label in front of the registered domain of another website
     * ("outlook.webaccess-alert.com"), when the rest of the address also carries a word scams use to sound official.
     * Without that second sign it is left alone: companies run their own mail pages at "outlook.theircompany.com".
     * Sites listed in brands.known_lookalikes are skipped. Returns the name and the word, or null.
     *
     * @return array{brand: string, word: string}|null
     */
    private function brandUsedAsSubdomain(string $host): ?array
    {
        $registrable = RegistrableDomain::of($host);

        if ($registrable === null || ! str_ends_with($host, '.'.$registrable)) {
            return null;
        }

        if (in_array($registrable, array_map('strtolower', config('brands.known_lookalikes', [])), true)) {
            return null;
        }

        $labels = explode('.', substr($host, 0, -(strlen($registrable) + 1)));
        $names = array_map('strtolower', config('brands.subdomain_brands', []));

        foreach ($labels as $i => $label) {
            if (! in_array($label, $names, true)) {
                continue;
            }

            $others = $labels;
            unset($others[$i]);
            $rest = implode('.', array_merge($others, [$registrable]));

            if (preg_match(self::OFFICIAL_SOUNDING_WORDS, $rest, $found) === 1) {
                return ['brand' => $label, 'word' => $found[1]];
            }
        }

        return null;
    }

    /**
     * Whether an address has a second sign of a disguise besides containing a word: the word written
     * with look-alike characters ("b4nk"), a heap of hyphens, a word scams use to sound official, or a
     * longer brand name that contains the word ("maybank").
     */
    private function addressLooksDisguised(string $host, string $word): bool
    {
        // The word may be part of a real organisation's name ("maybank", "citibank"), taken from the
        // official-domain list. An address that carries such a name but is not that organisation's site
        // is imitating it, not just using an everyday word.
        foreach (array_keys($this->officialBrandDomains()) as $name) {
            if ($name !== $word && str_contains($name, $word) && str_contains($host, $name)) {
                return true;
            }
        }

        return ! str_contains($host, $word)
            || substr_count($host, '-') >= 2
            || preg_match('/(secure|login|signin|verify|verification|update|support|alert|confirm|auth|unlock|billing|refund|claim|wallet)/', $host) === 1;
    }

    /**
     * Brand names (from config/brands.php) distinctive enough that finding one inside a web address means
     * something: one word of at least seven letters that is not also an everyday word.
     *
     * @return array<int, string>
     */
    private function distinctiveBrandNames(): array
    {
        $everyday = config('brands.ordinary_word_brands', []);

        return array_values(array_filter(
            array_keys($this->brandDomainMap()),
            fn (string $name): bool => strlen($name) >= 7
                && preg_match('/^[a-z0-9]+$/', $name) === 1
                && ! in_array($name, $everyday, true),
        ));
    }

    /**
     * A host written to be mistaken for a known brand (see LookAlike), or null. The brands and the
     * list of real sites that must never be caught come from config/brands.php.
     *
     * @return array{type: string, brand: ?string, name: string}|null
     */
    private function lookAlikeFinding(string $host): ?array
    {
        $brands = array_values(array_filter(
            array_keys($this->brandDomainMap()),
            fn (string $brand): bool => preg_match('/^[a-z0-9]+$/', $brand) === 1,
        ));

        $knownGood = config('brands.known_lookalikes', []);
        $found = LookAlike::find($host, $brands, $knownGood);

        if ($found !== null) {
            return $found;
        }

        // The most visited sites of the world are protected too (see ThreatLookup::popularNames), unless the
        // address is itself a site on the popular list: a real site of its own is not a copy of another.
        if (ThreatLookup::position($host) !== null) {
            return null;
        }

        $popular = array_values(array_diff(ThreatLookup::popularNames(), $brands));

        return $popular === [] ? null : LookAlike::find($host, $popular, $knownGood);
    }

    /** Plain-language reason for a look-alike finding. */
    private function lookAlikeReason(array $finding): string
    {
        $brand = $finding['brand'] !== null ? ucfirst($finding['brand']) : null;

        return match ($finding['type']) {
            'mixed' => 'The domain mixes letters from different alphabets (for example Latin and Cyrillic), a trick used to fake a real name',
            'homoglyph' => "The domain uses look-alike letters from another alphabet to imitate \"{$brand}\"",
            'typo' => "The domain looks like \"{$brand}\" with a letter missing or swapped, but is not its official domain",
            default => "The domain looks like \"{$brand}\" written with look-alike or repeated letters, but is not its official domain",
        };
    }

    /** A site must be at least this old (in days) before a brand word in its address is treated as a plain name. */
    private const ESTABLISHED_SITE_DAYS = 730;

    /** And at least this many security companies must have looked at it, with none flagging it. */
    private const ESTABLISHED_SITE_VENDORS = 30;

    /**
     * No list of official domains can be complete (a brand has many country sites). So when the ONLY thing
     * wrong with a link is that a well-known brand name appears in it, the site is old (2 years or more),
     * and a good number of security companies checked it and none flagged it, the brand rule is shown as
     * information instead of adding points. A new, flagged or otherwise odd site keeps every point.
     *
     * @param  array  $syntax  result of checkUrlSyntax()
     * @param  int  $otherPoints  points from every other check of the same scan (age, SSL, blacklists, ...)
     * @param  array  $age  result of checkDomainAge()
     * @param  array  $virusTotal  result of checkVirusTotal()
     */
    public function softenBrandWordForEstablishedSite(array $syntax, int $otherPoints, array $age, array $virusTotal): array
    {
        $brandPoints = (int) ($syntax['brand_rule_points'] ?? 0);

        if ($brandPoints <= 0 || (int) $syntax['points'] !== $brandPoints || $otherPoints > 0) {
            return $syntax;
        }

        $days = $age['domain_age_days'] ?? null;

        if ($days === null || $days < self::ESTABLISHED_SITE_DAYS) {
            return $syntax;
        }

        if (($virusTotal['unavailable'] ?? false) || ($virusTotal['malicious'] ?? 1) !== 0 || (int) ($virusTotal['total'] ?? 0) < self::ESTABLISHED_SITE_VENDORS) {
            return $syntax;
        }

        $years = intdiv((int) $days, 365);

        return [
            'flagged' => false,
            'points' => 0,
            'neutral' => true,
            'brand_rule_points' => 0,
            'reasons' => ["A well-known brand name appears in this address, but this address was registered about {$years} years ago and none of {$virusTotal['total']} security companies flag it, so it is treated as an established site and not a copy."],
        ];
    }

    /**
     * Accepts either a full URL (http://example.com) or a bare domain/host (example.com).
     *
     * The age belongs to the registrable domain ("example.com" for "login.shop.example.com"), so that is what is
     * looked up. A page on a shared platform ("someone.github.io") has no age of its own and gets an honest
     * "not applicable" answer instead of the platform's date. The date comes from the domain registry (RDAP) first,
     * then WHOIS, then VirusTotal, and is cached because a registration date never changes.
     */
    public function checkDomainAge(string $urlOrHost): array
    {
        $host = str_contains($urlOrHost, '://')
            ? (parse_url($urlOrHost, PHP_URL_HOST) ?? $urlOrHost)
            : $urlOrHost;

        $host = strtolower(trim((string) preg_replace('/^www\./i', '', (string) $host), '[]. '));

        $unavailable = fn (string $reason) => [
            'flagged' => false,
            'points' => 0,
            'domain_age_days' => null,
            'reasons' => [$reason],
            'unavailable' => true,
        ];

        if ($host === '') {
            return $unavailable('Could not retrieve WHOIS data');
        }

        if (preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $host) || str_contains($host, ':')) {
            return $unavailable('This address is made of numbers, so it has no registration date to look up');
        }

        if (($platform = RegistrableDomain::sharedPlatform($host)) !== null) {
            return [
                'flagged' => false,
                'points' => 0,
                'neutral' => true,
                'domain_age_days' => null,
                'reasons' => ["This page sits on a shared platform ({$platform}), so the registration date belongs to the platform and says nothing about this page. It is not used in the score."],
            ];
        }

        $domain = RegistrableDomain::of($host) ?? $host;

        try {
            $found = $this->lookupRegistrationDate($domain);

            if ($found['created'] === null) {
                return $unavailable('WHOIS data unavailable for this domain');
            }

            $createdAt = Carbon::createFromTimestamp($found['created']);
            $ageDays = (int) round($createdAt->diffInDays(now()));

            $points = 0;
            $reasons = [];

            if ($ageDays < 7) {
                $points = 40;
                $reasons[] = "Domain was registered only {$ageDays} day(s) ago — a strong phishing indicator";
            } elseif ($ageDays < 30) {
                $points = 25;
                $reasons[] = "Domain was registered {$ageDays} days ago — recently registered domains are higher risk";
            } elseif ($ageDays < 180) {
                $points = 10;
                $reasons[] = "Domain is relatively new ({$ageDays} days old)";
            }

            // shown on the result page when the check passes (see buildCheck); never used for scoring
            $detail = null;
            if ($reasons === []) {
                $howOld = $ageDays >= 730 ? intdiv($ageDays, 365).' years' : ($ageDays >= 365 ? 'a year' : intdiv($ageDays, 30).' months');
                $source = ['rdap' => 'the domain registry', 'whois' => 'a WHOIS record', 'virustotal' => 'VirusTotal'][$found['source']] ?? 'a public record';
                $detail = 'The web address was registered on '.$createdAt->format('j M Y').', about '.$howOld.' ago, according to '.$source.'.';
                if ($domain !== $host) {
                    $detail .= " That is the age of {$domain}, the main domain of this address.";
                }
            }

            return [
                'flagged' => $points > 0,
                'points' => $points,
                'domain_age_days' => $ageDays,
                'reasons' => $reasons,
                'detail' => $detail,
                'source' => $found['source'],
            ];
        } catch (\Throwable $e) {
            return $unavailable('Could not retrieve WHOIS data');
        }
    }

    /**
     * When was this registrable domain registered, and which source said so?
     * Tries the registry (RDAP), then WHOIS, then VirusTotal; the first usable date wins.
     * A date is kept for a week; "not found" is only kept for half an hour so a short outage heals itself.
     *
     * @return array{created: int|null, source: string|null}
     */
    private function lookupRegistrationDate(string $domain): array
    {
        $key = 'domain-age:'.$domain;
        $cached = Cache::get($key);

        if (is_array($cached) && array_key_exists('created', $cached)) {
            return $cached;
        }

        // A registration date before the web existed, or one in the future, is bad data, not an answer.
        $usable = fn ($time) => is_int($time) && $time > 473385600 && $time <= time() + 86400;

        $sources = [
            'rdap' => fn () => Rdap::registrationDate($domain),
            'whois' => fn () => $this->whoisCreationDate($domain),
            'virustotal' => fn () => $this->virusTotalCreationDate($domain),
        ];

        $result = ['created' => null, 'source' => null];

        foreach ($sources as $name => $ask) {
            try {
                $time = $ask();
            } catch (\Throwable) {
                continue;
            }

            if ($usable($time)) {
                $result = ['created' => $time, 'source' => $name];
                break;
            }
        }

        Cache::put($key, $result, $result['created'] === null ? now()->addMinutes(30) : now()->addDays(7));

        return $result;
    }

    /** The registration date from a WHOIS server. Protected so a test can stand in for the network. */
    protected function whoisCreationDate(string $domain): ?int
    {
        // Bound the socket to 8s: the package default is 60s, which can dominate a scan's runtime.
        $info = Factory::get()->createWhois(new SocketLoader(8))->loadDomainInfo($domain);

        return $info && $info->creationDate ? (int) $info->creationDate : null;
    }

    /** VirusTotal keeps a creation date for most domains it has seen. Null without a key or a date. */
    private function virusTotalCreationDate(string $domain): ?int
    {
        $apiKey = config('services.virustotal.key');
        if (! $apiKey) {
            return null;
        }

        $response = Http::withHeaders(['x-apikey' => $apiKey])
            ->timeout(8)
            ->get('https://www.virustotal.com/api/v3/domains/'.$domain);

        $date = $response->successful() ? $response->json('data.attributes.creation_date') : null;

        return is_numeric($date) ? (int) $date : null;
    }

    public function checkSslCertificate(string $url): array
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== 'https') {
            return [
                'flagged' => true,
                'points' => 20,
                'reasons' => ['Website does not use HTTPS (no SSL encryption)'],
            ];
        }

        if (($why = SafeUrl::problem($url)) !== null) {
            return ['flagged' => false, 'points' => 0, 'reasons' => [$why], 'unavailable' => true];
        }

        try {
            // Confirmed via testing: a raw stream_socket_client TLS handshake
            // (the previous approach) failed on a legitimate, correctly
            // configured site (politeknikbrunei.edu.bn) even on a good
            // connection, while every other check in this class — using
            // Laravel's Http client — reached the same site without issue.
            // Curl (which Http uses under the hood) handles SNI, modern TLS
            // versions, and certificate chains far more robustly than a raw
            // socket handshake. A HEAD request is enough to trigger the full
            // TLS handshake without downloading the page body.
            Http::withOptions(SafeUrl::redirectOptions())->timeout(10)->head($url);

            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => [],
                'detail' => 'The website uses a secure connection (HTTPS) and its security certificate was accepted.',
            ];
        } catch (ConnectionException $e) {
            $message = $e->getMessage();

            if (stripos($message, 'certificate has expired') !== false) {
                return ['flagged' => true, 'points' => 30, 'reasons' => ['SSL certificate has expired']];
            }

            if (stripos($message, 'does not match') !== false || stripos($message, 'subject name') !== false) {
                return ['flagged' => true, 'points' => 20, 'reasons' => ['SSL certificate does not match the domain']];
            }

            // "unable to get local issuer certificate" (OpenSSL error 20) means
            // the certificate's chain could not be completed from this server.
            // The most common cause is a site that does not send its
            // intermediate certificate (browsers fetch it themselves, curl
            // does not) — a server misconfiguration, not evidence of
            // phishing. Report it as UNKNOWN rather than a misleading
            // SUSPICIOUS, consistent with how failed checks are handled.
            if (stripos($message, 'unable to get local issuer') !== false) {
                return [
                    'flagged' => false,
                    'points' => 0,
                    'reasons' => ['The certificate chain could not be fully verified from our server (the site may not send its intermediate certificate). This is often a server misconfiguration and does not count against the site.'],
                    'unavailable' => true,
                ];
            }

            if (stripos($message, 'ssl') !== false || stripos($message, 'certificate') !== false) {
                return ['flagged' => true, 'points' => 25, 'reasons' => ['Could not verify a valid, trusted SSL certificate for this domain']];
            }

            // A connection-level failure that isn't SSL-specific (DNS
            // failure, connection refused, timeout) shouldn't count against
            // the SSL check specifically — other checks (redirect chain, IP
            // reputation) already surface general connectivity problems.
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Could not connect to verify SSL (unrelated to certificate validity)'], 'unavailable' => true];
        } catch (\Throwable $e) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Could not verify SSL certificate due to an unexpected error'], 'unavailable' => true];
        }
    }

    /**
     * Known threat lists: the community phishing lists saved by feeds:update (OpenPhish, URLhaus, PhishTank)
     * and Google Safe Browsing. A hit on either is a warning; with no hit the Google result is returned as it is.
     */
    public function checkBlacklist(string $url): array
    {
        $google = $this->checkSafeBrowsing($url);
        $feeds = $this->checkThreatFeeds($url);

        if (! $feeds['flagged']) {
            if (! $google['flagged'] && ! ($google['unavailable'] ?? false) && ($google['reasons'] ?? []) === []) {
                $google['detail'] = 'This address is not on Google Safe Browsing or on the community phishing lists we keep (OpenPhish, URLhaus and PhishTank).';
            }

            return $google;
        }

        $googleHit = $google['flagged'] && ! ($google['unavailable'] ?? false);

        return [
            'flagged' => true,
            'points' => min(60, $feeds['points'] + ($googleHit ? $google['points'] : 0)),
            'reasons' => array_merge($feeds['reasons'], $googleHit ? $google['reasons'] : []),
        ];
    }

    /**
     * The saved community lists. The exact address on a list is a strong sign. Another page on the same
     * registered domain is a weaker one, and is ignored for a well-known site: one phishing page on a big
     * platform says nothing about the platform.
     */
    public function checkThreatFeeds(string $url): array
    {
        $none = ['flagged' => false, 'points' => 0, 'reasons' => []];
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return $none;
        }

        if (($listed = ThreatLookup::exactMatch($url)) !== null) {
            return [
                'flagged' => true,
                'points' => 50,
                'reasons' => ["This exact address is on {$listed['name']}, a community list of reported phishing and malware pages"],
            ];
        }

        if (($listed = ThreatLookup::domainMatch($host)) !== null && ! ThreatLookup::isPopular($host)) {
            return [
                'flagged' => true,
                'points' => 25,
                'reasons' => ["Another page on this website is on {$listed['name']}, a community list of reported phishing and malware pages"],
            ];
        }

        return $none;
    }

    private function checkSafeBrowsing(string $url): array
    {
        $apiKey = config('services.google_safe_browsing.key');

        if (! $apiKey) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check skipped: no API key configured'], 'unavailable' => true];
        }

        try {
            // Every other network call in this class has an explicit timeout;
            // this one was missing it and could hang with no defined bound.
            $response = Http::timeout(10)->post(
                "https://safebrowsing.googleapis.com/v4/threatMatches:find?key={$apiKey}",
                [
                    'client' => ['clientId' => 'phishcore', 'clientVersion' => '1.0.0'],
                    'threatInfo' => [
                        'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
                        'platformTypes' => ['ANY_PLATFORM'],
                        'threatEntryTypes' => ['URL'],
                        'threatEntries' => [['url' => $url]],
                    ],
                ]
            );

            $matches = $response->json('matches', []);

            if (! empty($matches)) {
                return [
                    'flagged' => true,
                    'points' => 50,
                    'reasons' => ["URL is flagged on Google Safe Browsing's threat list"],
                ];
            }

            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        } catch (\Throwable $e) {
            return ['flagged' => false, 'points' => 0, 'reasons' => ['Blacklist check unavailable'], 'unavailable' => true];
        }
    }

    /**
     * Checks a URL against VirusTotal's multi-vendor threat database.
     * Tries a fast cached lookup first; only submits + polls for a fresh
     * scan if VT has no existing record for this URL.
     */
    public function checkVirusTotal(string $url): array
    {
        $apiKey = config('services.virustotal.key');

        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'malicious' => null,
            'total' => null,
            'raw' => null,
            'unavailable' => true,
        ];

        if (! $apiKey) {
            $empty['reasons'][] = 'VirusTotal check skipped: no API key configured';

            return $empty;
        }

        try {
            $urlId = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');

            $lookup = Http::withHeaders(['x-apikey' => $apiKey])
                ->timeout(10)
                ->get("https://www.virustotal.com/api/v3/urls/{$urlId}");

            $stats = null;
            $raw = null;

            if ($lookup->successful()) {
                $stats = $lookup->json('data.attributes.last_analysis_stats');
                $raw = $lookup->json();
            } elseif ($lookup->status() === 404) {
                $submit = Http::withHeaders(['x-apikey' => $apiKey])
                    ->timeout(10)
                    ->asForm()
                    ->post('https://www.virustotal.com/api/v3/urls', ['url' => $url]);

                if (! $submit->successful()) {
                    $empty['reasons'][] = 'Could not submit URL to VirusTotal for scanning';

                    return $empty;
                }

                $analysisId = $submit->json('data.id');

                // VT's own scan queue time is outside our control, but a brand
                // new URL previously polled up to 4x with a 2s wait and a 15s
                // timeout each — up to 68s just for this one check. Trimmed to
                // bound our worst-case patience without giving up entirely.
                for ($attempt = 0; $attempt < 3; $attempt++) {
                    sleep(1);
                    $analysisResp = Http::withHeaders(['x-apikey' => $apiKey])
                        ->timeout(8)
                        ->get("https://www.virustotal.com/api/v3/analyses/{$analysisId}");

                    if ($analysisResp->json('data.attributes.status') === 'completed') {
                        $stats = $analysisResp->json('data.attributes.stats');
                        $raw = $analysisResp->json();
                        break;
                    }
                }

                if (! $stats) {
                    $empty['reasons'][] = 'VirusTotal analysis is still processing for this new URL — try scanning again shortly';

                    return $empty;
                }
            } else {
                $empty['reasons'][] = 'VirusTotal lookup failed';

                return $empty;
            }

            if (! $stats) {
                $empty['reasons'][] = 'VirusTotal has no analysis data available for this URL';

                return $empty;
            }

            $malicious = $stats['malicious'] ?? 0;
            $suspicious = $stats['suspicious'] ?? 0;
            $harmless = $stats['harmless'] ?? 0;
            $undetected = $stats['undetected'] ?? 0;
            $total = $malicious + $suspicious + $harmless + $undetected;
            $flaggedCount = $malicious + $suspicious;

            $points = 0;
            $reasons = [];

            if ($flaggedCount > 0) {
                $points = min(50, $flaggedCount * 5);
                $reasons[] = "{$flaggedCount} out of {$total} security vendors on VirusTotal flagged this URL as malicious or suspicious";
                if ($flaggedCount <= 3) {
                    $reasons[] = 'Only a very small share of vendors flagged it. This is often a false alarm on well-known, legitimate sites, so it counts as a minor signal.';
                }
            } else {
                $reasons[] = "0 out of {$total} security vendors on VirusTotal flagged this URL";
            }

            return [
                'flagged' => $flaggedCount > 0,
                'points' => $points,
                'reasons' => $reasons,
                'malicious' => $flaggedCount,
                'total' => $total,
                'raw' => $raw,
            ];
        } catch (\Throwable $e) {
            $empty['reasons'][] = 'Could not reach VirusTotal';

            return $empty;
        }
    }

    /**
     * Resolves the URL's domain to an IP address and checks its geolocation,
     * ISP, and whether it's a proxy/VPN or generic hosting provider — common
     * traits of rapidly-deployed phishing infrastructure.
     */
    public function checkIpReputation(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);

        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'ip' => null,
            'country' => null,
            'summary' => null,
            'unavailable' => true,
        ];

        if (! $host) {
            $empty['reasons'][] = 'Could not determine host from URL';

            return $empty;
        }

        $ip = @gethostbyname($host);
        if (! $ip || $ip === $host) {
            $empty['reasons'][] = 'Could not resolve domain to an IP address';

            return $empty;
        }

        try {
            $response = Http::timeout(10)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,message,country,countryCode,isp,org,proxy,hosting,query',
            ]);

            if (! $response->successful() || $response->json('status') !== 'success') {
                $empty['ip'] = $ip;
                $empty['reasons'][] = 'IP reputation lookup unavailable';

                return $empty;
            }

            $data = $response->json();
            $country = $data['country'] ?? 'Unknown';
            $isp = $data['isp'] ?? 'Unknown ISP';
            $isProxy = $data['proxy'] ?? false;
            $isHosting = $data['hosting'] ?? false;

            $reasons = [];
            $points = 0;

            // On a free hosting platform the IP is shared by every site on it.
            // ip-api marks those shared IPs as proxies (github.com and every
            // github.io page get the flag), which says nothing about this one
            // site; the hosting-platform check already covers the platform.
            $sharedPlatform = $this->checkFreeHostingPlatform($url)['platform'];

            if ($isProxy && $sharedPlatform !== null) {
                $reasons[] = "IP address is flagged as a proxy but is shared by all sites on \"{$sharedPlatform}\", so it says nothing about this site";
            } elseif ($isProxy) {
                // Lowered from 20 after the accuracy test (scan:batch): ip-api
                // marks shared platform IPs as proxies, so github.com and
                // moh.gov.bn scored 25 and were flagged, and 6 of the 8 scams
                // that got this flag were github.io pages sharing GitHub's own
                // address. At 10 both real sites pass and those scams are still
                // caught by the hosting-platform check on top of this one.
                $reasons[] = 'IP address is associated with a known proxy or VPN service, often used to mask phishing infrastructure';
                $points += 10;
            }

            if ($isHosting) {
                // Reduced from 15: datacenter/cloud hosting is now the norm
                // for the vast majority of legitimate websites too (AWS,
                // Google Cloud, Cloudflare, etc.) — including Google's own
                // infrastructure — so this alone is weak evidence on its own,
                // unlike proxy/VPN usage above which remains far more specific.
                $reasons[] = 'IP address belongs to a datacenter/hosting provider rather than a residential ISP — common for phishing infrastructure, but also true of most legitimate modern websites';
                $points += 5;
            }

            if (empty($reasons)) {
                $reasons[] = "Hosted in {$country} via {$isp}, no proxy or datacenter flags";
            }

            $summary = "{$ip} — {$country} ({$isp})"
                .($isProxy ? ' · Proxy/VPN' : '')
                .($isHosting ? ' · Hosting/Datacenter' : '');

            return [
                'flagged' => $points > 0,
                'points' => $points,
                'reasons' => $reasons,
                'ip' => $ip,
                'country' => $country,
                'summary' => $summary,
            ];
        } catch (\Throwable $e) {
            $empty['ip'] = $ip;
            $empty['reasons'][] = 'Could not reach IP reputation service';

            return $empty;
        }
    }

    /**
     * Manually follows the URL's redirect chain (without auto-following)
     * to detect domain-hopping and excessive redirect counts.
     */
    public function checkRedirectChain(string $url): array
    {
        $chain = [$url];
        $originalHost = parse_url($url, PHP_URL_HOST);
        $current = $url;
        $reached = false;
        $refusedHop = false;

        // This server visits whatever address a visitor types in, so each address (the first one and
        // every redirect after it) is checked before it is requested. See SafeUrl.
        if (($why = SafeUrl::problem($url)) !== null) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => [$why],
                'chain' => $chain,
                'unavailable' => true,
            ];
        }

        for ($hop = 0; $hop < 5; $hop++) {
            try {
                $response = Http::withOptions(['allow_redirects' => false])
                    ->timeout(8)
                    ->get($current);
            } catch (\Throwable $e) {
                break;
            }

            $reached = true;
            $status = $response->status();

            if (! in_array($status, [301, 302, 303, 307, 308])) {
                break;
            }

            $location = $response->header('Location');
            if (! $location) {
                break;
            }

            if (! str_starts_with($location, 'http')) {
                $parsed = parse_url($current);
                $location = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').$location;
            }

            $chain[] = $location;

            if (SafeUrl::problem($location) !== null) {
                $refusedHop = true;

                break;
            }

            $current = $location;
        }

        // If the site never answered (offline, or the name no longer exists) we cannot
        // know where the link leads, so say so instead of "no redirects".
        if (! $reached) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['Could not reach the website, so where the link leads could not be checked'],
                'chain' => $chain,
                'unavailable' => true,
            ];
        }

        $hopCount = count($chain) - 1;
        $finalHost = parse_url(end($chain), PHP_URL_HOST);

        $reasons = [];
        $points = 0;

        if ($hopCount >= 3) {
            $reasons[] = "URL redirects through {$hopCount} hops before reaching its final destination, an unusually long chain";
            $points += 15;
        }

        // A legitimate site that sends its own visitors elsewhere on the same site is the everyday
        // case: example.com -> www.example.com, example.com -> shop.example.com, bibd.com.bn ->
        // online.bibd.com.bn. Comparing the registrable domain (see RegistrableDomain) treats all of
        // those as the same site; only a genuinely different domain is flagged.
        if ($refusedHop) {
            // A public website that sends the visitor to an internal address is attacking whoever follows
            // it (or whoever scans it), and no real site does this to the open internet.
            $reasons[] = 'The link redirects to an internal or private address, which no legitimate public website does';
            $points += 20;
        } elseif ($finalHost && $originalHost && ! RegistrableDomain::same($finalHost, $originalHost)) {
            if ($this->isOfficialBrandDomain((string) $originalHost)) {
                // Domains on the curated official list cannot be registered by a
                // scammer, so where they send visitors is the owner's choice.
                $reasons[] = "URL redirects to {$finalHost}; the submitted domain is on PhishCore's official-domain list";
            } elseif ($this->isBruneiGovernmentHost($originalHost) && $this->isBruneiGovernmentHost($finalHost)) {
                // e.g. www.mofe.gov.bn -> www.mof.gov.bn after a ministry rename.
                $reasons[] = "URL redirects to another .gov.bn domain ({$finalHost}); redirects between Brunei government sites are normal";
            } elseif (ThreatLookup::isPopular((string) $originalHost) && ThreatLookup::isPopular((string) $finalHost)) {
                // Two of the world's most visited sites (raw.githubusercontent.com -> github.com, youtu.be ->
                // youtube.com). A scammer cannot make a brand-new site rank among them, so this is the owner's
                // own routing. A popular site that sends visitors to an unknown one is still flagged below.
                $reasons[] = "URL redirects to {$finalHost}; both are well-known, widely visited websites";
            } else {
                $reasons[] = "URL ultimately redirects to a different domain ({$finalHost}) than the one submitted ({$originalHost})";
                $points += 20;
            }
        }

        if (empty($reasons)) {
            $reasons[] = $hopCount > 0
                ? "Redirects {$hopCount} time(s) but stays on the same domain"
                : 'No redirects detected';
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
            'chain' => $chain,
        ];
    }

    /**
     * Flags domains hosted on free website-builder / static-hosting
     * platforms (Weebly, Wix, Netlify, GitHub Pages, etc.) — a well-known,
     * legitimate phishing-kit pattern: these platforms require no identity
     * verification, are free, and can be spun up and abandoned in minutes.
     * This is a supporting signal only (modest points), since plenty of
     * hobbyists and small legitimate projects also use these same
     * platforms — the check flags the infrastructure choice, not intent.
     *
     * Matches on the registrable base domain (e.g. "weebly.com"), not a
     * substring, so a legitimate domain that merely contains one of these
     * words (unlikely, but possible) isn't wrongly matched.
     */
    public function checkFreeHostingPlatform(string $url): array
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $host = preg_replace('/^www\./', '', $host);

        // One shared list (also used to tell "same site" from "same platform", see RegistrableDomain).
        foreach (RegistrableDomain::FREE_HOSTING_SUFFIXES as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                $points = 10;
                $reasons = ["Hosted on \"{$suffix}\", a free website-builder/hosting platform commonly used for disposable phishing pages — legitimate small projects also use these, so this is a supporting signal only"];

                // On a free platform the subdomain is whoever signed up, so a
                // famous brand in the PATH (github.io/Amazon-Clone, /DHL,
                // /Netflix) is the usual shape of a fake page copying that brand.
                // A brand's own official pages are skipped.
                $brand = $this->brandInPath((string) parse_url($url, PHP_URL_PATH));
                if ($brand !== null && ! $this->isOfficialBrandDomain($host)) {
                    $points += 15;
                    $reasons[] = "The page address on this free platform names a well-known brand (\"{$brand}\") that the site does not belong to";
                }

                return [
                    'flagged' => true,
                    'points' => $points,
                    'reasons' => $reasons,
                    'platform' => $suffix,
                ];
            }
        }

        return [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'platform' => null,
            'detail' => 'This website is not on a free website builder or hosting platform.',
        ];
    }

    /**
     * Whether the host has any DNS record. gethostbyname() only looks for an
     * IPv4 address and can fail on some resolvers or for IPv6-only and CNAME
     * hosts, which made real sites (a Brunei bank and ministry sites among
     * them) show as "no longer resolves". Falling back to a full record lookup
     * means only a domain with no A, AAAA or CNAME record is called offline.
     */
    private function hostResolves(string $host): bool
    {
        $ip = @gethostbyname($host);

        if ($ip && $ip !== $host) {
            return true;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA | DNS_CNAME);

        return is_array($records) && $records !== [];
    }

    /**
     * Checks whether the site is currently reachable at all, distinguishing
     * WHY it isn't (if it isn't) rather than lumping every failure into a
     * single generic "unavailable." This is informational only — it does
     * NOT affect the risk score in either direction. A site going offline
     * doesn't undo the fact that it was confirmed malicious when reported
     * (the original scan/VT/content evidence stands), and a site still
     * being live doesn't make it more dangerous than the other checks
     * already established. This exists purely so a reader — especially a
     * team member managing an investigation — can see at a glance whether
     * the threat is still active or already taken down.
     */
    public function checkSiteAvailability(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ['Could not determine host from URL'],
            ];
        }

        if (! $this->hostResolves($host)) {
            return [
                'status_label' => 'OFFLINE',
                'reasons' => ["This domain (\"{$host}\") no longer resolves — it may have expired, been suspended, or been taken down entirely"],
            ];
        }

        if (($why = SafeUrl::problem($url)) !== null) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => [$why],
            ];
        }

        try {
            // Same browser-style User-Agent as the page-content check: many
            // banks and CDNs drop connections from the default HTTP-client agent.
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->withOptions(SafeUrl::redirectOptions(3))
                ->timeout(8)
                ->get($url);
        } catch (ConnectionException $e) {
            // A failed connection is not proof the site is down: slow servers
            // and bot protection cause the same error on perfectly live sites
            // (real Brunei banks were reported "offline" this way). Only a
            // domain that no longer resolves is reported as OFFLINE.
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ['The domain resolves, but the server did not answer in time or refused the connection. It may be offline, slow, or blocking automated requests, so its status could not be confirmed'],
            ];
        } catch (\Throwable $e) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ['Could not determine site availability due to an unexpected error'],
            ];
        }

        $status = $response->status();

        if (in_array($status, [404, 410])) {
            return [
                'status_label' => 'TAKEN DOWN',
                'reasons' => ["The server responded with HTTP {$status} — the specific page appears to have been removed, though the domain itself is still active"],
            ];
        }

        if ($status >= 500) {
            return [
                'status_label' => 'UNKNOWN',
                'reasons' => ["The server responded with HTTP {$status} — a server-side error, which may be temporary"],
            ];
        }

        return [
            'status_label' => 'LIVE',
            'reasons' => ["Site is currently live and reachable (HTTP {$status})"],
        ];
    }

    /**
     * Tier 1 content validation: fetches the page's actual HTML (rather than
     * just checking reputation/metadata about the URL) and looks for concrete
     * phishing evidence — a credential-harvesting login form, phishing-style
     * lure language in the visible text, and brand impersonation (the page
     * content claims to be a known brand while the domain does not belong to
     * that brand). This is potentially the strongest signal a scan can
     * produce, but Tier 1 pattern-matching alone can't truly verify intent,
     * so it's weighted as a supporting signal (capped well below VirusTotal)
     * rather than a dominant one until proven out with real-world testing.
     *
     * Safety guards: only http(s) schemes are fetched, private/internal IPs
     * are refused (prevents this server being tricked into scanning its own
     * internal network), redirects are capped, and a short timeout is used —
     * this server is deliberately visiting live, possibly-malicious URLs.
     */
    public function checkPageContent(string $url): array
    {
        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'unavailable' => true,
        ];

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'])) {
            $empty['reasons'][] = 'Content check skipped: unsupported URL scheme';

            return $empty;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            $empty['reasons'][] = 'Content check skipped: could not determine host';

            return $empty;
        }

        // Refuse to fetch an address that leads to this server's own network: an IP in any spelling
        // (127.0.0.1, 2130706433, 0x7f.0.0.1, [::1]), a name that resolves to an internal address,
        // an unusual port, and every redirect the page answers with (see SafeUrl).
        //
        // A name that PHP cannot resolve is still fetched: PHP's resolver can fail on a domain that
        // curl reaches, and blocking those wrongly flagged a real site as "private" once. A name that
        // resolves to an internal address on this lookup but to a public one on curl's own lookup
        // (DNS rebinding) is the one gap left; pinning the resolved address would close it.
        if (($why = SafeUrl::problem($url)) !== null) {
            $empty['reasons'][] = 'Content check skipped: '.lcfirst($why);

            return $empty;
        }

        try {
            // A browser-style User-Agent avoids unnecessary 403s from sites
            // that block generic/bot-looking requests (confirmed via testing
            // against Wikipedia) — improving real-world coverage without
            // pretending to be anything other than an automated fetch.
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->withOptions(SafeUrl::redirectOptions(3))
                ->timeout(8)
                ->get($url);
        } catch (\Throwable $e) {
            $empty['reasons'][] = 'Could not fetch page content for analysis';

            return $empty;
        }

        // Some sites put a security check ("Just a moment...") in front of the page for anything that is not a
        // browser. That page says nothing about the site, so it must not count as a clean read.
        $challenge = PageInspector::challenge($response->body(), $response->header('cf-mitigated'));
        if ($challenge !== null) {
            $empty['reasons'][] = "The website answered with a security check from {$challenge} instead of its page, so its content could not be read";

            return $empty;
        }

        if (! $response->successful()) {
            $empty['reasons'][] = "Page returned HTTP {$response->status()} — content could not be analyzed";

            return $empty;
        }

        return $this->inspectPageHtml(
            PageInspector::toUtf8($response->body(), $response->header('Content-Type')),
            (string) $host,
            $response->header('Server') ?: null
        );
    }

    /**
     * Reads the HTML of a page for warning signs: login and card forms, lure wording, brand imitation, form
     * destinations, redirect tricks and scrambled scripts. Nothing here fetches anything.
     *
     * $rendered is true when the HTML is what a browser showed AFTER the scripts ran (the sandbox's copy of a page).
     * Such a page is not an empty shell waiting for JavaScript, so that special answer is skipped.
     */
    public function inspectPageHtml(string $html, string $host, ?string $serverHeader = null, bool $rendered = false): array
    {
        // Cap how much HTML we process — phishing pages are almost always
        // small, and this bounds worst-case processing time on a huge page.
        $html = substr($html, 0, 200000);

        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch);
        $title = trim(strip_tags($titleMatch[1] ?? ''));

        $sensitiveFields = $this->detectSensitiveInputFields($html);
        $hasPasswordField = $sensitiveFields['has_password'];
        $hasSensitiveField = $hasPasswordField || $sensitiveFields['has_other_sensitive'];

        // Strip script/style blocks before converting to plain text so their
        // contents (JS code, CSS rules) don't pollute keyword/brand matching.
        // The readable text comes from a real HTML parser (no scripts, styles, noscript fallbacks or hidden helpers);
        // the old tag-stripping is only the fallback for markup the parser cannot read.
        $bodyText = PageInspector::visibleText($html);
        if ($bodyText === '') {
            $bodyText = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);
            $bodyText = trim(strip_tags($bodyText));
        }
        $bodyText = Str::limit($bodyText, 5000, '');

        $combinedText = trim($title.' '.$bodyText);

        $reasons = [];
        $points = 0;

        // A password field alone is near-universal on legitimate sites
        // (banks, universities, webmail, forums) and is NOT evidence of
        // phishing by itself — confirmed via testing against a real
        // Politeknik Brunei login page, which has a password field and
        // nothing else suspicious, yet was being marked SUSPICIOUS purely
        // for having a login form. It's kept as context only (0 points);
        // it still unlocks the corroborating checks below (brand mismatch,
        // form-action security), which is where genuine evidence comes from.
        if ($hasPasswordField) {
            $reasons[] = 'Page contains a password input field';
        } elseif ($sensitiveFields['has_other_sensitive']) {
            $fieldList = implode(', ', $sensitiveFields['matched']);
            $reasons[] = "Page requests sensitive information without a login form: {$fieldList} — a common non-login credential/PII harvesting pattern (e.g. fake refund or OTP-verification pages)";
            $points += 12;
        }
        $patternResult = $this->checkContentPatterns($combinedText, true);
        // Deliberately requires 2+ co-occurring lure patterns, not just 1 —
        // confirmed via testing that a single matched category (e.g. the
        // word "account" or "verify" appearing anywhere) triggers on
        // completely legitimate e-commerce pages like Amazon's, which
        // naturally use this vocabulary throughout checkout/account flows.
        // Multiple DIFFERENT lure categories appearing together is a much
        // more specific, less common pattern.
        if ($patternResult['matched_categories'] >= 3) {
            $reasons[] = 'Page text contains multiple phishing-style lure phrases (urgency, credential requests, etc.)';
            $points += 15;
        } elseif ($patternResult['matched_categories'] >= 2) {
            $reasons[] = 'Page text contains phishing-style lure language';
            $points += 8;
        }

        // Brand impersonation is only flagged when CORROBORATED by an actual
        // password field on the same page. Brand mention alone is a weak,
        // extremely common signal — any Wikipedia article, news piece, or
        // review site legitimately mentions a brand by name while living on
        // a different domain. Confirmed via testing: without this gate, a
        // Wikipedia article about PayPal was flagged identically to a real
        // PayPal phishing clone. A genuine credential-harvesting page
        // impersonates a brand AND asks for a password together — an
        // informational page about that brand does not do both.
        if ($hasSensitiveField) {
            $brandDetection = $this->detectBrandExactMatch(
                $combinedText,
                fn (string $brand) => $this->brandIsPageIdentityOrLoginPrompt($brand, $title, $html, $bodyText)
            );
            if ($brandDetection['brand']) {
                $brandMismatch = $this->checkPageBrandMismatch($brandDetection['brand'], $brandDetection['surface'], $host);
                if ($brandMismatch['flagged']) {
                    $reasons = array_merge($reasons, $brandMismatch['reasons']);
                    $points += $brandMismatch['points'];
                }
            }

            $formSecurityResult = $this->checkFormActionSecurity($html, $host);
            if ($formSecurityResult['flagged']) {
                $reasons = array_merge($reasons, $formSecurityResult['reasons']);
                $points += $formSecurityResult['points'];
            }
        }

        // Static HTML redirect trick that checkRedirectChain() can't see —
        // that check only follows real HTTP 3xx responses.
        $metaRefreshResult = $this->checkMetaRefresh($html, $host);
        if ($metaRefreshResult['flagged']) {
            $reasons = array_merge($reasons, $metaRefreshResult['reasons']);
            $points += $metaRefreshResult['points'];
        }

        $obfuscationResult = $this->checkObfuscationPatterns($html);
        if ($obfuscationResult['flagged']) {
            $reasons = array_merge($reasons, $obfuscationResult['reasons']);
            $points += $obfuscationResult['points'];
        }

        // A page that JavaScript builds arrives as an empty shell, so "no login form found" would only mean the
        // form had not been drawn yet. Say so instead of passing it. Anything actually found above still counts.
        if (empty($reasons) && ! $rendered && PageInspector::needsJavaScript($html)) {
            return [
                'flagged' => false,
                'points' => 0,
                'neutral' => true,
                'reasons' => ['This page is drawn by JavaScript, so only an empty shell could be read and a login form on it would not show up here. Nothing suspicious was found in the part that could be read.'],
                'page_title' => $title,
                'page_text' => $bodyText,
                'has_sensitive_field' => false,
            ];
        }

        if (empty($reasons)) {
            $reasons[] = 'No login forms, sensitive-data requests, phishing-style language, brand impersonation, hidden iframes, or obfuscated scripts detected in page content';
        }

        // Purely informational — doesn't affect flagged/points. Legitimate
        // sites and phishing pages both routinely omit or fake this header,
        // so it's context for a reader, not evidence on its own.
        if ($serverHeader) {
            $reasons[] = "Server responds as: {$serverHeader}";
        }

        return [
            'flagged' => $points > 0,
            'points' => min(70, $points),
            'reasons' => $reasons,
            // Extra context for the optional AI page review. Not shown to users.
            'page_title' => $title,
            'page_text' => $bodyText,
            'has_sensitive_field' => $hasSensitiveField,
        ];
    }

    /**
     * Scans <input> tags for fields beyond just "password" that also
     * indicate credential/PII harvesting — card numbers, CVV, bank
     * account/routing numbers, OTP/PIN codes, and national ID/SSN numbers.
     * A fake "claim your refund" or "verify OTP" page often has NO password
     * field at all, so gating every deeper check on password presence alone
     * (the previous behavior) let this entire class of phishing page through
     * with a clean result. Matches on name/id/placeholder attributes since
     * that's how these fields are actually labeled in real markup.
     */
    private function detectSensitiveInputFields(string $html): array
    {
        $hasPassword = (bool) preg_match('/<input[^>]+type\s*=\s*["\']password["\']/i', $html);

        $labeledPatterns = [
            'card number' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:card[-_ ]?number|cardnumber|cc[-_]?num)[^"\']*["\']/i',
            'CVV/CVC' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:cvv|cvc|security[-_ ]?code)[^"\']*["\']/i',
            'bank account/routing number' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:account[-_ ]?number|routing[-_ ]?number|iban|swift)[^"\']*["\']/i',
            'OTP/PIN' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:otp|one[-_ ]?time[-_ ]?password|verification[-_ ]?code|\bpin\b)[^"\']*["\']/i',
            'national ID/SSN' => '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:ssn|social[-_ ]?security|national[-_ ]?id|nric)[^"\']*["\']/i',
        ];

        preg_match_all('/<input\b[^>]*>/i', $html, $inputTags);
        $matched = [];

        foreach ($inputTags[0] as $tag) {
            foreach ($labeledPatterns as $label => $regex) {
                if (preg_match($regex, $tag) && ! in_array($label, $matched)) {
                    $matched[] = $label;
                }
            }
        }

        return [
            'has_password' => $hasPassword,
            'has_other_sensitive' => ! empty($matched),
            'matched' => $matched,
        ];
    }

    /**
     * Unlabeled regex list shared with detectSensitiveInputFields() so
     * checkFormActionSecurity() can flag a card/OTP/bank-harvesting form's
     * action destination exactly the same way it already does for password
     * forms, without duplicating the pattern definitions.
     */
    private function sensitiveFieldPatterns(): array
    {
        return [
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:card[-_ ]?number|cardnumber|cc[-_]?num)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:cvv|cvc|security[-_ ]?code)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:account[-_ ]?number|routing[-_ ]?number|iban|swift)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:otp|one[-_ ]?time[-_ ]?password|verification[-_ ]?code|\bpin\b)[^"\']*["\']/i',
            '/(?:name|id|placeholder)\s*=\s*["\'][^"\']*(?:ssn|social[-_ ]?security|national[-_ ]?id|nric)[^"\']*["\']/i',
        ];
    }

    private function formContainsSensitiveField(string $formBody): bool
    {
        foreach ($this->sensitiveFieldPatterns() as $regex) {
            if (preg_match($regex, $formBody)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects a <meta http-equiv="refresh"> tag that redirects to a
     * DIFFERENT domain than the page itself — the meta-tag equivalent of
     * the cross-domain redirect already flagged in checkRedirectChain(),
     * which only follows real HTTP 3xx status codes and would miss this
     * entirely, since a page using meta-refresh returns a normal HTTP 200.
     */
    private function checkMetaRefresh(string $html, string $pageHost): array
    {
        if (! preg_match('/<meta[^>]+http-equiv\s*=\s*["\']refresh["\'][^>]*content\s*=\s*["\']([^"\']*)["\']/i', $html, $m)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        if (! preg_match('/url\s*=\s*(\S+)/i', $m[1], $urlMatch)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $targetUrl = trim($urlMatch[1], '\'" ');
        $targetHost = str_starts_with($targetUrl, 'http')
            ? parse_url($targetUrl, PHP_URL_HOST)
            : $pageHost;

        $normalize = fn (?string $h) => preg_replace('/^www\./', '', strtolower($h ?? ''));

        if ($this->isOfficialBrandDomain($pageHost)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        if ($targetHost && $normalize($targetHost) !== $normalize($pageHost)) {
            return [
                'flagged' => true,
                'points' => 15,
                'reasons' => ["Page uses a meta-refresh tag to automatically redirect visitors to a different domain ({$targetHost}) than the one they loaded"],
            ];
        }

        return ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    /**
     * The first well-known brand named as a whole word in a URL path, or null.
     * Words are split on anything that is not a letter or digit. A brand of
     * five letters or more also matches a word that starts with it
     * ("tiktokshop"); shorter brands ("dhl", "ups") must match exactly so
     * that "setups" or "groups" never count.
     */
    private function brandInPath(string $path): ?string
    {
        $words = preg_split('/[^a-z0-9]+/', strtolower($path), -1, PREG_SPLIT_NO_EMPTY);

        foreach (array_keys($this->officialBrandDomains()) as $brand) {
            if (! preg_match('/^[a-z0-9]+$/', $brand)) {
                continue; // multi-word or dotted names such as "royal mail"
            }

            foreach ($words as $word) {
                if ($word === $brand || (strlen($brand) >= 5 && str_starts_with($word, $brand))) {
                    return $brand;
                }
            }
        }

        return null;
    }

    /**
     * True when the host is, or is a subdomain of, a domain on the official
     * brand list (the same list the page-content brand check uses).
     */
    /** Every known brand (lower case) => its official domains, Brunei names and acronyms included. */
    private function brandDomainMap(): array
    {
        return array_merge($this->officialBrandDomains(), $this->bruneiBrands(), array_change_key_case($this->bruneiAcronyms(), CASE_LOWER));
    }

    private function isOfficialBrandDomain(string $host): bool
    {
        $host = strtolower(preg_replace('/^www\./', '', $host));

        foreach ($this->brandDomainMap() as $domains) {
            foreach ($domains as $official) {
                if ($host === $official || str_ends_with($host, '.'.$official)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** True for gov.bn itself and anything under it. */
    /**
     * A message that pushes the reader to act (fine, threat, prize, urgency)
     * and carries a link to a site that is not a known official domain. Real
     * authorities send these through their own app or website, so the pairing
     * is worth points even when the link itself looks plain.
     */
    /**
     * Optional AI read of the message (see AiTextCheck). Skipped when the rules alone
     * already say phishing, since it could add nothing useful there.
     */
    private function checkAiText(string $text, int $rulePoints): ?array
    {
        $ai = app(AiTextCheck::class);

        if (! $ai->enabled() || $rulePoints >= 60) {
            return null;
        }

        return $ai->assess($text);
    }

    /**
     * Optional AI read of a web page (see AiTextCheck). Skipped when the other checks already
     * say phishing, on official brand and Brunei government sites, and when the page has too
     * little text to judge.
     */
    private function checkAiPage(string $host, array $contentResult, int $otherPoints): ?array
    {
        $ai = app(AiTextCheck::class);

        if (! $ai->enabled() || $otherPoints >= 60) {
            return null;
        }

        $host = strtolower((string) preg_replace('/^www\./', '', $host));

        if ($this->isOfficialBrandDomain($host) || $this->isBruneiGovernmentHost($host)) {
            return null;
        }

        $text = trim((string) ($contentResult['page_text'] ?? ''));

        if (mb_strlen($text) < 40) {
            return null;
        }

        $input = 'Domain: '.$host."\n"
            .'Page title: '.trim((string) ($contentResult['page_title'] ?? ''))."\n"
            .'Has a password or other sensitive input field: '.(($contentResult['has_sensitive_field'] ?? false) ? 'yes' : 'no')."\n\n"
            ."Visible text:\n".$text;

        return $ai->assess($input, 'page');
    }

    private function checkLinkInPressureMessage(array $contentResult, ?string $url): array
    {
        $none = ['flagged' => false, 'points' => 0, 'reasons' => []];

        if (! $url || ($contentResult['matched_categories'] ?? 0) < 1) {
            return $none;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        if ($host === '' || $this->isOfficialBrandDomain($host) || $this->isBruneiGovernmentHost($host)) {
            return $none;
        }

        return [
            'flagged' => true,
            'points' => 15,
            'reasons' => ["The message pressures the reader to act and links to \"{$host}\", which is not a known official domain"],
        ];
    }

    private function isBruneiGovernmentHost(?string $host): bool
    {
        $host = strtolower((string) $host);

        return $host === 'gov.bn' || str_ends_with($host, '.gov.bn');
    }

    /**
     * Recognises the hidden iframe Google Tag Manager uses as its <noscript>
     * fallback, in the three forms seen on real sites: a googletagmanager.com
     * link, the same link kept in data-src (lazy loading), and a first-party
     * path on the site's own server that carries a GTM container id.
     *
     * A link that merely contains "GTM-" on another site does not qualify, so
     * a phishing page cannot use the exemption by copying the id.
     */
    private function isTagManagerIframe(string $tag): bool
    {
        $read = function (string $attribute) use ($tag): ?string {
            // (?<![\w-]) stops "src" matching inside "data-src".
            if (! preg_match('/(?<![\w-])'.$attribute.'\s*=\s*["\']([^"\']*)["\']/i', $tag, $m)) {
                return null;
            }

            $value = html_entity_decode(trim($m[1]));

            // about:blank and inline images (the 1x1 GIF lazy-loaders use as a
            // placeholder) load nothing. Other data: URLs, such as data:text/html,
            // are real content and must not be skipped.
            if ($value === '' || strcasecmp($value, 'about:blank') === 0 || stripos($value, 'data:image/') === 0) {
                return null;
            }

            return $value;
        };

        // The browser loads src when there is one, so src decides. data-src is
        // only consulted when src is absent or blank (lazy-loading scripts fill
        // src in later); otherwise a page could pair an evil src with a
        // harmless-looking data-src to slip past the check.
        $value = $read('src') ?? $read('data-src');

        if ($value === null) {
            return false;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if ($host && preg_match('/(^|\.)googletagmanager\.com$/i', $host)) {
            return true;
        }

        return str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && preg_match('/[?&]id=GTM-[A-Z0-9]+/i', $value) === 1;
    }

    /**
     * Flags two Tier-1-detectable evasion patterns that need no headless
     * browser or JS execution: (1) a hidden iframe (zero-size or
     * display:none/visibility:hidden), commonly used to load malicious
     * content invisibly, and (2) eval() combined with atob()/unescape() —
     * decode-then-execute, a common way phishing kits hide their real
     * payload from simple text scanning. Neither requires a sensitive
     * field to be present — a hidden iframe or obfuscated script is
     * suspicious on any page.
     */
    private function checkObfuscationPatterns(string $html): array
    {
        $reasons = [];
        $points = 0;

        // Zero-size iframes are also how Google Tag Manager's standard
        // <noscript> snippet works, and that snippet is on a large share of
        // legitimate sites (found when PayPal, Grab and several Brunei sites
        // were flagged for it). Those are skipped; any other hidden iframe
        // still counts.
        if (preg_match_all('/<iframe\b[^>]*>/i', $html, $iframeTags)) {
            foreach ($iframeTags[0] as $iframeTag) {
                // (?<![\w-]) so "marginwidth=\"0\"" and "data-width=\"0\"" are not read as width="0".
                $isHidden = preg_match('/(?<![\w-])(?:width|height)\s*=\s*["\']0["\']|display\s*:\s*none|visibility\s*:\s*hidden/i', $iframeTag);

                if ($isHidden && ! $this->isTagManagerIframe($iframeTag)) {
                    $reasons[] = 'Page contains a hidden iframe (zero-size or display:none/visibility:hidden) — commonly used to load malicious content invisibly to the visitor';
                    $points += 20;
                    break;
                }
            }
        }

        if (preg_match('/eval\s*\(\s*(?:atob|unescape)\s*\(/i', $html)) {
            $reasons[] = 'Page contains obfuscated JavaScript (eval combined with atob/unescape decoding) — a common technique for hiding a script\'s real behavior from simple text scanning';
            $points += 15;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
        ];
    }

    /** "Sign in with Google", "Continue with Facebook", "Log in using Apple", "Daftar dengan Google". Not "with YOUR PayPal account". */
    private const SOCIAL_LOGIN_PHRASE = '/\b(?:sign\s*(?:in|up)|log\s*(?:in|on)|login|continue|connect|register|join|masuk|daftar|log\s*masuk)\s+(?:with|using|via|through|dengan|guna|melalui)\b(?!\s+(?:your|my|an?|the|akaun|kata)\b)/iu';

    /** Sign-in pages of the big identity providers, for "Sign in with ..." buttons that show only a logo. */
    private const IDENTITY_PROVIDER_LINK = '~(?:accounts\.google\.com|facebook\.com/(?:v[\d.]+/)?dialog/oauth|appleid\.apple\.com|login\.microsoftonline\.com|login\.live\.com|github\.com/login/oauth|linkedin\.com/oauth|(?:api\.)?(?:twitter|x)\.com/(?:i/)?oauth)~i';

    /** The page's HTML without its "sign in with another service" buttons and links. */
    private function withoutSocialLoginControls(string $html): string
    {
        return preg_replace_callback('/<(a|button)\b[^>]*>.*?<\/\1>/is', function (array $match): string {
            $block = $match[0];
            $namesProviderAsLogin = mb_strlen(trim(strip_tags($block))) <= 80 && preg_match(self::SOCIAL_LOGIN_PHRASE, $block) === 1;
            $linksToProvider = preg_match(self::IDENTITY_PROVIDER_LINK, $block) === 1;

            return $namesProviderAsLogin || $linksToProvider ? ' ' : $block;
        }, $html) ?? $html;
    }

    /**
     * Whether a brand named somewhere in a page is being USED by the page, not
     * just mentioned. It counts when the brand is in the page's identity (title,
     * a heading, an image's alt/title text, or a site-name meta tag), or when
     * it sits within a few words of a login or credential word ("log in to your
     * PayPal account"). A school page that lists "Microsoft Paint, Microsoft
     * Excel" among its computer-lab software does neither, and was being
     * called brand impersonation because it also has a staff login box.
     */
    private function brandIsPageIdentityOrLoginPrompt(string $brand, string $title, string $html, string $bodyText): bool
    {
        // "Sign in with Google" and "Continue with Facebook" buttons name a brand as the way to
        // log in, not as the site. Almost every shop, forum and app has them, so they are set
        // aside before looking for a page that pretends to BE the brand.
        $withoutSocialButtons = $this->withoutSocialLoginControls($html);
        if ($withoutSocialButtons !== $html) {
            $html = $withoutSocialButtons;
            $bodyText = Str::limit(trim(strip_tags(preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html)), 5000, '');
        }

        $b = preg_quote($brand, '/');

        $identity = $title;

        if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $headings)) {
            $identity .= ' '.strip_tags(implode(' ', $headings[1]));
        }

        if (preg_match_all('/<img[^>]+(?:alt|title)\s*=\s*["\']([^"\']*)["\']/i', $html, $imageText)) {
            $identity .= ' '.implode(' ', $imageText[1]);
        }

        if (preg_match_all('/<meta[^>]+(?:og:site_name|application-name)[^>]*content\s*=\s*["\']([^"\']*)["\']/i', $html, $siteNames)) {
            $identity .= ' '.implode(' ', $siteNames[1]);
        }

        if (preg_match('/\b'.$b.'\b/i', $identity)) {
            return true;
        }

        $credential = '(?:sign\s*in|log\s*in|login|logon|password|passcode|verify|verification|confirm|secure|account|billing|payment|wallet|recover|unlock|suspended)';

        return preg_match('/\b'.$credential.'\b(?:\W+\w+){0,6}?\W+\b'.$b.'\b/i', $bodyText) === 1
            || preg_match('/\b'.$b.'\b(?:\W+\w+){0,6}?\W+\b'.$credential.'\b/i', $bodyText) === 1;
    }

    /**
     * Exact-only brand mention detection for page content — deliberately
     * simpler than detectBrandInText()'s fuzzy fallback. See the caller in
     * checkPageContent() for why: fuzzy matching against a full page body
     * has far more false-positive surface than short OCR/email text.
     */
    private function detectBrandExactMatch(string $text, ?callable $confirm = null): array
    {
        // The names come from config/brands.php (page_brands).
        $knownBrands = config('brands.page_brands', []);

        $knownBrands = array_values(array_unique(array_merge($knownBrands, array_keys($this->bruneiBrands()))));

        $lowerText = strtolower($text);

        foreach ($knownBrands as $brand) {
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/', $lowerText) && ($confirm === null || $confirm($brand))) {
                return ['brand' => $brand, 'surface' => $brand];
            }
        }

        return ['brand' => null, 'surface' => null];
    }

    /**
     * Checks whether a brand referenced in a page's visible content actually
     * belongs to the domain serving that page — the page-content equivalent
     * of checkBrandSenderMismatch() for emails. Reuses the same official
     * domain list so a brand is judged by the same standard everywhere.
     */
    private function checkPageBrandMismatch(string $brand, ?string $surfaceText, string $host): array
    {
        $host = strtolower(preg_replace('/^www\./', '', $host));

        if (in_array($brand, $this->bruneiGovNames(), true) && $this->isBruneiGovernmentHost($host)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $brandDomains = $this->brandDomainMap();

        $allowed = $brandDomains[$brand] ?? [$brand.'.com'];
        foreach ($allowed as $officialDomain) {
            if ($host === $officialDomain || str_ends_with($host, '.'.$officialDomain)) {
                return ['flagged' => false, 'points' => 0, 'reasons' => []];
            }
        }

        $brandName = array_key_exists(strtoupper($brand), $this->bruneiAcronyms()) ? strtoupper($brand) : ucfirst($brand);
        $displayBrand = $surfaceText && $surfaceText !== $brand
            ? strtoupper($surfaceText).'" (a lookalike of "'.$brandName.'")'
            : $brandName;

        return [
            'flagged' => true,
            'points' => 30,
            'reasons' => ['Page content references "'.$displayBrand."\" branding, but the domain (\"{$host}\") does not belong to {$brand} — likely brand impersonation"],
        ];
    }

    /**
     * Checks a login form's own <form action="..."> destination — two
     * well-documented static-HTML phishing indicators from published
     * anti-phishing research: (1) the form submits credentials to a
     * DIFFERENT domain than the page itself is hosted on, and (2) the form
     * submits directly via "mailto:" rather than a server endpoint, a
     * common low-effort phishing-kit pattern that needs no backend at all.
     * Both are considered stronger, more specific signals than a bare
     * password field alone, since a cloned legitimate page's HTML often
     * carries the real site's structure but must redirect stolen
     * credentials somewhere the attacker actually controls.
     *
     * Deliberately scoped PER-FORM: only forms that themselves contain a
     * password field are checked. This avoids false positives from
     * unrelated forms elsewhere on the same page (e.g. a legitimate
     * newsletter signup submitting to Mailchimp) being flagged just
     * because a login form exists somewhere else on the page.
     */
    private function checkFormActionSecurity(string $html, string $pageHost): array
    {
        $reasons = [];
        $points = 0;

        preg_match_all('/<form\b[^>]*\baction\s*=\s*["\']([^"\']*)["\'][^>]*>(.*?)<\/form>/is', $html, $matches, PREG_SET_ORDER);

        $flaggedExternal = false;
        $flaggedEmail = false;

        foreach ($matches as $match) {
            $action = trim($match[1]);
            $formBody = $match[2];

            $formHasPassword = (bool) preg_match('/<input[^>]+type\s*=\s*["\']password["\']/i', $formBody);
            $formHasSensitive = $formHasPassword || $this->formContainsSensitiveField($formBody);
            if (! $formHasSensitive || $action === '') {
                continue;
            }

            if (stripos($action, 'mailto:') === 0) {
                $flaggedEmail = true;

                continue;
            }

            $actionHost = parse_url($action, PHP_URL_HOST);
            if ($actionHost === null) {
                // A relative path ("/login", "submit.php") resolves to the
                // same host the page is already on — not suspicious.
                continue;
            }

            // Same site means the same registered domain: a login on lms.example.edu.bn that posts to
            // sso.example.edu.bn is one organisation's own sign-in, not a different website.
            if (! RegistrableDomain::same($actionHost, $pageHost)) {
                $flaggedExternal = true;
            }
        }

        if ($flaggedEmail) {
            $reasons[] = 'Login form submits directly to an email address (mailto:) instead of a server endpoint — a common low-effort phishing-kit pattern requiring no backend';
            $points += 20;
        }

        if ($flaggedExternal) {
            $reasons[] = 'Login form submits entered credentials to a different domain than the page itself — a strong indicator of credential harvesting';
            $points += 25;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
        ];
    }

    /**
     * Normalizes common leetspeak digit-for-letter substitutions used to
     * evade simple brand-name substring matching (e.g. "micros0ft.com"
     * using a zero in place of the letter "o"). This deliberately covers
     * only the most common digit tricks, not full Unicode homoglyph
     * detection (e.g. Cyrillic lookalike characters), which is a much
     * larger problem outside this project's scope.
     */
    private function normalizeForBrandMatch(string $text): string
    {
        return strtr($text, [
            '0' => 'o',
            '1' => 'l',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '7' => 't',
            '8' => 'b',
        ]);
    }

    /**
     * Extracts the domain after "@" and flags brand-impersonation / suspicious patterns.
     */
    public function checkEmailDomain(string $email): array
    {
        if (! str_contains($email, '@')) {
            return [
                'flagged' => true,
                'points' => 30,
                'reasons' => ['Not a valid email address format'],
                'domain' => null,
            ];
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1));
        $reasons = [];
        $points = 0;

        $brands = ['paypal', 'google', 'facebook', 'apple', 'microsoft', 'amazon', 'bank', 'dhl', 'fedex', 'ups', 'maybank', 'bibd', 'coinbase', 'binance', 'metamask', 'bradesco', 'livelo', 'correios', 'whatsapp',
            'roblox', 'discord', 'epicgames', 'riotgames', 'garena', 'minecraft', 'playstation', 'xbox', 'nintendo', 'steampowered', 'steamcommunity'];
        $normalizedDomain = $this->normalizeForBrandMatch($domain);
        // A sender on a verified official domain (bibd.com.bn, maybank2u.com.my,
        // blog.google and so on) is not mimicking anything.
        $isOfficialSender = $this->isOfficialBrandDomain($domain) || ThreatLookup::isPopular($domain);
        foreach ($brands as $brand) {
            if ($isOfficialSender) {
                break;
            }
            $matchesBrand = str_contains($normalizedDomain, $brand);
            $isOfficialDomain = str_ends_with($domain, $brand.'.com') || $domain === $brand.'.com';

            if ($matchesBrand && ! $isOfficialDomain) {
                // "bank" is an ordinary word as well as a brand, so on its own it is a mild warning only.
                if (in_array($brand, self::ORDINARY_WORD_BRANDS, true) && ! $this->addressLooksDisguised($domain, $brand)) {
                    $reasons[] = "Sender domain contains the common word \"{$brand}\", which is only a mild warning on its own";
                    $points += 10;
                    break;
                }

                $reasons[] = str_contains($domain, $brand)
                    ? "Sender domain mimics the brand \"{$brand}\" without being the official domain"
                    : "Sender domain mimics the brand \"{$brand}\" using character substitution (e.g. a digit in place of a letter), without being the official domain";
                $points += 35;
                break;
            }
        }

        // A sender name written to be mistaken for a brand ("gooogle.com", "arnazon.com"), when no brand rule fired above.
        if (! $isOfficialSender && $points === 0 && ($lookAlike = $this->lookAlikeFinding($domain)) !== null) {
            $reasons[] = str_replace('The domain', "The sender's domain", $this->lookAlikeReason($lookAlike));
            $points += $lookAlike['type'] === 'typo' ? 25 : 30;
        }

        // Hyphens and digit runs are judged on the name the sender registered ("secure-login-1234.com"),
        // not on mail-server subdomains: bulk senders use names such as "em1234.mail.example.com" or
        // "mail-us-east-1.example.com" for ordinary newsletters and receipts.
        $registered = RegistrableDomain::of($domain) ?? $domain;

        $hyphenCount = LookAlike::hyphenCount($registered);
        if ($hyphenCount >= 2) {
            $reasons[] = "Sender domain contains {$hyphenCount} hyphens, which is unusually high";
            $points += 15;
        }

        // Punycode names ("xn--80ak6aa92e") carry digits as part of the encoding, so they are not counted.
        if (preg_match('/\d{2,}/', preg_replace('/xn--[a-z0-9\-]+/', '', $registered) ?? $registered)) {
            $reasons[] = 'Sender domain contains an unusual number sequence, common in disposable phishing domains';
            $points += 15;
        }

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => $reasons,
            'domain' => $domain,
            'detail' => $reasons === [] ? "Nothing unusual in the sender's address: no look-alike brand name, odd hyphens or strange number sequence." : null,
        ];
    }

    /**
     * Validates reported phone numbers using Google's libphonenumber (via the
     * giggsey/libphonenumber-for-php port) instead of hand-rolled per-country
     * rules. This correctly validates format and numbering-plan assignment for
     * any country, not just Brunei, and identifies the number's line type
     * (mobile, VoIP, premium-rate, etc.) using real carrier metadata.
     *
     * "BN" is passed as the default region: if the number already includes an
     * explicit country code (e.g. "+1 555..."), that code takes priority
     * regardless; if it's a bare local-style number with no country code, it's
     * assumed to be a Brunei number, matching how people naturally type local
     * numbers into this platform.
     */
    public function checkPhoneNumber(string $phone): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsed = $phoneUtil->parse($phone, 'BN');

            if (! $phoneUtil->isValidNumber($parsed)) {
                return [
                    'flagged' => true,
                    'points' => 35,
                    'reasons' => ["This number cannot exist under its country's numbering plan, so it may have been typed wrongly. Check it again. If it really called or messaged you, the caller ID may be fake (spoofed), which scammers do"],
                ];
            }

            $reasons = [];
            $points = 0;

            $region = $phoneUtil->getRegionCodeForNumber($parsed);
            $type = $phoneUtil->getNumberType($parsed);
            $typeLabel = match ($type) {
                PhoneNumberType::FIXED_LINE => 'Fixed line',
                PhoneNumberType::MOBILE => 'Mobile',
                PhoneNumberType::FIXED_LINE_OR_MOBILE => 'Fixed line or mobile',
                PhoneNumberType::TOLL_FREE => 'Toll-free',
                PhoneNumberType::PREMIUM_RATE => 'Premium rate',
                PhoneNumberType::SHARED_COST => 'Shared cost',
                PhoneNumberType::VOIP => 'VoIP',
                PhoneNumberType::PERSONAL_NUMBER => 'Personal number',
                PhoneNumberType::PAGER => 'Pager',
                PhoneNumberType::UAN => 'Universal access number',
                PhoneNumberType::VOICEMAIL => 'Voicemail',
                PhoneNumberType::EMERGENCY => 'Emergency',
                PhoneNumberType::SHORT_CODE => 'Short code',
                PhoneNumberType::STANDARD_RATE => 'Standard rate',
                default => 'Unknown',
            };

            switch ($type) {
                case PhoneNumberType::PREMIUM_RATE:
                    $reasons[] = 'Number is a premium-rate line, unusual for a personal sender and often used in call-back scams';
                    $points += 35;
                    break;
                case PhoneNumberType::VOIP:
                    $reasons[] = 'Number is a VoIP/internet-based line, inexpensive to acquire anonymously and commonly used in phishing/smishing campaigns';
                    $points += 20;
                    break;
                case PhoneNumberType::UNKNOWN:
                    $reasons[] = 'Number type could not be determined by the numbering plan, which can indicate an unusual allocation';
                    $points += 15;
                    break;
                case PhoneNumberType::PAGER:
                case PhoneNumberType::UAN:
                case PhoneNumberType::SHARED_COST:
                    $reasons[] = "Number is a {$typeLabel} line, an uncommon type for a personal sender";
                    $points += 10;
                    break;
                default:
                    // Mobile, Fixed line, Fixed line or mobile, Toll-free,
                    // Personal number, Voicemail: no penalty by default.
                    break;
            }

            // Deliberately NOT a flat penalty for being non-Brunei — a real
            // foreign contact (family, courier, overseas business) is
            // completely normal. This is informational context only.
            if ($region && $region !== 'BN') {
                $reasons[] = "Number originates from outside Brunei (region: {$region}) — worth verifying if it claims to represent a local Brunei service";
            }

            $nationalDigits = preg_replace('/\D/', '', $phoneUtil->format($parsed, PhoneNumberFormat::NATIONAL));

            if ($nationalDigits !== '' && preg_match('/^(\d)\1+$/', $nationalDigits)) {
                $reasons[] = 'Number consists of a single digit repeated throughout, a common sign of a fabricated number even if technically within a valid range';
                $points += 25;
            } elseif ($nationalDigits !== '' && $this->isSequentialDigits($nationalDigits)) {
                $reasons[] = 'Number follows a simple sequential digit pattern, a common sign of a fabricated number';
                $points += 20;
            }

            if (empty($reasons)) {
                $reasons[] = "Valid {$typeLabel} number".($region ? " registered in {$region}" : '').', no issues detected';
            }

            return [
                'flagged' => $points > 0,
                'points' => $points,
                'reasons' => $reasons,
                'region' => $region,
                'type' => $typeLabel,
            ];
        } catch (NumberParseException $e) {
            // Genuinely unparseable input ("2", random text) isn't evidence
            // of a scam — it's more likely a typo or incomplete report. Flag
            // this distinctly so analyzePhone() can route it to an honest
            // "not enough information" result instead of a confident
            // suspicious score, the same way screenshot scanning handles an
            // image with no extractable phishing content.
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['This does not appear to be a phone number at all — please check the format and try again'],
                'unparseable' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['Could not validate this number due to an unexpected error'],
            ];
        }
    }

    private function checkContentPatterns(string $text, bool $forPage = false): array
    {
        $text = mb_strtolower($text);
        $reasons = [];
        $points = 0;
        $matchedCategories = 0;
        $specificCategories = 0;
        $everydayCategories = 0;
        $disguised = false;
        if (! $forPage) {
            [$text, $disguised] = $this->unmaskText($text);
        }

        // Secrecy is read from the original text: "jangan bagitau sesiapa" is a scam cue, and the
        // warning clean-up below would otherwise remove it.
        $secrecy = (bool) preg_match('/(keep (this|the) (matter |call |conversation )?(confidential|secret|private)|rahsiakan|jangan (beritahu|bagitau|bagitahu|bagi tau|kasi tau|cerita) (\S+\s+){0,2}?(sesiapa|siapa|orang|keluarga|family)|(don\'?t|do not|never) (tell|inform) (anyone|your family|family|the bank)|between you and me)/u', $text);

        // Safety warnings quote the words scams use ("never share your OTP", "jangan kongsi kod
        // OTP", "banks will never ask for your PIN", "returns are not guaranteed"). Those clauses
        // are removed first so a warning is not scored as the scam it warns about.
        // Web pages keep their original scoring (see below), so this is for messages only.
        $text = $forPage ? $text : preg_replace([
            '/\b(never|do not|don\'t|dont|jangan|usah|tidak (?:boleh|perlu)|supaya tidak|not to)\s+(?:\S+\s+){0,2}?(share|give|send|disclose|reveal|forward|kongsi|berkongsi|beri|berikan|bagi|hantar|dedahkan)\s+(?:\S+\s+){0,4}?(otp|tac|pin|password|passcode|kata laluan|code|codes|kod|card|kad|bank|banking|perbankan|details|maklumat|personal|ic|this code)\b[^.!?\n]*/u',
            '/\b(will never|would never|never|tidak akan|tidak pernah|takkan|tak akan)\s+(?:\S+\s+){0,2}?(ask|request|call|meminta|minta|bertanya|menghubungi|menelefon)\b[^.!?\n]*/u',
            '/\b(not|tidak|tak|bukan)\s+(guaranteed|dijamin)\b/u',
            // A code being delivered ("Your verification code is 482913") is what every sign-in and sign-up email does.
            // Asking someone to hand a code over is a different thing and is still caught (see secret_ask).
            '/\b(?:verification|security|login|sign-?in|confirmation|one[- ]time|otp|tac)\s*(?:code|password|pin)?\s*(?:is|are|:)\s*\d{4,8}\b/u',
            // Public warnings ("police warn the public about job scams on Telegram...") describe a scam.
            '/[^.!?\n]*\b(warns?|warned|warning) (the public|people|everyone|orang ramai|customers|members of the public)\b[^.!?\n]*/u',
            '/[^.!?\n]*\b(beware of|be careful of|berhati-hati dengan|waspada (dengan|terhadap)|(diingatkan|dinasihatkan) supaya|scam alert|amaran penipuan)\b[^.!?\n]*/u',
        ], ' ', $text) ?? $text;

        $patterns = [
            'urgency' => [
                'regex' => '/\b(within (the )?next 24 hours|less than 24 hours|within (24|48|72) hours|expires? in \d+ (hours|days)|act now|act immediately|urgent|urgently|final notice|immediately|offer ends|ends today|tindakan segera|segera (sahkan|kemas kini|bertindak|hubungi)|dalam masa 24 jam|notis akhir|amaran terakhir|at[eé] amanh[aã]|pr[oó]ximos de expirar|[uú]ltimo aviso|intento final|aviso final|dringend|sofort)\b/u',
                'message_regex' => '/\b(within (the )?next 24 hours|less than 24 hours|within (24|48|72) hours|within \d+ (minutes|hours|days)|expires? in \d+ (hours|days)|act now|act immediately|urgent|urgently|final notice|immediately|offer ends|ends today|today only|before midnight|please hurry|hurry|tindakan segera|segera|dalam masa \d+ (jam|minit)|sebelum (jam|tarikh|tengah malam|slot)|hari ani|hari ini juga|cepat|penting (ni|ani)|notis akhir|amaran terakhir|at[eé] amanh[aã]|pr[oó]ximos de expirar|[uú]ltimo aviso|intento final|aviso final|dringend|sofort)\b/u',
                'points' => 15,
                'label' => 'Urgency language detected (e.g. "urgent", "24 hours", "act now", "segera")',
                'lure' => true,
            ],
            'account_threat' => [
                'regex' => '/(account will be suspended|(account|wallet|access|password|mailbox|membership) (is|has been|was|will be|is now)( temporarily| permanently)? (locked|suspended|deactivated|blocked|restricted|limited|expired)|password (will )?expir(es|y)|temporary suspension|avoid deactivation|will be blocked|have been blocked|messages blocked|unusual sign-?in|unusual (login|activity)|akaun (anda )?(akan |telah |sedang )?(di)?(sekat|bekukan|gantung|kunci|tutup)|valor bloqueado|conta (foi|ser[aá]|est[aá]) (bloquead|suspens|cancelad)|cuenta (ha sido|ser[aá]) (bloquead|suspendid)|vor[uü]bergehend|einschr[aä]nkung|activit[eé]s de connexion inhabituelles)/u',
                'message_regex' => '/(account will be suspended|(account|wallet|access|password|mailbox|membership) (is|has been|was|will be|is now)( temporarily| permanently)? (locked|suspended|deactivated|blocked|restricted|limited|expired|frozen)|password (will )?expir(es|y)|temporary suspension|avoid deactivation|will be blocked|have been blocked|messages blocked|unusual sign-?in|unusual (login|activity)|has been frozen|will be (suspended|deactivated|disconnected|frozen|terminated|cut off)|(access|account|banking) will expire|expire today|akaun (anda )?(akan |telah |sedang )?(di)?(sekat|bekukan|gantung|kunci|tutup)|(akaun|kad|card|talian|line|lesen|pasport|nombor|bekalan|supply|akses)\s+(\S+\s+){0,3}?(akan |telah |sudah |kena |kana |sedang )?(di)?(sekat|bekukan|beku|gantung|putuskan|potong|tamat tempoh|block|blocked|suspend|suspended|deactivated|disconnected|frozen|cut off)\b|(kena|kana) (sekat|block|gantung)|valor bloqueado|conta (foi|ser[aá]|est[aá]) (bloquead|suspens|cancelad)|cuenta (ha sido|ser[aá]) (bloquead|suspendid)|vor[uü]bergehend|einschr[aä]nkung|activit[eé]s de connexion inhabituelles)/u',
                'points' => 15,
                'label' => 'Account threat language detected (suspension/deactivation, "akaun disekat")',
                'lure' => true,
            ],
            'credential_request' => [
                'regex' => '/(verify your account|verify my|verify your (identity|wallet|email)|confirm your (password|information|identity|details)|enter your login|verification code|verify now|update your (payment|billing|account)|reset access|secret recovery phrase|seed phrase|recovery phrase|connect (your )?wallet|wallet verification|sahkan (akaun|maklumat|identiti|kata laluan)|masukkan (kata laluan|nombor pin|no\\.? pin|otp)|kod pengesahan|kod otp|confirme (seus|suas) dados|atualize (seus|suas|sua) (dados|conta|cadastro)|aktualisieren sie|kontoinformationen|mettez [aà] jour)/u',
                'message_regex' => '/(verify your account|verify my|verify your (identity|wallet|email)|confirm your (password|information|identity|details)|enter your login|enter your (ic|bank|card|online banking|account)|submit your (ic|identity card|bank|online banking|password|card)|update (your )?(ic|personal|account|bank) (details|information|number)|update your details|(fill in|isi) (your )?(bank|card|maklumat bank|butiran bank)|(bank|card|login) details|verification code|verify now|update your (payment|billing|account)|reset access|secret recovery phrase|seed phrase|recovery phrase|connect (your )?wallet|wallet verification|sahkan (akaun|maklumat|identiti|kata laluan)|masukkan (kata laluan|nombor pin|no\\.? pin|otp)|kemas ?kini (maklumat|butiran|akaun|data)|maklumat (bank|akaun|perbankan|kad)|butiran (bank|akaun|kad)|card number|nombor kad|isi borang|kod pengesahan|kod otp|confirme (seus|suas) dados|atualize (seus|suas|sua) (dados|conta|cadastro)|aktualisieren sie|kontoinformationen|mettez [aà] jour)/u',
                'points' => 20,
                'label' => 'Credential/verification request detected',
                'ask' => true,
            ],
            'secret_ask' => [
                'message_regex' => '/((send|share|give|forward|tell|reply with|provide|bagi|beri|berikan|hantar|kongsi)\s+(\S+\s+){0,3}?(otp|tac|pin|password|passcode|kata laluan|verification code|kod pengesahan|security code|the code|that code|kod (atu|itu|ani|ini|tadi|tu)|6[- ]digit|six[- ]digit|card number|nombor kad|no\.? kad|cvv)\b(?!\s+(setup|guide|form|settings|policy|manual|document|instructions|app|feature|generator|device|token))|terhantar kod|tersalah hantar kod|sent (my|the|a) (verification )?code to your number|forward it to me)/u',
                'points' => 30,
                'label' => 'Asks you to send a code, OTP, PIN or card number (no real bank, agency or friend needs these)',
                'ask' => true,
            ],
            'money_ask' => [
                'message_regex' => '/(transfer (me |us )?(bnd|b\$|\$|rm|sgd)\s?\d|pay (bnd|b\$|\$|rm)\s?\d|transfer (it |the money |a deposit |your savings |all )?(to|into) (our|my|this|the following|a safe|a secure|another)|send (me |us )?(bnd|b\$|\$|rm)\s?\d|send (me |us )?money|pay (a |the )?(deposit|processing fee|admin fee|registration fee|clearance fee|release fee|small admin fee|fee first)|(processing|admin|administration|registration|clearance|release) fee|transfer (a |the )?deposit|bayar (deposit|yuran|cagaran|duti|caj)|(bayar|transfer|pindah|hantar)\s(\S+\s){0,3}?dulu\b|transfer ke akaun|pindah(kan)? (semua )?(wang|duit)|hantar duit|yuran (proses|pemprosesan|pendaftaran|pelepasan|penghantaran)|invest (bnd|b\$|\$)\s?\d|minimum deposit|deposit minimum|bayar (zakat|derma|sumbangan)|(donation|derma|sumbangan) (needed|diperlukan|segera))/u',
                'points' => 15,
                'label' => 'Asks you to send money, pay a fee or pay a deposit first',
                'ask' => true,
            ],
            'financial_request' => [
                'regex' => '/(transfer (of )?funds|payment required|invoice|bank account|refund|pindahan wang|pindah wang|bayaran (diperlukan|segera)|akaun bank|bayaran balik)/',
                'points' => 15,
                'label' => 'Financial request or invoice language detected',
            ],
            'identity_claim' => [
                'message_regex' => '/(new number|nombor baru|nombor baharu|no\.? baru|nombor lama (sudah |dah )?(inda|tidak|tak) aktif|(lost|broke|changed) my phone|using a friend\'?s? phone|phone (ku|aku|saya|kamu) (rosak|hilang)|this is (the )?(royal brunei )?(police|polis|bank|bdcb|customs|immigration)|(kami|saya|aku|ani|i am|we are|i\'m)\s+(\S+\s+)?(dari|from) (pihak )?(bank|polis|police|kastam|customs|jpd|bdcb|imigresen|immigration|mahkamah|court)\b|ani pegawai|saya pegawai|this is (officer|inspector)|(whatsapp|customer|technical|account|security) support|(bank|bdcb) officer|pegawai (bank|penyiasat|polis))/u',
                'points' => 15,
                'label' => 'Claims to be someone you trust (a family member on a "new number", a bank, the police or "support")',
                'lure' => true,
            ],
            'too_good' => [
                'message_regex' => '/(guaranteed? (profit|returns?|income|payout)|(profit|returns?|pulangan|untung|keuntungan)\s+(\S+\s+){0,3}?(guaranteed|dijamin)|dijamin (halal|untung)|(earn|make|dapat|gaji|rate|pendapatan|upah|komisen)\s+(up to\s+)?(bnd|b\$|\$|rm)?\s?\d[\d,]*(\s?(to|hingga|-)\s?(bnd|b\$|\$)?\s?\d[\d,]*)?\s*(per |a |\/ ?)?(day|hari|sehari|week|weekly|seminggu|daily|hour|jam)\b|(bnd|b\$|\$)\s?\d[\d,]*\s*(sehari|per day|a day|daily|weekly|seminggu)|(sehari|per day|a day|daily) (bnd|b\$|\$)\s?\d|\d+\s?% (sebulan|setiap bulan|seminggu|a month|per month|monthly|every month|a week|per week|weekly|daily|sehari)|passive income|kerja (sambilan|online|dari rumah)|part[- ]time job|data entry from home|work from home|like (and|dan|&) subscribe|liking (\S+ )?videos|no experience (needed|required)|tanpa pengalaman|modal kecil|untung besar|receive (bnd|b\$|\$)\s?\d[\d,]* in \d+ days|made (bnd|b\$|\$)\s?\d[\d,]* in|no guarantor|tanpa penjamin|tiada semakan kredit|no credit check|(approved|lulus) (in|dalam) \d+ (minutes?|minit|hours?|jam)|fast cash loan|\b(job|kerja|jawatan|position|work permit|visa)\b.{0,80}\b(fee|yuran)\b|\b(fee|yuran)\b.{0,60}\b(job|kerja|jawatan|work permit|visa)\b)/u',
                'points' => 20,
                'label' => 'Too-good-to-be-true offer (easy money, guaranteed returns, an instant loan, or a job that needs a fee)',
                'lure' => true,
            ],
            'prize_scam' => [
                'regex' => '/(you have won|you\'ve won|you could win|you have been chosen|you\'ve been chosen|chosen to (receive|participate)|congratulations.{0,40}(won|selected|chosen)|claim your (prize|reward|gift|refund|free|share|tokens?)|free (gift|rewards?)|weekly lottery|survey zone|redeem (your|for) |tahniah.{0,40}(menang|dipilih|terpilih)|anda (telah )?memenangi|tuntut hadiah|pontos acumulados|resgate (agora|pendente)|resgatar|b[oô]nus de .{0,20}pontos)/u',
                'message_regex' => '/(you have won|you\'ve won|you could win|you have been chosen|you\'ve been chosen|chosen to (receive|participate)|congratulations.{0,40}(won|selected|chosen)|claim your (prize|reward|gift|refund|free|share|tokens?)|free (gift|rewards?|tickets?)|weekly lottery|survey zone|redeem (your|for) |lucky draw|cabutan bertuah|giving away|giveaway|lucky you|selected as the winner|winner of|tahniah.{0,40}(menang|dipilih|terpilih|layak)|anda (telah )?memenangi|\b(menang|memenangi|pemenang)\b|layak menerima|eligible to (receive|withdraw|claim)|bantuan (kerajaan|tunai|khas)|tuntut (hadiah|bantuan|wang|tunai)|can claim|claim (it|now|bnd|\$)|pontos acumulados|resgate (agora|pendente)|resgatar|b[oô]nus de .{0,20}pontos)/u',
                'points' => 20,
                'label' => 'Prize/reward language detected (e.g. "you have won", "claim your reward", "tahniah anda menang", "cabutan bertuah")',
                'lure' => true,
            ],
            // Free in-game items, top-up bonuses and fake ban warnings. Real publishers do run
            // giveaways, so this is a weak lure: on its own it stays below the "suspicious" line,
            // and it only counts for more next to a link to a site that is not the publisher's own
            // (see checkLinkInPressureMessage) or a request for a login, code or payment.
            'gaming_lure' => [
                'regex' => '/((robux|v-?bucks|vbucks|diamonds?|uc|primogems?|gems|coins|skins?(?!\s?care)|nitro|steam (wallet|gift)( cards?)?) (generator|hack|cheat|glitch|exploit)|(generator|hack tool|cheat tool) for (roblox|fortnite|free fire|pubg|mobile legends|mlbb|genshin|minecraft|steam)|(claim|get) (your )?free(?! fire\b) (robux|v-?bucks|vbucks|diamonds?|nitro|steam (wallet|gift)))/',
                'message_regex' => '/(\bfree(?! fire\b)\s+(\S+\s+){0,2}?(diamonds?|skins?(?!\s?care)|uc|robux|v-?bucks|vbucks|primogems?|genesis crystals?|gems?|coins?|nitro|battle ?pass|elite pass|redeem codes?|steam (wallet|gift|codes?)|gift ?cards?)\b|\b(diamonds?|skins?(?!\s?care)|uc|robux|v-?bucks|vbucks|primogems?|gems?|coins?|nitro|battle ?pass|elite pass|redeem codes?)\s+(\S+\s+){0,2}?(percuma|gratis|free)\b|\b(percuma|gratis)\s+(\S+\s+){0,2}?(diamonds?|skins?(?!\s?care)|uc|robux|v-?bucks|vbucks|primogems?|gems?|coins?|nitro|battle ?pass|elite pass|redeem codes?|gift ?cards?)\b|(robux|v-?bucks|vbucks|diamonds?|uc|primogems?|gems|coins|skins?(?!\s?care)|nitro|steam (wallet|gift)( cards?)?) (generator|hack|cheat|glitch|exploit)|(generator|hack tool|cheat tool) for (roblox|fortnite|free fire|pubg|mobile legends|mlbb|genshin|minecraft|steam)|(top ?up|topup) (murah|cheap|diskaun|discount|promo)|(murah|cheap|diskaun) (top ?up|topup)|(bonus|extra) (diamonds?|uc|robux|v-?bucks) (when|bila|jika|if)|gift ?cards? (generator|giveaway)|(account|akaun)(\s(game|anda|kamu|awak)){0,2} (will be|has been|is|akan|telah|sudah|kena|kana|dah) (permanently )?(banned|di-?ban|diban|dibanned)|(game|roblox|steam|discord|mlbb|free fire|pubg|valorant|genshin|minecraft) (account|akaun)(\s(anda|kamu|awak))? (will be|has been|is|akan|telah|sudah|kena|kana|dah) (permanently )?(suspended|terminated|digantung|disekat)|(account|akaun) game(\s(anda|kamu|awak))? (will be|has been|is|akan|telah|sudah|kena|kana|dah) (permanently )?(suspended|terminated|digantung|disekat)|(kena|kana) (ban|banned)\b|(trade offer|tawaran (tukar|pertukaran))|(log ?in|login|masuk) (with|using|via|guna|dengan) (steam|discord|roblox|facebook|google).{0,60}(claim|reward|free|percuma|dapat|tuntut)|(connect|link|sambung|pautkan) (your |akaun )?(steam|discord|roblox|mlbb|free fire)( account| akaun)?.{0,40}(claim|reward|free|percuma|dapat|tuntut|verify|sahkan)|(dapat|dapatkan|bagi|bagi-bagi) (free|percuma|diamond|skin|robux|uc)\b)/u',
                'points' => 15,
                'label' => 'Game giveaway, top-up or ban-warning lure detected (e.g. "free Robux", "diamond percuma", "your account will be banned")',
                'lure' => true,
                'skip_on_warning_page' => true,
            ],
            // More scam types. Each is a weak lure (15 to 25 points) on purpose: real people do ask for
            // deposits, send money by mistake, install remote-support tools and talk about investing, so
            // on its own none of these is more than a warning sign. They count for more together with a
            // request for money or a code, or with a link to a site that is not the sender's own.
            'marketplace_scam' => [
                'regex' => '/((seller|penjual)\s+(\S+\s+){0,6}?(insurance|insurans)\s+(fee|yuran)\s+(\S+\s+){0,10}?(receive|release|terima|keluarkan))/u',
                'message_regex' => '/((deposit|booking fee|down ?payment|bayar (deposit|booking|cagaran)|tempahan)\s+(\S+\s+){0,4}?(to |untuk )?(hold|reserve|secure|simpan|tahan|book)\b|\b(hold|reserve|simpan|tahan)\s+(\S+\s+){0,3}?(the |this |item|barang|unit)\s*(\S+\s+){0,4}?(deposit|transfer|bayar)|(seller|penjual|buyer|pembeli)\s+(\S+\s+){0,6}?(insurance|insurans|protection|escrow|verification|pengesahan)\s+(fee|yuran|charge|caj)|(my|our) (courier|agent|driver)\s+(\S+\s+){0,4}?(collect|pick ?up|ambil)\s+(\S+\s+){0,10}?(pay|bayar|fee|yuran|transfer)|\bcod\s+(\S+\s+){0,4}?(fee|yuran|deposit))/u',
                'points' => 15,
                'label' => 'Buying-and-selling scam wording detected (a deposit to "hold" an item, a fee the seller must pay first, or a courier you must pay)',
                'lure' => true,
                'skip_on_warning_page' => true,
            ],
            'romance_scam' => [
                'message_regex' => '/((i|im|i.?m)\s+(a |an )?(us |uk |american |british )?(army|soldier|marine|navy|peacekeeper|widow|widower|oil rig (engineer|worker))\b.{0,80}(overseas|deployed|abroad|syria|afghanistan|offshore|mission)|(i love you|i miss you|my love|my darling|sweetheart|sayang)\b.{0,200}(gift ?cards?|western union|moneygram|customs|airport|stranded|ticket to (see|visit|meet)|bitcoin|crypto|pelaburan|invest))/u',
                'points' => 15,
                'label' => 'Romance-scam wording detected (an affectionate stranger who brings up gift cards, money, customs or an "investment")',
                'lure' => true,
            ],
            'rental_scam' => [
                'regex' => '/((owner|landlord|tuan rumah|pemilik)\s+(\S+\s+){0,6}?(abroad|overseas|luar negara|out of (the )?country)\b.{0,160}(key|kunci)\s+(\S+\s+){0,6}?(courier|pos|hantar|post|mail)|(deposit|bayar deposit)\s+(\S+\s+){0,6}?(before|sebelum)\s+(\S+\s+){0,4}?(view|viewing|tengok|lihat)\b.{0,200}(whatsapp|telegram))/u',
                'message_regex' => '/((deposit|booking|bayar deposit|bayar booking)\s+(\S+\s+){0,6}?(before|sebelum|dulu|first)\s+(\S+\s+){0,4}?(view|viewing|tengok|lihat|visit|key|kunci|pemeriksaan)|(owner|landlord|tuan rumah|pemilik)\s+(\S+\s+){0,6}?(abroad|overseas|luar negara|outside (the )?country|out of (the )?country)\b.{0,160}(key|kunci|deposit|transfer|courier)|(key|kunci)\s+(\S+\s+){0,6}?(courier|pos|hantar|post|mail)\b.{0,100}(deposit|transfer|bayar))/u',
                'points' => 15,
                'label' => 'Rental-scam wording detected (a deposit before viewing, or an owner who is "abroad" and sends the key by courier)',
                'lure' => true,
                'skip_on_warning_page' => true,
            ],
            'money_mule' => [
                'message_regex' => '/((rent|lend|sewa|pinjam|pinjamkan)\s+(\S+\s+){0,3}?(your |akaun |kad )?(bank )?(account|akaun|atm card|kad atm|debit card)\b.{0,120}(commission|komisen|fee|upah|per (day|month|transaction)|sehari|sebulan)|(commission|komisen|upah|fee)\b.{0,80}(rent|lend|sewa|pinjam|pinjamkan)\s+(\S+\s+){0,3}?(your |akaun )?(bank )?(account|akaun|atm card|kad atm)\b|\bpinjam akaun\b|money mule|(receive|terima)\s+(\S+\s+){0,3}?(money|payments?|transfers?|duit|wang)\s+(\S+\s+){0,6}?(forward|transfer|hantar|pindah|pass|serahkan)\s+(\S+\s+){0,8}?(commission|komisen|keep \d+%|\d+% ))/u',
                'points' => 25,
                'label' => 'Asks you to rent out or lend your bank account for a fee (money-mule recruitment: you could be charged for the crime)',
                'lure' => true,
            ],
            'family_emergency' => [
                'message_regex' => '/((accident|kemalangan|arrested|ditangkap|kena tangkap|kidnap|diculik|lockup|balai polis|police station|in custody)\b.{0,200}(send|transfer|pay|bayar|hantar|pindah)\s+(\S+\s+){0,4}?(money|duit|wang|bnd|rm ?\d|\$|bail|ikat jamin)|(your\s+(\S+\s+){0,2}?(son|daughter|child|husband|wife|mum|mom|dad)|anak awak|mak awak|ayah awak|suami awak|isteri awak)\s+(\S+\s+){0,3}?(had|got|was in|is in|kena|terlibat|ditahan|ditangkap|tangkap|diculik|arrested)\b.{0,200}(send|transfer|pay|bayar|hantar|pindah))/u',
                'points' => 20,
                'label' => 'Family-emergency wording detected (an accident, arrest or kidnap together with a request to send money now)',
                'lure' => true,
            ],
            'remote_access' => [
                'message_regex' => '/((install|download|muat turun|pasang|open|buka|launch|run|give|beri|bagi|share|kongsi|send|hantar)\s+(\S+\s+){0,4}?(anydesk|any desk|teamviewer|team viewer|ultraviewer|rustdesk|quick ?support|supremo|aweray|remote (access|support|desktop|control) (app|software|tool))|(anydesk|teamviewer|ultraviewer|rustdesk)\s+(\S+\s+){0,5}?(code|id|kod|digit))/u',
                'points' => 20,
                'label' => 'Asks you to install a remote-control app (AnyDesk, TeamViewer and similar), which gives a stranger full control of your device',
                'lure' => true,
            ],
            'tech_support' => [
                'regex' => '/(your (pc|computer|laptop|device|windows|system|phone) (is|has been|was|may be|might be) (infected|hacked|compromised|at risk|blocked|locked)|(virus|malware|trojan|spyware)s? (has been |have been |was |were )?(detected|found|alert)|(call|contact) (microsoft|apple|windows|google) (support|technician|helpline|security)|(microsoft|windows|apple) (security|support) (alert|warning|team)|do not (shut ?down|restart|close) (this|your)( computer| pc| device| browser| window)?)/',
                'message_regex' => '/(your (pc|computer|laptop|device|windows|system|phone) (is|has been|was|may be|might be) (infected|hacked|compromised|at risk|blocked|locked)|(virus|malware|trojan|spyware)s? (has been |have been |was |were )?(detected|found|alert)|(call|contact) (microsoft|apple|windows|google) (support|technician|helpline|security)|(microsoft|windows|apple) (security|support) (alert|warning|team)|do not (shut ?down|restart|close) (this|your)( computer| pc| device| browser| window)?)/',
                'points' => 15,
                'label' => 'Fake security-alert wording detected (a "virus found" or "your computer is infected" warning that tells you to call a number)',
                'lure' => true,
                'skip_on_warning_page' => true,
            ],
            'wrong_transfer' => [
                'message_regex' => '/((by mistake|mistakenly|accidentally|tersalah|salah transfer|silap transfer|salah hantar)\b.{0,120}(your account|ur account|akaun (awak|anda|kamu)|to you|kepada (awak|anda)).{0,160}(return|refund|send (it )?back|transfer (it )?back|pulangkan|kembalikan|bagi balik|hantar balik|transfer balik)|(sent|transferred|hantar|transfer)\s+(\S+\s+){0,8}?(to (your|ur) account|ke akaun (awak|anda|kamu))\s+(\S+\s+){0,3}?(by mistake|mistakenly|accidentally|tersalah|silap)\b.{0,120}(return|send (it )?back|transfer (it )?back|refund|pulang|kembali|balik)|(return|refund|send back|transfer back|pulangkan|kembalikan|bagi balik|hantar balik|transfer balik)\b.{0,100}(by mistake|mistakenly|accidentally|tersalah|salah transfer|silap))/u',
                'points' => 15,
                'label' => 'Wrong-transfer wording detected ("I sent money to you by mistake, please send it back"). This can be real, so check with your own bank before sending anything',
                'lure' => true,
            ],
            'malicious_app' => [
                'message_regex' => '/(\.apk\b|\bapk\b|unknown sources|sumber tidak diketahui|allow (the )?install(ation)? from|benarkan (pemasangan|pasang)|install (it )?from outside (the )?(google )?play ?store)/',
                'points' => 25,
                'label' => 'Mentions an APK file or installing from "unknown sources" (a common way to install a banking or spying app outside the app store)',
                'lure' => true,
            ],
            'booking_scam' => [
                'message_regex' => '/((hotel|booking|reservation|tempahan|airbnb|flight|tiket|ticket|homestay|resort)\b.{0,160}(pay|bayar|transfer|deposit)\s+(\S+\s+){0,6}?(outside|luar|whatsapp|another account|akaun lain|gift ?card|crypto|bitcoin|western union)|(pay|bayar|transfer)\s+(\S+\s+){0,6}?(outside|luar) (the )?(platform|app|aplikasi|airbnb|booking\.com|booking)\b)/u',
                'points' => 15,
                'label' => 'Booking-scam wording detected (asks you to pay for a hotel, flight or stay outside the booking platform, by gift card or crypto)',
                'lure' => true,
            ],
            'investment_con' => [
                'regex' => '/((pay|bayar)\s+(\S+\s+){0,3}?(tax|cukai|fee|yuran|deposit)\s+(\S+\s+){0,4}?(to|untuk)\s+(withdraw|unlock|keluarkan)|\b\d{1,3}(\.\d+)?%\s+(daily|per day|sehari|setiap hari)\b)/u',
                'message_regex' => '/((forex|crypto|trading|saham|stock|binary options?)\s+(\S+\s+){0,3}?(mentor|signals?|vip (group|room|channel)|analyst|robot|\bbot\b|copy ?trad)|\b\d{1,3}(\.\d+)?%\s+(daily|weekly|monthly|per day|per week|per month|sehari|seminggu|sebulan|setiap hari)|(daily|weekly|monthly|sehari|seminggu|sebulan|harian|mingguan)\s+(profit|returns?|income|pulangan|untung|keuntungan)\s+(of\s+)?\d{1,3}(\.\d+)?%|(trading|investment|pelaburan)\s+(platform|app|aplikasi|website|laman)\W+(\S+\s+){0,10}?(withdraw|pengeluaran|tax|cukai|unlock|vip level|upgrade))/u',
                'points' => 15,
                'label' => 'Investment-scam wording detected (a trading "mentor" or signals group, fixed daily returns, or a platform that charges tax or fees to withdraw)',
                'lure' => true,
                'skip_on_warning_page' => true,
            ],
            'insurance_scam' => [
                'message_regex' => '/((insurance|insurans|takaful|polisi|policy)\s+(\S+\s+){0,4}?(has lapsed|have lapsed|will lapse|will be (cancelled|terminated)|akan (dibatalkan|ditamatkan)|telah dibatalkan|has been cancelled)\b.{0,160}(click|klik|pay|bayar|renew|perbaharui|link|pautan|http))/u',
                'points' => 15,
                'label' => 'Insurance-cancellation wording detected (a policy "has lapsed" or "will be cancelled" with a link to pay or renew)',
                'lure' => true,
            ],
            'boss_fraud' => [
                'message_regex' => '/((boss|bos|ceo|director|pengarah|manager|pengurus|supervisor|ketua|chairman|md)\b.{0,100}(gift ?cards?|vouchers?|itunes|google play card|steam card|transfer|pay|bayar|hantar)\s+(\S+\s+){0,6}?(urgent|segera|now|quickly|cepat|today|hari ini|confidential|rahsia)|(are you (available|free|at your desk)|awak ada|kamu ada)\s*(\S+\s+){0,3}?(i need|saya perlu|aku nak|need a favou?r|nak minta tolong)\s+(\S+\s+){0,8}?(gift ?cards?|vouchers?|transfer|pay|bayar))/u',
                'points' => 20,
                'label' => 'Boss or manager impersonation wording detected (a "quick favour" asking for gift cards or an urgent transfer)',
                'lure' => true,
            ],
            'crypto_scam' => [
                'regex' => '/(airdrop|token allocation|free mint|erc-?20|ethereum 2\.0|claim your (xrp|usdt|eth|btc|nft)|usdt|nft cashback|bitcoin (payment|accounts?)|crypto(currency)? (giveaway|reward))/',
                'points' => 25,
                'label' => 'Cryptocurrency giveaway or wallet-claim language detected (a common scam lure)',
                'lure' => true,
            ],
            'advance_fee' => [
                'regex' => '/(dear friend\b(?!s)|god bless you|next of kin|inheritance|you have a donation|donation of \$|contacting you for the second time|reply urgently|powerball|meu caro amigo)/',
                'points' => 25,
                'label' => 'Advance-fee / "dear friend" scam language detected',
                'lure' => true,
            ],
            'fine_lure' => [
                'regex' => '/(traffic (violation|fine|ticket|challan|record)|red[- ]light|e-?challan|challan|speeding (fine|ticket)|parking (fine|ticket)|unpaid (toll|fine|ticket)|toll (fee|charge)|outstanding (fine|penalty)|saman (trafik|jalan raya|tertunggak|anda)|\bkompaun\b|compound (fine|notice))/',
                'message_regex' => '/(traffic (violation|fine|ticket|challan|record)|red[- ]light|e-?challan|challan|speeding (fine|ticket)|parking (fine|ticket)|unpaid (toll|fine|ticket)|toll (fee|charge)|outstanding (fine|penalty)|court summons|saman (trafik|jalan raya|tertunggak|anda|mahkamah)|saman (belum|tidak) (bayar|dibayar)|ada saman|\bkompaun\b|compound (fine|notice)|bil (elektrik|air|api)( anda| kamu)? (tertunggak|belum)|tunggakan bil)/',
                'points' => 20,
                'label' => 'Traffic-fine / penalty language detected (a common SMS scam: "violation", "e-challan", "unpaid toll", "saman")',
                'lure' => true,
            ],
            'authority_threat' => [
                'regex' => '/(notice of involvement|notis (penglibatan|siasatan)|royal brunei police|polis diraja brunei|interpol|waran (tangkap|geledah)|arrest warrant|section 420|seksyen 420|keep (this|the) matter confidential|rahsiakan (perkara|hal) ini)/',
                'message_regex' => '/(notice of involvement|notis (penglibatan|siasatan)|interpol|waran (tangkap|geledah)|arrest warrant|section 420|seksyen 420|money laundering|pengubahan wang haram|kes dadah|drug case|narcotics|narkotik|illegal items|barang (terlarang|haram)|being investigated|sedang disiasat|clear your name|safe account|akaun selamat|frozen by (bdcb|the police))/',
                'points' => 20,
                'label' => 'Authority-impersonation language detected (police/Interpol/investigation notice, "safe account")',
                'lure' => true,
            ],
            'customs_fee' => [
                'regex' => '/((customs|kastam).{0,30}(fee|duty|charge|clearance|caj|duti|yuran)|(caj|yuran|bayaran) (kastam|penghantaran|pelepasan)|redelivery|delivery (attempt )?failed|will be sent back|taxa de recolhimento|alf[aâ]ndega)/u',
                'message_regex' => '/((customs|kastam).{0,30}(fee|duty|charge|clearance|caj|duti|yuran)|(caj|yuran|bayaran) (kastam|penghantaran|pelepasan)|duti kastam|tahan kastam|ditahan (di )?kastam|stuck at customs|held at customs|redelivery|delivery (attempt )?failed|will be sent back|taxa de recolhimento|alf[aâ]ndega)/u',
                'points' => 15,
                'label' => 'Customs/delivery-fee language detected (a common parcel scam)',
                'lure' => true,
            ],
            'channel_move' => [
                'message_regex' => '/((whatsapp|telegram|wechat|pm|dm)\s+(us|me|kami|saya|ku|aku)\b|contact (our|the|my) (agent|officer|manager|admin)|hubungi (ejen|pegawai|agen|admin)|(join|sertai) (our |the |kumpulan )?(telegram|whatsapp)|telegram (group|channel)|kumpulan (telegram|whatsapp)|(di|on|via|through|melalui) (telegram|whatsapp|wechat)\b|call (the )?(bdcb |bank |police )?(officer|inspector|pegawai))/u',
                'points' => 10,
                'label' => 'Pushes you to a private chat or to contact an "agent" or "officer"',
                'ask' => true,
                'weak' => true,
            ],
            'call_to_action' => [
                'regex' => '/(click here|click the button|click below|follow the link|klik (di ?sini|pautan|link|butang)|ikut pautan)/',
                'message_regex' => '/(click here|click the button|click below|follow the link|click (the |this )?link|tap (the |this )?link|klik (di ?sini|sini|pautan|link|butang)|tekan (link|pautan|sini)|ikut pautan|re-?activate now|apply now|register now|daftar sekarang|join (now|sekarang)|sign up now|secure your account|batalkan di)/',
                'points' => 10,
                'label' => 'Suspicious call-to-action phrasing detected (click/follow link)',
                'ask' => true,
                'weak' => true,
            ],
        ];

        // On a web page only the original wording counts ('regex'): the message-only additions
        // ('message_regex', e.g. "apply now", "giveaway", "segera") are everyday words on real
        // shop, bank and job sites and would add false alarms to link scans.
        // English wording found missing on 230 real phishing emails (phishing_pot) and checked
        // against 500 ordinary ones (SpamAssassin). Messages only, like 'message_regex'.
        $extra = [
            'urgency' => '/\b(as soon as possible|final (warning|reminder|call)|last (warning|reminder|chance)|time is (running out|ticking)|expires? (today|soon|tonight)|before it.s too late|respond by|avoid (suspension|deletion|deactivation|interruption|loss))\b/u',
            'account_threat' => '/((wallet|assets?|account|access|card|storage|mailbox|service)\s+(\S+\s+){0,3}?(has|have|will) been (temporarily |permanently )?(suspended|locked|blocked|frozen|restricted|disabled|terminated)|will (lose|be locked out of) (all )?(your )?(assets|funds|access|data|files|photos)|lose (all )?your (assets|funds|data|files|photos)|(storage|mailbox|inbox)\s+(\S+\s+){0,2}?(is|are) (\d+% )?(full|almost full|over.?limit)|exceeded your (storage|quota|plan)|(scheduled|marked) for (permanent )?(deletion|removal)|will be (permanently )?(deleted|removed|erased|lost|locked)|data (loss|deletion) warning|password (has )?expired|password (expires|is due to expire)|keep (using )?(the |your )?(same|current) password|inactive account|period (of|without) (in)?activity|confirm (your )?continued use|(payment|billing|renewal)\s+(\S+\s+){0,2}?(failed|declined|could not be (processed|completed))|unable to (process|charge|validate|verify) your (payment|card|account|billing)|could(n.t| not) charge|problem with your (billing|payment)|payment (issue|problem)|update (your )?(payment|billing) (method|details|information)|someone (tried|attempted) to (log|sign) ?in|(new|recent|unusual) (sign|log).?in (attempt|activity))/u',
            'credential_request' => '/(complete (your )?verification|(has|have) not (yet )?been verified|not (yet )?verified|unverified (account|wallet)s?|\bkyc\b|verify (your )?(recovery phrase|seed|wallet|address|account ownership)|(log|sign) ?in to (review|view|access|confirm)|confirm (your )?(address|identity|account|details|information)|reconfirm your (home )?address|update (your )?(account|personal) (information|details)|reset (your )?(pin|password)s?\b)/u',
            'prize_scam' => '/(you.ve been selected|you have been selected|been selected (as|to|for)|selected (as )?one of our|valued winners?|lucky (winner|one)|exclusive reward|loyalty (program|reward)|(short|quick) survey|take (this|a|our) survey|complimentary (gift|reward)|free trial offer|reward (is )?(waiting|ready|awaiting)|claim (your|the) (gift|bonus|reward|prize)|(donated|donating) \$?[\d.,]+|(selected|chosen) to receive)/u',
            'advance_fee' => '/(\bbeneficiary\b|compensation fund|scam victims?|victims? compensation|consignment (box )?of (funds|money)|your consignment|diplomatic agent|abandoned (fund|check|cheque|funds)|unclaimed (balance|fund|funds|holdings)|unreviewed (credits|holdings)|lucrative (deal|business|offer)|business (proposal|dealing)|investment proposal|private client|fund manager|late client|foreign remittance|atm card (delivery|worth|of \$)|your atm card|atm delivery|bank draft|draft worth|charity.{0,20}foundation|(\$\s?[\d.,]+\s?(million|m)\b|[\d.,]+ million (us )?(usd|dollars))[^.]{0,80}(donat|compensat|inherit|claim|beneficiar|lotter|winning|transfer|fund))/u',
            'crypto_scam' => '/(\bstaking\b|stake your|token (redistribution|distribution|reward)|claim (your )?[\d,]* ?(tokens?|btc|eth|xrp|sol|uni|op)\b|cloud mining|mining platform|upgrade your (wallet|ledger|device)|wallet (connected|upgrade)|withdraw your (\+?\$|btc|funds|assets|earnings)|bitcoin portfolio)/u',
            'customs_fee' => '/(package (is )?pending|(parcel|package|shipment) (is )?(pending|on hold|waiting)|pending (for )?delivery|schedule (your )?delivery|delivery (fee|charge|payment)|confirm the payment of|shipping fee|(returned|sent back) to (the )?sender)/u',
            'call_to_action' => '/(click (on )?(the )?(button|link)( below)?|(button|link) below|get started( now)?|confirm (now|here|below)|claim (it )?now|update now|renew now|review (the )?documents?|(sign|log) ?in (now|here|below)|keep (current|same) password|download (the )?(secure )?(attachment|document|invoice|file)|upgrade (your )?(storage|plan) now|renew (your )?(protection|subscription|plan)|restore (your )?(access|storage|account|protection)|reactivate (your )?(account|subscription))/u',
            'identity_claim' => '/(director of|head of (the )?(foreign|remittance|operations)|on behalf of (mr|mrs|ms|dr)\b|(security|support|billing|account) (team|department|center|centre)|customer (support|service) (team|department))/u',
        ];
        $found = [];
        // A page that is about scams (a news article, a bank's warning, a "how to spot a scam" guide)
        // quotes the scam wording, and unlike a message it is not cleaned of warnings. Rules marked
        // 'skip_on_warning_page' are therefore skipped on a page that reads like a warning.
        $isWarningPage = $forPage && (bool) preg_match('/(beware of|be careful (of|with)|scam alert|scam warning|scam news|how to (spot|avoid|protect|recognise|recognize|identify)|tips to (avoid|spot)|protect yourself from|awareness|waspada|berhati-hati|amaran penipuan|cara (mengenali|mengelak))/u', $text);
        foreach ($patterns as $key => $pattern) {
            if ($isWarningPage && ($pattern['skip_on_warning_page'] ?? false)) {
                continue;
            }
            $regex = $forPage ? ($pattern['regex'] ?? null) : ($pattern['message_regex'] ?? $pattern['regex']);
            if (($regex !== null && preg_match($regex, $text)) || (! $forPage && isset($extra[$key]) && preg_match($extra[$key], $text))) {
                $found[$key] = $pattern;
            }
        }
        if (! $forPage) {
            // Callback scam: a "purchase" or "renewal" you never made, a phone number to ring,
            // and a reason to call ("if you did not authorise this", "to cancel").
            if (preg_match('/\b(order|purchase|renewal|subscription|transaction|invoice|statement)\b.{0,200}?\b(confirm|approv|processed|paid|complete|renew|charged|placed|active)/su', $text)
                && preg_match('/(\+?1[\s.-]?)?\(?\d{3}\)?[\s.-]\d{3}[\s.-]\d{4}/', $text)
                && preg_match('/(did ?n.?t|did not|not you|not authori[sz]e|cancel|dispute|refund|unauthori[sz]ed)/u', $text)) {
                $found['callback_scam'] = ['points' => 25, 'label' => 'A purchase or renewal you did not make, with a phone number to call (a common "call us to cancel" scam)', 'lure' => true];
            }
            if (preg_match('/(your (pc|device|computer|phone) (has been|was) (hacked|infected|compromised)|i (have )?recorded you|(compromising|intimate|private) (video|photos)|(send|pay|transfer) .{0,40}(bitcoin|btc)|(bitcoin|btc) (address|wallet):)/u', $text)) {
                $found['extortion'] = ['points' => 25, 'label' => 'Threat or blackmail asking for payment (a common "your device was hacked, pay in Bitcoin" scam)', 'lure' => true];
            }
            if ($disguised) {
                $found['disguised'] = ['points' => 20, 'label' => 'Text is disguised with look-alike letters, hidden characters or spaced-out letters (a trick to get past spam filters)', 'lure' => true];
            }
        }
        if ($secrecy && ! $forPage) {
            $found['secrecy'] = ['points' => 15, 'label' => 'Asks you to keep it secret (scammers say this so no one can warn you)', 'lure' => true];
        }

        foreach ($found as $key => $pattern) {
            $reasons[] = $pattern['label'];
            $points += $pattern['points'];
            $matchedCategories++;
            if (in_array($key, self::EVERYDAY_WORDING, true)) {
                $everydayCategories++;
            } else {
                $specificCategories++;
            }
        }

        // Hurry-up wording and a "click here" on their own are everyday newsletter style; without a
        // request, a fake identity or a lure they are not enough to call a message suspicious.
        if ($found !== [] && array_diff(array_keys($found), ['urgency', 'call_to_action']) === []) {
            $points = min($points, 20);
        }

        // Ordinary wording alone, however many tactics it uses, stays below the "suspicious" line. It still adds to
        // a score next to other evidence (a bad link, a look-alike sender, failed sender checks).
        if (! $forPage && $found !== [] && $specificCategories === 0) {
            $points = min($points, self::LONE_SIGNAL_CEILING);
        }

        // Bonuses for tactics piling up only apply when at least one is scam wording; a password reset or an
        // invoice reminder uses urgency, a verify request and a link without being a scam.
        if ($matchedCategories >= 3 && $specificCategories >= 1) {
            $reasons[] = "{$matchedCategories} distinct phishing behavior patterns found together — a coordinated social-engineering pattern, not an isolated keyword";
            $points += 15;
        }

        // The core of almost every scam: a request (money, a code, your details, or a move to a
        // private chat) together with a pressure tactic (a fake identity, a threat, a prize, an
        // easy-money offer). Either one alone is common in normal messages; together they are not.
        $strongAsk = array_filter($found, fn ($p) => ($p['ask'] ?? false) && ! ($p['weak'] ?? false));
        $weakAsk = array_filter($found, fn ($p) => $p['weak'] ?? false);
        $lures = array_filter($found, fn ($p) => $p['lure'] ?? false);
        $strongLures = array_diff_key($lures, ['urgency' => true]);
        if ($specificCategories >= 1 && (($strongAsk && $lures) || ($weakAsk && $strongLures))) {
            $reasons[] = 'Asks you to act (pay, share a code or details, or contact someone) while using a pressure tactic: the core pattern of a scam';
            $points += 15;
        }

        return [
            'flagged' => $points > 0,
            'points' => min(90, $points),
            'reasons' => $reasons,
            'matched_categories' => $matchedCategories,
            'specific_categories' => $specificCategories,
            'everyday_categories' => $everydayCategories,
        ];
    }

    /**
     * How many kinds of evidence the wording is worth. Wording that real companies use every day (urgency,
     * an account notice, a request to verify, an invoice, "click here") counts as one kind however many of
     * those tactics a message uses (they never corroborate each other); each scam-specific tactic (a prize, a code request, a fee, a fake
     * officer) counts as its own kind.
     */
    private function wordingKinds(array $content): int
    {
        $everyday = (int) ($content['everyday_categories'] ?? 0);

        $specific = (int) ($content['specific_categories'] ?? 0);
        $kinds = $specific + ($everyday > 0 ? 1 : 0);

        // A scam-specific tactic that scores 25 or more (a code request, a fake "call to cancel" order, a
        // crypto or advance-fee lure) is clear enough to stand on its own.
        if ($specific >= 1 && (int) ($content['points'] ?? 0) >= 25) {
            $kinds++;
        }

        return max(1, $kinds);
    }

    /**
     * Undoes the tricks phishing uses to slip past filters, and reports whether any were used:
     * invisible characters inside words ("A­m­a­z­o­n"), Cyrillic look-alike letters mixed into
     * Latin words ("Yоur wаllet") and words spelt out letter by letter ("D e a r  c u s t o m e r").
     *
     * @return array{0: string, 1: bool} the cleaned text and whether it was disguised
     */
    private function unmaskText(string $text): array
    {
        $hidden = '\x{00AD}\x{034F}\x{200B}-\x{200D}\x{2060}\x{FEFF}\x{FE00}-\x{FE0F}\x{E0100}-\x{E01EF}';
        $disguised = (bool) preg_match('/\p{L}['.$hidden.']+\p{L}/u', $text)
            || (bool) preg_match('/(?=\w*\p{Latin})(?=\w*\p{Cyrillic})\b\w+\b/u', $text);

        $text = preg_replace('/['.$hidden.']/u', '', $text) ?? $text;
        $text = strtr($text, ['а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p', 'с' => 'c', 'у' => 'y', 'х' => 'x', 'і' => 'i', 'ј' => 'j', 'ѕ' => 's', 'ԁ' => 'd', 'ӏ' => 'l', 'ո' => 'n', 'ս' => 'u', 'ο' => 'o', 'α' => 'a']);

        $spaced = preg_replace_callback('/(?<!\S)(?:\p{L} ){7,}\p{L}(?!\S)/u', fn ($m) => str_replace(' ', '', $m[0]), $text);
        if ($spaced !== null && $spaced !== $text) {
            $disguised = true;
            $text = $spaced;
        }

        return [$text, $disguised];
    }

    /**
     * Reduces a word to a form where look-alike characters compare equal:
     * digits become the letters they imitate, "i" and "l" are treated as the
     * same, "rn" reads as "m" and "vv" as "w".
     */
    private function foldLookalikes(string $word): string
    {
        $word = strtolower($this->normalizeForBrandMatch($word));
        $word = str_replace(['rn', 'vv'], ['m', 'w'], $word);

        return str_replace(['i', '|'], 'l', $word);
    }

    private function detectBrandInText(string $text): array
    {
        // The names come from config/brands.php (text_brands).
        $knownBrands = config('brands.text_brands', []);
        $knownBrands = array_merge($knownBrands, ['standard chartered'], array_keys($this->bruneiBrands()));

        $lowerText = strtolower($text);

        // Brunei names first: a local scam often also mentions WhatsApp or Google ("contact our
        // agent on WhatsApp"), and the local bank or agency is the one being impersonated.
        foreach ([...array_keys($this->bruneiBrands()), ...$this->bruneiGovNames()] as $brand) {
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/', $lowerText)) {
                return ['brand' => $brand, 'surface' => $brand];
            }
        }
        foreach (array_keys($this->bruneiAcronyms()) as $acronym) {
            if (preg_match('/\b'.$acronym.'\b/', $text)) {
                return ['brand' => strtolower($acronym), 'surface' => strtolower($acronym)];
            }
        }

        // Exact match first — cheapest and most reliable when the brand name
        // is spelled correctly in the message.
        foreach ($knownBrands as $brand) {
            // "ups" is an everyday word ("follow-ups", "ups and downs"); only the
            // courier's written form, capitalised UPS, counts.
            if ($brand === 'ups') {
                if (preg_match('/\bUPS\b/', $text)) {
                    return ['brand' => $brand, 'surface' => $brand];
                }

                continue;
            }
            if (preg_match('/\b'.preg_quote($brand, '/').'\b/', $lowerText)) {
                return ['brand' => $brand, 'surface' => $brand];
            }
        }

        // Lookalike fallback — catches a VISIBLE brand name that is itself a
        // typosquat (e.g. "DHI" for "DHL", "Paypa1", "Arnazon"). It only
        // accepts look-alike characters (1/l/i, 0/o, 3/e, 5/s, rn/m, vv/w),
        // never general similarity: an earlier similarity rule matched
        // ordinary words such as "up" (UPS), "apply" (Apple), "people",
        // "again", "image" and "build", and flagged normal emails.
        $foldBrand = fn (string $w) => $this->foldLookalikes($w);
        preg_match_all('/\b[A-Za-z0-9]{2,12}\b/', $text, $m);
        $words = array_unique(array_map('strtolower', $m[0] ?? []));

        foreach ($words as $word) {
            foreach ($knownBrands as $brand) {
                if ($word === $brand || str_contains($brand, ' ')) {
                    continue;
                }
                if ($foldBrand($word) === $foldBrand($brand)) {
                    return ['brand' => $brand, 'surface' => $word];
                }
            }
        }

        $bestBrand = null;
        $bestSurface = null;

        return ['brand' => $bestBrand, 'surface' => $bestSurface];
    }

    /**
     * Whether a message presents itself AS the brand it names, rather than just mentioning it.
     * Phishing puts the brand up front ("Microsoft account: unusual sign-in", "Netflix - payment
     * failed") and pushes the reader; a normal email that only talks about Microsoft or Google
     * mid-way through (newsletters, mailing lists, colleagues) does neither. Found on 400+
     * ordinary emails, where a bare brand mention was the biggest single cause of false alarms.
     *
     * @param  array{brand: ?string, surface: ?string}  $brandDetection
     */
    private function brandSpeaksInMessage(string $text, array $brandDetection, array $contentResult): bool
    {
        if (! $brandDetection['brand']) {
            return false;
        }

        $opening = mb_strtolower(mb_substr(trim($text), 0, 200));
        $surface = mb_strtolower((string) ($brandDetection['surface'] ?: $brandDetection['brand']));

        if (! str_contains($opening, $surface) || ($contentResult['matched_categories'] ?? 0) < 1) {
            return false;
        }

        // "Join our Discord server" or "follow me on Steam" names a place, not a sender. Unless the
        // message also talks as the brand ("Discord team", "from Discord"), it only mentions it.
        $quoted = preg_quote($surface, '/');
        $namesAPlace = (bool) preg_match('/\b(join|follow|sertai|ikut)\s+(\S+\s+){0,3}'.$quoted.'\b|\b'.$quoted.'\s+(server|channel|group|community)\b/u', $opening);
        $talksAsBrand = (bool) preg_match('/\b'.$quoted.'\s+(team|support|security|staff|admin|account|akaun|official|customer|service|login)\b|\b(from|dari)\s+'.$quoted.'\b/u', $opening);

        return ! ($namesAPlace && ! $talksAsBrand);
    }

    private function brandOnlyMentioned(?string $brand): array
    {
        return $brand
            ? ['flagged' => false, 'points' => 0, 'reasons' => ['"'.ucfirst($brand).'" is mentioned, but the message does not present itself as coming from '.ucfirst($brand)]]
            : ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    private function checkBrandSenderMismatch(?string $brand, ?string $surfaceText, ?string $senderEmail): array
    {
        if (! $brand || ! $senderEmail || ! str_contains($senderEmail, '@')) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        [$localPart, $domain] = explode('@', $senderEmail, 2);
        $domain = strtolower($domain);
        $localPart = strtolower($localPart);
        $brandName = array_key_exists(strtoupper($brand), $this->bruneiAcronyms()) ? strtoupper($brand) : ucfirst($brand);
        $displayBrand = $surfaceText && $surfaceText !== $brand
            ? strtoupper($surfaceText).'" (a lookalike of "'.$brandName
            : $brandName;

        $brandDomains = $this->brandDomainMap();

        if (in_array($brand, $this->bruneiGovNames(), true) && $this->isBruneiGovernmentHost($domain)) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $allowed = $brandDomains[$brand] ?? [$brand.'.com'];
        $isOfficial = false;
        foreach ($allowed as $officialDomain) {
            if ($domain === $officialDomain || str_ends_with($domain, '.'.$officialDomain)) {
                $isOfficial = true;
                break;
            }
        }

        if ($isOfficial) {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $reasons = ['"'.$displayBrand."\" branding was detected in the message, but the sender domain (\"{$domain}\") does not belong to {$brand}"];
        $points = 25;

        $cleanedLocal = preg_replace('/^(no-?reply|support|info|admin|service|notification|alert|team|contact)[-_.]?/i', '', $localPart);
        if ($cleanedLocal === '') {
            $cleanedLocal = $localPart;
        }
        $domainRoot = explode('.', $domain)[0] ?? $domain;

        similar_text($cleanedLocal, $brand, $localPercent);
        similar_text($domainRoot, $brand, $domainPercent);
        $bestPercent = max($localPercent, $domainPercent);
        $comparedAgainst = $localPercent >= $domainPercent ? $cleanedLocal : $domainRoot;

        if ($bestPercent >= 45 && $comparedAgainst !== $brand) {
            $reasons[] = "\"{$comparedAgainst}\" is ".round($bestPercent)."% similar to \"{$brand}\", suggesting a lookalike/typosquat attempt";
            $points += 15;
        }

        return ['flagged' => true, 'points' => $points, 'reasons' => $reasons];
    }

    /**
     * File names shown in a message or screenshot, judged by name only (a screenshot shows a file's
     * name, never what is inside it). The same name rules as the file scan: an app or program,
     * a program dressed up as a document with two endings, an archive or an Office file.
     * A PDF is normal in a message, so it adds no points and is shown as information.
     *
     * Only a PDF can be checked inside by the file scan (apps are not accepted), so only a PDF gets the
     * hint to upload the file itself.
     *
     * @return array{flagged: bool, points: int, reasons: array<int, string>, neutral?: bool, file_name?: string}
     */
    private function detectAttachment(string $text): array
    {
        $uploadHint = ' The screenshot only shows its name. If you still have the file, save it and upload it in Scan File to check what is inside.';

        if (preg_match('/([\w\-]+\.(?:pdf|docx?|xlsx?|jpe?g|png|txt|mp4)\.(?:apk|exe|scr|bat|js|vbs|msi|jar|lnk|hta|cmd|pif))\b/i', $text, $m)) {
            return ['flagged' => true, 'points' => 30, 'file_name' => $m[1], 'reasons' => ["File name with two endings detected: {$m[1]} — a program made to look like a document or a picture"]];
        }
        if (preg_match('/([\w\-]+\.apk)\b/i', $text, $m)) {
            return ['flagged' => true, 'points' => 30, 'file_name' => $m[1], 'reasons' => ["Android app (APK) attachment detected: {$m[1]} — installing an app sent in a message is a common way scams take over phones."]];
        }
        if (preg_match('/([\w\-]+\.(?:exe|scr|js|bat|vbs|msi|jar|lnk|hta|ps1|cmd|pif))\b/i', $text, $m)) {
            return ['flagged' => true, 'points' => 25, 'file_name' => $m[1], 'reasons' => ["Executable/script attachment detected: {$m[1]} — high risk file type"]];
        }
        if (preg_match('/([\w\-]+\.(?:zip|rar|7z|iso))\b/i', $text, $m)) {
            return ['flagged' => true, 'points' => 15, 'file_name' => $m[1], 'reasons' => ["Compressed archive attachment detected: {$m[1]}"]];
        }
        if (preg_match('/([\w\-]+\.(?:docm|xlsm))\b/i', $text, $m)) {
            return ['flagged' => true, 'points' => 15, 'file_name' => $m[1], 'reasons' => ["Macro-enabled Office document attachment detected: {$m[1]} — can execute code when opened"]];
        }
        if (preg_match('/([\w\-]+\.(?:doc|docx|xls|xlsx))\b/i', $text, $m)) {
            return ['flagged' => true, 'points' => 10, 'file_name' => $m[1], 'reasons' => ["Office document attachment detected: {$m[1]}"]];
        }
        if (preg_match('/([\w\-]+\.pdf)\b/i', $text, $m)) {
            return ['flagged' => false, 'neutral' => true, 'points' => 0, 'file_name' => $m[1], 'reasons' => ["A PDF file is mentioned: {$m[1]}.".$uploadHint]];
        }

        return ['flagged' => false, 'points' => 0, 'reasons' => []];
    }

    private function isSequentialDigits(string $digits): bool
    {
        $ascending = true;
        $descending = true;

        for ($i = 1; $i < strlen($digits); $i++) {
            if ((int) $digits[$i] !== (int) $digits[$i - 1] + 1) {
                $ascending = false;
            }
            if ((int) $digits[$i] !== (int) $digits[$i - 1] - 1) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }

    /**
     * Sends the uploaded image to OCR.space and returns the extracted text.
     *
     * OCR.space's free tier can return inconsistent results for the exact
     * same image between calls — empty or truncated text on one run, full
     * text on the next. To reduce false "needs review" results caused by
     * this flakiness, we retry once if the first attempt fails outright or
     * comes back with no usable text.
     */
    public function checkScreenshotOcr(string $imagePath): array
    {
        $apiKey = config('services.ocr_space.key');

        if (! $apiKey) {
            return ['success' => false, 'text' => '', 'error' => 'OCR API key not configured'];
        }

        if (! file_exists($imagePath)) {
            return ['success' => false, 'text' => '', 'error' => 'Uploaded image could not be found'];
        }

        $lastError = 'OCR processing failed';

        // The free OCR plan rejects files over about 1 MB, and phone screenshots are often larger.
        [$imageBytes, $imageName] = $this->shrinkImageForOcr((string) file_get_contents($imagePath), basename($imagePath));

        // Engine 2 is the best reader; if it is throttled or fails, the retry uses engine 3,
        // which runs on separate servers and often stays up when engines 1 and 2 are overloaded.
        $engines = ['2', '3'];

        for ($attempt = 0; $attempt < count($engines); $attempt++) {
            try {
                $response = Http::asMultipart()
                    ->attach('file', $imageBytes, $imageName)
                    ->timeout(30)
                    ->post('https://api.ocr.space/parse/image', [
                        'apikey' => $apiKey,
                        'language' => 'eng',
                        'isOverlayRequired' => 'false',
                        'OCREngine' => $engines[$attempt],
                        'scale' => 'true',
                    ]);

                $data = $response->json();

                // OCR.space throttles free keys when it is busy (HTTP 503, error E571). That is temporary.
                if ($response->status() === 503 || $response->status() === 429 || str_contains($response->body(), 'E571')) {
                    $lastError = 'the free OCR service is overloaded right now, please try again in a few minutes';
                    Log::warning('OCR.space is throttling the free key', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);

                    continue;
                }

                if (! is_array($data)) {
                    // Not a normal OCR answer (blocked key, outage): keep the real reason.
                    $body = trim(preg_replace('/\s+/', ' ', strip_tags($response->body())) ?? '');
                    $lastError = 'OCR service answered HTTP '.$response->status().($body !== '' ? ': '.Str::limit($body, 140) : '');
                    Log::warning('OCR.space gave an unreadable answer', ['status' => $response->status(), 'body' => Str::limit($body, 300)]);

                    continue;
                }

                if (($data['IsErroredOnProcessing'] ?? true) === true) {
                    $reason = $data['ErrorMessage'] ?? $data['error'] ?? null;
                    $reason = is_array($reason) ? implode(' ', array_map('strval', $reason)) : (string) $reason;
                    $reason = trim($reason !== '' ? $reason : (string) ($data['ErrorDetails'] ?? $data['details'] ?? ''));
                    $lastError = $reason !== '' ? Str::limit($reason, 160) : 'OCR processing failed (HTTP '.$response->status().')';
                    Log::warning('OCR.space could not process the image', ['status' => $response->status(), 'reason' => $lastError]);

                    continue; // try again before giving up
                }

                $text = trim($data['ParsedResults'][0]['ParsedText'] ?? '');

                if ($text === '') {
                    $lastError = 'OCR returned no readable text';

                    continue; // empty text — worth one retry in case this run was flaky
                }

                return ['success' => true, 'text' => $text, 'error' => null];
            } catch (\Throwable $e) {
                $lastError = 'Could not reach the OCR service';
                Log::warning('OCR.space request failed', ['error' => $e->getMessage()]);
            }
        }

        return ['success' => false, 'text' => '', 'error' => $lastError];
    }

    /**
     * Scales a large image down (and saves it as a JPEG) until it fits the OCR service's 1 MB limit.
     * Small images, and images that cannot be decoded, are sent exactly as uploaded.
     *
     * @return array{0: string, 1: string} the image bytes and the file name to send
     */
    private function shrinkImageForOcr(string $bytes, string $name): array
    {
        $limit = 1_000_000;

        if (strlen($bytes) <= $limit || ! function_exists('imagecreatefromstring')) {
            return [$bytes, $name];
        }

        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return [$bytes, $name];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);
        $jpegName = pathinfo($name, PATHINFO_FILENAME).'.jpg';
        $best = $bytes;

        foreach ([[1800, 85], [1500, 80], [1200, 75], [900, 70]] as [$maxSide, $quality]) {
            $scale = min(1, $maxSide / $longest);
            $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

            if ($resized === false) {
                continue;
            }

            ob_start();
            imagejpeg($resized, null, $quality);
            $jpeg = (string) ob_get_clean();

            if ($jpeg !== '') {
                $best = $jpeg;
            }

            if (strlen($best) <= $limit) {
                break;
            }
        }

        return $best === $bytes ? [$bytes, $name] : [$best, $jpegName];
    }

    /**
     * Returns ALL URL-like matches in the text, not just the first — a
     * screenshot can contain multiple links (e.g. a legitimate-looking one
     * alongside the actual phishing link), and only checking whichever
     * appears first in reading order risks missing the more suspicious one.
     */
    private function extractAllUrlsFromText(string $text): array
    {
        preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $this->joinWrappedLinks($text), $matches);

        return array_map(fn ($m) => rtrim($m, '.,;:)'), $matches[0] ?? []);
    }

    /**
     * A long link in a chat bubble often breaks right after "https://", so the OCR text has the
     * start of the link on one line and the address on the next. Joins them back together so the
     * link is read as one.
     */
    private function joinWrappedLinks(string $text): string
    {
        return preg_replace('~\b(https?://)\s+(?=[a-z0-9])~i', '$1', $text) ?? $text;
    }

    /**
     * Domains written without "http://" or "https://" in screenshot text, such as the "polydrop.top"
     * in a fake giveaway post. Scammers often write links this way, so the full-link search misses them.
     *
     * To avoid false alarms the domain must end in a real, known ending, and email addresses, full links,
     * file names ("photo.png"), numbers ("2.5K", "v1.2.3") and abbreviations ("e.g.") are all ignored.
     *
     * @return array<int, string> lower-case domains without "www.", in the order they appear
     */
    public function extractBareDomainsFromText(string $text): array
    {
        $endings = 'com|net|org|info|biz|edu|gov|mil|io|co|me|app|dev|top|xyz|online|site|store|shop|live|club|vip|icu|cc|tk|ml|ga|cf|gq|ws|link|click|cloud|tech|pro|fun|buzz|work|world|today|page|pages|win|bid|loan|men|rest|cyou|sbs|mobi|bn|my|sg|id|in|uk|us|au|ph|th|vn|cn|hk|jp|kr|de|fr|ru|cfd|bond|quest|monster|uno|boats|autos|homes|cam|pw';

        $text = $this->joinWrappedLinks($text);
        $withoutEmails = preg_replace('/[a-z0-9._%+\-]+@[a-z0-9.\-]+/i', ' ', $text) ?? $text;
        $withoutLinks = preg_replace('~https?://[^\s"\'<>]+~i', ' ', $withoutEmails) ?? $withoutEmails;

        preg_match_all(
            '/(?<![\w@\/:\-])((?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+(?:'.$endings.'))(?![\w@\-]|\.\w)/i',
            $withoutLinks,
            $matches
        );

        $domains = [];
        foreach ($matches[1] as $domain) {
            $domains[preg_replace('/^www\./', '', strtolower($domain))] = true;
        }

        return array_keys($domains);
    }

    /**
     * OCR often introduces stray spaces around "@" and "." in email addresses
     * (e.g. "services @ paypal - accounts . com"). Normalize those artifacts
     * before matching so noisy-but-readable text still extracts correctly.
     *
     * Returns ALL email-like matches, not just the first — a phishing
     * screenshot can list more than one sender address (e.g. a display
     * address and a reply-to), and checking only whichever comes first
     * positionally can miss a more obviously fake one listed afterward.
     */
    private function extractAllEmailsFromText(string $text): array
    {
        $normalized = preg_replace('/\s*@\s*/', '@', $text);
        $normalized = preg_replace('/\s*\.\s*(?=[a-zA-Z]{2,}\b)/', '.', $normalized);

        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $normalized, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Returns ALL phone-number-like sequences found in the text, validated
     * through the SAME libphonenumber parsing checkPhoneNumber() uses, not
     * just a loose regex match.
     *
     * Requires EITHER a leading "+" (international format) OR at least one
     * separator character (space/dash) between digit groups — a bare,
     * unbroken digit run (e.g. "9523732") is far more likely to be a
     * report ID, order number, or database reference than a phone number.
     * Confirmed via testing: a phishing-database listing's own reference
     * ID number was being misread as a phone number candidate under the
     * previous looser pattern, purely because it happened to be 7 digits
     * long. Genuine phone numbers, even typed carelessly, are almost
     * always separated somehow (spaces, dashes, or a leading "+").
     */
    private function extractAllPhonesFromText(string $text): array
    {
        preg_match_all('/\+\d[\d\-\s\(\)]{5,17}\d|\d{2,4}[\s\-]\d{2,4}[\s\-]\d{2,6}(?:[\s\-]\d{2,4})?/', $text, $matches);

        $candidates = array_map(function ($m) {
            return trim(preg_replace('/\s{2,}/', ' ', $m));
        }, $matches[0] ?? []);

        return array_values(array_unique(array_filter($candidates, fn ($c) => strlen(preg_replace('/\D/', '', $c)) >= 7)));
    }

    private function decodeQrCode(string $imagePath): ?string
    {
        if (! class_exists(QrReader::class)) {
            return null;
        }

        try {
            $qrReader = new QrReader($imagePath);
            $decoded = $qrReader->text();

            return (is_string($decoded) && $decoded !== '') ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * A lightweight keyword check for common phishing/scam lure language.
     * Used purely to make the "no URL or email found" message clearer when a
     * screenshot has no extractable link/address — distinguishing a genuine
     * phishing message that OCR just couldn't pull a link/email out of, from
     * an entirely unrelated image (a game screenshot, a personal photo, etc.)
     * that was never phishing-related in the first place. This is a simple
     * substring check, not real language understanding — it will miss
     * cleverly-worded scams and can occasionally flag legitimate messages
     * that happen to use similar phrasing (e.g. a real password-reset email).
     * It only affects wording, not scoring.
     */
    private function containsPhishingLureLanguage(string $text): bool
    {
        $lureKeywords = [
            'urgent', 'verify your', 'verify account', 'account suspended', 'account locked',
            'account has been', 'confirm your', 'unusual activity', 'security alert',
            'password expired', 'click here', 'click the link', 'log in to', 'login to',
            'reset your password', 'update your payment', 'update your billing',
            'otp', 'one-time password', 'one time password', 'verification code',
            'congratulations', 'you have won', 'you\'ve won', 'claim your', 'limited time',
            'act now', 'act immediately', 'final notice', 'immediate action required',
            'failed delivery', 'delivery failed', 'tracking number', 'could not be delivered',
            'invoice attached', 'payment failed', 'refund', 'suspended due to',
            // Malay
            'tindakan segera', 'sahkan akaun', 'akaun anda', 'kod pengesahan', 'kod otp',
            'tahniah', 'memenangi', 'klik di sini', 'klik pautan', 'bayaran balik',
            'caj kastam', 'notis penglibatan',
        ];

        $lowerText = strtolower($text);
        foreach ($lureKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Join reasons into one readable message, making sure each reads as its
     * own sentence (several reasons are written without a final full stop).
     */
    private function joinReasons(array $reasons): string
    {
        return implode(' ', array_map(
            fn ($reason) => preg_match('/[.!?)]$/', $reason = rtrim((string) $reason)) ? $reason : $reason.'.',
            $reasons
        ));
    }

    /**
     * Build a structured check result for the UI (name, status, message).
     */
    private function buildCheck(string $name, array $result, string $flaggedStatus = 'SUSPICIOUS'): array
    {
        if ($result['unavailable'] ?? false) {
            return [
                'name' => $name,
                'status' => 'UNKNOWN',
                'message' => ! empty($result['reasons'])
                    ? $this->joinReasons($result['reasons'])
                    : 'This check could not be completed.',
                'points' => $result['points'],
            ];
        }

        // A "nothing found" result that says nothing about safety (such as a number nobody has
        // reported yet) is shown as information, not as a green pass.
        $passStatus = ($result['neutral'] ?? false) ? 'INFO' : 'SAFE';

        return [
            'name' => $name,
            'status' => $result['flagged'] ? $flaggedStatus : $passStatus,
            'message' => ! empty($result['reasons'])
                ? $this->joinReasons($result['reasons'])
                // a passing check can say what it found ('detail'); otherwise the plain "nothing found" line
                : (($result['detail'] ?? null) ?: 'No issues detected for this check.'),
            'points' => $result['points'],
        ];
    }

    public function analyze(string $type, ?string $url = null, ?string $email = null, ?string $phone = null, ?string $screenshotPath = null, ?int $reportId = null, ?string $emailSubject = null, ?string $emailBody = null, ?string $filePath = null, ?string $fileName = null, ?string $emailHeaders = null): array
    {
        return match ($type) {
            'email' => $this->analyzeEmail($email ?? '', $reportId, $emailSubject, $emailBody, $emailHeaders),
            'phone' => $this->analyzePhone($phone ?? '', $reportId),
            'screenshot' => $this->analyzeScreenshot($screenshotPath ?? '', $reportId),
            'file' => $this->analyzeFile($filePath ?? '', $fileName, $reportId),
            default => $this->analyzeUrl($url ?? '', $reportId),
        };
    }

    /**
     * Runs checks that do not depend on each other, and returns their results under the same keys.
     *
     * By default they run one after the other, exactly as before. With SCAN_PARALLEL=true they run at the same
     * time in separate PHP processes (Laravel's Concurrency), so a scan takes about as long as its slowest check
     * instead of all of them added up. If running them side by side fails for any reason, they are simply run one
     * after the other, so a scan never fails because of this.
     *
     * @param  array<string, Closure>  $tasks
     * @return array<string, mixed>
     */
    private function runIndependently(array $tasks): array
    {
        $oneByOne = fn (): array => array_map(fn (Closure $task) => $task(), $tasks);

        if (! config('services.scan.parallel', false) || count($tasks) < 2) {
            return $oneByOne();
        }

        try {
            $results = Concurrency::run($tasks);

            // Every task must have answered with its own result; anything else cannot be trusted.
            if (array_keys($results) === array_keys($tasks) && count(array_filter($results, 'is_array')) === count($tasks)) {
                return $results;
            }

            Log::warning('Scan checks gave unexpected results when run in parallel; running one by one');
        } catch (\Throwable $e) {
            Log::warning('Scan checks could not run in parallel; running one by one', ['reason' => $e::class]);
        }

        return $oneByOne();
    }

    private function analyzeUrl(string $url, ?int $reportId = null): array
    {
        $syntaxResult = $this->checkUrlSyntax($url);
        $host = parse_url($url, PHP_URL_HOST);
        $domainHistoryResult = $this->checkPreviousDomainReports($host, $reportId);
        $hostingResult = $this->checkFreeHostingPlatform($url);

        // These eight each go out to the network and none needs another's answer, so they can run side by side
        // (see runIndependently). Same checks, same order, same results either way.
        [
            'age' => $ageResult,
            'ssl' => $sslResult,
            'blacklist' => $blacklistResult,
            'vt' => $vtResult,
            'ip' => $ipResult,
            'redirect' => $redirectResult,
            'content' => $contentResult,
            'availability' => $availabilityResult,
        ] = $this->runIndependently([
            'age' => fn () => $this->checkDomainAge($url),
            'ssl' => fn () => $this->checkSslCertificate($url),
            'blacklist' => fn () => $this->checkBlacklist($url),
            'vt' => fn () => $this->checkVirusTotal($url),
            'ip' => fn () => $this->checkIpReputation($url),
            'redirect' => fn () => $this->checkRedirectChain($url),
            'content' => fn () => $this->checkPageContent($url),
            'availability' => fn () => $this->checkSiteAvailability($url),
        ]);

        // An old site that no security company flags is not treated as a copy just because a brand name is in its address.
        $syntaxResult = $this->softenBrandWordForEstablishedSite(
            $syntaxResult,
            $ageResult['points'] + $sslResult['points'] + $blacklistResult['points'] + $vtResult['points'] + $ipResult['points']
                + $redirectResult['points'] + $contentResult['points'] + $hostingResult['points'] + $domainHistoryResult['points'],
            $ageResult,
            $vtResult,
        );

        $otherPoints = $syntaxResult['points'] + $ageResult['points'] + $sslResult['points']
            + $blacklistResult['points'] + $vtResult['points'] + $ipResult['points'] + $redirectResult['points']
            + $contentResult['points'] + $hostingResult['points'] + $domainHistoryResult['points'];
        $aiPageResult = $host ? $this->checkAiPage((string) $host, $contentResult, $otherPoints) : null;

        $totalPoints = min(100, $otherPoints + ($aiPageResult['points'] ?? 0));

        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('SSL Certificate', $sslResult, 'SUSPICIOUS'),
            $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('URL Structure', $syntaxResult, 'SUSPICIOUS'),
            $this->buildCheck('Blacklist Database', $blacklistResult, 'DETECTED'),
            $this->buildCheck('VirusTotal / CTI Check', $vtResult, 'DETECTED'),
            $this->buildCheck('IP Reputation & Location', $ipResult, 'SUSPICIOUS'),
            $this->buildCheck('Redirect Chain', $redirectResult, 'SUSPICIOUS'),
            $this->buildCheck('Page Content Analysis', $contentResult, $contentResult['points'] >= 30 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('Hosting Platform', $hostingResult, 'SUSPICIOUS'),
            [
                'name' => 'Site Availability',
                'status' => $availabilityResult['status_label'],
                'message' => $this->joinReasons($availabilityResult['reasons']),
                'points' => 0,
            ],
            $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS'),
        ];

        if ($aiPageResult !== null) {
            $checks[] = $this->buildCheck('AI Page Review', $aiPageResult, $aiPageResult['verdict'] === 'scam' ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        $result = [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'],
            'url_syntax_score' => $syntaxResult['points'],
            'ip_address' => $ipResult['ip'],
            'ip_reputation' => $ipResult['summary'],
            'country' => $ipResult['country'],
            'redirect_chain' => $redirectResult['chain'],
            'checks' => $checks,
        ];

        if ($vtResult['raw'] !== null) {
            $threatScore = $vtResult['total'] > 0
                ? round(($vtResult['malicious'] / $vtResult['total']) * 100, 1)
                : 0.0;

            $result['cti'] = [
                'source' => 'VirusTotal',
                'raw_response' => $vtResult['raw'],
                'threat_score' => $threatScore,
            ];
        }

        return $result;
    }

    /**
     * Did this email really come from the domain it claims? Reads the pasted email headers (see EmailHeaders):
     * SPF, DKIM and DMARC are checks the visitor's own mailbox provider ran when the email arrived, and the
     * Reply-To line shows where an answer would go.
     *
     * Careful about false alarms, because real mail fails these checks now and then (a mailing list, a forward,
     * a company that set its records up wrongly). So:
     *  - one failed check is a small signal; DMARC failing, or several failing together, is a larger one;
     *  - a failure counts most when the From domain is a well-known one (on the official list or among the
     *    most visited sites), because those domains publish strict records, so a failure there means somebody
     *    else is using the name;
     *  - a Reply-To on a different domain is only a small signal, and only for a sender on the official list:
     *    bulk-mail and invoicing services (Brevo, Mailchimp, SendGrid) are popular sites too, and they set the
     *    customer's own address as Reply-To on purpose;
     *  - passing never lowers the score: scammers authenticate their own domains too.
     * Headers alone can add at most 50 points, so they cannot make a phishing verdict by themselves.
     */
    public function checkEmailAuthentication(string $rawHeaders, string $enteredSender = ''): array
    {
        $headers = EmailHeaders::parse($rawHeaders);

        if (! $headers->hasHeaders()) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ['No email headers could be read from what was pasted, so the sender could not be verified'],
                'unavailable' => true,
            ];
        }

        $fromDomain = $headers->fromDomain();
        $fromRegistrable = RegistrableDomain::of($fromDomain);
        $isWellKnown = $fromDomain !== null && ($this->isOfficialBrandDomain($fromDomain) || ThreatLookup::isPopular($fromDomain));
        $isOfficialSender = $fromDomain !== null && $this->isOfficialBrandDomain($fromDomain);
        $isFreeMailSender = $fromRegistrable !== null && in_array($fromRegistrable, EmailHeaders::FREE_MAIL, true);

        $reasons = [];
        $points = 0;

        $enteredDomain = strtolower((string) substr(strrchr($enteredSender, '@') ?: '', 1));

        if ($fromDomain !== null && $enteredDomain !== '' && ! RegistrableDomain::same($fromDomain, $enteredDomain)) {
            $reasons[] = "The From address in the headers ({$fromDomain}) is a different domain from the sender you entered ({$enteredDomain})";
        }

        $auth = $headers->authentication();

        if ($auth['found']) {
            $failing = array_values(array_filter([
                $auth['dmarc'] === 'fail' ? 'DMARC' : null,
                in_array($auth['spf'], ['fail', 'softfail'], true) ? 'SPF' : null,
                $auth['dkim'] === 'fail' ? 'DKIM' : null,
            ]));

            if ($failing !== []) {
                $hardSpfFail = $auth['spf'] === 'fail';

                if ($auth['dmarc'] === 'fail') {
                    $authPoints = 20;
                } elseif (in_array('SPF', $failing, true) && in_array('DKIM', $failing, true)) {
                    $authPoints = 20;
                } elseif ($hardSpfFail) {
                    $authPoints = 10;
                } else {
                    $authPoints = 5;
                }

                $authPoints = min(30, $authPoints + 5 * (count($failing) - 1));

                $imitated = $isWellKnown && $authPoints >= 20;

                if ($imitated) {
                    // A well-known domain that fails its own checks is being imitated.
                    $authPoints = $auth['dmarc'] === 'fail' ? 40 : max($authPoints, 25);
                }

                $points += $authPoints;
                $names = implode(', ', $failing);
                $where = $fromDomain !== null ? " for {$fromDomain}" : '';
                $reasons[] = "The email failed sender verification ({$names}){$where}, so it may not really have come from that domain";

                if ($imitated) {
                    $reasons[] = 'That is a well-known domain, which normally passes these checks, so someone else may be using its name';
                }
            } else {
                $passed = array_values(array_filter([
                    $auth['spf'] === 'pass' ? 'SPF' : null,
                    $auth['dkim'] === 'pass' ? 'DKIM' : null,
                    $auth['dmarc'] === 'pass' ? 'DMARC' : null,
                ]));

                $reasons[] = $passed !== []
                    ? 'The email passed sender verification ('.implode(', ', $passed).'). That shows which domain sent it, but scammers can set this up for their own domains too, so it does not prove the email is safe'
                    : 'The headers show no failed sender verification';
            }
        }

        // Where an answer would go.
        $replyDomains = array_values(array_filter(
            $headers->replyToDomains(),
            fn (string $domain): bool => $fromRegistrable !== null && ! RegistrableDomain::same($domain, $fromRegistrable)
        ));

        if ($replyDomains !== [] && $isOfficialSender && ! $isFreeMailSender) {
            $freeMail = array_values(array_filter($replyDomains, fn (string $d): bool => in_array(RegistrableDomain::of($d), EmailHeaders::FREE_MAIL, true)));
            $unknown = array_values(array_filter($replyDomains, fn (string $d): bool => ! $this->isOfficialBrandDomain($d) && ! ThreatLookup::isPopular($d)));

            if ($freeMail !== []) {
                $points += 10;
                $reasons[] = "Replies go to a free mailbox ({$freeMail[0]}) instead of {$fromDomain}'s own domain. Real companies usually answer from their own domain, though some services pass on a customer's address, so this is a small warning";
            } elseif ($unknown !== []) {
                $points += 10;
                $reasons[] = "Replies go to a different, little-known domain ({$unknown[0]}) instead of {$fromDomain}";
            }
        }

        if (! $auth['found']) {
            $reasons[] = 'These headers have no sender verification results (SPF, DKIM or DMARC). Some mail apps remove them, so copy the full original headers if you can';

            if ($points === 0) {
                return ['flagged' => false, 'points' => 0, 'reasons' => $reasons, 'unavailable' => true];
            }
        }

        $points = min(50, $points);

        return ['flagged' => $points > 0, 'points' => $points, 'reasons' => $reasons];
    }

    private function analyzeEmail(string $email, ?int $reportId = null, ?string $subject = null, ?string $body = null, ?string $emailHeaders = null): array
    {
        $emailResult = $this->checkEmailDomain($email);
        $domain = $emailResult['domain'];

        $ageResult = $domain
            ? $this->checkDomainAge($domain)
            : ['flagged' => false, 'points' => 0, 'domain_age_days' => null, 'reasons' => ['Could not extract a domain from this email']];

        $domainHistoryResult = $this->checkPreviousDomainReports($domain, $reportId);

        $combinedText = trim(($subject ?? '').' '.($body ?? ''));
        $hasContentText = $combinedText !== '';

        $contentResult = $hasContentText
            ? $this->checkContentPatterns($combinedText)
            : ['flagged' => false, 'points' => 0, 'reasons' => [], 'matched_categories' => 0];

        $brandDetection = $hasContentText
            ? $this->detectBrandInText($combinedText)
            : ['brand' => null, 'surface' => null];

        $brandResult = $this->brandSpeaksInMessage($combinedText, $brandDetection, $contentResult)
            ? $this->checkBrandSenderMismatch($brandDetection['brand'], $brandDetection['surface'], $email)
            : $this->brandOnlyMentioned($brandDetection['brand']);

        // Only when the visitor pasted the email's headers. Without them nothing is added or shown.
        $headerResult = trim((string) $emailHeaders) !== '' ? $this->checkEmailAuthentication((string) $emailHeaders, $email) : null;

        $linkResult = $hasContentText ? $this->checkEmailLinks($combinedText, $domain) : ['flagged' => false, 'points' => 0, 'reasons' => [], 'url' => null];

        $rulePoints = $emailResult['points'] + $ageResult['points'] + $contentResult['points'] + $brandResult['points']
            + $domainHistoryResult['points'] + ($headerResult['points'] ?? 0) + $linkResult['points'];
        $aiResult = $hasContentText ? $this->checkAiText($combinedText, $rulePoints) : null;

        $totalPoints = $this->requireCorroboration(min(100, $rulePoints + ($aiResult['points'] ?? 0)), [
            $emailResult['points'] + $ageResult['points'],
            $domainHistoryResult['points'],
            $contentResult['points'],
            $brandResult['points'],
            $headerResult['points'] ?? 0,
            $aiResult['points'] ?? 0,
            $linkResult['points'],
        ], $this->wordingKinds($contentResult) - 1 + (($headerResult['points'] ?? 0) >= 30 ? 1 : 0));
        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('Sender Domain Analysis', $emailResult, 'SUSPICIOUS'),
            $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS'),
        ];

        if ($headerResult !== null) {
            $checks[] = $this->buildCheck('Email Authentication', $headerResult, $headerResult['points'] >= 30 ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        if ($hasContentText) {
            $checks[] = $this->buildCheck('Message Content / Behavior Patterns', $contentResult, $contentResult['matched_categories'] >= 3 ? 'HIGH RISK' : 'SUSPICIOUS');
            if ($brandDetection['brand']) {
                $checks[] = $this->buildCheck('Brand / Sender Correlation', $brandResult, 'HIGH RISK');
            }
            if ($linkResult['url'] !== null) {
                $checks[] = $this->buildCheck('Links in the Email', $linkResult, $linkResult['points'] >= 25 ? 'HIGH RISK' : 'SUSPICIOUS');
            }
            if ($aiResult !== null) {
                $checks[] = $this->buildCheck('AI Message Review', $aiResult, $aiResult['verdict'] === 'scam' ? 'HIGH RISK' : 'SUSPICIOUS');
            }
        } else {
            $checks[] = [
                'name' => 'Message Content / Behavior Patterns',
                'status' => 'REVIEW',
                'message' => 'No subject or body text was provided — content analysis skipped. Add the subject/body for a more accurate result.',
                'points' => 0,
            ];
        }

        return [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'] ?? null,
            'url_syntax_score' => null,
            'checks' => $checks,
            // Only used to offer a page preview of the link in the message. It does not change the score.
            'extracted_url' => $hasContentText ? $this->pickLinkForPreview($combinedText) : null,
        ];
    }

    /**
     * Checks the links written in an email with the same address rules a link scan uses, and counts the worst
     * one at 0.6, as a message scan does. A weak warning on a link to the sender's own site is left out: a company's
     * email links to its own pages. Only full links are read ("https://..."),
     * since an email carries its real addresses, and a bare "file.pdf" or "www" text is not evidence.
     *
     * @return array{flagged: bool, points: int, reasons: array<int, string>, url: ?string}
     */
    private function checkEmailLinks(string $text, ?string $senderDomain): array
    {
        $none = ['flagged' => false, 'points' => 0, 'reasons' => [], 'url' => null];
        $own = $senderDomain !== null && $senderDomain !== '' ? RegistrableDomain::of($senderDomain) : null;

        $links = array_values(array_unique(array_filter(
            $this->extractAllUrlsFromText($text),
            fn ($link) => filter_var($link, FILTER_VALIDATE_URL)
        )));

        $worst = null;

        foreach (array_slice($links, 0, 8) as $link) {
            $host = (string) parse_url($link, PHP_URL_HOST);

            if ($host === '') {
                continue;
            }

            $syntax = $this->checkUrlSyntax($link);

            // A link to the sender's own site is normal, and a weak warning there only repeats what the sender
            // check already said. A strong one (a look-alike name, a listed phishing site) still counts: scams
            // often send from the same address they link to.
            if ($own !== null && RegistrableDomain::of($host) === $own && $syntax['points'] < 25) {
                continue;
            }

            if ($worst === null || $syntax['points'] > $worst['syntax']['points']) {
                $worst = ['link' => $link, 'syntax' => $syntax];
            }
        }

        if ($worst === null || $worst['syntax']['points'] <= 0) {
            return $none;
        }

        $host = (string) parse_url($worst['link'], PHP_URL_HOST);
        $points = (int) round($worst['syntax']['points'] * 0.6);

        return [
            'flagged' => $points > 0,
            'points' => $points,
            'reasons' => array_merge(["A link in the email goes to \"{$host}\":"], $worst['syntax']['reasons']),
            'url' => $worst['link'],
        ];
    }

    /** The most suspicious-looking full link in a message, or null when there is none. */
    private function pickLinkForPreview(string $text): ?string
    {
        $links = array_values(array_unique(array_filter(
            $this->extractAllUrlsFromText($text),
            fn ($link) => filter_var($link, FILTER_VALIDATE_URL)
        )));

        if ($links === []) {
            return null;
        }

        usort($links, fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']);

        return $links[0];
    }

    private function analyzePhone(string $phone, ?int $reportId = null): array
    {
        $phoneResult = $this->checkPhoneNumber($phone);

        if ($phoneResult['unparseable'] ?? false) {
            return [
                'risk_score' => 0,
                'verdict' => 'review',
                'domain_age_days' => null,
                'url_syntax_score' => null,
                'checks' => [
                    $this->buildCheck('Phone Number Analysis', $phoneResult, 'REVIEW'),
                ],
            ];
        }

        $historyResult = $this->checkPreviousReports($phone, $reportId);
        $reputationResult = $this->checkPhoneReputation($phone);

        $totalPoints = min(100, $phoneResult['points'] + $historyResult['points'] + $reputationResult['points']);
        $verdict = $totalPoints >= 60 ? 'phishing' : ($totalPoints >= 25 ? 'suspicious' : 'clean');

        $checks = [
            $this->buildCheck('Phone Number Analysis', $phoneResult, 'SUSPICIOUS'),
            $this->buildCheck('Previous Reports', $historyResult, $historyResult['points'] >= 45 ? 'HIGH RISK' : 'SUSPICIOUS'),
            $this->buildCheck('Phone Reputation (AbstractAPI)', $reputationResult, $reputationResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS'),
        ];

        return [
            'risk_score' => $totalPoints,
            'verdict' => $verdict,
            'domain_age_days' => null,
            'url_syntax_score' => null,
            'checks' => $checks,
        ];
    }

    /**
     * Checks how many times a domain (from a URL host or an email's
     * sender domain) has already appeared in OTHER completed reports on
     * PhishCore — across url, email, AND screenshot scans — regardless of
     * how each one individually scored. The same phishing infrastructure
     * is frequently reused across multiple different pretexts (a fake DHL
     * email today, a fake Outlook email tomorrow, all from the same
     * domain), and a domain surfacing repeatedly is strong evidence even
     * when a single report in isolation looks unremarkable.
     *
     * Uses a LIKE match against the raw url/sender_email columns rather
     * than an exact host comparison, since those columns store full
     * submitted values (e.g. "https://sub.domain.com/path") not bare
     * hosts. This can over-match in rare edge cases (e.g. a domain name
     * appearing as a substring of an unrelated longer domain), a known
     * limitation acceptable for this project's scope.
     */
    private function checkPreviousDomainReports(?string $domain, ?int $excludeReportId = null): array
    {
        $domain = strtolower(trim((string) $domain));
        $domain = preg_replace('/^www\./', '', $domain);

        if ($domain === '') {
            return ['flagged' => false, 'points' => 0, 'reasons' => []];
        }

        $query = Report::where('status', 'completed')
            ->whereHas('analyses', function ($q) {
                $q->whereIn('verdict', ['suspicious', 'phishing']);
            })
            ->where(function ($q) use ($domain) {
                $q->where('url', 'like', "%{$domain}%")
                    ->orWhere('sender_email', 'like', "%{$domain}%");
            });

        if ($excludeReportId) {
            $query->where('id', '!=', $excludeReportId);
        }

        $candidateReports = $query->get(['id', 'user_id', 'url', 'sender_email', 'created_at']);

        // The LIKE query above is only a cheap first pass to shrink the result
        // set. It matches SUBSTRINGS, so a phishing lookalike domain like
        // "google.com.verify-account.tk" would incorrectly match "google.com"
        // — which is backwards: that domain is impersonating Google, not
        // evidence against the real one. Re-check precisely here by parsing
        // out the ACTUAL host/domain from each candidate and requiring an
        // exact match (or genuine subdomain), not just a substring.
        $matchingReports = $candidateReports->filter(function ($report) use ($domain) {
            $reportDomain = null;

            if ($report->url) {
                $host = parse_url($report->url, PHP_URL_HOST);
                $reportDomain = $host ? strtolower(preg_replace('/^www\./', '', $host)) : null;
            }

            if (! $reportDomain && $report->sender_email && str_contains($report->sender_email, '@')) {
                $reportDomain = strtolower(substr(strrchr($report->sender_email, '@'), 1));
            }

            if (! $reportDomain) {
                return false;
            }

            return $reportDomain === $domain || str_ends_with($reportDomain, '.'.$domain);
        });

        $distinctLoggedInReporters = $matchingReports->pluck('user_id')->filter()->unique()->count();
        $guestReportCount = $matchingReports->whereNull('user_id')->count();
        $reporterCount = $distinctLoggedInReporters + $guestReportCount;

        if ($reporterCount === 0) {
            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => ["No prior reports found for \"{$domain}\" on PhishCore"],
            ];
        }

        $firstSeen = $matchingReports->min('created_at');
        $firstSeenLabel = $firstSeen ? 'First seen on PhishCore: '.$firstSeen->format('j M Y') : null;

        if ($reporterCount === 1) {
            $reasons = ['This domain has appeared in 1 other report on PhishCore.'];
            if ($firstSeenLabel) {
                $reasons[] = $firstSeenLabel;
            }
            $reasons[] = '(Informational only — this does not change the risk score.)';

            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => $reasons,
            ];
        }

        if ($reporterCount <= 4) {
            $reasons = ["This domain has appeared in {$reporterCount} other reports on PhishCore — reused across multiple submissions."];
            if ($firstSeenLabel) {
                $reasons[] = $firstSeenLabel;
            }
            $reasons[] = '(Informational only — this does not change the risk score.)';

            return [
                'flagged' => false,
                'points' => 0,
                'reasons' => $reasons,
            ];
        }

        $reasons = ["This domain has appeared in {$reporterCount} other reports on PhishCore — repeatedly reused phishing infrastructure."];
        if ($firstSeenLabel) {
            $reasons[] = $firstSeenLabel;
        }
        $reasons[] = '(Informational only — this does not change the risk score.)';

        return [
            'flagged' => false,
            'points' => 0,
            'reasons' => $reasons,
        ];
    }

    /**
     * Checks how many times this exact phone number has already been
     * reported on PhishCore by other scans — a crowd-sourced signal similar
     * in spirit to caller-ID/spam-reporting apps, built from the platform's
     * own report history rather than an external database. This is
     * necessarily a much smaller dataset than a service like Truecaller,
     * which relies on hundreds of millions of users — that scale gap can't
     * be closed by a single platform's own report volume, and is a known,
     * honest limitation of phone-based detection generally, not something
     * this project can fully solve.
     *
     * Reports are weighted by RECENCY, not counted flat. Phone numbers are
     * routinely reassigned by telcos after a period of subscriber
     * inactivity — a number reported as a scam a year ago may since belong
     * to an unrelated, innocent person. Treating a stale report the same
     * as a fresh one (the previous behavior) is a real source of the
     * inaccurate results common to phone-lookup tools generally. Recent
     * reports (<=90 days) count at full weight; moderately old ones
     * (91-365 days) at half weight; anything older is tapered further
     * rather than dropped outright, since an old report is still weak
     * supporting evidence, just not proof of current activity.
     *
     * Numbers are compared with punctuation/whitespace stripped so the same
     * number submitted in different formats (e.g. "+673 811 1346" vs
     * "6738111346") is still recognized as a repeat.
     *
     * $excludeReportId excludes the current report's own row — the Report
     * record for this scan already exists in the database by the time this
     * runs, so without excluding it, every number would show as "reported
     * at least once" on its very first scan.
     */
    private function checkPreviousReports(string $phone, ?int $excludeReportId = null): array
    {
        $normalizedInput = preg_replace('/[\s()\-]/', '', $phone);

        // Only scans by signed-in people count. A guest has no account to tell one
        // person from another, so each guest scan would otherwise look like a new reporter.
        $query = Report::where('type', 'phone')
            ->where('status', 'completed')
            ->whereNotNull('phone_number')
            ->whereNotNull('user_id');

        if ($excludeReportId) {
            $query->where('id', '!=', $excludeReportId);
        }

        $matchingReports = $query->get()
            ->filter(function ($report) use ($normalizedInput) {
                return preg_replace('/[\s()\-]/', '', $report->phone_number) === $normalizedInput;
            });

        // People who pressed "Report this number as a scam" (one per person per number). A number a
        // team member has hidden because its listing was wrong counts as having no reports.
        $isHidden = HiddenNumber::where('phone', $normalizedInput)->exists();
        $explicitReports = $isHidden ? collect() : NumberReport::where('phone', $normalizedInput)->get();

        if ($isHidden) {
            $matchingReports = collect();
        }

        // A number an administrator added because a trusted source (the police, BDCB, a news report) named it.
        // A hidden number is treated as having no reports, this one included.
        if (! $isHidden) {
            try {
                $known = KnownScamNumber::where('phone', $normalizedInput)->first();
            } catch (\Throwable) {
                $known = null;
            }

            if ($known !== null) {
                $kind = NumberReport::CATEGORIES[$known->category] ?? NumberReport::CATEGORIES['other'];

                return [
                    'flagged' => true,
                    'points' => 60,
                    'reasons' => array_values(array_filter([
                        "{$known->source} has named this number as a scam ({$kind}).",
                        $known->note ? 'Note: '.$known->note : null,
                        'Added to PhishCore on '.$known->created_at->format('j F Y').'. Phone numbers can be given to someone else later, so check the date.',
                    ])),
                ];
            }
        }

        if ($matchingReports->isEmpty() && $explicitReports->isEmpty()) {
            return [
                'flagged' => false,
                'neutral' => true,
                'points' => 0,
                'reasons' => [
                    'No prior reports found for this number on PhishCore — note that phone-scam detection relies on crowdsourced reports, so this does not guarantee the number is safe, only that it has not been reported here before.',
                ],
            ];
        }

        // Deduplicate by reporter first (same rationale as before — one
        // person testing repeatedly shouldn't look like multiple
        // independent reporters), THEN apply a recency weight to each
        // distinct reporter's most recent report of this number.
        $entries = [];

        foreach ($matchingReports->groupBy('user_id') as $key => $reportsForReporter) {
            $entries[$key] = ['when' => $reportsForReporter->max('created_at'), 'explicit' => false];
        }

        // Someone who said "this is a scam" on purpose counts for more than someone who only looked the number up.
        foreach ($explicitReports as $row) {
            if (isset($entries[$row->user_id])) {
                $entries[$row->user_id]['explicit'] = true;

                if ($row->created_at->gt($entries[$row->user_id]['when'])) {
                    $entries[$row->user_id]['when'] = $row->created_at;
                }
            } else {
                $entries[$row->user_id] = ['when' => $row->created_at, 'explicit' => true];
            }
        }

        $now = now();
        $explicitScore = 0.0;
        $lookupScore = 0.0;
        $reporterCount = 0;
        $lookupCount = 0;

        foreach ($entries as $entry) {
            $ageDays = $entry['when']->diffInDays($now);

            $weight = match (true) {
                $ageDays <= 90 => 1.0,
                $ageDays <= 365 => 0.5,
                default => 0.2,
            };

            if ($entry['explicit']) {
                $explicitScore += $weight * 1.5;
                $reporterCount++;
            } else {
                $lookupScore += $weight * 0.25;
                $lookupCount++;
            }
        }

        // Looking a number up is not the same as saying it is a scam: people also check numbers that
        // turn out to be fine. On their own, lookups add nothing. Next to real reports they add a
        // little, up to a small cap, so a few extra accounts can never tip a number into "suspicious".
        if ($reporterCount === 0) {
            return [
                'flagged' => false,
                'neutral' => true,
                'points' => 0,
                'reasons' => [
                    "No one has reported this number as a scam on PhishCore. It has been looked up by {$lookupCount} signed-in user(s) before, which does not count as a report — note that phone-scam detection relies on crowdsourced reports, so this does not guarantee the number is safe.",
                ],
            ];
        }

        $weightedScore = $explicitScore + min($lookupScore, 0.4);
        $mostRecentReport = collect($entries)->where('explicit', true)->max('when');
        $recencyNote = 'Most recent report: '.$mostRecentReport->format('j F Y').'.';

        if ($weightedScore < 1) {
            return [
                'flagged' => true,
                'points' => 8,
                'reasons' => [
                    "This number was reported {$reporterCount} time(s) on PhishCore, but the reports are not recent — the number may since have been reassigned to a different subscriber by the telco.",
                    $recencyNote,
                ],
            ];
        }

        if ($weightedScore < 2) {
            return [
                'flagged' => true,
                'points' => 15,
                'reasons' => [
                    "This number has been reported by {$reporterCount} user(s) on PhishCore.",
                    $recencyNote,
                ],
            ];
        }

        if ($weightedScore < 4) {
            return [
                'flagged' => true,
                'points' => 30,
                'reasons' => [
                    "This number has been reported by {$reporterCount} different users on PhishCore, including recent reports.",
                    $recencyNote,
                ],
            ];
        }

        return [
            'flagged' => true,
            'points' => 45,
            'reasons' => [
                "This number has been reported by {$reporterCount} different users on PhishCore — repeatedly and recently flagged.",
                $recencyNote,
            ],
        ];
    }

    /**
     * Checks phone number reputation via AbstractAPI's Phone Intelligence
     * API — an independent, carrier-backed signal to sit alongside
     * PhishCore's own crowdsourced report history. checkPhoneNumber() only
     * validates FORMAT and numbering-plan assignment, which is static,
     * offline data derived purely from the number's shape. This instead
     * queries a live third-party risk assessment.
     *
     * Field paths confirmed against a real response (not assumed from
     * marketing docs, which showed a different/inconsistent shape):
     * risk data lives under phone_risk.{risk_level,is_disposable,
     * is_abuse_detected}, and validity/line status under
     * phone_validation.{is_valid,line_status} — NOT top-level "valid" or
     * "risk_score" as an earlier version of this check assumed, which
     * caused every successful response to be misread as an error.
     *
     * Skip-safe like the other optional external checks (VirusTotal,
     * Google Safe Browsing) — if no API key is configured, this is
     * silently skipped rather than failing the scan.
     */
    public function checkPhoneReputation(string $phone): array
    {
        $apiKey = config('services.abstractapi_phone.key');

        $empty = [
            'flagged' => false,
            'points' => 0,
            'reasons' => [],
            'unavailable' => true,
        ];

        if (! $apiKey) {
            $empty['reasons'][] = 'Phone reputation check skipped: no API key configured';

            return $empty;
        }

        $normalized = preg_replace('/[^\d+]/', '', $phone);

        // the free plan allows few lookups, so a number looked up recently reuses the saved answer instead of spending another one
        $cacheKey = 'phone-reputation:'.md5($normalized);
        $saved = Cache::get($cacheKey);
        if (is_array($saved)) {
            return $saved;
        }

        try {
            $response = Http::timeout(10)->get('https://phoneintelligence.abstractapi.com/v1/', [
                'api_key' => $apiKey,
                'phone' => $normalized,
            ]);

            $data = $response->json();

            if (! $response->successful() || ! is_array($data) || ! isset($data['phone_risk'])) {
                // never show the vendor's own wording (for example a "subscribe to a paid plan" quota message) to users
                $empty['reasons'][] = 'The phone reputation service could not be reached right now, so this check was skipped. The other checks still ran.';

                return $empty;
            }

            $riskLevel = $data['phone_risk']['risk_level'] ?? null;
            $isDisposable = $data['phone_risk']['is_disposable'] ?? false;
            $isAbuseDetected = $data['phone_risk']['is_abuse_detected'] ?? false;
            $isValid = $data['phone_validation']['is_valid'] ?? true;
            $lineStatus = $data['phone_validation']['line_status'] ?? null;

            $reasons = [];
            $points = 0;

            if ($riskLevel !== null) {
                if (strtolower($riskLevel) === 'high') {
                    $reasons[] = 'AbstractAPI flags this number as HIGH risk';
                    $points += 35;
                } elseif (strtolower($riskLevel) === 'medium') {
                    $reasons[] = 'AbstractAPI flags this number as MEDIUM risk';
                    $points += 18;
                }
            }

            if ($isDisposable) {
                $reasons[] = 'AbstractAPI flags this as a disposable/temporary number';
                $points += 25;
            }

            if ($isAbuseDetected) {
                $reasons[] = 'AbstractAPI has recorded abuse associated with this number';
                $points += 35;
            }

            // Informational only — an invalid/inactive number isn't
            // double-penalized here since checkPhoneNumber() already
            // scores format/numbering-plan validity independently.
            if (! $isValid) {
                $reasons[] = 'AbstractAPI reports this number as not currently valid';
            } elseif ($lineStatus && strtolower($lineStatus) !== 'active') {
                $reasons[] = "Line status: {$lineStatus}";
            }

            if (empty($reasons)) {
                $reasons[] = 'AbstractAPI: no significant risk flags for this number';
            }

            $result = [
                'flagged' => $points > 0,
                'points' => min(60, $points),
                'reasons' => $reasons,
            ];

            // only real answers are saved, so an error such as a used-up quota is never remembered
            Cache::put($cacheKey, $result, now()->addDays(7));

            return $result;
        } catch (\Throwable $e) {
            $empty['reasons'][] = 'Could not reach AbstractAPI';

            return $empty;
        }
    }

    /** A single kind of evidence only stands alone when it is at least this strong (the same scale as the check points). */
    /** Tactics that ordinary mail uses too (password resets, invoices, newsletters): they never corroborate each other. */
    private const EVERYDAY_WORDING = ['urgency', 'credential_request', 'financial_request', 'call_to_action'];

    private const STRONG_ON_ITS_OWN = 35;

    /** The most a result can score when its only evidence is one weak kind: just under the "suspicious" line. */
    private const LONE_SIGNAL_CEILING = 24;

    /**
     * One weak kind of evidence on its own is not enough to call a message suspicious. A real password-reset
     * email asks you to log in, a real parcel notice mentions a fee, a real offer says "limited time": each
     * is one sign, and scams are the messages where several different signs appear together (a wrong sender,
     * pressure wording, a link that does not match, a failed sender check). So when only one kind of evidence
     * scored, and it is not strong by itself, the result stays below the suspicious line.
     *
     * This is a general rule, not a list of exceptions, so it also covers emails nobody has seen yet. It only
     * applies to text checks (emails and messages), where each kind of evidence is counted separately.
     *
     * The wording check can find several different scam tactics in one text (pressure and a request to keep it
     * secret, say). Each tactic beyond the first counts as another kind of evidence, since the wording check
     * already treats them as a pattern that "appears together".
     *
     * @param  array<int, int|float>  $categoryPoints  the points of each kind of evidence, such as sender, wording, brand
     * @param  int  $extraKinds  further kinds of evidence inside one category (scam tactics beyond the first, or several failed sender checks)
     */
    private function requireCorroboration(int $total, array $categoryPoints, int $extraKinds = 0): int
    {
        $scored = array_filter($categoryPoints, fn ($points): bool => $points > 0);

        if (count($scored) + $extraKinds === 1 && max($scored) < self::STRONG_ON_ITS_OWN) {
            return min($total, self::LONE_SIGNAL_CEILING);
        }

        return $total;
    }

    /**
     * Rules-only score for a message's text: the same text steps a screenshot scan runs (wording,
     * brand vs link, a pressure message with a link, attachments, and 0.6 x the link's address
     * check), without any network lookup, phone lookup or AI check. Used by the Brunei test set
     * (php artisan scan:brunei) so its results are repeatable; a real scan can only score higher.
     *
     * @return array{score: int, verdict: string, reasons: array<int, string>, url: ?string, brand: ?string}
     */
    public function scoreMessageRules(string $text): array
    {
        $urls = array_values(array_filter($this->extractAllUrlsFromText($text), fn ($u) => filter_var($u, FILTER_VALIDATE_URL)));
        if ($urls === []) {
            $urls = array_map(fn ($domain) => 'https://'.$domain, $this->extractBareDomainsFromText($text));
        }
        usort($urls, fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']);
        $url = $urls[0] ?? null;

        $brand = $this->detectBrandInText($text);
        $content = $this->checkContentPatterns($text);
        $attachment = $this->detectAttachment($text);

        $points = 0;
        $reasons = [];
        foreach ([$content, $attachment] as $result) {
            if ($result['flagged']) {
                $points += $result['points'];
                $reasons = array_merge($reasons, $result['reasons']);
            }
        }
        if ($brand['brand'] && $url && ($host = parse_url($url, PHP_URL_HOST))) {
            $mismatch = $this->checkPageBrandMismatch($brand['brand'], $brand['surface'], $host);
            if ($mismatch['flagged']) {
                $points += $mismatch['points'];
                $reasons = array_merge($reasons, $mismatch['reasons']);
            }
        }
        $pressure = $this->checkLinkInPressureMessage($content, $url);
        if ($pressure['flagged']) {
            $points += $pressure['points'];
            $reasons = array_merge($reasons, $pressure['reasons']);
        }
        if ($url) {
            $syntax = $this->checkUrlSyntax($url);
            $points += (int) round($syntax['points'] * 0.6);
            $reasons = array_merge($reasons, $syntax['reasons']);
        }

        $score = $this->requireCorroboration(min(100, $points), [
            $content['flagged'] ? $content['points'] : 0,
            $attachment['flagged'] ? $attachment['points'] : 0,
            isset($mismatch) && $mismatch['flagged'] ? $mismatch['points'] : 0,
            $pressure['flagged'] ? $pressure['points'] : 0,
            $url ? round($syntax['points'] * 0.6) : 0,
        ], $this->wordingKinds($content) - 1);
        $hasEvidence = $url || $brand['brand'] || $content['flagged'] || $attachment['flagged'];
        $verdict = ! $hasEvidence ? 'review' : ($score >= 60 ? 'phishing' : ($score >= 25 ? 'suspicious' : 'clean'));

        return ['score' => $score, 'verdict' => $verdict, 'reasons' => $reasons, 'url' => $url, 'brand' => $brand['brand']];
    }

    /**
     * Rules-only score for an email: the sender address, the wording and the brand-vs-sender
     * check, exactly as the email scan does them, without the domain-age lookup, earlier reports
     * or the AI check. Used for repeatable offline tests; a real scan can only score higher.
     *
     * @return array{score: int, reasons: array<int, string>}
     */
    public function scoreEmailRules(string $sender, string $subject, string $body): array
    {
        $text = trim($subject.' '.$body);
        $sender = $sender !== '' ? $sender : 'unknown@example.com';
        $domain = $this->checkEmailDomain($sender);
        $content = $this->checkContentPatterns($text);
        $brand = $this->detectBrandInText($text);
        $brandResult = $this->brandSpeaksInMessage($text, $brand, $content)
            ? $this->checkBrandSenderMismatch($brand['brand'], $brand['surface'], $sender)
            : $this->brandOnlyMentioned($brand['brand']);

        $links = $this->checkEmailLinks($text, $domain['domain'] ?? null);

        $score = $this->requireCorroboration(min(100, $domain['points'] + $content['points'] + $brandResult['points'] + $links['points']), [
            $domain['points'], $content['points'], $brandResult['points'], $links['points'],
        ], $this->wordingKinds($content) - 1);
        $reasons = array_merge($domain['reasons'], $content['reasons'], $brandResult['points'] > 0 ? $brandResult['reasons'] : [], $links['reasons']);

        return ['score' => $score, 'reasons' => $reasons];
    }

    private function analyzeScreenshot(string $imagePath, ?int $reportId = null): array
    {
        $ocr = $this->checkScreenshotOcr($imagePath);

        if (! $ocr['success']) {
            return [
                'risk_score' => 0,
                'verdict' => 'review',
                'domain_age_days' => null,
                'url_syntax_score' => null,
                'checks' => [[
                    'name' => 'Screenshot Text Extraction',
                    'status' => 'REVIEW',
                    'message' => 'Could not read text from this image ('.($ocr['error'] ?? 'unknown error').'). Manual review recommended.',
                    'points' => 0,
                ]],
            ];
        }

        $text = $ocr['text'];

        $qrUrl = $this->decodeQrCode($imagePath);

        $candidateUrlsRaw = $this->extractAllUrlsFromText($text);
        if ($qrUrl) {
            $candidateUrlsRaw[] = $qrUrl;
        }
        $candidateUrls = array_values(array_unique(array_filter($candidateUrlsRaw, fn ($u) => filter_var($u, FILTER_VALIDATE_URL))));
        $extractedUrl = null;
        if (! empty($candidateUrls)) {
            usort($candidateUrls, fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']);
            $extractedUrl = $candidateUrls[0];
        }
        $urlCameFromQr = $qrUrl && $extractedUrl === $qrUrl;

        // No full link anywhere? Scammers often write the address without "https://" (e.g. "polydrop.top").
        $urlWasBare = false;
        if ($extractedUrl === null) {
            $bareDomains = array_map(fn ($domain) => 'https://'.$domain, $this->extractBareDomainsFromText($text));
            if (! empty($bareDomains)) {
                usort($bareDomains, fn ($a, $b) => $this->checkUrlSyntax($b)['points'] <=> $this->checkUrlSyntax($a)['points']);
                $extractedUrl = $bareDomains[0];
                $urlWasBare = true;
            }
        }

        $candidateEmails = $this->extractAllEmailsFromText($text);
        $extractedEmail = null;
        if (! empty($candidateEmails)) {
            usort($candidateEmails, fn ($a, $b) => $this->checkEmailDomain($b)['points'] <=> $this->checkEmailDomain($a)['points']);
            $extractedEmail = $candidateEmails[0];
        }

        $candidatePhones = $this->extractAllPhonesFromText($text);
        $extractedPhone = null;
        $phoneCheckResult = null;
        foreach ($candidatePhones as $candidate) {
            $result = $this->checkPhoneNumber($candidate);
            if ($result['unparseable'] ?? false) {
                continue;
            }
            if ($phoneCheckResult === null || $result['points'] > $phoneCheckResult['points']) {
                $extractedPhone = $candidate;
                $phoneCheckResult = $result;
            }
        }

        $brandDetection = $this->detectBrandInText($text);
        $detectedBrand = $brandDetection['brand'];
        $brandSurfaceText = $brandDetection['surface'];
        $contentResult = $this->checkContentPatterns($text);
        $attachmentResult = $this->detectAttachment($text);
        $aiResult = $this->checkAiText($text, $contentResult['points']);
        $hasAnyEvidence = $extractedUrl || $extractedEmail || $extractedPhone || $detectedBrand || $contentResult['flagged'] || $attachmentResult['flagged']
            || ($aiResult['flagged'] ?? false);

        $checks = [];
        $totalPoints = 0;
        $signalCategories = 0;
        $ageResult = ['domain_age_days' => null];
        $extractedUrlCti = null;

        $extractionParts = [];
        if ($extractedUrl) {
            $extractionParts[] = $urlWasBare
                ? 'Link written without https://: '.preg_replace('~^https://~', '', $extractedUrl)
                : (($urlCameFromQr ? 'URL (from QR code): ' : 'URL: ').$extractedUrl);
        }
        if ($extractedEmail) {
            $extractionParts[] = "Sender: {$extractedEmail}";
        }
        if ($extractedPhone) {
            $extractionParts[] = "Phone number: {$extractedPhone}";
        }
        if ($detectedBrand) {
            $extractionParts[] = 'Brand referenced: '.ucfirst($detectedBrand);
        }

        $checks[] = [
            'name' => 'Screenshot Text Extraction',
            'status' => $hasAnyEvidence ? 'INFO' : 'REVIEW',
            'message' => $hasAnyEvidence
                ? ('Extracted from image: '.implode(' | ', $extractionParts ?: ['phishing-style language']))
                : 'No URL, email address, phone number, brand reference, QR code, or phishing-style language was found in the image. Extracted text: "'.Str::limit($text, 200).'"',
            'points' => 0,
        ];

        if ($extractedEmail) {
            $emailResult = $this->checkEmailDomain($extractedEmail);
            $domain = $emailResult['domain'];
            $ageResult = $domain
                ? $this->checkDomainAge($domain)
                : ['flagged' => false, 'points' => 0, 'domain_age_days' => null, 'reasons' => []];

            $totalPoints += $emailResult['points'] + $ageResult['points'];
            if ($emailResult['points'] > 0 || $ageResult['points'] > 0) {
                $signalCategories++;
            }

            $checks[] = $this->buildCheck('Sender Domain Analysis', $emailResult, 'SUSPICIOUS');
            if ($domain) {
                $checks[] = $this->buildCheck('Domain Age', $ageResult, $ageResult['points'] >= 40 ? 'HIGH RISK' : 'SUSPICIOUS');
            }
        }

        if ($extractedPhone && $phoneCheckResult) {
            $totalPoints += (int) round($phoneCheckResult['points'] * 0.7);
            if ($phoneCheckResult['points'] > 0) {
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('Phone Number Analysis', $phoneCheckResult, 'SUSPICIOUS');

            $phoneHistoryResult = $this->checkPreviousReports($extractedPhone, $reportId);
            if ($phoneHistoryResult['flagged'] ?? false) {
                $totalPoints += (int) round($phoneHistoryResult['points'] * 0.7);
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('Previous Reports (Phone)', $phoneHistoryResult, $phoneHistoryResult['points'] >= 45 ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        if ($detectedBrand) {
            $brandCheckLabel = 'Brand / Domain Correlation';
            if ($extractedEmail) {
                $brandDomainResult = $this->brandSpeaksInMessage($text, $brandDetection, $contentResult)
                    ? $this->checkBrandSenderMismatch($detectedBrand, $brandSurfaceText, $extractedEmail)
                    : $this->brandOnlyMentioned($detectedBrand);
                $brandCheckLabel = 'Brand / Sender Correlation';
            } elseif ($extractedUrl && ($urlHost = parse_url($extractedUrl, PHP_URL_HOST))) {
                $brandDomainResult = $this->checkPageBrandMismatch($detectedBrand, $brandSurfaceText, $urlHost);
            } else {
                $brandDomainResult = [
                    'flagged' => false,
                    'points' => 0,
                    'reasons' => ['Brand "'.ucfirst($detectedBrand).'" referenced in image text, but no URL or sender email was extracted to verify it against'],
                ];
            }

            if ($brandDomainResult['flagged']) {
                $totalPoints += $brandDomainResult['points'];
                $signalCategories++;
            }
            $checks[] = $this->buildCheck($brandCheckLabel, $brandDomainResult, 'HIGH RISK');
        }

        if ($contentResult['flagged']) {
            $totalPoints += $contentResult['points'];
            $signalCategories++;
        }
        $checks[] = $this->buildCheck('Message Content / Behavior Patterns', $contentResult, $contentResult['matched_categories'] >= 3 ? 'HIGH RISK' : 'SUSPICIOUS');

        if ($aiResult !== null) {
            if ($aiResult['flagged']) {
                $totalPoints += $aiResult['points'];
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('AI Message Review', $aiResult, $aiResult['verdict'] === 'scam' ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        $pressureLink = $this->checkLinkInPressureMessage($contentResult, $extractedUrl);
        if ($pressureLink['flagged']) {
            $totalPoints += $pressureLink['points'];
            $signalCategories++;
            $checks[] = $this->buildCheck('Link in Pressure Message', $pressureLink, 'SUSPICIOUS');
        }

        if ($attachmentResult['flagged']) {
            $totalPoints += $attachmentResult['points'];
            $signalCategories++;
        }
        if ($attachmentResult['flagged'] || ($attachmentResult['neutral'] ?? false)) {
            $checks[] = $this->buildCheck('Attachment', $attachmentResult, 'SUSPICIOUS');
        }

        if ($extractedUrl) {
            $urlAnalysis = $this->analyzeUrl($extractedUrl, $reportId);
            $totalPoints += (int) round($urlAnalysis['risk_score'] * 0.6);
            $signalCategories++;
            $checks = array_merge($checks, $urlAnalysis['checks']);
            $extractedUrlCti = $urlAnalysis['cti'] ?? null;
        }

        if ($extractedEmail) {
            $emailDomain = $this->checkEmailDomain($extractedEmail)['domain'];
            $domainHistoryResult = $this->checkPreviousDomainReports($emailDomain, $reportId);
            if ($domainHistoryResult['flagged'] ?? false) {
                $totalPoints += $domainHistoryResult['points'];
                $signalCategories++;
            }
            $checks[] = $this->buildCheck('Previous Reports (Domain)', $domainHistoryResult, $domainHistoryResult['points'] >= 50 ? 'HIGH RISK' : 'SUSPICIOUS');
        }

        $riskScore = min(100, $totalPoints);
        $verdict = ! $hasAnyEvidence ? 'review' : ($riskScore >= 60 ? 'phishing' : ($riskScore >= 25 ? 'suspicious' : 'clean'));
        $confidence = $hasAnyEvidence ? min(95, 40 + $signalCategories * 13) : 20;

        $result = [
            'risk_score' => $riskScore,
            'confidence' => $confidence,
            'verdict' => $verdict,
            'domain_age_days' => $ageResult['domain_age_days'] ?? null,
            'url_syntax_score' => null,
            'checks' => $checks,
            'extracted_url' => $extractedUrl,
            'extracted_email' => $extractedEmail,
            'extracted_phone' => $extractedPhone,
        ];

        if (! empty($extractedUrlCti)) {
            $result['cti'] = $extractedUrlCti;
        }

        return $result;
    }
}