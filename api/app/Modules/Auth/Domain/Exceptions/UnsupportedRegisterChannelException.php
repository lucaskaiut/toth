<?php

namespace App\Modules\Auth\Domain\Exceptions;

use Exception;

class UnsupportedRegisterChannelException extends Exception
{
    public function __construct(string $channel)
    {
        parent::__construct("Canal de cadastro não suportado: {$channel}");
    }
}
