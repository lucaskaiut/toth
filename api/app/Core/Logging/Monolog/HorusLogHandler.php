<?php

namespace App\Core\Logging\Monolog;

use App\Core\Logging\HorusLogPayloadFactory;
use App\Core\Logging\Jobs\SendHorusLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class HorusLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (! (bool) config('horus.enabled', true)) {
            return;
        }

        $token = (string) config('horus.token');
        $baseUrl = (string) config('horus.base_url');

        if ($token === '' || $baseUrl === '') {
            return;
        }

        $factory = app(HorusLogPayloadFactory::class);
        $exception = $record->context['exception'] ?? null;

        $payload = $factory->make(
            level: strtolower($record->level->getName()),
            message: (string) $record->message,
            context: $this->sanitizeContext($record->context),
            exception: $exception instanceof \Throwable ? $exception : null,
        );

        SendHorusLogJob::dispatch($payload);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        // Evita enviar objetos complexos para o Horus.
        return json_decode(json_encode($context, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}

