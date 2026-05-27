<?php

namespace App\Modules\Lead\Domain\DTOs;

readonly class UpdateLeadData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $companyName = null,
        public ?string $notes = null,
    ) {}
}
