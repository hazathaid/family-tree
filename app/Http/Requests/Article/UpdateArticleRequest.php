<?php

namespace App\Http\Requests\Article;

use App\Http\Requests\ApiFormRequest;
use App\Models\Article;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['category_uuid' => ['sometimes', 'required', 'uuid', 'exists:article_categories,uuid'], 'title' => ['sometimes', 'required', 'string', 'max:255'], 'excerpt' => ['nullable', 'string', 'max:1000'], 'content' => ['sometimes', 'required', 'string'], 'status' => ['sometimes', Rule::in(Article::STATUSES)]];
    }
}
