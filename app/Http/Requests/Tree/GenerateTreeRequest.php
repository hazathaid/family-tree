<?php

namespace App\Http\Requests\Tree;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class GenerateTreeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['member_uuid' => ['required', 'uuid', 'exists:family_members,uuid'], 'mode' => ['sometimes', Rule::in(['ancestor', 'descendant', 'full'])],
            'depth' => ['sometimes', 'integer', 'between:1,20'], 'layout' => ['sometimes', Rule::in(['vertical', 'horizontal', 'radial'])]];
    }
}
