<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use Illuminate\Support\Carbon;

class OtpCodeRepository implements OtpCodeRepositoryInterface
{
    public function createFor(string $email, OtpPurpose $purpose, string $hashedCode, Carbon $expiresAt): OtpCode
    {
        OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose->value)
            ->delete();

        return OtpCode::query()->create([
            'email' => $email,
            'purpose' => $purpose,
            'code' => $hashedCode,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findActive(string $email, OtpPurpose $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
    }

    public function incrementAttempts(OtpCode $otpCode): void
    {
        $otpCode->increment('attempts');
    }

    public function markConsumed(OtpCode $otpCode): void
    {
        $otpCode->update(['consumed_at' => now()]);
    }
}
