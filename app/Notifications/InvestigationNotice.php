<?php

namespace App\Notifications;

use App\Models\Investigation;
use Illuminate\Notifications\Notification;

/**
 * One bell notification about an investigation. Stored in the database only (no email yet).
 * The text is built here once, so the bell, the list and the tests all read the same words.
 */
class InvestigationNotice extends Notification
{
    public const REQUESTED = 'requested';

    public const STATUS_CHANGED = 'status_changed';

    public const ASSIGNED = 'assigned';

    public const UNASSIGNED = 'unassigned';

    private const STATUS_LABELS = [
        'active' => 'Active',
        'completed' => 'Completed',
        'takedown_requested' => 'Takedown requested',
        'takedown_confirmed' => 'Takedown confirmed',
    ];

    public function __construct(private Investigation $investigation, private string $kind)
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
        $report = $this->investigation->report;
        $subject = $report?->url ?: $report?->sender_email ?: $report?->phone_number;
        $subject = $subject
            ? mb_strimwidth((string) $subject, 0, 60, '…')
            : 'a screenshot scan'.($report ? ' ('.self::reference($report).')' : '');

        $message = match ($this->kind) {
            self::REQUESTED => 'New investigation request on '.$subject,
            self::ASSIGNED => 'You were assigned an investigation on '.$subject,
            self::UNASSIGNED => 'New unassigned investigation on '.$subject.', needs someone to take it',
            default => 'Your investigation on '.$subject.' is now: '.(self::STATUS_LABELS[$this->investigation->status] ?? $this->investigation->status),
        };

        return [
            'kind' => $this->kind,
            'message' => $message,
            'investigation_id' => $this->investigation->id,
            'report_id' => $this->investigation->report_id,
        ];
    }

    /** The same reference id the result page shows, so the team can tell screenshot scans apart. */
    private static function reference($report): string
    {
        return 'PG-'.$report->created_at->format('Y-md').'-'.strtoupper(substr(md5((string) $report->id), 0, 5));
    }
}