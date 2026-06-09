<?php

namespace App\Modules\ExternalIntegration\Domain\Services;

use App\Core\Integration\DTOs\ExternalToolDefinition;

class ExternalToolParameterValidator
{
    /**
     * @var list<string>
     */
    private const PLACEHOLDER_PATTERNS = [
        '/^YYYY-MM-DD$/i',
        '/^DD-MM-YYYY$/i',
        '/^TBD$/i',
        '/^N\/A$/i',
        '/^null$/i',
        '/^undefined$/i',
        '/^example$/i',
        '/^xxx+$/i',
        '/^<[^>]+>$/',
        '/^\[[^\]]+\]$/',
        '/^0000-00-00$/',
    ];

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function validate(ExternalToolDefinition $tool, array $parameters): ?string
    {
        foreach ($tool->parameters as $parameter) {
            $name = (string) ($parameter['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $required = ($parameter['required'] ?? false) === true;
            $type = (string) ($parameter['type'] ?? 'string');
            $hasValue = array_key_exists($name, $parameters);
            $value = $parameters[$name] ?? null;

            if ($required && $this->isEmpty($value)) {
                return "Informe o parâmetro obrigatório \"{$name}\" com um valor real.";
            }

            if (! $hasValue || $value === null) {
                continue;
            }

            if ($this->isPlaceholder($value)) {
                return "O parâmetro \"{$name}\" contém um placeholder. Use um valor real conforme a descrição do parâmetro.";
            }

            if (! $this->matchesType($value, $type)) {
                return "O parâmetro \"{$name}\" possui tipo inválido.";
            }

            if ($this->expectsDate($name, $parameter) && ! $this->isValidDateValue($value)) {
                return "Informe uma data válida no parâmetro \"{$name}\" (formato AAAA-MM-DD).";
            }
        }

        return null;
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return false;
    }

    private function isPlaceholder(mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return true;
        }

        foreach (self::PLACEHOLDER_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'integer', 'int' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'number', 'float' => is_numeric($value),
            'boolean', 'bool' => is_bool($value) || in_array(strtolower((string) $value), ['true', 'false', '0', '1'], true),
            default => is_string($value) || is_numeric($value),
        };
    }

    /**
     * @param  array{name?: string, description?: string, type?: string, required?: bool}  $parameter
     */
    private function expectsDate(string $name, array $parameter): bool
    {
        $haystack = strtolower($name.' '.(string) ($parameter['description'] ?? ''));

        return str_contains($haystack, 'date')
            || str_contains($haystack, 'data')
            || str_contains($haystack, 'dia');
    }

    private function isValidDateValue(mixed $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $normalized = trim((string) $value);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $normalized));

        return checkdate($month, $day, $year);
    }
}
