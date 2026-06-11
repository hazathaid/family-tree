<?php

namespace App\Http\Resources;

use App\Models\FamilyUserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyUserRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FamilyUserRole $membership */
        $membership = $this->resource;

        return [
            'uuid' => $membership->uuid,
            'family_uuid' => $membership->family->uuid,
            'user' => new UserResource($membership->user),
            'role' => $membership->role,
            'created_at' => $membership->created_at?->toISOString(),
            'updated_at' => $membership->updated_at?->toISOString(),
        ];
    }
}
