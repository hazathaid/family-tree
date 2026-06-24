<?php

namespace App\Http\Requests\Relationship;

use App\Http\Requests\ApiFormRequest;

class ResolveRelationshipRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'source_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'target_member_id' => ['required', 'integer', 'exists:family_members,id'],
        ];
    }
}
