<?php

namespace App\Modules\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('knowledge.max_upload_kb', 10240);

        return [
            'file' => [
                'required',
                'file',
                'max:'.($maxKb),
                'mimes:txt,pdf,md,doc,docx',
            ],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
