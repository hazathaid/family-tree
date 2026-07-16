<?php

namespace App\Policies;

use App\Models\ArticleCategory;
use App\Models\User;

class ArticleCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ArticleCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, ArticleCategory $category): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, ArticleCategory $category): bool
    {
        return $user->hasRole('super-admin');
    }
}
