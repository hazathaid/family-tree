<?php

namespace App\Http\Requests\Relationship;

use App\Http\Requests\ApiFormRequest;

class ResolveRelationshipRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'source_member_uuid' => ['required', 'uuid', 'exists:family_members,uuid'],
            'target_member_uuid' => ['required', 'uuid', 'exists:family_members,uuid'],
        ];
    }
}
