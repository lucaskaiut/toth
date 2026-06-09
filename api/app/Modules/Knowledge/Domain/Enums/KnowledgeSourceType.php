<?php

namespace App\Modules\Knowledge\Domain\Enums;

enum KnowledgeSourceType: string
{
    case Company = 'company';
    case Faq = 'faq';
    case Product = 'product';
    case Policy = 'policy';
    case Script = 'script';
    case Document = 'document';
    case FreeContext = 'free_context';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
