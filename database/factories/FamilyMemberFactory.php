<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FamilyMember>
 */
class FamilyMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'family_branch_id' => null,
            'full_name' => fake()->name(),
            'nickname' => fake()->optional()->firstName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'birth_place' => fake()->optional()->city(),
            'is_alive' => true,
            'death_date' => null,
            'death_place' => null,
            'biography' => fake()->optional()->paragraph(),
            'profile_photo' => null,
            'profile_photo_thumbnail' => null,
            'created_by' => User::factory(),
        ];
    }

    public function deceased(): static
    {
        return $this->state(fn (): array => [
            'is_alive' => false,
            'death_date' => fake()->date('Y-m-d', 'now'),
            'death_place' => fake()->city(),
        ]);
    }
}
