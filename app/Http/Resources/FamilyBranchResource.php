<?php

namespace App\Http\Resources;

use App\Models\FamilyBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FamilyBranch $branch */
        $branch = $this->resource;

        return [
            'uuid' => $branch->uuid,
            'family_uuid' => $branch->family->uuid,
            'name' => $branch->name,
            'description' => $branch->description,
            'created_at' => $branch->created_at?->toISOString(),
            'updated_at' => $branch->updated_at?->toISOString(),
        ];
    }
}
