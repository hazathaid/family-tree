<?php

namespace App\Http\Requests\Photo;

use App\Http\Requests\ApiFormRequest;

class UpdatePhotoAlbumRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['name' => ['sometimes', 'required', 'string', 'max:150'], 'description' => ['sometimes', 'nullable', 'string', 'max:2000']];
    }
}
