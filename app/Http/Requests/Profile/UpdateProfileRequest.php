<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
