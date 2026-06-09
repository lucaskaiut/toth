<?php

namespace App\Core\Logging\Contracts;

interface LogDriver
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload): void;
}

