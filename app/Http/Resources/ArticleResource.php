<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Article $article */
        $article = $this->resource;

        return [
            'uuid' => $article->uuid,
            'family_uuid' => $article->family->uuid,
            'category' => new ArticleCategoryResource($article->category),
            'author' => ['uuid' => $article->author->uuid, 'name' => $article->author->name],
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'content' => $article->content,
            'featured_image_url' => $article->featured_image ? asset('storage/'.$article->featured_image) : null,
            'status' => $article->status,
            'is_featured' => $article->is_featured,
            'published_at' => $article->published_at?->toISOString(),
            'likes_count' => (int) ($article->getAttribute('likes_count') ?? 0),
            'comments_count' => (int) ($article->getAttribute('comments_count') ?? 0),
            'is_liked_by_me' => (bool) ($article->getAttribute('is_liked_by_me') ?? false),
        ];
    }
}
