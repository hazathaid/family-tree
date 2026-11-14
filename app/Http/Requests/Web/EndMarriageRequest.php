<?php

namespace App\Http\Requests\Web;

use App\Models\MemberRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EndMarriageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'end_date' => ['required', 'date'],
            'end_reason' => ['required', Rule::in(MemberRelationship::END_REASONS)],
        ];
    }
}
