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

    public function isValidationError(): bool
    {
        return ($this->error['type'] ?? null) === 'validation';
    }

    public function toToolMessageContent(): string
    {
        return $this->toLlmInstructionContent();
    }

    public function toLlmInstructionContent(): string
    {
        if ($this->success) {
            return json_encode([
                'status' => 'ok',
                'instruction' => 'Use os dados retornados para responder ao cliente em JSON com message, suggested_stage, summary e should_reply. Não repita este payload nem exponha detalhes técnicos.',
                'data' => $this->data,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        if ($this->isValidationError()) {
            return json_encode([
                'status' => 'invalid_parameters',
                'instruction' => 'Os parâmetros enviados são inválidos. Corrija os valores e tente novamente sem usar placeholders ou IDs inventados. Depois responda ao cliente em JSON com message, suggested_stage, summary e should_reply.',
                'detail' => (string) ($this->error['message'] ?? 'Parâmetros inválidos.'),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return json_encode([
            'status' => 'failed',
            'instruction' => 'A ação externa falhou. Informe o cliente com empatia que não foi possível concluir a consulta agora e que encaminhará para a equipe humana. Responda em JSON com message (obrigatório), suggested_stage, summary e should_reply. Nunca repita este JSON nem exponha erros técnicos.',
            'context' => (string) ($this->error['message'] ?? 'Falha na integração externa.'),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
