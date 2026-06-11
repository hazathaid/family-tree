<?php

namespace App\Http\Requests\Family;

use App\Http\Requests\ApiFormRequest;

class UpdateFamilyBranchRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
