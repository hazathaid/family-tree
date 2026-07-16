<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return ['uuid' => (string) Str::uuid(), 'family_id' => Family::factory(), 'author_id' => User::factory(), 'category_id' => ArticleCategory::factory(), 'title' => $title, 'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)), 'excerpt' => fake()->sentence(), 'content' => '<p>'.fake()->paragraph().'</p>', 'status' => Article::STATUS_DRAFT, 'is_featured' => false];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Article::STATUS_PUBLISHED, 'published_at' => now()]);
    }
}
