<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! Hash::check((string) $this->input('current_password'), (string) $this->user()?->password)) {
                    $validator->errors()->add('current_password', 'The current password is incorrect.');
                }
            },
        ];
    }
}
