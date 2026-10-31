<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;

#[Bind(TwoFactorService::class)]
#[Singleton]
interface TwoFactorServiceInterface
{
    /**
     * @return array{secret: string, qr_code_url: string}
     */
    public function enable(User $user): array;

    /**
     * @return array{recovery_codes: array<int, string>}
     */
    public function confirm(User $user, string $code): array;

    public function disable(User $user): void;

    /**
     * @return array{recovery_codes: array<int, string>}
     */
    public function generateNewRecoveryCodes(User $user): array;

    public function verifyCode(User $user, string $code): bool;

    public function verifyRecoveryCode(User $user, string $recoveryCode): bool;
}
