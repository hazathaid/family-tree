<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ArticleCommentService
{
    public function __construct(
        private readonly ArticleCommentRepositoryInterface $comments,
        private readonly NotificationService $notifications,
    ) {}

    public function create(Article $article, User $user, string $text): ArticleComment
    {
        $this->ensurePublished($article);

        $comment = $this->comments->create(['article_id' => $article->id, 'user_id' => $user->id, 'comment' => $text]);
        $this->notifyAuthor($article, $user);

        return $comment;
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

    private function notifyAuthor(Article $article, User $commenter): void
    {
        $author = $article->author;

        if (! $author instanceof User || $author->id === $commenter->id) {
            return;
        }

        $this->notifications->dispatchForUser(
            $author,
            NotificationService::CATEGORY_FAMILY_UPDATES,
            'article_comment',
            'Komentar baru pada artikel Anda',
            $commenter->name.' mengomentari "'.$article->title.'".',
            ['article_uuid' => $article->uuid, 'target_type' => 'article', 'target_uuid' => $article->uuid],
        );
    }
}
