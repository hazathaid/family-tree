<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;

class MergeMemberRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'duplicate_uuid' => ['required', 'uuid', 'exists:family_members,uuid'],
        ];
    }
}
