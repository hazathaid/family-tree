<?php

namespace App\Http\Requests\Family;

use App\Http\Requests\ApiFormRequest;

class UpdateFamilyAssetsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:cover_image'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'required_without:logo'],
        ];
    }
}
