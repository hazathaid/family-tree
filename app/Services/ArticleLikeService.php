<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Repositories\Contracts\ArticleLikeRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ArticleLikeService
{
    public function __construct(private readonly ArticleLikeRepositoryInterface $likes) {}

    public function like(Article $article, User $user): array
    {
        $this->ensurePublished($article);
        $this->likes->like($article, $user);

        return $this->state($article, $user);
    }

    public function unlike(Article $article, User $user): array
    {
        $this->ensurePublished($article);
        $this->likes->unlike($article, $user);

        return $this->state($article, $user);
    }

    private function state(Article $article, User $user): array
    {
        return ['likes_count' => $this->likes->count($article), 'is_liked_by_me' => $this->likes->exists($article, $user)];
    }

    private function ensurePublished(Article $article): void
    {
        if ($article->status !== Article::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['article' => ['Only published articles can be liked.']]);
        }
    }
}
