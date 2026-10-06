<?php

namespace App\Http\Requests\Tree;

use App\Models\TreeExport;
use Illuminate\Validation\Rule;

class RequestTreeExportRequest extends GenerateTreeRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'format' => ['required', Rule::in(TreeExport::FORMATS)],
            'paper_size' => ['sometimes', Rule::in(['A4', 'A3', 'A2'])],
        ];
    }
}
