<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->string('email')->toString() !== $this->user()?->email),
                'nullable',
                'string',
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->string('email')->toString() !== $this->user()?->email
                && ! Hash::check($this->string('current_password')->toString(), (string) $this->user()?->password)) {
                $validator->errors()->add('current_password', 'The current password is incorrect.');
            }
        }];
    }
}
