<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;

#[Bind(DeviceDetectorService::class)]
#[Singleton]
interface DeviceDetectorServiceInterface
{
    public function detect(?string $userAgent = null): string;
}
