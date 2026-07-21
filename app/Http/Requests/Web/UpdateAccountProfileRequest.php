<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class UpdateAccountProfileRequest extends UpdateProfileRequest
{
    public function rules(): array
    {
        return parent::rules() + ['current_password' => ['nullable', 'string']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $user = $this->user();
            if ($user && $this->string('email')->toString() !== $user->email
                && ! Hash::check((string) $this->input('current_password'), $user->password)) {
                $validator->errors()->add('current_password', 'Password saat ini diperlukan untuk mengubah email.');
            }
        }];
    }
}
