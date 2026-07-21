<?php

namespace App\Http\Resources;

use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Family */
class AdminFamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'origin_city' => $this->origin_city,
            'creator' => $this->whenLoaded('creator', fn () => [
                'uuid' => $this->creator->uuid,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'members_count' => $this->whenCounted('members'),
            'articles_count' => $this->whenCounted('articles'),
            'photos_count' => $this->whenCounted('photos'),
            'events_count' => $this->whenCounted('events'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
