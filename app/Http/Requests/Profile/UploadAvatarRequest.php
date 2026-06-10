<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class UploadAvatarRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
