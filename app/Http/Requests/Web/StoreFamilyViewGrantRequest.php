<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyViewGrantRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'anchor_member_uuid' => ['required', 'string', 'uuid', 'exists:family_members,uuid'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'expires_in_days' => ['sometimes', 'integer', 'between:1,90'],
        ];
    }
}
