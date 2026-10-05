<?php

namespace App\Services;

use App\Jobs\SendPushNotification;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\FamilyActivityNotification;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationService
{
    public const CATEGORY_EVENT_REMINDERS = 'event_reminders';

    public const CATEGORY_FAMILY_UPDATES = 'family_updates';

    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    /**
     * Unconditional in-app notification with push delivery.
     *
     * Retained for callers that have already resolved consent and for direct
     * system messages. Domain events should prefer {@see dispatchForUser()} so
     * user notification preferences are honored.
     */
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

    /**
     * Preference-aware in-app notification, push and email for a single user.
     */
    public function dispatchForUser(
        User $recipient,
        string $category,
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): ?Notification {
        if (! $this->categoryEnabled($recipient, $category)) {
            return null;
        }

        $notification = $this->notifications->create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $this->deliver($recipient, $notification);

        return $notification;
    }

    public function notifyEvent(User|int $recipient, Event $event, string $title, string $body): ?Notification
    {
        $user = $recipient instanceof User ? $recipient : User::query()->find($recipient);

        if (! $user instanceof User) {
            return null;
        }

        if (! $this->categoryEnabled($user, self::CATEGORY_EVENT_REMINDERS)) {
            return null;
        }

        $notification = $this->notifications->createForEvent($user->id, $event, $title, $body);

        $this->deliver($user, $notification);

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

    private function deliver(User $user, Notification $notification): void
    {
        if ($this->channelEnabled($user, 'push')) {
            SendPushNotification::dispatch($notification->id)->afterCommit();
        }

        if ($this->channelEnabled($user, 'email') && is_string($user->email) && $user->email !== '') {
            $user->notify(new FamilyActivityNotification($notification));
        }
    }

    private function categoryEnabled(User $user, string $category): bool
    {
        $preferences = $user->notification_preferences ?? [];

        return (bool) ($preferences[$category] ?? true);
    }

    private function channelEnabled(User $user, string $channel): bool
    {
        $preferences = $user->notification_preferences ?? [];

        return (bool) ($preferences[$channel] ?? true);
    }
}
