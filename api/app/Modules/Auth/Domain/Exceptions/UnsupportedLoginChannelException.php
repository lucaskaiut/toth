<?php

namespace App\Modules\Auth\Domain\Exceptions;

use Exception;

class UnsupportedLoginChannelException extends Exception
{
    public function __construct(string $channel)
    {
        parent::__construct("Canal de login não suportado: {$channel}");
    }
}
