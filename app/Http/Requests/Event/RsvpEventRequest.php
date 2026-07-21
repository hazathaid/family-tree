<?php

namespace App\Http\Requests\Event;

use App\Http\Requests\ApiFormRequest;
use App\Models\EventAttendee;
use Illuminate\Validation\Rule;

class RsvpEventRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(EventAttendee::STATUSES)]];
    }
}
