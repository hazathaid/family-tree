<?php

namespace App\Http\Requests\Photo;

use App\Http\Requests\ApiFormRequest;

class TagMemberPhotoRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['member_uuids' => ['required', 'array', 'max:100'], 'member_uuids.*' => ['required', 'uuid', 'distinct', 'exists:family_members,uuid']];
    }
}
