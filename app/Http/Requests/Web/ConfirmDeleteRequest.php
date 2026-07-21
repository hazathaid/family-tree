<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeleteRequest extends FormRequest
{
    public function rules(): array
    {
        return ['confirm' => ['required', 'accepted']];
    }
}
