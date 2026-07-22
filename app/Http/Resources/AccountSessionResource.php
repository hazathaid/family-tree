<?php

namespace App\Http\Resources;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PersonalAccessToken $token */
        $token = $this->resource;

        return [
            'uuid' => $token->uuid,
            'device_name' => $token->name,
            'is_current' => (bool) $token->getAttribute('is_current'),
            'last_active_at' => $token->last_used_at?->toISOString() ?? $token->created_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
        ];
    }
}
