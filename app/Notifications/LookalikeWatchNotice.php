<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Bell notification for the team: the Look-alike Watch found new websites copying a Brunei brand. */
class LookalikeWatchNotice extends Notification
{
    public const KIND = 'lookalike_watch';

    public function __construct(private int $count, private string $example) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $message = $this->count === 1
            ? 'Look-alike Watch found a new website copying a Brunei brand: '.$this->example
            : "Look-alike Watch found {$this->count} new websites copying Brunei brands, such as ".$this->example;

        return [
            'kind' => self::KIND,
            'message' => $message,
            'link' => route('lookalike-watch.index', absolute: false),
        ];
    }
}