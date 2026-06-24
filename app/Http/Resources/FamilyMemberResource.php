<?php

namespace App\Http\Resources;

use App\Models\FamilyMember;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FamilyMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FamilyMember $member */
        $member = $this->resource;
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'uuid' => $member->uuid,
            'family_uuid' => $member->family->uuid,
            'family_branch_uuid' => $member->branch?->uuid,
            'full_name' => $member->full_name,
            'nickname' => $member->nickname,
            'gender' => $member->gender,
            'birth_date' => $member->birth_date?->toDateString(),
            'birth_place' => $member->birth_place,
            'is_alive' => $member->is_alive,
            'death_date' => $member->death_date?->toDateString(),
            'death_place' => $member->death_place,
            'biography' => $member->biography,
            'profile_photo' => $member->profile_photo,
            'profile_photo_url' => $member->profile_photo ? $disk->url($member->profile_photo) : null,
            'profile_photo_thumbnail' => $member->profile_photo_thumbnail,
            'profile_photo_thumbnail_url' => $member->profile_photo_thumbnail ? $disk->url($member->profile_photo_thumbnail) : null,
            'created_by' => $member->created_by,
            'created_at' => $member->created_at?->toISOString(),
            'updated_at' => $member->updated_at?->toISOString(),
        ];
    }
}
