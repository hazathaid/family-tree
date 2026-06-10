<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
