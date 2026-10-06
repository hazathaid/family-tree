<?php

namespace App\Http\Requests\FamilyMember;

use App\Http\Requests\ApiFormRequest;
use App\Models\MemberDocument;
use Illuminate\Validation\Rule;

class StoreMemberDocumentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(MemberDocument::CATEGORIES)],
            'document_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
        ];
    }
}
