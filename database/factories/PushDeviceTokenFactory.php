<?php

namespace Database\Factories;

use App\Models\PushDeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PushDeviceToken> */
class PushDeviceTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'platform' => fake()->randomElement(PushDeviceToken::PLATFORMS),
            'token' => fake()->unique()->sha256(),
            'is_active' => true,
            'last_used_at' => now(),
        ];
    }
}
