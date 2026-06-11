<?php

namespace App\Http\Requests\Family;

use App\Http\Requests\ApiFormRequest;
use App\Models\FamilyUserRole;
use Illuminate\Validation\Rule;

class InviteFamilyMemberRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', Rule::in(FamilyUserRole::ROLES)],
        ];
    }
}
