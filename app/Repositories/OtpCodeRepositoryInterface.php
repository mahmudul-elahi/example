<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Carbon;

#[Bind(OtpCodeRepository::class)]
#[Scoped]
interface OtpCodeRepositoryInterface
{
    /**
     * Replace any existing code for the email/purpose with a freshly issued one.
     */
    public function createFor(string $email, OtpPurpose $purpose, string $hashedCode, Carbon $expiresAt): OtpCode;

    /**
     * The most recently issued, not-yet-consumed code for the email/purpose.
     */
    public function findActive(string $email, OtpPurpose $purpose): ?OtpCode;

    public function incrementAttempts(OtpCode $otpCode): void;

    public function markConsumed(OtpCode $otpCode): void;
}
