<?php

namespace App\Http\Requests\Family;

use App\Http\Requests\ApiFormRequest;
use App\Models\FamilyUserRole;
use Illuminate\Validation\Rule;

class AssignFamilyRoleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(FamilyUserRole::ROLES)],
        ];
    }
}
