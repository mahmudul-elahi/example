<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "imagick", "gd"
    |
    | Imagick is preferred when installed because it processes high-resolution
    | multi-megapixel photos outside of PHP's Zend memory limit, preventing
    | memory exhaustion crashes on large uploads.
    |
    */
    'default' => env('IMAGE_DRIVER', extension_loaded('imagick') ? 'imagick' : 'gd'),
];
