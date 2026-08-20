<?php

namespace App\Http\Requests\Family;

use App\Http\Requests\ApiFormRequest;

class ImportGedcomRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A GEDCOM file is required.',
            'file.max' => 'The GEDCOM file must not be larger than 10 MB.',
        ];
    }
}
