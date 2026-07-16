<?php

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Models\ArticleLike;
use App\Models\User;
use App\Repositories\Contracts\ArticleLikeRepositoryInterface;

class EloquentArticleLikeRepository implements ArticleLikeRepositoryInterface
{
    public function like(Article $article, User $user): void
    {
        ArticleLike::query()->firstOrCreate(['article_id' => $article->id, 'user_id' => $user->id]);
    }

    public function unlike(Article $article, User $user): void
    {
        ArticleLike::query()->where('article_id', $article->id)->where('user_id', $user->id)->delete();
    }

    public function count(Article $article): int
    {
        return $article->likes()->count();
    }

    public function exists(Article $article, User $user): bool
    {
        return $article->likes()->where('user_id', $user->id)->exists();
    }
}
