<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Illuminate\Notifications\DatabaseNotification */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->data['title'] ?? 'Notificación',
            'message' => $this->data['message'] ?? '',
            'type' => $this->data['type'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'read' => $this->read_at !== null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
