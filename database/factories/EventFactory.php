<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'event_date' => now()->addWeek(),
            'location' => fake()->city(),
            'organizer_id' => User::factory(),
        ];
    }
}
