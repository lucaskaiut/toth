<?php

namespace Tests\Unit\Core\Integration;

use App\Core\Integration\DTOs\ExternalToolExecutionResult;
use Tests\TestCase;

class ExternalToolExecutionResultTest extends TestCase
{
    public function test_failed_result_instructs_llm_to_respond_to_customer(): void
    {
        $content = (new ExternalToolExecutionResult(
            success: false,
            error: ['message' => 'Timeout'],
        ))->toLlmInstructionContent();

        $decoded = json_decode($content, true);

        $this->assertSame('failed', $decoded['status']);
        $this->assertStringContainsString('message', $decoded['instruction']);
        $this->assertStringNotContainsString('"success"', $content);
    }

    public function test_validation_error_requests_parameter_correction(): void
    {
        $content = (new ExternalToolExecutionResult(
            success: false,
            error: ['type' => 'validation', 'message' => 'Data inválida'],
        ))->toLlmInstructionContent();

        $decoded = json_decode($content, true);

        $this->assertSame('invalid_parameters', $decoded['status']);
        $this->assertStringContainsString('Corrija', $decoded['instruction']);
    }
}
