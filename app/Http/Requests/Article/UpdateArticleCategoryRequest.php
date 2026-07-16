<?php

namespace App\Http\Requests\Article;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleCategoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('article_categories', 'name')->ignore($this->route('article_category'))], 'description' => ['nullable', 'string']];
    }
}
