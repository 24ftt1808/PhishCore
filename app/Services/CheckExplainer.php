<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Turns the engine's technical check names, statuses and messages into plain words for the
 * result page. It only changes how a result is shown. The scores and the stored results are
 * never touched, and the technical names stay in the report and the technical section.
 */
class CheckExplainer
{
    /** Friendly question for each check. */
    private const TITLES = [
        'SSL Certificate' => 'Is the connection secure?',
        'Domain Age' => 'How old is the website?',
        'URL Structure' => 'Does the link look normal?',
        'Blacklist Database' => 'Is it on a known scam list?',
        'VirusTotal / CTI Check' => 'What do security companies say?',
        'IP Reputation & Location' => 'Where is the website hosted?',
        'Redirect Chain' => 'Does the link send you somewhere else?',
        'Page Content Analysis' => 'What does the page ask you to do?',
        'Hosting Platform' => 'Is it on a free website builder?',
        'Site Availability' => 'Is the website still online?',
        'Previous Reports (Domain)' => 'Has anyone reported this website before?',
        'Previous Reports (Phone)' => 'Has anyone reported this number before?',
        'Previous Reports' => 'Has anyone reported this number before?',
        'AI Page Review' => 'What does an AI think of the page?',
        'AI Message Review' => 'What does an AI think of the message?',
        'Sender Domain Analysis' => 'Does the sender address look real?',
        'Message Content / Behavior Patterns' => 'Does the message use scam tricks?',
        'Brand / Sender Correlation' => 'Is the sender really who they say they are?',
        'Phone Number Analysis' => 'Does the phone number look normal?',
        'Phone Reputation (AbstractAPI)' => 'Is the number linked to spam or fraud?',
        'Link in Pressure Message' => 'Does the message push you to click a link?',
        'Attachment' => 'Is there a risky attachment?',
        'Screenshot Text Extraction' => 'What does the text in the picture say?',
    ];

    /** One or two plain sentences on what each check looks at. */
    private const MEANINGS = [
        'SSL Certificate' => 'Secure websites use HTTPS, which shows as a padlock in your browser and keeps what you type private. Scam sites often skip it. A padlock alone does not prove a site is safe.',
        'Domain Age' => 'Scammers often set up a new website and drop it within days. Older websites are usually more trustworthy.',
        'URL Structure' => 'We look for tricks in the link itself, such as numbers instead of a name, copycat spellings of real brands, or lots of dashes.',
        'Blacklist Database' => 'We ask Google Safe Browsing whether it already knows this link as dangerous.',
        'VirusTotal / CTI Check' => 'VirusTotal asks dozens of security companies about the link. One or two flags can be a mistake. Many flags is serious.',
        'IP Reputation & Location' => 'Every website lives on a computer called a server. We check where it is and whether it is the kind of server scammers like to use.',
        'Redirect Chain' => 'Some links quietly pass you through other addresses before showing a page, to hide where you really end up.',
        'Page Content Analysis' => 'We read the page and look for password boxes, urgent threats, hidden tricks, or pretending to be a bank or company.',
        'Hosting Platform' => 'Anyone can make a free website on a website builder. Scammers often use them because it is quick and free.',
        'Site Availability' => 'Scam websites are often taken down fast. A site that is gone cannot harm you now, but the message that sent it may still be a scam.',
        'Previous Reports' => 'We check whether someone has already reported this to PhishCore. This is for information and does not change the score.',
        'AI Page Review' => 'An AI reads the page and gives a second opinion. It can be wrong, so it is only one signal among many.',
        'AI Message Review' => 'An AI reads the message and gives a second opinion. It can be wrong, so it is only one signal among many.',
        'Sender Domain Analysis' => 'The part of an email address after the @ should match the real company. Scammers use look-alike addresses.',
        'Message Content / Behavior Patterns' => 'We look for common scam tricks: rushing you, threats, prizes, or asking for codes and money.',
        'Brand / Sender Correlation' => 'If a message says it is from a bank but the sender address is not the bank\'s, that is a red flag.',
        'Phone Number Analysis' => 'We check whether the number is valid, what kind of line it is and which country it comes from.',
        'Phone Reputation' => 'An outside service tells us whether this number has been linked to spam or fraud.',
        'Link in Pressure Message' => 'A message that rushes you and includes a link to an unknown website is a classic scam.',
        'Attachment' => 'Some file types can install harmful software when you open them.',
        'Screenshot Text Extraction' => 'We read the words in your picture so we can check them the same way as a typed message.',
    ];

    private const STATUS_LABELS = [
        'SAFE' => 'Looks fine',
        'LIVE' => 'Online',
        'SUSPICIOUS' => 'Be careful',
        'HIGH RISK' => 'Danger',
        'DETECTED' => 'Flagged',
        'UNKNOWN' => "Couldn't check",
        'REVIEW' => 'Needs a person to check',
        'OFFLINE' => 'Offline',
        'TAKEN DOWN' => 'Taken down',
    ];

    /** Technical wording and its plain replacement, longest and most specific first. */
    private const PLAIN_WORDS = [
        'URL uses a raw IP address instead of a domain name' => 'The link uses a string of numbers (an IP address) instead of a normal website name',
        'Website does not use HTTPS (no SSL encryption)' => 'This website does not use a secure connection (HTTPS), so what you type could be seen by others',
        'SSL certificate has expired' => "The website's security certificate has expired",
        'SSL certificate does not match the domain' => "The website's security certificate belongs to a different website",
        'Could not verify a valid, trusted SSL certificate for this domain' => "We couldn't confirm that this website's security certificate is genuine",
        'Could not connect to verify SSL (unrelated to certificate validity)' => "We couldn't connect to check the website's security certificate",
        'Could not verify SSL certificate due to an unexpected error' => "We couldn't check the website's security certificate",
        'WHOIS data unavailable for this domain' => "We couldn't look up when this website was created",
        'Could not retrieve WHOIS data' => "We couldn't look up when this website was created",
        'Domain was registered only' => 'The website was created only',
        'Domain was registered' => 'The website was created',
        'Domain is relatively new' => 'The website is fairly new',
        'a strong phishing indicator' => 'a strong sign of a scam site',
        'recently registered domains are higher risk' => 'brand-new websites are riskier',
        'security vendors on VirusTotal' => 'security companies (checked through VirusTotal)',
        'security vendors' => 'security companies',
        'Only a very small share of vendors flagged it.' => 'Only a very small number of them flagged it.',
        'This is often a false alarm on well-known, legitimate sites, so it counts as a minor signal.' => 'This is often a false alarm on well-known, genuine sites, so it counts as a small warning only.',
        'Could not resolve domain to an IP address.' => "We couldn't find the server this website runs on.",
        'IP address is associated with a known proxy or VPN service, often used to mask phishing infrastructure' => 'The website runs on a service that hides who is behind it, which scammers often use',
        'IP address belongs to a datacenter/hosting provider rather than a residential ISP' => 'The website runs on a company that rents out servers, not a home internet provider',
        'no proxy or datacenter flags' => 'nothing unusual about the server',
        'URL redirects through' => 'The link passes you through',
        'before reaching its final destination, an unusually long chain' => 'before reaching the final page, which is unusually many',
        'URL ultimately redirects to a different domain' => 'The link ends up at a different website',
        'than the one submitted' => 'than the one you entered',
        'No redirects detected.' => 'The link goes straight to its page.',
        'Page uses a meta-refresh tag to automatically redirect visitors to a different domain' => 'The page automatically sends visitors to a different website',
        'Content check skipped: URL points directly at a private/internal address.' => "We didn't look inside this page because the address points to a private network, not the public internet.",
        'Page contains a password input field' => 'The page has a box asking for a password',
        'credential harvesting' => 'stealing passwords',
        'Page contains a hidden iframe (zero-size or display:none/visibility:hidden)' => 'The page has a hidden box that loads other content without you seeing it',
        'obfuscated JavaScript' => 'scrambled hidden code',
        'This domain (' => 'This website address (',
        ') no longer resolves' => ') no longer exists on the internet',
        'it may have expired, been suspended, or been taken down entirely' => 'it may have expired, been shut down, or been removed',
        'Site is currently live and reachable' => 'The website is online',
        'likely brand impersonation' => 'likely pretending to be that company',
        'lookalike/typosquat attempt' => 'a copycat of that name',
        'phishing-style lure language' => 'scam-style wording',
        'phishing-style lure phrases' => 'scam-style wording',
        'Not a valid email address format' => "This doesn't look like a proper email address",
        'Sender domain contains' => 'The sender address contains',
        'No prior reports found for' => 'Nobody has reported',
        'on PhishCore' => 'to PhishCore before',
        '(Informational only — this does not change the risk score.)' => '(For information only. This does not change the score.)',
        'VoIP/internet-based line' => 'internet phone line',
        'smishing' => 'scam text message',
        'HTTP ' => 'web server code ',
    ];

    public static function title(string $name): string
    {
        return self::TITLES[$name] ?? $name;
    }

    /** True when the check has a friendlier title than its technical name. */
    public static function hasFriendlyTitle(string $name): bool
    {
        return isset(self::TITLES[$name]);
    }

    public static function meaning(string $name): ?string
    {
        if (isset(self::MEANINGS[$name])) {
            return self::MEANINGS[$name];
        }

        foreach (['Previous Reports', 'Phone Reputation', 'AI '] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return self::MEANINGS[$prefix === 'AI ' ? (str_contains($name, 'Page') ? 'AI Page Review' : 'AI Message Review') : $prefix];
            }
        }

        return null;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(strtolower($status));
    }

    /** warn = something looks wrong, ok = looks fine, info = no answer or just information. */
    public static function group(string $status): string
    {
        return match ($status) {
            'SUSPICIOUS', 'HIGH RISK', 'DETECTED' => 'warn',
            'SAFE', 'LIVE' => 'ok',
            default => 'info',
        };
    }

    /** The engine's sentence with the technical words swapped for everyday ones. */
    public static function plain(string $message): string
    {
        return strtr($message, self::PLAIN_WORDS);
    }

    /**
     * One sentence for the top of the checks, such as "We ran 9 checks. 2 raised a warning, ...".
     *
     * @param  Collection<int, array<string, mixed>>  $checks
     */
    public static function summary(Collection $checks): string
    {
        $counts = $checks->countBy(fn ($check) => self::group((string) ($check['status'] ?? '')));
        $total = $checks->count();
        $warn = (int) ($counts['warn'] ?? 0);
        $ok = (int) ($counts['ok'] ?? 0);
        $info = (int) ($counts['info'] ?? 0);

        $parts = [];
        if ($warn > 0) {
            $parts[] = $warn.($warn === 1 ? ' raised a warning' : ' raised warnings');
        }
        if ($ok > 0) {
            $parts[] = $ok.' look'.($ok === 1 ? 's' : '').' fine';
        }
        if ($info > 0) {
            $parts[] = $info.' could not be checked or are just for information';
        }

        return 'We ran '.$total.($total === 1 ? ' check' : ' checks').'. '.ucfirst(implode(', ', $parts)).'.';
    }
}