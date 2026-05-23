<?php

namespace App\Modules\Company\Domain\DTOs;

readonly class CreateCompanyData
{
    public function __construct(
        public string $name,
    ) {}
}
