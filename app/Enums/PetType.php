<?php

declare(strict_types=1);

namespace App\Enums;

enum PetType: string
{
    case Dog = 'dog';
    case Cat = 'cat';
    case Bird = 'bird';
    case Fish = 'fish';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
