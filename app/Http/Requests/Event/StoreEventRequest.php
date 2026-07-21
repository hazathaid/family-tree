<?php

namespace App\Http\Requests\Event;

use App\Http\Requests\ApiFormRequest;

class StoreEventRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'family_uuid' => ['required', 'uuid', 'exists:families,uuid'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date', 'after:now'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
