<?php

namespace App\Http\Requests\Profile\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

trait ConfirmsCurrentPassword
{
    /**
     * @return array<int, callable>
     */
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
