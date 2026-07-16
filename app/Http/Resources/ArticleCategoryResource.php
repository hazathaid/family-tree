<?php

namespace App\Http\Resources;

use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    { /** @var ArticleCategory $category */ $category = $this->resource;

        return ['uuid' => $category->uuid, 'name' => $category->name, 'slug' => $category->slug, 'description' => $category->description];
    }
}
