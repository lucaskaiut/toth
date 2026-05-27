<?php

namespace App\Modules\Conversation\Domain\Enums;

enum ConversationAttendanceStatus: string
{
    case AiEnabled = 'ai_enabled';
    case HandoffToHuman = 'handoff_to_human';
    case WaitingHuman = 'waiting_human';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::AiEnabled => 'IA ativa',
            self::HandoffToHuman => 'Humano no atendimento',
            self::WaitingHuman => 'Aguardando humano',
            self::Closed => 'Encerrada',
        };
    }

    public function allowsAiProcessing(): bool
    {
        return $this === self::AiEnabled;
    }
}
