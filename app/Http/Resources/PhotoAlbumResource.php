<?php

namespace App\Http\Resources;

use App\Models\PhotoAlbum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoAlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PhotoAlbum $album */
        $album = $this->resource;

        return ['uuid' => $album->uuid, 'family_uuid' => $album->family->uuid, 'name' => $album->name, 'description' => $album->description, 'photos_count' => (int) ($album->getAttribute('photos_count') ?? 0), 'created_by' => ['uuid' => $album->creator->uuid, 'name' => $album->creator->name], 'created_at' => $album->created_at?->toISOString()];
    }
}
