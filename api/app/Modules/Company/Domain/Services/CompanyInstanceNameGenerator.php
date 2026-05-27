<?php

namespace App\Modules\Company\Domain\Services;

class CompanyInstanceNameGenerator
{
    public function generate(int $companyId): string
    {
        $hash = substr(hash('sha256', $companyId.config('app.key')), 0, 8);

        return "toth_{$companyId}_{$hash}";
    }
}
