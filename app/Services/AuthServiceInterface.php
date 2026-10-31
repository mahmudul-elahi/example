<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;

#[Bind(AuthService::class)]
#[Scoped]
interface AuthServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}|array{two_factor_required: true, two_factor_token: string}
     */
    public function login(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function twoFactorChallenge(array $data): array;

    public function logout(User $user): void;

    public function me(User $user): User;
}
