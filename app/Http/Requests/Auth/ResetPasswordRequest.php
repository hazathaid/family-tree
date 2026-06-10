<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
