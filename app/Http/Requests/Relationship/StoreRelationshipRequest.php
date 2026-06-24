<?php

namespace App\Http\Requests\Relationship;

use App\Http\Requests\ApiFormRequest;
use App\Models\MemberRelationship;
use Illuminate\Validation\Rule;

class StoreRelationshipRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'family_uuid' => ['required_without:family_id', 'uuid', 'exists:families,uuid'],
            'family_id' => ['required_without:family_uuid', 'integer', 'exists:families,id'],
            'source_member_uuid' => ['required_without:source_member_id', 'uuid', 'exists:family_members,uuid'],
            'source_member_id' => ['required_without:source_member_uuid', 'integer', 'exists:family_members,id'],
            'target_member_uuid' => ['required_without:target_member_id', 'uuid', 'exists:family_members,uuid'],
            'target_member_id' => ['required_without:target_member_uuid', 'integer', 'exists:family_members,id'],
            'relationship_type' => ['required', Rule::in(MemberRelationship::TYPES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
