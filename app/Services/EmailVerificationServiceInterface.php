<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Validation\ValidationException;

#[Bind(EmailVerificationService::class)]
#[Scoped]
interface EmailVerificationServiceInterface
{
    /**
     * Email a fresh verification code unless the address is already verified.
     */
    public function sendOtp(User $user): void;

    /**
     * Verify the code and mark the user's email as verified. Idempotent.
     *
     * @throws ValidationException
     */
    public function verify(User $user, string $code): void;
}
