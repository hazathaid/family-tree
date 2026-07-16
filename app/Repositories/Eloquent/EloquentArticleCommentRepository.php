<?php

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Repositories\Contracts\ArticleCommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentArticleCommentRepository implements ArticleCommentRepositoryInterface
{
    public function paginate(Article $article, int $perPage): LengthAwarePaginator
    {
        return $article->comments()->with('user')->oldest()->paginate($perPage);
    }

    public function create(array $attributes): ArticleComment
    {
        return ArticleComment::query()->create($attributes)->load('user');
    }

    public function update(ArticleComment $comment, string $text): ArticleComment
    {
        $comment->update(['comment' => $text]);

        return $comment->refresh()->load('user');
    }

    public function delete(ArticleComment $comment): void
    {
        $comment->delete();
    }
}
