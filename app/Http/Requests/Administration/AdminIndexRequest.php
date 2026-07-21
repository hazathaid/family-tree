<?php

namespace App\Http\Requests\Administration;

use App\Http\Requests\ApiFormRequest;

class AdminIndexRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
