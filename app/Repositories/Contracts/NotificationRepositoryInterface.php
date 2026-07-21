<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function create(array $attributes): Notification;

    public function createForEvent(int $userId, int $eventId, string $title, string $body): Notification;

    public function paginateForUser(User $user, int $perPage, ?bool $isRead = null): LengthAwarePaginator;

    public function findForUser(User $user, string $uuid): Notification;

    public function markAllRead(User $user): int;

    public function unreadCount(User $user): int;

    public function recentForUser(User $user, int $limit): Collection;
}
