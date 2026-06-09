<?php

namespace App\Modules\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKnowledgeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
