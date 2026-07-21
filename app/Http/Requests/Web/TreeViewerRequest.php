<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreeViewerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'root' => ['nullable', 'uuid'],
            'mode' => ['nullable', Rule::in(['ancestor', 'descendant', 'full'])],
            'depth' => ['nullable', 'integer', 'between:1,20'],
            'layout' => ['nullable', Rule::in(['vertical', 'horizontal', 'compact'])],
            'member_search' => ['nullable', 'string', 'max:100'],
            'living_only' => ['nullable', Rule::in(['0', '1'])],
            'show_photos' => ['nullable', Rule::in(['0', '1'])],
            'show_nicknames' => ['nullable', Rule::in(['0', '1'])],
            'show_relationships' => ['nullable', Rule::in(['0', '1'])],
        ];
    }
}
