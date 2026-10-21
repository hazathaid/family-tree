<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyViewRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'depth' => ['sometimes', 'integer', 'between:1,20'],
            'layout' => ['sometimes', Rule::in(['vertical', 'horizontal', 'radial', 'compact'])],
        ];
    }
}
