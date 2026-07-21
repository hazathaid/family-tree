<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function notify(User|int $recipient, string $type, string $title, string $body, array $data = []): Notification
    {
        $notification = $this->notifications->create([
            'user_id' => $recipient instanceof User ? $recipient->id : $recipient,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        SendPushNotification::dispatch($notification->id)->afterCommit();

        return $notification;
    }

    public function notifyEvent(int $userId, Event $event, string $title, string $body): Notification
    {
        $notification = $this->notifications->createForEvent($userId, $event->id, $title, $body);
        SendPushNotification::dispatch($notification->id)->afterCommit();

        return $notification;
    }

    public function markRead(Notification $notification): Notification
    {
        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return $notification->refresh();
    }

    public function markAllRead(User $user): int
    {
        return $this->notifications->markAllRead($user);
    }
}
