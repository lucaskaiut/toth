<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'string'],
            'data' => ['required', 'array'],
            'data.company_name' => ['required_if:channel,internal', 'nullable', 'string', 'max:255'],
            'data.whatsapp' => ['required_if:channel,internal', 'nullable', 'string', 'regex:/^\d{10,15}$/'],
            'data.name' => ['required_if:channel,internal', 'nullable', 'string', 'max:255'],
            'data.email' => ['required_if:channel,internal', 'nullable', 'string', 'email', 'max:255'],
            'data.password' => ['required_if:channel,internal', 'nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.whatsapp.required_if' => 'Informe o WhatsApp principal da empresa.',
            'data.whatsapp.regex' => 'Informe um WhatsApp válido com DDD e número (somente dígitos).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('data.whatsapp');

        if (is_string($whatsapp)) {
            $this->merge([
                'data' => array_merge($this->input('data', []), [
                    'whatsapp' => preg_replace('/\D+/', '', $whatsapp) ?? $whatsapp,
                ]),
            ]);
        }
    }
}
