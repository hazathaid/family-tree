<?php

namespace App\Http\Requests\Timeline;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class TimelineIndexRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'family_uuid' => ['nullable', 'uuid', 'exists:families,uuid'],
            'type' => ['nullable', Rule::in(['articles', 'photos', 'events', 'members'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
