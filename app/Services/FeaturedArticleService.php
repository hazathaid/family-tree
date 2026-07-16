<?php

namespace App\Services;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Validation\ValidationException;

class FeaturedArticleService
{
    public function __construct(private readonly ArticleRepositoryInterface $articles) {}

    public function feature(Article $article): Article
    {
        if ($article->status !== Article::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['article' => ['Only published articles can be featured.']]);
        }

        return $this->articles->update($article, ['is_featured' => true, 'featured_at' => $article->featured_at ?? now()]);
    }

    public function unfeature(Article $article): Article
    {
        return $this->articles->update($article, ['is_featured' => false, 'featured_at' => null]);
    }
}
