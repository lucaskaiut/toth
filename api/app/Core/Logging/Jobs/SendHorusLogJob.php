<?php

namespace App\Core\Logging\Jobs;

use App\Core\Logging\Contracts\LogDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendHorusLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public int $tries = 5;

    public function handle(LogDriver $driver): void
    {
        if (! (bool) config('horus.enabled', true)) {
            return;
        }

        $driverName = (string) config('horus.driver', 'horus');

        if ($driverName !== 'horus') {
            return;
        }

        $driver->send($this->payload);
    }
}

