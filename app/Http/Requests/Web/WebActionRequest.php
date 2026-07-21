<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class WebActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
