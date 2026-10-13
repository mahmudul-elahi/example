<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\OtpCodeRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService implements OtpServiceInterface
{
    public function __construct(
        protected OtpCodeRepositoryInterface $otps,
    ) {}

    public function generate(User $user, OtpPurpose $purpose): string
    {
        $code = $this->randomCode();

        $this->otps->createFor(
            $user,
            $purpose,
            Hash::make($code),
            now()->addMinutes((int) config('auth.otp.expire')),
        );

        return $code;
    }

    public function validate(User $user, OtpPurpose $purpose, string $code): OtpCode
    {
        $otp = $this->otps->findActive($user, $purpose);

        if (
            ! $otp instanceof OtpCode
            || ! $otp->isUsable()
            || $otp->hasExceededAttempts((int) config('auth.otp.max_attempts'))
        ) {
            throw $this->invalidCode();
        }

        if (! Hash::check($code, $otp->code)) {
            $this->otps->incrementAttempts($otp);

            throw $this->invalidCode();
        }

        return $otp;
    }

    public function verify(User $user, OtpPurpose $purpose, string $code): void
    {
        $this->otps->markConsumed($this->validate($user, $purpose, $code));
    }

    private function randomCode(): string
    {
        $digits = (int) config('auth.otp.digits');

        return str_pad((string) random_int(0, (10 ** $digits) - 1), $digits, '0', STR_PAD_LEFT);
    }

    private function invalidCode(): ValidationException
    {
        return ValidationException::withMessages([
            'otp' => 'The provided code is invalid or has expired.',
        ]);
    }
}
