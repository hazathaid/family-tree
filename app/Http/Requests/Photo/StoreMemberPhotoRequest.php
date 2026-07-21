<?php

namespace App\Http\Requests\Photo;

use App\Http\Requests\ApiFormRequest;

class StoreMemberPhotoRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['family_uuid' => ['required', 'uuid', 'exists:families,uuid'], 'album_uuid' => ['nullable', 'uuid', 'exists:photo_albums,uuid'], 'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], 'caption' => ['nullable', 'string', 'max:2000'], 'captured_at' => ['nullable', 'date']];
    }
}
