<?php

namespace Database\Factories;

use App\Models\ArticleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ArticleCategory> */
class ArticleCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['uuid' => (string) Str::uuid(), 'name' => $name, 'slug' => Str::slug($name), 'description' => fake()->sentence()];
    }
}
