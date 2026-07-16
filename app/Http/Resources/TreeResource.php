<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tree = $this->resource;
        $tree['nodes'] = array_map(function (array $node): array {
            $node['profile_photo_url'] = $node['profile_photo'] ? Storage::url($node['profile_photo']) : null;
            unset($node['id'], $node['profile_photo']);

            return $node;
        }, $tree['nodes']);

        return $tree;
    }
}
