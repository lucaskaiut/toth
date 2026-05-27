<?php

namespace App\Modules\CompanyConfig\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyConfigsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'configs' => ['required', 'array'],
            'configs.*.key' => ['required', 'string', 'max:255'],
            'configs.*.value' => ['nullable'],
            'configs.*.type' => ['sometimes', 'string', 'in:string,int,bool,json'],
        ];
    }
}
