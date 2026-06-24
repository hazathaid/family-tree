<?php

namespace App\Http\Resources;

use App\Models\MemberRelationship;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberRelationshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MemberRelationship $relationship */
        $relationship = $this->resource;

        return [
            'uuid' => $relationship->uuid,
            'family_uuid' => $relationship->family->uuid,
            'source_member_uuid' => $relationship->sourceMember->uuid,
            'source_member_name' => $relationship->sourceMember->full_name,
            'target_member_uuid' => $relationship->targetMember->uuid,
            'target_member_name' => $relationship->targetMember->full_name,
            'relationship_type' => $relationship->relationship_type,
            'start_date' => $relationship->start_date?->toDateString(),
            'end_date' => $relationship->end_date?->toDateString(),
            'notes' => $relationship->notes,
            'created_at' => $relationship->created_at?->toISOString(),
            'updated_at' => $relationship->updated_at?->toISOString(),
        ];
    }
}
