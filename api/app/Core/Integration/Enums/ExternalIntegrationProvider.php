<?php

namespace App\Core\Integration\Enums;

enum ExternalIntegrationProvider: string
{
    case Nox = 'nox';

    public function label(): string
    {
        return (string) config("integration.providers.{$this->value}.label", $this->value);
    }

    public function baseUrl(): string
    {
        return rtrim((string) config("integration.providers.{$this->value}.base_url", ''), '/');
    }

    public function timeout(): int
    {
        return (int) config("integration.providers.{$this->value}.timeout", 30);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
