<?php

namespace App\Http\Requests\Gamification;

use App\Http\Requests\ApiFormRequest;

class LeaderboardRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['limit' => ['sometimes', 'integer', 'min:1', 'max:100']];
    }
}
