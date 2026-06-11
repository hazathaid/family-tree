<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Keluarga Besar '.fake()->lastName();

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->optional()->paragraph(),
            'origin_city' => fake()->optional()->city(),
            'logo' => null,
            'cover_image' => null,
            'created_by' => User::factory(),
        ];
    }
}
