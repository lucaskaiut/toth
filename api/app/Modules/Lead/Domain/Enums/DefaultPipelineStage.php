<?php

namespace App\Modules\Lead\Domain\Enums;

enum DefaultPipelineStage: string
{
    case NovoLead = 'novo_lead';
    case Qualificacao = 'qualificacao';
    case Proposta = 'proposta';
    case Fechado = 'fechado';

    public function label(): string
    {
        return match ($this) {
            self::NovoLead => 'Novo Lead',
            self::Qualificacao => 'Qualificação',
            self::Proposta => 'Proposta',
            self::Fechado => 'Fechado',
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::NovoLead => 0,
            self::Qualificacao => 1,
            self::Proposta => 2,
            self::Fechado => 3,
        };
    }

    /**
     * @return list<array{slug: string, name: string, position: int}>
     */
    public static function definitions(): array
    {
        return array_map(
            fn (self $stage) => [
                'slug' => $stage->value,
                'name' => $stage->label(),
                'position' => $stage->position(),
            ],
            self::cases(),
        );
    }
}
