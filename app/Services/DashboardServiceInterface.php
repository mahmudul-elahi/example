<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;

#[Bind(DashboardService::class)]
#[Scoped]
interface DashboardServiceInterface
{
    /**
     * @return array{users: array{users: int, new_last_7_days: int}}
     */
    public function summary(): array;
}
