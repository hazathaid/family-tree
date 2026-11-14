<?php

namespace App\Http\Requests\Relationship;

use App\Http\Requests\ApiFormRequest;
use App\Models\MemberRelationship;
use Illuminate\Validation\Rule;

class MarriageEndRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date'],
            'end_reason' => ['required', Rule::in(MemberRelationship::END_REASONS)],
        ];
    }
}
