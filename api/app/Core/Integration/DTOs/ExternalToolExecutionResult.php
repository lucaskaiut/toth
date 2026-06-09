<?php

namespace App\Core\Integration\DTOs;

readonly class ExternalToolExecutionResult
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $error
     */
    public function __construct(
        public bool $success,
        public array $data = [],
        public ?array $error = null,
    ) {}

    public function toToolMessageContent(): string
    {
        $payload = $this->success
            ? ['success' => true, 'data' => $this->data]
            : ['success' => false, 'error' => $this->error ?? ['message' => 'Erro desconhecido']];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
