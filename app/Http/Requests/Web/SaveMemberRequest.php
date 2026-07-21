<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'family_branch_uuid' => ['nullable', 'uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'is_alive' => ['required', 'boolean'],
            'death_date' => [Rule::requiredIf(fn (): bool => ! $this->boolean('is_alive')), 'nullable', 'date', 'after_or_equal:birth_date'],
            'death_place' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
