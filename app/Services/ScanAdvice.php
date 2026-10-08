<?php

namespace App\Services;

/**
 * The "what you should do" advice shown on a scam result and in its PDF report.
 * Kept in one place so the page and the PDF always say the same thing.
 */
class ScanAdvice
{
    /** Verdicts that get the advice. */
    public const VERDICTS = ['suspicious', 'phishing'];

    /**
     * @return array{intro: string, steps: array<int, string>, affectedTitle: string, affected: array<int, string>}|null
     */
    public static function for(string $type, string $verdict): ?array
    {
        if (! in_array($verdict, self::VERDICTS, true)) {
            return null;
        }

        return [
            'intro' => $verdict === 'phishing'
                ? 'This looks like a scam. Please take these steps.'
                : 'This could be a scam. Until you are sure, treat it as one.',
            'steps' => self::steps($type),
            'affectedTitle' => 'Already sent money or shared bank details?',
            'affected' => [
                'Call your bank straight away, using the number on your card or the bank\'s official website, and ask them to block the transfer or card.',
                'Call the national anti-scam helpline on 16993 for help and guidance.',
                'If you are in danger or the crime is happening now, call the police on 993, or go to your nearest police station.',
                'Keep the messages, screenshots and this report as evidence.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function steps(string $type): array
    {
        return match ($type) {
            'email' => [
                'Do not reply, and do not open any attachment or click any link in the email.',
                'Report it as phishing in your mail app, then delete it.',
                'If it claims to be from a bank or company, contact them using the number on their official website, not the one in the email.',
                'If you already clicked a link or typed a password, change that password now and turn on two-step verification.',
            ],
            'phone' => [
                'Do not call back or reply, and never share a one-time code (OTP), PIN or password.',
                'Block the number on your phone.',
                'If the caller claimed to be from a bank, the police or a government office, hang up and call the official number yourself.',
                'If you already shared card or banking details, call your bank straight away.',
            ],
            'screenshot' => [
                'Do not tap any link in the message, and do not send money, codes or personal details.',
                'Block the sender and report the message in the app you received it on.',
                'If it claims to be from a bank, courier or government office, contact them using the number on their official website.',
                'If you already tapped the link or entered details, change your passwords and tell your bank.',
            ],
            default => [
                'Do not enter usernames, passwords or payment details on this site, and do not download anything from it.',
                'Close the page. If you were sent the link, do not forward it.',
                'Report the link to the organisation it is pretending to be.',
                'If you already entered a password, change it now and turn on two-step verification.',
            ],
        };
    }
}