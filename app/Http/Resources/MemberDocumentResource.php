<?php

namespace App\Http\Resources;

use App\Models\MemberDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MemberDocument $document */
        $document = $this->resource;

        return [
            'uuid' => $document->uuid,
            'family_uuid' => $document->family->uuid,
            'member_uuid' => $document->member->uuid,
            'title' => $document->title,
            'category' => $document->category,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'document_date' => $document->document_date?->toDateString(),
            'notes' => $document->notes,
            'download_url' => url('/api/v1/member-documents/'.$document->uuid.'/download'),
            'uploaded_by' => [
                'uuid' => $document->uploader->uuid,
                'name' => $document->uploader->name,
            ],
            'created_at' => $document->created_at?->toISOString(),
        ];
    }
}
