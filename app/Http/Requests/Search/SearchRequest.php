<?php

namespace App\Http\Requests\Search;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'family_uuid' => ['nullable', 'uuid', 'exists:families,uuid'],
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'generation' => ['nullable', 'integer', 'between:-100,100', 'required_with:root_member_uuid'],
            'status' => ['nullable', Rule::in(['alive', 'deceased'])],
            'root_member_uuid' => ['nullable', 'uuid', 'exists:family_members,uuid', 'required_with:generation'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
