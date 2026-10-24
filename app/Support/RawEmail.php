<?php

namespace App\Support;

use Throwable;

/**
 * Reads a whole email: the text someone pasted from "Show original" or a saved .eml file. It splits the
 * header block from the message, finds the sender and subject, and turns the message into plain text, so the
 * visitor does not have to fill in each box by hand.
 *
 * Only plain reading is done: nothing is run, opened or stored, attachments are skipped, and a web-page
 * (HTML) message is turned into text. The address behind every link is kept in that text, because the
 * words shown on a link can say one thing while the address goes somewhere else.
 */
final class RawEmail
{
    /** Largest email read. A saved email with a big picture attached can be a few megabytes; the text is far smaller. */
    public const MAX_BYTES = 1_000_000;

    /** The scan form keeps at most this much of the message text. */
    public const MAX_BODY = 5000;

    /** Headers only mail software writes. A pasted message with a "From:" line but none of these is just text. */
    private const MAIL_ONLY_HEADERS = [
        'received', 'received-spf', 'authentication-results', 'return-path', 'message-id',
        'mime-version', 'dkim-signature', 'delivered-to', 'x-mailer', 'content-transfer-encoding',
    ];

    /** True when the text starts like the source of an email (header lines, with a From and a mail-only header). */
    public static function looksRaw(string $text): bool
    {
        $text = ltrim(mb_substr($text, 0, 6000), "\xEF\xBB\xBF \t\r\n");

        if (! preg_match('/^[!-9;-~]+:[ \t]*\S/', $text)) {
            return false;
        }

        return self::isEmail(EmailHeaders::parse($text));
    }

    /**
     * @return array{from: ?string, subject: ?string, body: string, headers: string}|null null when this is not an email
     */
    public static function parse(string $raw): ?array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", substr($raw, 0, self::MAX_BYTES));
        $raw = ltrim($raw, "\xEF\xBB\xBF \t\n");

        [$headerText, $messageText] = array_pad(preg_split('/\n[ \t]*\n/', $raw, 2) ?: [], 2, '');
        $headers = EmailHeaders::parse($headerText);

        if (! self::isEmail($headers)) {
            return null;
        }

        $from = $headers->fromAddress();

        return [
            'from' => $from !== null && filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : null,
            'subject' => $headers->subject(),
            'body' => mb_substr(trim(self::text($headers, $messageText, 0)), 0, self::MAX_BODY),
            'headers' => mb_substr($headerText, 0, EmailHeaders::MAX_LENGTH),
        ];
    }

    private static function isEmail(EmailHeaders $headers): bool
    {
        return $headers->has('from') && array_intersect(self::MAIL_ONLY_HEADERS, $headers->names()) !== [];
    }

    /** The readable text of one part of a message. */
    private static function text(EmailHeaders $headers, string $body, int $depth): string
    {
        $contentType = $headers->first('content-type') ?? 'text/plain';
        $type = strtolower($contentType);

        if (str_starts_with($type, 'multipart/')) {
            if ($depth < 5 && preg_match('/boundary\s*=\s*"?([^";\s]+)"?/i', $contentType, $match)) {
                return self::multipart($body, $match[1], str_contains($type, 'multipart/alternative'), $depth);
            }

            return '';
        }

        if (str_starts_with(strtolower($headers->first('content-disposition') ?? ''), 'attachment') || ! str_starts_with($type, 'text/')) {
            return '';
        }

        $decoded = self::decode($body, strtolower(trim($headers->first('content-transfer-encoding') ?? '7bit')), $contentType);

        return str_starts_with($type, 'text/html') ? self::htmlToText($decoded) : $decoded;
    }

    /** The text of a multipart message. For "alternative" parts (the same message written twice) the plain one is used. */
    private static function multipart(string $body, string $boundary, bool $alternative, int $depth): string
    {
        $parts = preg_split('/^--'.preg_quote($boundary, '/').'(?:--)?[ \t]*$/m', $body) ?: [];
        array_shift($parts); // whatever comes before the first boundary is only a note for old mail programs

        $found = [];

        foreach (array_slice($parts, 0, 50) as $part) {
            $part = ltrim($part, "\n");

            if (trim($part) === '') {
                continue;
            }

            [$partHeaderText, $partBody] = array_pad(preg_split('/\n[ \t]*\n/', $part, 2) ?: [], 2, '');
            $partHeaders = EmailHeaders::parse($partHeaderText);
            $text = trim(self::text($partHeaders, $partBody, $depth + 1));

            if ($text === '') {
                continue;
            }

            $isHtml = str_starts_with(strtolower($partHeaders->first('content-type') ?? ''), 'text/html');
            $found[] = ['html' => $isHtml, 'text' => $text];
        }

        if ($alternative) {
            $plain = array_values(array_filter($found, fn (array $f): bool => ! $f['html']));
            $found = $plain !== [] ? $plain : $found;
        }

        return implode("\n\n", array_column($found, 'text'));
    }

    /** Undoes base64 or quoted-printable and converts the text to UTF-8. */
    private static function decode(string $body, string $encoding, string $contentType): string
    {
        $text = match ($encoding) {
            'base64' => (string) base64_decode((string) preg_replace('/\s+/', '', $body)),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        $charset = preg_match('/charset\s*=\s*"?([^";\s]+)/i', $contentType, $match) ? strtoupper($match[1]) : 'UTF-8';

        if (! in_array($charset, ['UTF-8', 'UTF8', 'US-ASCII', 'ASCII'], true)) {
            try {
                $text = mb_convert_encoding($text, 'UTF-8', $charset);
            } catch (Throwable) {
                // an unknown character set: keep the text as it is
            }
        }

        return mb_scrub($text);
    }

    /** A web-page message as plain text, keeping the address of every link next to its words. */
    private static function htmlToText(string $html): string
    {
        $html = preg_replace('~<(script|style|head)\b.*?</\1>~is', ' ', $html) ?? $html;

        $html = preg_replace_callback('~<a\b[^>]*?\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>~is', function (array $match): string {
            $words = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $address = trim(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (! preg_match('~^https?://~i', $address)) {
                return $words; // mailto:, tel:, #top
            }

            return ($words !== '' && $words !== $address ? $words.' ' : '').$address.' ';
        }, $html) ?? $html;

        $html = preg_replace('~<br\s*/?>|</(?:p|div|tr|li|h[1-6])>~i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ ?\n ?/', "\n", $text) ?? $text;

        return trim(preg_replace('/\n{3,}/', "\n\n", $text) ?? $text);
    }
}
