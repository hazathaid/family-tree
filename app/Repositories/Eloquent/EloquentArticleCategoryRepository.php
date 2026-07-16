<?php

namespace App\Repositories\Eloquent;

use App\Models\ArticleCategory;
use App\Repositories\Contracts\ArticleCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentArticleCategoryRepository implements ArticleCategoryRepositoryInterface
{
    public function paginate(?string $search, int $perPage): LengthAwarePaginator
    {
        return ArticleCategory::query()->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))->orderBy('name')->paginate($perPage);
    }

    public function create(array $attributes): ArticleCategory
    {
        return ArticleCategory::query()->create($attributes);
    }

    public function update(ArticleCategory $category, array $attributes): ArticleCategory
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function delete(ArticleCategory $category): void
    {
        $category->delete();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return ArticleCategory::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists();
    }

    public function hasArticles(ArticleCategory $category): bool
    {
        return $category->articles()->exists();
    }
}
