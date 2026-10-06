<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class TwoFactorChallengeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string'],
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ];
    }
}
