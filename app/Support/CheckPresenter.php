<?php

namespace App\Support;

/**
 * Turns one check from a scan into what the result page and the PDF report show: a short bold headline,
 * the rest of the sentence, a vendor meter and a few evidence rows.
 *
 * It only rewrites how the engine's sentences are laid out. Nothing is scored here, and no wording is ever
 * dropped: when a sentence has no good place to cut, it is returned whole as the body.
 */
class CheckPresenter
{
    /**
     * @param  array{name: string, message: string}  $c  the check; "message" is already in plain words (CheckExplainer::plain)
     * @return array{headline: ?string, body: string, meter: ?array, facts: array}
     */
    public static function present(array $c): array
    {
        $msg = $c['message'];
        $out = ['headline' => null, 'body' => $msg, 'meter' => null, 'facts' => []];
        $up = fn (string $t) => mb_strtoupper(mb_substr($t, 0, 1)).mb_substr($t, 1);
        $dot = fn (string $t) => preg_match('/[.!?)]$/u', $t) ? $t : $t.'.';
        // Cut a text at the first sentence end or spaced dash that is outside brackets and quotes.
        // A full stop is ignored after "e.g." / "i.e." / "etc." / "vs.".
        $cut = function (string $t, string $kind): ?array {
            $chars = preg_split('//u', $t, -1, PREG_SPLIT_NO_EMPTY);
            $depth = 0;
            $quoted = false;
            $head = '';
            foreach ($chars as $i => $ch) {
                if ($ch === '(') {
                    $depth++;
                } elseif ($ch === ')') {
                    $depth = max(0, $depth - 1);
                } elseif ($ch === '"' || $ch === '“' || $ch === '”') {
                    $quoted = $ch === '“' ? true : ($ch === '”' ? false : ! $quoted);
                }
                $prev = $chars[$i - 1] ?? null;
                $next = $chars[$i + 1] ?? null;
                $hit = match ($kind) {
                    'sentence' => in_array($ch, ['.', '!', '?'], true) && ($next === null || preg_match('/\s/u', $next))
                        && ! preg_match('/(?:\be\.g|\bi\.e|\bvs|\betc)$/iu', $head),
                    default => ($ch === '—' || $ch === '–') && $prev === ' ' && $next === ' ',
                };
                if ($hit && $depth === 0 && ! $quoted) {
                    return [rtrim($head), trim(implode('', array_slice($chars, $i + 1)))];
                }
                $head .= $ch;
            }

            return null;
        };

        if ($c['name'] === 'Blacklist Database' && preg_match('/^This exact address is on ([A-Za-z0-9][\w.\-]*), a (.+)$/su', $msg, $m)) {
            // only "this exact address"; "Another page on this website is on ..." keeps its own sentence
            $out['headline'] = 'Listed on '.$m[1];
            $out['body'] = 'This exact address is on a '.$m[2];
        } elseif ($c['name'] === 'Blacklist Database' && preg_match('/^URL is flagged on Google Safe Browsing(?:\'|’)s threat list\.?(.*)$/su', $msg, $m)) {
            $out['headline'] = 'Flagged by Google Safe Browsing';
            // any sentence the engine added after the first one stays
            $out['body'] = trim('This link is on Google Safe Browsing’s list of known dangerous websites. '.trim($m[1]));
        } elseif ($c['name'] === 'SSL Certificate' && preg_match('/^This website does not use a secure connection \(HTTPS\), so (.+)$/su', $msg, $m)) {
            $out['headline'] = 'No secure connection (HTTPS)';
            $out['body'] = $dot($up(trim($m[1])));
        } elseif ($c['name'] === 'Hosting Platform' && preg_match('/^Hosted on [“"]([^”"]+)[”"], a free website-builder\/hosting platform commonly used for disposable phishing pages\s[—–]\s(.+)$/su', $msg, $m)) {
            // the headline carries the new information (which host), the card title already says "Free website builder"
            $out['headline'] = 'Hosted on '.$m[1];
            $out['body'] = 'A free website builder commonly used for disposable phishing pages. '.$dot($up(trim($m[2])));
        } elseif ($c['name'] === 'VirusTotal / CTI Check' && preg_match('/^(\d+) out of (\d+) security companies \(checked through VirusTotal\) flagged this link as harmful or suspicious\.?(.*)$/su', $msg, $m)) {
            $flagged = (int) $m[1];
            $of = (int) $m[2];
            $out['headline'] = $flagged.' of '.$of.' vendors flagged this link';
            // anything the engine added after that first sentence (such as "only a very small share...") stays
            $out['body'] = trim('Checked through VirusTotal. These security companies marked the link as harmful or suspicious. '.trim($m[3]));
            $out['meter'] = ['value' => $flagged, 'total' => $of, 'pct' => $of > 0 ? min(100, round($flagged / $of * 100, 2)) : 0];
        } elseif ($c['name'] === 'IP Reputation & Location' && preg_match('/^The website runs on a rented server rather than a home internet connection\s[—–]\s(.+)$/su', $msg, $m)) {
            // only when this is the first sentence; a proxy/VPN sentence in front means the full message is shown
            $out['headline'] = 'Runs on a rented server';
            $out['body'] = mb_strtoupper(mb_substr($m[1], 0, 1)).mb_substr($m[1], 1);
        } elseif ($c['name'] === 'Page Content Analysis') {
            $password = (bool) preg_match('/field asking for a password/i', $msg);
            $brand = preg_match('/refers to [“"]([^”"]+)[”"]/u', $msg, $b) ? $b[1] : null;
            $domain = preg_match('/the website \([“"]?([^)”"]+)[”"]?\)/u', $msg, $d) ? $d[1] : null;
            $server = preg_match('/Server responds as:\s*(\S+(?:\s\([^)]*\))?)/u', $msg, $s) ? rtrim($s[1], '.') : null;

            if ($password) {
                $out['facts'][] = ['label' => 'Password field', 'value' => 'Yes', 'mono' => false, 'danger' => true];
            }
            if ($brand) {
                $out['facts'][] = ['label' => 'Page claims to be', 'value' => $brand, 'mono' => false, 'danger' => false];
            }
            if ($domain) {
                $out['facts'][] = ['label' => 'Actual website', 'value' => $domain, 'mono' => true, 'danger' => false];
            }
            if ($server) {
                $out['facts'][] = ['label' => 'Server responds as', 'value' => $server, 'mono' => true, 'danger' => false];
            }

            if ($password && $brand) {
                $out['headline'] = 'Password field on a page that appears to imitate '.$brand;
            } elseif ($password) {
                $out['headline'] = 'The page asks for a password';
            }

            if ($out['headline'] || $out['facts']) {
                // take out only what the headline and the evidence panel already say; every other sentence stays
                $body = preg_replace('/The page has a field asking for a password\.?\s*/u', '', $msg);
                $body = preg_replace('/\s*Server responds as:\s*\S+(?:\s\([^)]*\))?/u', '', $body);
                $body = preg_replace('/(the website)\s*\([“"]?[^)”"]+[”"]?\)/u', '$1', $body);
                $body = trim($body);
                if ($body !== '') {
                    $out['body'] = $body;
                }
            }
        }

        // Any other check: a short first sentence becomes the headline. A longer one is cut at its first spaced
        // dash (the engine writes "— why this matters" on purpose) and the rest stays as the body, so every card
        // opens the same way and not one word is dropped. Commas are never cut: they split sentences badly.
        // If there is no good place to cut, the message stays as plain text.
        if ($out['headline'] === null) {
            [$first, $after] = $cut($out['body'], 'sentence') ?? [trim($out['body']), ''];
            $head = null;
            $tail = $after;
            if ($first !== '' && $first[0] !== '(' && mb_strlen($first) <= 70) {
                $head = $first;
            } elseif ($first !== '' && $first[0] !== '(') {
                $part = $cut($first, 'dash');
                if ($part && mb_strlen($part[0]) >= 12 && mb_strlen($part[0]) <= 95 && $part[1] !== '') {
                    $head = $part[0];
                    $tail = trim($dot($up($part[1])).' '.$after);
                } elseif (mb_strlen($first) <= 100) {
                    $head = $first;
                }
            }
            if ($head !== null) {
                $out['headline'] = $head;
                $out['body'] = $tail;
            }
        }

        return $out;
    }

    /** One short line saying what a check looked at, for the "Looked at" line of passed checks. */
    public static function lookedAt(string $name): ?string
    {
        $lines = [
            'SSL Certificate' => 'Whether the connection is encrypted and the certificate is genuine',
            'Domain Age' => 'When the web address was registered',
            'URL Structure' => 'Tricks in how the link is written, such as fake brand names or odd hyphens',
            'Blacklist Database' => 'Google Safe Browsing and community lists of known phishing sites',
            'VirusTotal / CTI Check' => 'What dozens of security companies say about this link',
            'IP Reputation & Location' => 'Where the server is and whether it hides who runs it',
            'Redirect Chain' => 'Where the link really leads once you open it',
            'Page Content Analysis' => 'Password forms, urgent wording and brand imitation on the page',
            'Hosting Platform' => 'Whether the site sits on a free website builder',
            'Site Availability' => 'Whether the website is online right now',
            'Previous Reports' => 'Whether other PhishCore users reported this before',
            'AI Page Review' => 'A second opinion on the page from an AI model',
            'AI Message Review' => 'A second opinion on the message from an AI model',
            'Sender Domain Analysis' => 'Whether the sender\'s domain looks genuine',
            'Email Authentication' => 'Whether the email passed sender verification (SPF, DKIM, DMARC)',
            'Message Content / Behavior Patterns' => 'Pressure, threats, prizes and requests for money or codes',
            'Links in the Email' => 'Every link inside the email',
            'Brand / Sender Correlation' => 'Whether the sender really belongs to the company it names',
            'Brand / Domain Correlation' => 'Whether the website really belongs to the company it names',
            'Phone Number Analysis' => 'Whether the number is valid and which country it is from',
            'Phone Reputation' => 'Spam or fraud reports about this number',
            'Link in Pressure Message' => 'A pushy message that links to an unknown website',
            'Attachment' => 'The attachment\'s file name and type',
            'File Type' => 'What the file really is, whatever its name says',
            'VirusTotal File Fingerprint' => 'Whether security companies already know this file',
            'App Permissions' => 'What the app is allowed to do on the phone',
            'PDF Contents' => 'Hidden links and actions inside the PDF',
            'PDF Text' => 'Scam wording in the text of the PDF',
            'Admin Review' => 'Whether a PhishCore administrator reviewed this file',
        ];
        if (isset($lines[$name])) {
            return $lines[$name];
        }
        foreach (['Previous Reports', 'Phone Reputation'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $lines[$prefix];
            }
        }
        if (str_starts_with($name, 'AI ')) {
            return $lines[str_contains($name, 'Page') ? 'AI Page Review' : 'AI Message Review'];
        }

        return null;
    }
}