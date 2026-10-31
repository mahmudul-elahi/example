<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Roles;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected EmailVerificationServiceInterface $emailVerification,
        protected DeviceDetectorServiceInterface $deviceDetector,
        protected TwoFactorServiceInterface $twoFactor,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data): User {
            $user = $this->users->create($data);
            $user->setRole(Roles::User);

            return $user;
        });

        $user = $this->users->loadRoles($user);

        $this->emailVerification->sendOtp($user);

        return [
            'user' => $user,
            'token' => $this->issueToken($user, $data['device_name'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}|array{two_factor_required: true, two_factor_token: string}
     */
    public function login(array $data): array
    {
        $user = $this->users->findByEmail($data['email']);

        if (! $user instanceof User || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            if (! empty($data['code'])) {
                if (! $this->twoFactor->verifyCode($user, (string) $data['code'])) {
                    throw ValidationException::withMessages([
                        'code' => ['The provided two factor authentication code was invalid.'],
                    ]);
                }
            } elseif (! empty($data['recovery_code'])) {
                if (! $this->twoFactor->verifyRecoveryCode($user, (string) $data['recovery_code'])) {
                    throw ValidationException::withMessages([
                        'recovery_code' => ['The provided two factor recovery code was invalid.'],
                    ]);
                }
            } else {
                $challengeToken = Str::random(40);
                Cache::put("two_factor:{$challengeToken}", $user->id, now()->addMinutes(10));

                return [
                    'two_factor_required' => true,
                    'two_factor_token' => $challengeToken,
                ];
            }
        }

        return [
            'user' => $user,
            'token' => $this->issueToken($user, $data['device_name'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function twoFactorChallenge(array $data): array
    {
        $challengeToken = (string) $data['two_factor_token'];
        $userId = Cache::get("two_factor:{$challengeToken}");

        if ($userId === null) {
            throw ValidationException::withMessages([
                'two_factor_token' => ['The two factor authentication session has expired or is invalid.'],
            ]);
        }

        $user = $this->users->find((int) $userId);

        if (! empty($data['code'])) {
            if (! $this->twoFactor->verifyCode($user, (string) $data['code'])) {
                throw ValidationException::withMessages([
                    'code' => ['The provided two factor authentication code was invalid.'],
                ]);
            }
        } elseif (! empty($data['recovery_code'])) {
            if (! $this->twoFactor->verifyRecoveryCode($user, (string) $data['recovery_code'])) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided two factor recovery code was invalid.'],
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'code' => ['A verification code or recovery code is required.'],
            ]);
        }

        Cache::forget("two_factor:{$challengeToken}");

        return [
            'user' => $this->users->loadRoles($user),
            'token' => $this->issueToken($user, $data['device_name'] ?? null),
        ];
    }

    protected function issueToken(User $user, ?string $customDeviceName = null): string
    {
        $deviceName = ! empty($customDeviceName) ? $customDeviceName : $this->deviceDetector->detect();
        $token = $user->createToken($deviceName);

        $token->accessToken->forceFill([
            'ip_address' => request()->ip(),
        ])->save();

        return $token->plainTextToken;
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
