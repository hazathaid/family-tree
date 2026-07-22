<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class UpdateNotificationPreferencesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'boolean'],
            'push' => ['required', 'boolean'],
            'event_reminders' => ['required', 'boolean'],
            'family_updates' => ['required', 'boolean'],
        ];
    }
}
