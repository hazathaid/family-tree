<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\ApiFormRequest;

class UpdateNotificationPreferencesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email_events' => ['nullable', 'boolean'],
            'email_birthdays' => ['nullable', 'boolean'],
            'email_articles' => ['nullable', 'boolean'],
            'in_app_activity' => ['nullable', 'boolean'],
        ];
    }

    public function preferences(): array
    {
        return collect(array_keys($this->rules()))->mapWithKeys(fn (string $key): array => [$key => $this->boolean($key)])->all();
    }
}
