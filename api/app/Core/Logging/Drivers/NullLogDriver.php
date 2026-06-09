<?php

namespace App\Core\Logging\Drivers;

use App\Core\Logging\Contracts\LogDriver;

class NullLogDriver implements LogDriver
{
    public function send(array $payload): void
    {
        // no-op
    }
}

