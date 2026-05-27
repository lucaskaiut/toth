<?php

namespace App\Modules\Auth\Domain\Channels;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\RegisterChannel;
use App\Modules\Auth\Domain\Exceptions\RegisterProvisioningException;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Company\Domain\Services\CompanyWhatsAppProvisioningService;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Support\Facades\DB;

class InternalRegisterChannel implements RegisterChannel
{
    public function __construct(
        private readonly PipelineStageService $pipelineStageService,
        private readonly CompanyWhatsAppProvisioningService $provisioningService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): ?User
    {
        $companyName = $data['company_name'] ?? null;
        $whatsapp = $data['whatsapp'] ?? null;
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (
            ! is_string($companyName)
            || ! is_string($whatsapp)
            || ! is_string($name)
            || ! is_string($email)
            || ! is_string($password)
        ) {
            return null;
        }

        if (User::query()->where('email', $email)->exists()) {
            return null;
        }

        $whatsappDigits = preg_replace('/\D+/', '', $whatsapp) ?? $whatsapp;

        try {
            return DB::transaction(function () use ($companyName, $whatsappDigits, $name, $email, $password) {
                $company = Company::query()->create([
                    'name' => trim($companyName),
                    'whatsapp' => $whatsappDigits,
                    'status' => CompanyStatus::PendingWhatsappConnection,
                ]);

                $this->pipelineStageService->seedForCompany($company);

                $user = $company->users()->create([
                    'name' => trim($name),
                    'email' => $email,
                    'password' => $password,
                ]);

                $this->provisioningService->provision($company);

                return $user->load('company');
            });
        } catch (\Throwable $exception) {
            throw new RegisterProvisioningException(
                'Não foi possível provisionar o WhatsApp. Tente novamente em instantes.',
                previous: $exception,
            );
        }
    }
}
