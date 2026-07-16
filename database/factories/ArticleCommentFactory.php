<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ArticleComment> */
class ArticleCommentFactory extends Factory
{
    public function definition(): array
    {
        return ['uuid' => (string) Str::uuid(), 'article_id' => Article::factory(), 'user_id' => User::factory(), 'comment' => fake()->sentence()];
    }
}
