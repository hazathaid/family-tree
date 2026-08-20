<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;

class ImportMembersRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A CSV file is required.',
            'file.max' => 'The CSV file must not be larger than 2 MB.',
        ];
    }
}
