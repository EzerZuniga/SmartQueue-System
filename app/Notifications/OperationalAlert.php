<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class OperationalAlert extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $message;

    public $level;

    public $sourceId;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message, string $level = 'warning', ?string $sourceId = null)
    {
        $this->message = $message;
        $this->level = $level;
        $this->sourceId = $sourceId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'level' => $this->level,
            'source_id' => $this->sourceId,
            'type' => 'operational_alert', // Custom type identifier
        ];
    }
}
