<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Roles;
use App\Repositories\UserRepositoryInterface;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{users: array{users: int, new_last_7_days: int}}
     */
    public function summary(): array
    {
        return [
            'users' => [
                'users' => $this->users->countByRole(Roles::User),
                'new_last_7_days' => $this->users->countCreatedSince(now()->subDays(7)),
            ],
        ];
    }
}
