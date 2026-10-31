<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

#[Bind(UserSessionService::class)]
#[Singleton]
interface UserSessionServiceInterface
{
    /**
     * @return Collection<int, PersonalAccessToken>
     */
    public function listSessions(User $user): Collection;

    public function revokeSession(User $user, int $sessionId): void;

    public function revokeOtherSessions(User $user): void;
}
