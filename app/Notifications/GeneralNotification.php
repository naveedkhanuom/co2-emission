<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A single, reusable in-app notification. Every alert in the platform is one of
 * these — the difference is only the copy, icon, colour and link passed in. Use
 * the App\Support\Notifier helper to raise them with consistent wording.
 *
 * Delivery is the database channel only (synchronous, no queue worker needed),
 * so notifications appear the moment the triggering action completes. A future
 * real-time layer can add 'broadcast' to via() once Reverb + channels are live.
 */
class GeneralNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $icon = 'fa-bell',
        public string $color = 'primary',
        public ?string $url = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'icon'    => $this->icon,
            'color'   => $this->color,
            'url'     => $this->url,
        ];
    }
}
