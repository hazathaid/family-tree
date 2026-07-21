<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['read', 'unread'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
