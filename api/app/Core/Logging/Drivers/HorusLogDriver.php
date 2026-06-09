<?php

namespace App\Core\Logging\Drivers;

use App\Core\Logging\Contracts\LogDriver;
use Illuminate\Support\Facades\Http;

class HorusLogDriver implements LogDriver
{
    public function send(array $payload): void
    {
        $token = (string) config('horus.token');
        $baseUrl = rtrim((string) config('horus.base_url'), '/');
        $timeout = (int) config('horus.timeout', 5);

        if ($token === '' || $baseUrl === '') {
            return;
        }

        Http::timeout($timeout)
            ->withToken($token)
            ->acceptJson()
            ->post($baseUrl.'/api/logs', $payload);
    }
}

