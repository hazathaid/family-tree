<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'members' => FamilyMemberResource::collection($this->resource['members']),
            'articles' => ArticleResource::collection($this->resource['articles']),
            'events' => EventResource::collection($this->resource['events']),
            'pagination' => [
                'page' => $request->integer('page', 1),
                'limit' => $request->integer('limit', 15),
                'has_more' => collect($this->resource)->contains(fn ($items) => $items->count() === $request->integer('limit', 15)),
            ],
        ];
    }
}
