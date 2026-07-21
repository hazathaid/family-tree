<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $attributes): Notification
    {
        return Notification::query()->create($attributes);
    }

    public function createForEvent(int $userId, int $eventId, string $title, string $body): Notification
    {
        return Notification::query()->firstOrCreate(
            ['event_id' => $eventId, 'user_id' => $userId],
            ['type' => 'event_reminder', 'title' => $title, 'body' => $body, 'data' => ['event_id' => $eventId]],
        );
    }

    public function paginateForUser(User $user, int $perPage, ?bool $isRead = null): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->when($isRead !== null, fn ($query) => $query->where('is_read', $isRead))
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(User $user, string $uuid): Notification
    {
        return Notification::query()->where('user_id', $user->id)->where('uuid', $uuid)->firstOrFail();
    }

    public function markAllRead(User $user): int
    {
        return Notification::query()->where('user_id', $user->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }
}
