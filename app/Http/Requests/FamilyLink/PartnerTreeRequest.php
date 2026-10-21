<?php

namespace App\Http\Requests\FamilyLink;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class PartnerTreeRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'family_uuid' => ['sometimes', 'string', 'uuid', 'exists:families,uuid'],
            'mode' => ['sometimes', Rule::in(['ancestor', 'descendant', 'full'])],
            'depth' => ['sometimes', 'integer', 'between:1,20'],
            'layout' => ['sometimes', Rule::in(['vertical', 'horizontal', 'radial', 'compact'])],
        ];
    }
}
