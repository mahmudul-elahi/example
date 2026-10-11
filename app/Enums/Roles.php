<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The only roles that exist in the application. Each user has exactly one role.
 */
enum Roles: string
{
    case Admin = 'admin';
    case User = 'user';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
