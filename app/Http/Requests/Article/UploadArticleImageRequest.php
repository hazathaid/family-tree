<?php

namespace App\Http\Requests\Article;

use App\Http\Requests\ApiFormRequest;

class UploadArticleImageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']];
    }
}
