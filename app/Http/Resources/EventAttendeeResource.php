<?php

namespace App\Http\Resources;

use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventAttendeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var EventAttendee $attendee */
        $attendee = $this->resource;

        return [
            'uuid' => $attendee->uuid,
            'user' => ['uuid' => $attendee->user->uuid, 'name' => $attendee->user->name],
            'status' => $attendee->status,
        ];
    }
}
