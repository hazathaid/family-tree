<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Notification $notification */
        $notification = $this->resource;

        return [
            'uuid' => $notification->uuid,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data ?? [],
            'is_read' => $notification->is_read,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}
