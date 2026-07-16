<?php

namespace App\Repositories\Contracts;

use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleCommentRepositoryInterface
{
    public function paginate(Article $article, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): ArticleComment;

    public function update(ArticleComment $comment, string $text): ArticleComment;

    public function delete(ArticleComment $comment): void;
}
