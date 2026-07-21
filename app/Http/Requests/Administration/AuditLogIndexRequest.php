<?php

namespace App\Http\Requests\Administration;

use App\Http\Requests\ApiFormRequest;

class AuditLogIndexRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'max:100'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
