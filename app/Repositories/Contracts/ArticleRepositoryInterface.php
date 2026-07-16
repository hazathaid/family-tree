<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use App\Models\Family;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator;

    public function featured(Family $family, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): Article;

    public function update(Article $article, array $attributes): Article;

    public function delete(Article $article): void;

    public function slugExists(Family $family, string $slug, ?int $ignoreId = null): bool;

    public function loadDetails(Article $article, ?User $user = null): Article;
}
