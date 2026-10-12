<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Validation\ValidationException;

#[Bind(PasswordResetService::class)]
#[Scoped]
interface PasswordResetServiceInterface
{
    /**
     * Send a reset code to the address if it belongs to a user.
     */
    public function sendOtp(string $email): void;

    /**
     * Confirm a reset code is valid without consuming it (second step of the flow).
     *
     * @throws ValidationException
     */
    public function verifyOtp(string $email, string $code): void;

    /**
     * Reset the user's password after verifying the code, revoking all tokens.
     *
     * @throws ValidationException
     */
    public function reset(string $email, string $code, string $password): void;
}
