<?php

namespace App\Http\Resources;

use App\Models\PushDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushDeviceTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PushDeviceToken $device */
        $device = $this->resource;

        return [
            'uuid' => $device->uuid,
            'platform' => $device->platform,
            'is_active' => $device->is_active,
            'last_used_at' => $device->last_used_at?->toISOString(),
        ];
    }
}
