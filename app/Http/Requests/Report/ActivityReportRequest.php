<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\ApiFormRequest;

class ActivityReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
