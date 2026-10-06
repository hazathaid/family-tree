<?php

namespace App\Http\Resources;

use App\Models\TreeExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreeExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TreeExport $export */
        $export = $this->resource;

        return [
            'uuid' => $export->uuid,
            'status' => $export->status,
            'format' => $export->format,
            'mode' => $export->mode,
            'depth' => $export->depth,
            'layout' => $export->layout,
            'paper_size' => $export->paper_size,
            'error' => $export->error,
            'download_url' => $export->isCompleted()
                ? url('/api/v1/tree/exports/'.$export->uuid.'/download')
                : null,
            'completed_at' => $export->completed_at?->toISOString(),
            'expires_at' => $export->expires_at?->toISOString(),
            'created_at' => $export->created_at?->toISOString(),
        ];
    }
}
