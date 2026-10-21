<?php

namespace App\Http\Resources;

use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyLinkCandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FamilyMember $member */
        $member = $this->resource;

        return [
            'uuid' => $member->uuid,
            'full_name' => $member->full_name,
            'nickname' => $member->nickname,
            'gender' => $member->gender,
            'is_alive' => $member->is_alive,
            'family' => [
                'uuid' => $member->family?->uuid,
                'name' => $member->family?->name,
            ],
        ];
    }
}
