<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use App\Models\User;

interface ArticleLikeRepositoryInterface
{
    public function like(Article $article, User $user): void;

    public function unlike(Article $article, User $user): void;

    public function count(Article $article): int;

    public function exists(Article $article, User $user): bool;
}
