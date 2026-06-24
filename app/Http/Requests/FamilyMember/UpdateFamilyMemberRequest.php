<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateFamilyMemberRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'family_branch_uuid' => ['nullable', 'uuid', 'exists:family_branches,uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'is_alive' => ['required', 'boolean'],
            'death_date' => [
                Rule::requiredIf(fn (): bool => $this->boolean('is_alive') === false),
                'nullable',
                'date',
                'after_or_equal:birth_date',
            ],
            'death_place' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
        ];
    }
}
