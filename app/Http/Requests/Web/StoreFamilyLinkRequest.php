<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'member_uuid' => ['required', 'string', 'exists:family_members,uuid'],
            'linked_member_uuid' => ['required', 'string', 'different:member_uuid', 'exists:family_members,uuid'],
        ];
    }
}
