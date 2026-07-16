<?php

namespace App\Http\Requests\Article;

use App\Http\Requests\ApiFormRequest;

class StoreArticleCommentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['comment' => ['required', 'string', 'max:5000']];
    }
}
