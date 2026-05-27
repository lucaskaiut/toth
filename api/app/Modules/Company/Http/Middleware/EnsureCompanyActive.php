<?php

namespace App\Modules\Company\Http\Middleware;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyActive
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->currentCompany->get();

        if ($company->status !== CompanyStatus::Active) {
            return response()->json([
                'message' => 'Empresa aguardando conexão do WhatsApp.',
                'code' => 'company_pending_whatsapp',
                'company_status' => $company->status->value,
            ], 403);
        }

        return $next($request);
    }
}
