<?php

namespace App\Http\Resources;

use App\Models\ArticleComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    { /** @var ArticleComment $comment */ $comment = $this->resource;

        return ['uuid' => $comment->uuid, 'user' => ['uuid' => $comment->user->uuid, 'name' => $comment->user->name], 'comment' => $comment->comment, 'created_at' => $comment->created_at?->toISOString(), 'updated_at' => $comment->updated_at?->toISOString()];
    }
}
