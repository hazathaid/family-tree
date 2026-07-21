<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'activity_type' => ActivityLog::MEMBER_CREATED,
            'payload' => ['name' => fake()->name(), 'subject_uuid' => (string) Str::uuid()],
        ];
    }
}
