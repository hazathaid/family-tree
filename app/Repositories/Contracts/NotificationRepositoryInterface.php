<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;

interface NotificationRepositoryInterface
{
    public function createForEvent(int $userId, int $eventId, string $title, string $body): Notification;
}
