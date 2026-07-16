<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\FamilyUserRole;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Article $article): bool
    {
        $role = $this->role($user, $article);

        return $role !== null && ($article->status === Article::STATUS_PUBLISHED || $article->author_id === $user->id || in_array($role, [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true));
    }

    public function update(User $user, Article $article): bool
    {
        return ($article->author_id === $user->id && $article->status === Article::STATUS_DRAFT) || $this->isAdmin($user, $article);
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->update($user, $article);
    }

    public function interact(User $user, Article $article): bool
    {
        return $article->status === Article::STATUS_PUBLISHED && $this->role($user, $article) !== null;
    }

    public function feature(User $user, Article $article): bool
    {
        return $this->isAdmin($user, $article);
    }

    private function isAdmin(User $user, Article $article): bool
    {
        return in_array($this->role($user, $article), [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN], true);
    }

    private function role(User $user, Article $article): ?string
    {
        return $article->family->userRoles()->where('user_id', $user->id)->value('role');
    }
}
