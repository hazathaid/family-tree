<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;

class UploadFamilyMemberPhotoRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
