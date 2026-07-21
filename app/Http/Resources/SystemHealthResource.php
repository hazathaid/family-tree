<?php

namespace App\Http\Resources;

use App\DTOs\SystemHealthData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SystemHealthData */
class SystemHealthResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'checks' => $this->checks,
        ];
    }
}
