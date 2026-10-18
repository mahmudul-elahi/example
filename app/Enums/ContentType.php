<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\BlogPost;
use App\Models\PetMemorial;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * The content users can comment on and like, addressed by type in the shared
 * comments and likes routes.
 */
enum ContentType: string
{
    case Memorial = 'memorials';
    case BlogPost = 'blog-posts';
    case Product = 'products';

    /**
     * The model class behind the type.
     *
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Memorial => PetMemorial::class,
            self::BlogPost => BlogPost::class,
            self::Product => Product::class,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
