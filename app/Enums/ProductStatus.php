<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case OutOfStock = 'out_of_stock';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
