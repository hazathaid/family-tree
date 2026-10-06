<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class ConfirmTwoFactorRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:6'],
        ];
    }
}
