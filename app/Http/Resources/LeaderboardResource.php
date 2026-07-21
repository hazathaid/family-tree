<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array{rank: int, uuid: string, name: string, avatar?: string|null, logo?: string|null, points: int} $row */
        $row = (array) $this->resource;

        return [
            'rank' => $row['rank'],
            'uuid' => $row['uuid'],
            'name' => $row['name'],
            'image' => $row['avatar'] ?? $row['logo'] ?? null,
            'points' => $row['points'],
        ];
    }
}
