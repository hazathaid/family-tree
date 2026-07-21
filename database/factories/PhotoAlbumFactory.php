<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\PhotoAlbum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PhotoAlbum> */
class PhotoAlbumFactory extends Factory
{
    public function definition(): array
    {
        return ['uuid' => (string) Str::uuid(), 'family_id' => Family::factory(), 'created_by' => User::factory(), 'name' => fake()->words(3, true), 'description' => fake()->optional()->sentence()];
    }
}
