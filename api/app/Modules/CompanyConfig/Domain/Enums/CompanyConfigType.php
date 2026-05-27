<?php

namespace App\Modules\CompanyConfig\Domain\Enums;

enum CompanyConfigType: string
{
    case String = 'string';
    case Int = 'int';
    case Bool = 'bool';
    case Json = 'json';
}
