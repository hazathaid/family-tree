<?php

namespace App\Http\Resources;

use App\Models\MemberPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MemberPhoto $photo */
        $photo = $this->resource;

        return ['uuid' => $photo->uuid, 'family_uuid' => $photo->family->uuid, 'album' => $photo->album ? ['uuid' => $photo->album->uuid, 'name' => $photo->album->name] : null, 'url' => asset('storage/'.$photo->path), 'thumbnail_url' => asset('storage/'.$photo->thumbnail_path), 'original_name' => $photo->original_name, 'mime_type' => $photo->mime_type, 'size' => $photo->size, 'width' => $photo->width, 'height' => $photo->height, 'caption' => $photo->caption, 'captured_at' => $photo->captured_at?->toISOString(), 'uploaded_by' => ['uuid' => $photo->uploader->uuid, 'name' => $photo->uploader->name], 'tagged_members' => $photo->taggedMembers->map(fn ($member) => ['uuid' => $member->uuid, 'full_name' => $member->full_name])->values(), 'created_at' => $photo->created_at?->toISOString()];
    }
}
