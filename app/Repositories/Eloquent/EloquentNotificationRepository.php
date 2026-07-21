<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function createForEvent(int $userId, int $eventId, string $title, string $body): Notification
    {
        return Notification::query()->firstOrCreate(['event_id' => $eventId, 'user_id' => $userId], ['title' => $title, 'body' => $body]);
    }
}
