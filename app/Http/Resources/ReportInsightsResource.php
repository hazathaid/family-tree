<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportInsightsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cities' => $this->resource['cities'],
            'growth' => $this->resource['growth'],
            'activity' => $this->resource['activity'],
        ];
    }
}
