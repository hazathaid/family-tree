<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Event $event */
        $event = $this->resource;

        return [
            'uuid' => $event->uuid,
            'family_uuid' => $event->family->uuid,
            'title' => $event->title,
            'description' => $event->description,
            'event_date' => $event->event_date->toISOString(),
            'location' => $event->location,
            'organizer' => ['uuid' => $event->organizer->uuid, 'name' => $event->organizer->name],
            'rsvp_counts' => [
                'total' => (int) ($event->getAttribute('attendees_count') ?? 0),
                'yes' => (int) ($event->getAttribute('yes_count') ?? 0),
                'maybe' => (int) ($event->getAttribute('maybe_count') ?? 0),
            ],
            'my_rsvp' => $event->getAttribute('my_rsvp'),
            'attendees' => EventAttendeeResource::collection($this->whenLoaded('attendees')),
        ];
    }
}
