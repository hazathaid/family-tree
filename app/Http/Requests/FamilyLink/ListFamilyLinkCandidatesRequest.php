<?php

namespace App\Http\Requests\FamilyLink;

use App\Http\Requests\ApiFormRequest;

class ListFamilyLinkCandidatesRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['sometimes', 'integer', 'between:1,50'],
        ];
    }
}
