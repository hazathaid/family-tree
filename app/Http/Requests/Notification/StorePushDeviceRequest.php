<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\ApiFormRequest;
use App\Models\PushDeviceToken;
use Illuminate\Validation\Rule;

class StorePushDeviceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'platform' => ['required', Rule::in(PushDeviceToken::PLATFORMS)],
            'token' => ['required', 'string', 'max:512'],
        ];
    }
}
