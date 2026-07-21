<?php

namespace App\Http\Requests\Event;

use App\Http\Requests\ApiFormRequest;

class UpdateEventRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'event_date' => ['sometimes', 'required', 'date', 'after:now'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
