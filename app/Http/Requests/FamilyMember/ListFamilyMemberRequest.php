<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListFamilyMemberRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'family_uuid' => ['required', 'uuid', 'exists:families,uuid'],
            'search' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'is_alive' => ['nullable', 'boolean'],
            'branch_uuid' => ['nullable', 'uuid', 'exists:family_branches,uuid'],
            'sort' => ['nullable', Rule::in(['name', 'name_desc', 'newest', 'oldest'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
