<?php

namespace App\Http\Requests\MemberAccount;

use App\Http\Requests\ApiFormRequest;

class InviteMemberAccountRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email:rfc', 'max:255', 'unique:users,email']];
    }
}
