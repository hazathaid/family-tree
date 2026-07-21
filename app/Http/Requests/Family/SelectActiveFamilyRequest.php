<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class SelectActiveFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('family')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
