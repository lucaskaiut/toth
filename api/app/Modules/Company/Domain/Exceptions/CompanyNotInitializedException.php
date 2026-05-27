<?php

namespace App\Modules\Company\Domain\Exceptions;

use RuntimeException;

class CompanyNotInitializedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Company context has not been initialized.');
    }
}
