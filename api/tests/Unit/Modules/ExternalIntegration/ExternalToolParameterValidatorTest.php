<?php

namespace Tests\Unit\Modules\ExternalIntegration;

use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolParameterValidator;
use Tests\TestCase;

class ExternalToolParameterValidatorTest extends TestCase
{
    private ExternalToolParameterValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ExternalToolParameterValidator;
    }

    public function test_rejects_placeholder_date(): void
    {
        $tool = new ExternalToolDefinition(
            name: 'check_availability',
            description: 'Consulta horários',
            parameters: [
                [
                    'name' => 'date',
                    'description' => 'Data da consulta',
                    'type' => 'string',
                    'required' => true,
                ],
            ],
        );

        $error = $this->validator->validate($tool, ['date' => 'YYYY-MM-DD']);

        $this->assertNotNull($error);
        $this->assertStringContainsString('date', $error);
    }

    public function test_accepts_valid_parameters(): void
    {
        $tool = new ExternalToolDefinition(
            name: 'check_availability',
            description: 'Consulta horários',
            parameters: [
                [
                    'name' => 'date',
                    'description' => 'Data da consulta',
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'name' => 'service_id',
                    'description' => 'ID do serviço',
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
        );

        $error = $this->validator->validate($tool, [
            'date' => '2026-06-10',
            'service_id' => 12,
        ]);

        $this->assertNull($error);
    }
}
