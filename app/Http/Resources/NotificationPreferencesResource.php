<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $preferences = is_array($this->resource) ? $this->resource : [];

        return [
            'email' => (bool) ($preferences['email'] ?? true),
            'push' => (bool) ($preferences['push'] ?? true),
            'event_reminders' => (bool) ($preferences['event_reminders'] ?? true),
            'family_updates' => (bool) ($preferences['family_updates'] ?? true),
        ];
    }
}
