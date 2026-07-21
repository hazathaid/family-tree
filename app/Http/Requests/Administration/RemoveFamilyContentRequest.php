<?php

namespace App\Http\Requests\Administration;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class RemoveFamilyContentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'content_type' => ['required', Rule::in(['article', 'photo', 'event'])],
            'content_uuid' => ['required', 'uuid'],
        ];
    }
}
