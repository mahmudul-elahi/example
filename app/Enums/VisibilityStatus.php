<?php

declare(strict_types=1);

namespace App\Enums;

enum VisibilityStatus: string
{
    case Public = 'public';
    case Private = 'private';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
