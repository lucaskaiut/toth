<?php

namespace App\Modules\Company\Domain\Enums;

enum CompanyStatus: string
{
    case PendingWhatsappConnection = 'pending_whatsapp_connection';
    case Active = 'active';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function requiresWhatsappSetup(): bool
    {
        return $this === self::PendingWhatsappConnection;
    }
}
