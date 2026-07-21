<?php

namespace App\Http\Requests\Photo;

use App\Http\Requests\ApiFormRequest;

class StorePhotoAlbumRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['family_uuid' => ['required', 'uuid', 'exists:families,uuid'], 'name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:2000']];
    }
}
