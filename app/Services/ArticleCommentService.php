<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ArticleCommentService
{
    public function __construct(private readonly ArticleCommentRepositoryInterface $comments) {}

    public function create(Article $article, User $user, string $text): ArticleComment
    {
        $this->ensurePublished($article);

        return $this->comments->create(['article_id' => $article->id, 'user_id' => $user->id, 'comment' => $text]);
    }

    public function update(ArticleComment $comment, string $text): ArticleComment
    {
        return $this->comments->update($comment, $text);
    }

    public function delete(ArticleComment $comment): void
    {
        $this->comments->delete($comment);
    }

    private function ensurePublished(Article $article): void
    {
        if ($article->status !== Article::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['article' => ['Only published articles accept comments.']]);
        }
    }
}
