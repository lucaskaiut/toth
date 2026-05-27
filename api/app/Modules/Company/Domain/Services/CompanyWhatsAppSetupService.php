<?php

namespace App\Modules\Company\Domain\Services;

use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use RuntimeException;

class CompanyWhatsAppSetupService
{
    public function __construct(
        private readonly WhatsAppClient $whatsAppClient,
    ) {}

    public function connect(Company $company): array
    {
        $instanceName = $this->resolveInstanceName($company);

        $result = $this->whatsAppClient->connectInstance($instanceName);

        if (! $result->success) {
            throw new RuntimeException($result->error ?? 'Não foi possível obter dados de conexão.');
        }

        return [
            'instance_name' => $instanceName,
            'pairing_code' => $result->pairingCode,
            'code' => $result->code,
            'base64' => $result->base64,
        ];
    }

    public function connectionState(Company $company): array
    {
        $instanceName = $this->resolveInstanceName($company);
        $result = $this->whatsAppClient->getConnectionState($instanceName);

        if (! $result->success) {
            throw new RuntimeException($result->error ?? 'Não foi possível consultar status da conexão.');
        }

        $connected = $result->isConnected();

        if ($connected && $company->status !== CompanyStatus::Active) {
            $company->update(['status' => CompanyStatus::Active]);
        }

        return [
            'instance_name' => $instanceName,
            'state' => $result->state,
            'connected' => $connected,
            'company_status' => $company->fresh()->status->value,
        ];
    }

    private function resolveInstanceName(Company $company): string
    {
        $config = new CompanyConfigResolver($company->id);
        $instanceName = (string) $config->get('evolution.instance_name', '');

        if ($instanceName === '') {
            throw new RuntimeException('Instância WhatsApp não provisionada para esta empresa.');
        }

        return $instanceName;
    }
}
