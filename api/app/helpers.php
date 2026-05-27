<?php

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;

if (! function_exists('company_config')) {
    function company_config(string $key, mixed $default = null): mixed
    {
        $companyId = null;

        if (app()->bound(CurrentCompany::class)) {
            $currentCompany = app(CurrentCompany::class);

            if ($currentCompany->isSet()) {
                $companyId = $currentCompany->id();
            }
        }

        if ($companyId === null) {
            return $default;
        }

        return (new CompanyConfigResolver($companyId))->get($key, $default);
    }
}
