<?php

namespace App\Http\Resources;

use App\Models\FamilyLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class FamilyLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FamilyLink $link */
        $link = $this->resource;

        return [
            'uuid' => $link->uuid,
            'link_type' => $link->link_type,
            'status' => $link->status,
            'family' => [
                'uuid' => $link->family?->uuid,
                'name' => $link->family?->name,
            ],
            'linked_family' => [
                'uuid' => $link->linkedFamily?->uuid,
                'name' => $link->linkedFamily?->name,
            ],
            'member' => [
                'uuid' => $link->member?->uuid,
                'full_name' => $link->member?->full_name,
            ],
            'linked_member' => [
                'uuid' => $link->linkedMember?->uuid,
                'full_name' => $link->linkedMember?->full_name,
            ],
            'children_home_family' => $link->childrenHomeFamily ? [
                'uuid' => $link->childrenHomeFamily->uuid,
                'name' => $link->childrenHomeFamily->name,
            ] : null,
            'expires_at' => $link->expires_at?->toISOString(),
            'revoked_at' => $link->revoked_at?->toISOString(),
            'can_approve' => $link->isPending() && Gate::allows('approve', $link),
            'can_revoke' => ! in_array($link->status, [FamilyLink::STATUS_REVOKED, FamilyLink::STATUS_EXPIRED], true)
                && Gate::allows('revoke', $link),
            'created_at' => $link->created_at?->toISOString(),
            'updated_at' => $link->updated_at?->toISOString(),
        ];
    }
}
