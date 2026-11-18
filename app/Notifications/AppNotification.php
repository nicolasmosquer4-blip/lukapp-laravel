<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notificación interna de la aplicación (campana del topbar).
 * Se guarda en la tabla "notifications" mediante el canal database.
 */
class AppNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info',   // info | success | warning
        public ?string $link = null,
        public array $meta = [],       // datos extra (p. ej. alert_key para evitar duplicados)
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'link' => $this->link,
        ] + $this->meta;
    }

    public static function money(float|string $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }
}
