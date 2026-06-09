<?php

namespace App\Modules\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePipelineStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'ai_instruction' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
