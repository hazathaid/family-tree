<?php

namespace App\Services;

use App\DTOs\EventData;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Family;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;

class EventService
{
    public function __construct(private readonly EventRepositoryInterface $events, private readonly ActivityLogService $activityLog) {}

    public function create(User $user, EventData $data): Event
    {
        $family = Family::query()->where('uuid', $data->familyUuid)->firstOrFail();
        $event = $this->events->create([
            'family_id' => $family->id,
            'title' => $data->title,
            'description' => $data->description,
            'event_date' => $data->eventDate,
            'location' => $data->location,
            'organizer_id' => $user->id,
        ]);
        $this->activityLog->eventCreated($user, $event);

        return $this->events->loadDetails($event, $user);
    }

    public function update(Event $event, array $data, User $user): Event
    {
        unset($data['family_uuid']);
        if (isset($data['event_date']) && $data['event_date'] !== $event->event_date->format('Y-m-d H:i:s')) {
            $data['reminder_sent_at'] = null;
        }

        return $this->events->loadDetails($this->events->update($event, $data), $user);
    }

    public function delete(Event $event): void
    {
        $this->events->delete($event);
    }

    public function rsvp(Event $event, User $user, string $status): EventAttendee
    {
        return $this->events->rsvp($event, $user, $status)->load('user');
    }
}
