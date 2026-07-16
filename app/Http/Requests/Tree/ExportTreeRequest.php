<?php

namespace App\Http\Requests\Tree;

use Illuminate\Validation\Rule;

class ExportTreeRequest extends GenerateTreeRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'paper_size' => ['sometimes', Rule::in(['A4', 'A3', 'A2'])]];
    }
}
