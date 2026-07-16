<?php

namespace App\Http\Requests\Article;

use App\Http\Requests\ApiFormRequest;
use App\Models\Article;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['family_uuid' => ['required', 'uuid', 'exists:families,uuid'], 'category_uuid' => ['required', 'uuid', 'exists:article_categories,uuid'], 'title' => ['required', 'string', 'max:255'], 'excerpt' => ['nullable', 'string', 'max:1000'], 'content' => ['required', 'string'], 'status' => ['sometimes', Rule::in(Article::STATUSES)]];
    }
}
