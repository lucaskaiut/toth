<?php

namespace App\Modules\Company\Domain\Services;

use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Core\Whatsapp\DTOs\CreateWhatsAppInstanceData;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigService;
use RuntimeException;

class CompanyWhatsAppProvisioningService
{
    public function __construct(
        private readonly WhatsAppClient $whatsAppClient,
        private readonly CompanyInstanceNameGenerator $instanceNameGenerator,
        private readonly CompanyConfigService $companyConfigService,
    ) {}

    public function provision(Company $company): string
    {
        $instanceName = $this->instanceNameGenerator->generate($company->id);
        $webhookUrl = rtrim((string) config('app.url'), '/').'/api/webhooks/whatsapp';

        $result = $this->whatsAppClient->createInstance(new CreateWhatsAppInstanceData(
            instanceName: $instanceName,
            number: (string) $company->whatsapp,
            webhookUrl: $webhookUrl,
            webhookEvents: config('whatsapp.webhook_events', []),
            webhookHeaders: $this->webhookHeaders(),
        ));

        if (! $result->success) {
            throw new RuntimeException($result->error ?? 'Falha ao provisionar instância WhatsApp.');
        }

        $this->companyConfigService->setManyForCompany($company->id, [
            'evolution.instance_name' => [
                'value' => $instanceName,
                'type' => CompanyConfigType::String,
            ],
        ]);

        return $instanceName;
    }

    /**
     * @return array<string, string>
     */
    private function webhookHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $token = (string) config('whatsapp.webhook_token');

        if ($token !== '') {
            $headers['authorization'] = str_starts_with($token, 'Bearer ')
                ? $token
                : "Bearer {$token}";
        }

        return $headers;
    }
}
