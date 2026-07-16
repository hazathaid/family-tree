<?php

namespace App\Repositories\Contracts;

use App\Models\ArticleCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleCategoryRepositoryInterface
{
    public function paginate(?string $search, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): ArticleCategory;

    public function update(ArticleCategory $category, array $attributes): ArticleCategory;

    public function delete(ArticleCategory $category): void;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    public function hasArticles(ArticleCategory $category): bool;
}
