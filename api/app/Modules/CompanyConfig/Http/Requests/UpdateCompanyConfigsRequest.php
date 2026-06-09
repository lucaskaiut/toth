<?php

namespace App\Modules\CompanyConfig\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $configs = collect($this->input('configs', []))->keyBy('key');
            $enabled = $this->truthy($configs->get('integration.enabled')['value'] ?? false);

            if (! $enabled) {
                return;
            }

            $provider = trim((string) ($configs->get('integration.provider')['value'] ?? ''));
            $token = trim((string) ($configs->get('integration.api_token')['value'] ?? ''));

            if ($provider === '') {
                $validator->errors()->add(
                    'configs',
                    'Defina integration.provider ao ativar a integração externa.',
                );
            }

            if ($token === '') {
                $validator->errors()->add(
                    'configs',
                    'Defina integration.api_token ao ativar a integração externa.',
                );
            }
        });
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'sim', 'on'], true);
        }

        return (bool) $value;
    }
}
