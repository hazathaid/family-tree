<?php

namespace App\Http\Requests\MemberAccount;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptMemberAccountInvitationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
