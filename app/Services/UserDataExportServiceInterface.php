<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;

#[Bind(UserDataExportService::class)]
#[Singleton]
interface UserDataExportServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function exportData(User $user): array;
}
