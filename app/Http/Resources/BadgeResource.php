<?php

namespace App\Http\Resources;

use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UserBadge $award */
        $award = $this->resource;

        return [
            'uuid' => $award->badge->uuid,
            'code' => $award->badge->code,
            'name' => $award->badge->name,
            'description' => $award->badge->description,
            'awarded_at' => $award->awarded_at?->toISOString(),
        ];
    }
}
