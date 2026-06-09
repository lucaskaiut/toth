<?php

namespace App\Modules\Lead\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderPipelineStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stages' => ['required', 'array', 'min:1'],
            'stages.*' => ['integer', 'distinct', 'exists:pipeline_stages,id'],
        ];
    }
}
