<?php

namespace App\Enums;

enum StoryMediaType: string
{
    case Image = 'image';
    case Video = 'video';

    public static function fromMimeType(string $mimeType): self
    {
        return str_starts_with($mimeType, 'video/') ? self::Video : self::Image;
    }
}
