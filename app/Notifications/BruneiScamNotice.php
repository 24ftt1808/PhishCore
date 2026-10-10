<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

/** Bell notification for the team: a Brunei-related phishing scan or a possible scam campaign. The headline says which. */
class BruneiScamNotice extends Notification
{
    public const KIND = 'brunei_alert';

    public function __construct(private Report $report, private string $headline)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $subject = $this->report->url ?: $this->report->sender_email ?: $this->report->phone_number;
        $subject = $subject ? mb_strimwidth((string) $subject, 0, 60, '…') : 'a screenshot scan';

        return [
            'kind' => self::KIND,
            'message' => $this->headline.': '.$subject,
            'report_id' => $this->report->id,
        ];
    }
}