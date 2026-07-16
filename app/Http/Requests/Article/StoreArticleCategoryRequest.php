<?php

namespace App\Http\Requests\Article;

use App\Http\Requests\ApiFormRequest;

class StoreArticleCategoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100', 'unique:article_categories,name'], 'description' => ['nullable', 'string']];
    }
}
