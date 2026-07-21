<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebRemoveFamilyContentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content_type' => ['required', Rule::in(['article', 'photo', 'event'])],
            'content_uuid' => ['required', 'uuid'],
            'confirm' => ['required', 'accepted'],
        ];
    }
}
