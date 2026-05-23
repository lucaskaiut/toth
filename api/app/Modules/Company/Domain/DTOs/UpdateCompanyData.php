<?php

namespace App\Modules\Company\Domain\DTOs;

readonly class UpdateCompanyData
{
    public function __construct(
        public ?string $name = null,
    ) {}
}
