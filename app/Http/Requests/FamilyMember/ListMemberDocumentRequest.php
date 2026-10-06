<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;

class ListMemberDocumentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
