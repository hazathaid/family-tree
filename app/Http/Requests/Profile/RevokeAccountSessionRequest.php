<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\ApiFormRequest;

class RevokeAccountSessionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['session_uuid' => ['required', 'uuid']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['session_uuid' => $this->route('session')]);
    }
}
