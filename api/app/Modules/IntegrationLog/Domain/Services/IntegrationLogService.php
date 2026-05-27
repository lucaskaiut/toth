<?php

namespace App\Modules\IntegrationLog\Domain\Services;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\IntegrationLog\Domain\Models\IntegrationLog;
use Illuminate\Support\Facades\Log;

class IntegrationLogService
{
    public function __construct(
        private readonly ?CurrentCompany $currentCompany = null,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(
        string $integration,
        string $action,
        string $message,
        array $context = [],
        ?int $companyId = null,
    ): void {
        $resolvedCompanyId = $companyId ?? ($this->currentCompany?->isSet() ? $this->currentCompany->id() : null);

        IntegrationLog::query()->create([
            'company_id' => $resolvedCompanyId,
            'integration' => $integration,
            'action' => $action,
            'level' => 'error',
            'message' => $message,
            'context' => $context,
        ]);

        Log::error("[{$integration}:{$action}] {$message}", $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(
        string $integration,
        string $action,
        string $message,
        array $context = [],
        ?int $companyId = null,
    ): void {
        $resolvedCompanyId = $companyId ?? ($this->currentCompany?->isSet() ? $this->currentCompany->id() : null);

        IntegrationLog::query()->create([
            'company_id' => $resolvedCompanyId,
            'integration' => $integration,
            'action' => $action,
            'level' => 'info',
            'message' => $message,
            'context' => $context,
        ]);

        Log::info("[{$integration}:{$action}] {$message}", $context);
    }
}
