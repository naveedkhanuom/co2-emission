<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class FollowUpAddedNotification extends Notification
{
    use Queueable;

    public $followUp;

    public function __construct($followUp)
    {
        $this->followUp = $followUp;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "New follow-up added for client: " . ($this->followUp->followable->name ?? 'N/A'),
            'followUpId' => $this->followUp->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "New follow-up added for client: " . ($this->followUp->followable->name ?? 'N/A'),
            'followUpId' => $this->followUp->id,
        ]);
    }
}
