<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyBranch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FamilyBranch>
 */
class FamilyBranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'family_id' => Family::factory(),
            'name' => 'Cabang '.fake()->city(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
