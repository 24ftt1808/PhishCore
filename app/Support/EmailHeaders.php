<?php

namespace App\Support;

/**
 * Reads the header block of an email that someone pasted ("Show original" in Gmail, "View message
 * source" in Outlook), so the engine can check whether the email really came from the domain it
 * claims.
 *
 * The part that matters is the Authentication-Results header. The visitor's own mailbox provider
 * adds it when the email arrives, after checking three things the sender cannot fake:
 *  - SPF: was the sending server allowed to send for that domain?
 *  - DKIM: does the digital signature on the email match the domain?
 *  - DMARC: did SPF or DKIM pass for the same domain that is shown in the From line?
 *
 * Only the top-most Authentication-Results header is used. Providers add theirs at the top, so lower
 * copies could have been written by the sender or an earlier server. The same limit applies to any
 * pasted text: it is only as honest as the person who pasted it, which is why a pass never lowers
 * a score here (see AnalysisEngine::checkEmailAuthentication).
 */
final class EmailHeaders
{
    /** Longest header block read. Real header blocks are a few thousand characters. */
    public const MAX_LENGTH = 30000;

    /** Mailbox providers anyone can sign up to. A company writing from one of these is not using its own domain. */
    public const FREE_MAIL = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.com.sg', 'yahoo.com.my', 'ymail.com', 'outlook.com',
        'hotmail.com', 'live.com', 'msn.com', 'icloud.com', 'me.com', 'aol.com', 'proton.me', 'protonmail.com',
        'gmx.com', 'mail.com', 'zoho.com', 'yandex.com', 'yandex.ru', 'qq.com', '163.com', '126.com',
    ];

    /** @var array<string, list<string>> lower-case header name => its values, top-most first */
    private array $headers = [];

    /** @param  array<string, list<string>>  $headers */
    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /** Reads a pasted header block. Anything after the first blank line (the message itself) is ignored. */
    public static function parse(string $raw): self
    {
        $raw = mb_substr(str_replace(["\r\n", "\r"], "\n", $raw), 0, self::MAX_LENGTH);
        $raw = ltrim($raw, "\n");
        $parts = preg_split('/\n[ \t]*\n/', $raw, 2);
        $block = $parts[0] ?? '';

        $headers = [];
        $current = null;

        foreach (explode("\n", $block) as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && $current !== null) {
                // A header continued on the next line.
                $index = array_key_last($headers[$current]);
                $headers[$current][$index] .= ' '.trim($line);

                continue;
            }

            if (preg_match('/^([!-9;-~]+):[ \t]*(.*)$/', $line, $match)) {
                $current = strtolower($match[1]);
                $headers[$current][] = trim($match[2]);

                continue;
            }

            $current = null;
        }

        return new self($headers);
    }

    /** True when at least one header line was recognised. */
    public function hasHeaders(): bool
    {
        return $this->headers !== [];
    }

    /** True when the header exists. */
    public function has(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * The lower-case names of every header found.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->headers);
    }

    /** The first address in the From line, lower case, or null. */
    public function fromAddress(): ?string
    {
        foreach ($this->headers['from'] ?? [] as $value) {
            $value = preg_replace('/"[^"]*"/', ' ', $value) ?? $value;

            if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?)+/i', $value, $match)) {
                return strtolower($match[0]);
            }
        }

        return null;
    }

    /** The subject with any encoded words ("=?UTF-8?B?...?=") turned back into readable text. */
    public function subject(): ?string
    {
        $value = $this->first('subject');

        if ($value === null) {
            return null;
        }

        $decoded = function_exists('iconv_mime_decode') ? @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : false;

        if (! is_string($decoded) || $decoded === '') {
            $decoded = function_exists('mb_decode_mimeheader') ? mb_decode_mimeheader($value) : $value;
        }

        return trim(mb_scrub($decoded));
    }

    /** The first (top-most) value of a header, or null. */
    public function first(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /**
     * What SPF, DKIM and DMARC said. Each result is a lower-case word such as "pass", "fail",
     * "softfail", "none" or "neutral", or null when the headers do not say.
     *
     * @return array{spf: ?string, dkim: ?string, dmarc: ?string, found: bool}
     */
    public function authentication(): array
    {
        $result = ['spf' => null, 'dkim' => null, 'dmarc' => null, 'found' => false];
        $dkim = [];

        $value = $this->first('authentication-results');

        if ($value !== null) {
            $value = preg_replace('/\([^)]*\)/', ' ', $value) ?? $value;

            foreach (explode(';', $value) as $segment) {
                if (! preg_match('/^\s*(spf|dkim|dmarc)\s*=\s*([a-z]+)/i', $segment, $match)) {
                    continue; // the first part is the name of the server that wrote the header
                }

                $method = strtolower($match[1]);
                $outcome = strtolower($match[2]);

                if ($method === 'dkim') {
                    $dkim[] = $outcome;
                } elseif ($result[$method] === null) {
                    $result[$method] = $outcome;
                }
            }
        }

        if ($result['spf'] === null && ($spf = $this->first('received-spf')) !== null && preg_match('/^\s*([a-z]+)/i', $spf, $match)) {
            $result['spf'] = strtolower($match[1]);
        }

        if ($dkim !== []) {
            // Several signatures can be present: one that checks out is enough.
            $result['dkim'] = in_array('pass', $dkim, true) ? 'pass' : $dkim[0];
        }

        $result['found'] = $result['spf'] !== null || $result['dkim'] !== null || $result['dmarc'] !== null;

        return $result;
    }

    /** The domain of the From line, or null. */
    public function fromDomain(): ?string
    {
        $domains = $this->domainsIn('from');

        return $domains[0] ?? null;
    }

    /**
     * The domains in the Reply-To line (where an answer would be sent), or none.
     *
     * @return list<string>
     */
    public function replyToDomains(): array
    {
        return $this->domainsIn('reply-to');
    }

    /**
     * Every domain in the addresses of one header, lower case.
     *
     * @return list<string>
     */
    private function domainsIn(string $name): array
    {
        $domains = [];

        foreach ($this->headers[$name] ?? [] as $value) {
            // Quoted display names ("PayPal <help@paypal.com>") are cut off first, so only real addresses count.
            $value = preg_replace('/"[^"]*"/', ' ', $value) ?? $value;

            if (preg_match_all('/[a-z0-9._%+\-]+@([a-z0-9](?:[a-z0-9\-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?)+)/i', $value, $matches)) {
                foreach ($matches[1] as $domain) {
                    $domains[strtolower($domain)] = true;
                }
            }
        }

        return array_keys($domains);
    }
}
