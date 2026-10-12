<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Validation\ValidationException;

#[Bind(OtpService::class)]
#[Scoped]
interface OtpServiceInterface
{
    /**
     * Issue a fresh one-time code for the email/purpose and return its plain-text value.
     *
     * The plain value is only ever available to the caller (to place in a notification);
     * a hash is what gets persisted.
     */
    public function generate(string $email, OtpPurpose $purpose): string;

    /**
     * Check that a code is valid without consuming it. Wrong attempts still count
     * toward the lockout limit.
     *
     * @throws ValidationException when the code is invalid, expired,
     *                             already used, or has too many attempts.
     */
    public function validate(string $email, OtpPurpose $purpose, string $code): OtpCode;

    /**
     * Verify a code, consuming it on success.
     *
     * @throws ValidationException when the code is invalid, expired,
     *                             already used, or has too many attempts.
     */
    public function verify(string $email, OtpPurpose $purpose, string $code): void;
}
