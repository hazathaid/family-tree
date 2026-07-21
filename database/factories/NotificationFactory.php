<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Notification> */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'type' => 'general',
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
            'data' => [],
            'is_read' => false,
        ];
    }
}
