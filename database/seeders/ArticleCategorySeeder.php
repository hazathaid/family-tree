<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Sejarah', 'Pengumuman', 'Cerita', 'Memorial'] as $name) {
            ArticleCategory::query()->firstOrCreate(['name' => $name], ['uuid' => (string) Str::uuid(), 'slug' => Str::slug($name)]);
        }
    }
}
