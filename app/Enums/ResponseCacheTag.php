<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tags used by the response cache.
 *
 * A cached public endpoint declares its tag in the #[Cache] attribute, and the
 * observers that invalidate it flush the same tag, so every tag name is
 * defined in exactly one place.
 */
enum ResponseCacheTag: string
{
    case Memorials = 'memorials';
    case Comments = 'comments';
    case Blog = 'blog';
    case Shop = 'shop';

    /**
     * The string values of the given tags, ready for the response cache.
     *
     * @return list<string>
     */
    public static function values(self ...$tags): array
    {
        return array_map(fn (self $tag): string => $tag->value, $tags);
    }
}
