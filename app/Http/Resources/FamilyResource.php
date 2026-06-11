<?php

namespace App\Http\Resources;

use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Family $family */
        $family = $this->resource;

        return [
            'uuid' => $family->uuid,
            'name' => $family->name,
            'slug' => $family->slug,
            'description' => $family->description,
            'origin_city' => $family->origin_city,
            'logo' => $family->logo,
            'cover_image' => $family->cover_image,
            'created_by' => $family->created_by,
            'created_at' => $family->created_at?->toISOString(),
            'updated_at' => $family->updated_at?->toISOString(),
        ];
    }
}
