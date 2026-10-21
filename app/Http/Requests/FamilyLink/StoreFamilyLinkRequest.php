<?php

namespace App\Http\Requests\FamilyLink;

use App\Http\Requests\ApiFormRequest;

class StoreFamilyLinkRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'member_uuid' => ['required', 'string', 'uuid', 'exists:family_members,uuid'],
            'linked_member_uuid' => ['required', 'string', 'uuid', 'different:member_uuid', 'exists:family_members,uuid'],
        ];
    }
}
