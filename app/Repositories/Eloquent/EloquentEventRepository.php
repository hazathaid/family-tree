<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentEventRepository implements EventRepositoryInterface
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return Event::query()->with(['family', 'organizer'])->withCount([
            'attendees',
            'attendees as yes_count' => fn ($query) => $query->where('status', 'yes'),
            'attendees as maybe_count' => fn ($query) => $query->where('status', 'maybe'),
        ])->whereHas('family.userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->when($filters['family_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('family', fn ($family) => $family->where('uuid', $uuid)))
            ->when($filters['upcoming'] ?? null, fn ($query) => $query->where('event_date', '>=', now()))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->where('title', 'like', '%'.$search.'%')->orWhere('location', 'like', '%'.$search.'%')))
            ->orderBy('event_date')->paginate($perPage);
    }

    public function create(array $attributes): Event
    {
        return Event::query()->create($attributes);
    }

    public function update(Event $event, array $attributes): Event
    {
        $event->update($attributes);

        return $event->refresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    public function rsvp(Event $event, User $user, string $status): EventAttendee
    {
        return EventAttendee::query()->updateOrCreate(['event_id' => $event->id, 'user_id' => $user->id], ['status' => $status]);
    }

    public function loadDetails(Event $event, ?User $user = null): Event
    {
        $event->load(['family', 'organizer', 'attendees.user'])->loadCount([
            'attendees',
            'attendees as yes_count' => fn ($query) => $query->where('status', 'yes'),
            'attendees as maybe_count' => fn ($query) => $query->where('status', 'maybe'),
        ]);
        if ($user) {
            $event->setAttribute('my_rsvp', $event->attendees->firstWhere('user_id', $user->id)?->status);
        }

        return $event;
    }

    public function dueForReminder(): Collection
    {
        return Event::query()->whereNull('reminder_sent_at')->whereBetween('event_date', [now(), now()->addDay()])->get();
    }
}
