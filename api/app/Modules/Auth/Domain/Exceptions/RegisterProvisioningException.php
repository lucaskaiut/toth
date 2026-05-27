<?php

namespace App\Modules\Auth\Domain\Exceptions;

use RuntimeException;

class RegisterProvisioningException extends RuntimeException
{
    public function __construct(string $message = 'Não foi possível provisionar o WhatsApp da empresa.')
    {
        parent::__construct($message);
    }
}
