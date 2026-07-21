<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EventAttendee> */
class EventAttendeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(EventAttendee::STATUSES),
        ];
    }
}
