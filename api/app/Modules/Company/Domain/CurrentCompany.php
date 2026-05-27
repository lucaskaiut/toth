<?php

namespace App\Modules\Company\Domain;

use App\Modules\Company\Domain\Exceptions\CompanyNotInitializedException;
use App\Modules\Company\Domain\Models\Company;

class CurrentCompany
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function get(): Company
    {
        if ($this->company === null) {
            throw new CompanyNotInitializedException;
        }

        return $this->company;
    }

    public function id(): int
    {
        return $this->get()->id;
    }

    public function isSet(): bool
    {
        return $this->company !== null;
    }
}
