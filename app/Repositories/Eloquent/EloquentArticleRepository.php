<?php

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Models\Family;
use App\Models\FamilyUserRole;
use App\Models\User;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function paginateForUser(User $user, array $filters, int $perPage): LengthAwarePaginator
    {
        return Article::query()->with(['family', 'category', 'author'])->withCount(['likes', 'comments'])
            ->whereHas('family.userRoles', fn ($query) => $query->where('user_id', $user->id))
            ->where(function ($query) use ($user): void {
                $query->where('status', Article::STATUS_PUBLISHED)
                    ->orWhere('author_id', $user->id)
                    ->orWhereHas('family.userRoles', fn ($roles) => $roles->where('user_id', $user->id)->whereIn('role', [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN]));
            })
            ->when($filters['family_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('family', fn ($family) => $family->where('uuid', $uuid)))
            ->when($filters['category_uuid'] ?? null, fn ($query, $uuid) => $query->whereHas('category', fn ($category) => $category->where('uuid', $uuid)))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when(array_key_exists('featured', $filters), fn ($query) => $query->where('is_featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOL)))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->where('title', 'like', '%'.$search.'%')->orWhere('content', 'like', '%'.$search.'%')))
            ->latest()->paginate($perPage);
    }

    public function featured(Family $family, int $perPage): LengthAwarePaginator
    {
        return Article::query()->with(['family', 'category', 'author'])->withCount(['likes', 'comments'])->whereBelongsTo($family)->where('status', Article::STATUS_PUBLISHED)->where('is_featured', true)->latest('featured_at')->paginate($perPage);
    }

    public function create(array $attributes): Article
    {
        return Article::query()->create($attributes);
    }

    public function update(Article $article, array $attributes): Article
    {
        $article->update($attributes);

        return $article->refresh();
    }

    public function delete(Article $article): void
    {
        $article->delete();
    }

    public function slugExists(Family $family, string $slug, ?int $ignoreId = null): bool
    {
        return Article::query()->whereBelongsTo($family)->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists();
    }

    public function loadDetails(Article $article, ?User $user = null): Article
    {
        $article->load(['family', 'category', 'author'])->loadCount(['likes', 'comments']);
        if ($user) {
            $article->setAttribute('is_liked_by_me', $article->likes()->where('user_id', $user->id)->exists());
        }

        return $article;
    }
}
