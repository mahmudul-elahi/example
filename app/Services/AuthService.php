<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Roles;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = $this->users->create($data);
            $user->setRole(Roles::User);

            return [
                'user' => $this->users->loadRoles($user),
                'token' => $user->createToken('auth-token')->plainTextToken,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function login(array $data): array
    {
        $user = $this->users->findByEmail($data['email']);

        if (! $user instanceof User || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function me(User $user): User
    {
        return $this->users->loadRoles($user);
    }
}
