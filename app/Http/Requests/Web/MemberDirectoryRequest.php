<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberDirectoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'is_alive' => ['nullable', Rule::in(['0', '1'])],
            'branch' => ['nullable', 'uuid'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'name'])],
        ];
    }
}
