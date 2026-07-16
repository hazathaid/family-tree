<?php

namespace App\Policies;

use App\Models\ArticleComment;
use App\Models\FamilyUserRole;
use App\Models\User;

class ArticleCommentPolicy
{
    public function update(User $user, ArticleComment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, ArticleComment $comment): bool
    {
        return $comment->user_id === $user->id || $comment->article->family->userRoles()->where('user_id', $user->id)->whereIn('role', [FamilyUserRole::ROLE_OWNER, FamilyUserRole::ROLE_ADMIN])->exists();
    }
}
