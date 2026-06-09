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

    public function description(): string
    {
        return match ($this) {
            self::NovoLead => 'Primeiro contato sem contexto suficiente.',
            self::Qualificacao => 'Necessidade identificada e triagem em andamento.',
            self::Proposta => 'Cliente demonstrou intenção comercial concreta.',
            self::Fechado => 'Atendimento ou contratação confirmada.',
        };
    }

    public function aiInstruction(): string
    {
        return match ($this) {
            self::NovoLead => 'Use quando ainda não houver informação suficiente para qualificação.',
            self::Qualificacao => 'Use quando o cliente estiver explicando necessidade e coletando informações.',
            self::Proposta => 'Use quando houver solicitação de valores, orçamento ou agendamento.',
            self::Fechado => 'Use quando houver confirmação explícita do serviço.',
        };
    }

    /**
     * @return list<array{slug: string, name: string, position: int, description: string, ai_instruction: string}>
     */
    public static function definitions(): array
    {
        return array_map(
            fn (self $stage) => [
                'slug' => $stage->value,
                'name' => $stage->label(),
                'position' => $stage->position(),
                'description' => $stage->description(),
                'ai_instruction' => $stage->aiInstruction(),
            ],
            self::cases(),
        );
    }
}
