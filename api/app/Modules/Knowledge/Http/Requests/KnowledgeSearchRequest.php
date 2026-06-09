<?php

namespace App\Modules\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:5000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
