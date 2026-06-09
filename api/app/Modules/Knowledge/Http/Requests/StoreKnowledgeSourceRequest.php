<?php

namespace App\Modules\Knowledge\Http\Requests;

use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKnowledgeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(KnowledgeSourceType::values())],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
