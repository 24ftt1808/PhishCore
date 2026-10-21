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
    /** Short, clear heading for each check. */
    private const TITLES = [
        'SSL Certificate' => 'Secure connection',
        'Domain Age' => 'Website age',
        'URL Structure' => 'Link appearance',
        'Blacklist Database' => 'Known threat lists',
        'VirusTotal / CTI Check' => 'Security vendor results',
        'IP Reputation & Location' => 'Server and hosting',
        'Redirect Chain' => 'Link redirects',
        'Page Content Analysis' => 'Page content',
        'Hosting Platform' => 'Free website builder',
        'Site Availability' => 'Website status',
        'Previous Reports (Domain)' => 'Previous reports for this website',
        'Previous Reports (File)' => 'Previous reports for this file',
        'Previous Reports (Phone)' => 'Previous reports for this number',
        'Previous Reports' => 'Previous reports for this number',
        'AI Page Review' => 'AI review of the page',
        'AI Message Review' => 'AI review of the message',
        'Sender Domain Analysis' => 'Sender address',
        'Message Content / Behavior Patterns' => 'Scam warning signs in the message',
        'Brand / Sender Correlation' => 'Sender identity',
        'Brand / Domain Correlation' => 'Brand and website match',
        'Phone Number Analysis' => 'Phone number details',
        'Phone Reputation (AbstractAPI)' => 'Phone number reputation',
        'Link in Pressure Message' => 'Link in an urgent message',
        'Attachment' => 'Attachments',
        'Screenshot Text Extraction' => 'Text found in the image',
        'File Type' => 'What the file really is',
        'VirusTotal File Fingerprint' => 'Known bad file lookup',
        'App Permissions' => 'What the app can do',
        'PDF Contents' => 'Links and actions in the PDF',
        'PDF Text' => 'Wording inside the PDF',
        'Admin Review' => 'Checked by an administrator',
    ];

    /** A short explanation of what each check looks at and why it matters. */
    private const MEANINGS = [
        'SSL Certificate' => 'Genuine websites use a secure connection (HTTPS, shown as a padlock in the browser) so that what you type stays private. Many scam sites also use one, so a padlock alone does not mean a website is safe.',
        'Domain Age' => 'Scam websites are usually created shortly before an attack and abandoned within days. A new website is therefore a warning sign, while a long-established one is generally more trustworthy.',
        'URL Structure' => 'We examine the link itself for common tricks: numbers in place of a name, misspellings of well-known brands, or an unusual number of hyphens.',
        'Blacklist Database' => 'We check the link against Google Safe Browsing, a list of websites already known to be dangerous. New scam websites often take time to appear on it.',
        'VirusTotal / CTI Check' => 'VirusTotal checks the link with dozens of security companies at once. One or two flags may be a false alarm; several flags is a serious warning.',
        'IP Reputation & Location' => 'Every website runs on a server. We check where that server is and whether it uses services that scammers commonly rely on to hide their identity.',
        'Redirect Chain' => 'Some links pass you through other addresses before the final page appears, to hide where you actually end up. We follow the link to see where it leads.',
        'Page Content Analysis' => 'We read the page for warning signs such as password or card forms, urgent threats, hidden content, or imitation of a bank or company.',
        'Hosting Platform' => 'Free website builders let anyone publish a site in minutes without verifying who they are. Scammers use them for that reason, although many genuine sites do too.',
        'Site Availability' => 'Scam websites are often taken down quickly. A website that is offline cannot harm you now, but the message that contained the link may still be a scam.',
        'Previous Reports' => 'We check whether other PhishCore users have reported the same item before.',
        'AI Page Review' => 'An AI model reads the page and gives a second opinion. It is one signal among many and can make mistakes, so it can only raise the score, never lower it.',
        'AI Message Review' => 'An AI model reads the message and gives a second opinion. It is one signal among many and can make mistakes, so it can only raise the score, never lower it.',
        'Sender Domain Analysis' => 'The part of an email address after the @ should belong to the organisation it claims to be from. Scammers often use addresses that only look similar.',
        'Message Content / Behavior Patterns' => 'We look for the tactics scams rely on: pressure to act quickly, threats, prizes, and requests for money, codes or personal details.',
        'Brand / Sender Correlation' => 'If a message claims to come from a bank or company but the sender address does not belong to it, the sender is likely an impersonator.',
        'Brand / Domain Correlation' => 'If a page or message uses a company\'s name but the website does not belong to that company, it is likely impersonating it.',
        'Phone Number Analysis' => 'We check whether the number is valid, what type of line it is, and which country it belongs to.',
        'Phone Reputation' => 'An external service reports whether this number has been linked to spam or fraud.',
        'Link in Pressure Message' => 'A message that pressures you to act quickly and links to an unrecognised website is one of the most common scam patterns.',
        'Attachment' => 'Some file types can install harmful software as soon as they are opened. A screenshot only shows a file\'s name, not what is inside it, so this check judges the name and type only. To check inside an APK or PDF, upload the file itself in Scan File.',
        'Screenshot Text Extraction' => 'We read the text in your image so it can be checked in the same way as a typed message. This card is for information and does not affect the score.',
        'File Type' => 'We look inside the file to see what it really is, because scam apps are often renamed to look like a document or a picture.',
        'VirusTotal File Fingerprint' => 'We make a fingerprint of the file and ask VirusTotal, a service run by dozens of security companies, whether it already knows that file. The file itself is never sent. A file it has never seen is not proof that the file is safe.',
        'App Permissions' => 'An Android app lists what it is allowed to do. Fake bank and parcel apps often ask to read your text messages, control your screen or draw over other apps so they can steal one-time codes. Some real apps need these too, so check where the app came from.',
        'PDF Contents' => 'We look in the PDF for links and for hidden actions, such as starting a program or running a script. Content packed inside compressed parts of a PDF cannot be seen by this check.',
        'Admin Review' => 'A PhishCore administrator looked at this exact file (matched by its fingerprint) and marked it as safe, for example because a scan called a real app suspicious by mistake.',
        'PDF Text' => 'We read the words written in the PDF and look for the same scam wording we look for in messages, such as urgent demands, requests for codes or bank details, and threats. Only our own rules are used, and the text is never sent to an AI service. Scanned pictures of text cannot be read.',
    ];

    private const STATUS_LABELS = [
        'SAFE' => 'No issues found',
        'LIVE' => 'Online',
        'SUSPICIOUS' => 'Caution',
        'HIGH RISK' => 'High risk',
        'DETECTED' => 'Flagged',
        'UNKNOWN' => 'Unavailable',
        'INFO' => 'Information',
        'REVIEW' => 'Manual review',
        'OFFLINE' => 'Offline',
        'TAKEN DOWN' => 'Taken down',
    ];

    /** Technical wording and its plain replacement, longest and most specific first. */
    private const PLAIN_WORDS = [
        // Scam warning signs in messages. Labels ending in ")" get their own full stop here.
        'Urgency language detected (e.g. "urgent", "24 hours", "act now", "segera")' => 'It pressures you to act quickly (for example "urgent", "within 24 hours", "act now" or "segera").',
        'Account threat language detected (suspension/deactivation, "akaun disekat")' => 'It threatens to suspend or close your account (for example "akaun disekat").',
        'Prize/reward language detected (e.g. "you have won", "claim your reward", "tahniah anda menang", "cabutan bertuah")' => 'It promises a prize or reward (for example "you have won", "claim your reward" or "tahniah anda menang").',
        'Suspicious call-to-action phrasing detected (click/follow link)' => 'It urges you to click or follow a link.',
        'Authority-impersonation language detected (police/Interpol/investigation notice, "safe account")' => 'It claims to be from the police or another authority, or mentions moving money to a "safe account".',
        'Traffic-fine / penalty language detected (a common SMS scam: "violation", "e-challan", "unpaid toll", "saman")' => 'It mentions an unpaid fine or penalty, a common text-message scam (for example "violation", "unpaid toll" or "saman").',
        'Text is disguised with look-alike letters, hidden characters or spaced-out letters (a trick to get past spam filters)' => 'The text is disguised with look-alike letters, hidden characters or spaced-out letters, a trick used to slip past spam filters.',
        'A purchase or renewal you did not make, with a phone number to call (a common "call us to cancel" scam)' => 'It reports a purchase or renewal you did not make and gives a number to call, a common "call us to cancel" scam.',
        'Threat or blackmail asking for payment (a common "your device was hacked, pay in Bitcoin" scam)' => 'It threatens or blackmails you into paying, a common "your device was hacked, pay in Bitcoin" scam.',
        'Too-good-to-be-true offer (easy money, guaranteed returns, an instant loan, or a job that needs a fee)' => 'It makes an offer that is too good to be true, such as easy money, guaranteed returns, an instant loan or a job that requires a fee.',
        'Claims to be someone you trust (a family member on a "new number", a bank, the police or "support")' => 'It claims to be someone you trust, such as a family member on a "new number", a bank, the police or a support team.',
        'Asks you to send a code, OTP, PIN or card number (no real bank, agency or friend needs these)' => 'It asks for a code, OTP, PIN or card number. No genuine bank, agency or friend will ask for these.',
        'Asks you to keep it secret (scammers say this so no one can warn you)' => 'It asks you to keep it secret, so that no one has the chance to warn you.',
        'Pushes you to a private chat or to contact an "agent" or "officer"' => 'It pushes you to a private chat or to contact an "agent" or "officer"',
        'Asks you to send money, pay a fee or pay a deposit first' => 'It asks you to send money or pay a fee or deposit first',
        'Credential/verification request detected' => 'It asks you to verify your account or enter your login details',
        'Financial request or invoice language detected' => 'It asks for a payment or refers to an invoice',
        'Advance-fee / "dear friend" scam language detected' => 'It uses the wording of an advance-fee scam (a large sum of money offered in return for a small payment first)',
        'Cryptocurrency giveaway or wallet-claim language detected (a common scam lure)' => 'It offers a cryptocurrency giveaway or asks you to claim or unlock a wallet, a common scam lure.',
        'Customs/delivery-fee language detected (a common parcel scam)' => 'It asks for a customs or delivery fee, a common parcel scam.',
        'distinct phishing behavior patterns found together — a coordinated social-engineering pattern, not an isolated keyword' => 'different scam tactics appear together, which points to a deliberate scam rather than a coincidence',
        'Asks you to act (pay, share a code or details, or contact someone) while using a pressure tactic: the core pattern of a scam' => 'It asks you to do something (pay, share a code or details, or contact someone) while putting pressure on you, which is the core pattern of a scam',
        'The message pressures the reader to act and links to' => 'The message pressures you to act and links to',
        'which is not a known official domain' => 'which is not a recognised official website',
        'The AI reader thinks' => 'Our AI review judged that',
        'The AI reader found nothing suspicious in' => 'Our AI review found nothing suspicious in',
        'No issues detected for this check.' => 'No problems were found.',

        // Links, websites and servers.
        'URL uses a raw IP address instead of a domain name' => 'The link uses a string of numbers (an IP address) instead of a normal website name',
        'Website does not use HTTPS (no SSL encryption)' => 'This website does not use a secure connection (HTTPS), so information you enter could be intercepted',
        'SSL certificate has expired' => "The website's security certificate has expired",
        'SSL certificate does not match the domain' => "The website's security certificate belongs to a different website",
        'Could not verify a valid, trusted SSL certificate for this domain' => "We could not confirm that this website's security certificate is genuine",
        'Could not connect to verify SSL (unrelated to certificate validity)' => 'We could not connect to the website to check its security certificate',
        'Could not verify SSL certificate due to an unexpected error' => "We could not check the website's security certificate",
        'WHOIS data unavailable for this domain' => 'We could not find out when this website was created',
        'Could not retrieve WHOIS data' => 'We could not find out when this website was created',
        'Domain was registered only' => 'The website was created only',
        'Domain was registered' => 'The website was created',
        'Domain is relatively new' => 'The website is fairly new',
        'a strong phishing indicator' => 'a strong sign of a scam website',
        'recently registered domains are higher risk' => 'newly created websites carry a higher risk',
        'Sender domain contains' => 'The sender address contains',
        'Domain contains' => 'The website address contains',
        'hyphens, which is unusually high' => 'hyphens, which is unusually many',
        'security vendors on VirusTotal' => 'security companies (checked through VirusTotal)',
        'security vendors' => 'security companies',
        'flagged this URL as malicious or suspicious' => 'flagged this link as harmful or suspicious',
        'Only a very small share of vendors flagged it.' => 'Only a very small number of them flagged it.',
        'This is often a false alarm on well-known, legitimate sites, so it counts as a minor signal.' => 'This is often a false alarm on well-known, genuine websites, so it counts as a minor warning only.',
        'Could not resolve domain to an IP address.' => 'We could not find the server this website runs on.',
        'IP address is associated with a known proxy or VPN service, often used to mask phishing infrastructure' => 'The website runs through a service that hides who operates it, which scammers often use',
        'IP address belongs to a datacenter/hosting provider rather than a residential ISP' => 'The website runs on a rented server rather than a home internet connection',
        'no proxy or datacenter flags' => 'nothing unusual about the server',
        'Could not reach the website, so where the link leads could not be checked' => 'We could not reach the website, so we could not check where the link leads',
        'URL redirects through' => 'The link passes you through',
        'before reaching its final destination, an unusually long chain' => 'before reaching the final page, which is unusually many',
        'URL ultimately redirects to a different domain' => 'The link ends up at a different website',
        'than the one submitted' => 'from the one you entered',
        'No redirects detected.' => 'The link goes directly to its page.',
        'Page uses a meta-refresh tag to automatically redirect visitors to a different domain' => 'The page automatically sends visitors to a different website',
        'Content check skipped: URL points directly at a private/internal address.' => 'We did not read this page because the address points to a private network, not the public internet.',
        'Could not fetch page content for analysis.' => 'We could not load the page to read its content.',
        'Page contains a password input field' => 'The page has a field asking for a password',
        'credential harvesting' => 'stealing login details',
        'Page contains a hidden iframe (zero-size or display:none/visibility:hidden)' => 'The page contains a hidden frame that loads other content without you seeing it',
        'obfuscated JavaScript' => 'deliberately scrambled code',
        'This domain (' => 'This website address (',
        ') no longer resolves' => ') no longer exists on the internet',
        'it may have expired, been suspended, or been taken down entirely' => 'it may have expired, been suspended or been taken down',
        'Site is currently live and reachable' => 'The website is online',
        'Page content references' => 'The content refers to',
        'branding, but the domain' => 'but the website',
        ' — likely brand impersonation' => ', so it is likely impersonating that organisation',
        'likely brand impersonation' => 'likely impersonating that organisation',
        'lookalike/typosquat attempt' => 'a copycat of that name',
        'phishing-style lure language' => 'scam-style wording',
        'phishing-style lure phrases' => 'scam-style wording',
        'Extracted from image:' => 'Read from the image:',
        'Brand referenced:' => 'Organisation mentioned:',
        'Not a valid email address format' => 'This is not a valid email address',
        'No prior reports found for' => 'No one has reported',
        'on PhishCore' => 'to PhishCore before',
        '(Informational only — this does not change the risk score.)' => '(For information only. This does not affect the score.)',
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
            $parts[] = $ok.' found no issues';
        }
        if ($info > 0) {
            $parts[] = $info.($info === 1 ? ' was' : ' were').' unavailable or for information only';
        }

        $list = count($parts) > 1
            ? implode(', ', array_slice($parts, 0, -1)).' and '.end($parts)
            : implode('', $parts);

        return 'We ran '.$total.($total === 1 ? ' check' : ' checks').': '.$list.'.';
    }
}