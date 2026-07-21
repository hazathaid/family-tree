<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EventRepositoryInterface
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): Event;

    public function update(Event $event, array $attributes): Event;

    public function delete(Event $event): void;

    public function rsvp(Event $event, User $user, string $status): EventAttendee;

    public function loadDetails(Event $event, ?User $user = null): Event;

    public function dueForReminder(): Collection;
}
