<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;
use App\Http\Requests\Profile\Concerns\ConfirmsCurrentPassword;

class EnableTwoFactorRequest extends ApiFormRequest
{
    use ConfirmsCurrentPassword;

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
        ];
    }
}
