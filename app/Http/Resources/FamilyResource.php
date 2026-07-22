<?php

namespace App\Http\Resources;

use App\Models\Family;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Family $family */
        $family = $this->resource;
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'uuid' => $family->uuid,
            'name' => $family->name,
            'slug' => $family->slug,
            'description' => $family->description,
            'origin_city' => $family->origin_city,
            'logo' => $family->logo,
            'logo_url' => $family->logo ? $disk->url($family->logo) : null,
            'cover_image' => $family->cover_image,
            'cover_image_url' => $family->cover_image ? $disk->url($family->cover_image) : null,
            'privacy' => 'members_only',
            'current_user_role' => $family->relationLoaded('userRoles')
                ? $family->getRelation('userRoles')->first()?->role
                : $family->userRoles()->where('user_id', $request->user()?->id)->value('role'),
            'created_by' => $family->created_by,
            'created_at' => $family->created_at?->toISOString(),
            'updated_at' => $family->updated_at?->toISOString(),
        ];
    }
}
