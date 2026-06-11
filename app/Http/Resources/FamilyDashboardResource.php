<?php

namespace App\Http\Resources;

use App\DTOs\FamilyDashboardData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var FamilyDashboardData $dashboard */
        $dashboard = $this->resource;

        return $dashboard->toArray();
    }
}
