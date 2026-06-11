<?php

namespace App\Http\Requests\Family;

use App\Http\Requests\ApiFormRequest;

class UpdateFamilyRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
