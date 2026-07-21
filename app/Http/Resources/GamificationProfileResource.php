<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GamificationProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'points' => $this->resource['points'],
            'badges' => BadgeResource::collection($this->resource['badges']),
        ];
    }
}
