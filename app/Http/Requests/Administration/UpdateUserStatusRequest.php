<?php

namespace App\Http\Requests\Administration;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['active', 'suspended'])]];
    }
}
