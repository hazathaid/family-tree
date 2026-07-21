<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ActivityLog $activity */
        $activity = $this->resource;

        return [
            'uuid' => $activity->uuid,
            'family_uuid' => $activity->family->uuid,
            'type' => $activity->activity_type,
            'message' => $this->message($activity),
            'payload' => $activity->payload,
            'user' => $activity->user ? ['uuid' => $activity->user->uuid, 'name' => $activity->user->name] : null,
            'created_at' => $activity->created_at?->toISOString(),
        ];
    }

    private function message(ActivityLog $activity): string
    {
        return match ($activity->activity_type) {
            ActivityLog::MEMBER_CREATED => ($activity->payload['name'] ?? 'Anggota').' ditambahkan ke keluarga',
            ActivityLog::ARTICLE_CREATED => 'Artikel "'.($activity->payload['title'] ?? 'Tanpa judul').'" dibuat',
            ActivityLog::PHOTO_UPLOADED => 'Foto baru diunggah',
            ActivityLog::EVENT_CREATED => 'Event "'.($activity->payload['title'] ?? 'Tanpa judul').'" dibuat',
            default => 'Aktivitas keluarga diperbarui',
        };
    }
}
