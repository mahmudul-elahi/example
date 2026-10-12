<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Validation\ValidationException;

class EmailVerificationService implements EmailVerificationServiceInterface
{
    public function __construct(
        protected OtpServiceInterface $otps,
    ) {}

    public function sendOtp(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = $this->otps->generate($user->email, OtpPurpose::EmailVerification);

        $user->notify(new EmailVerificationOtpNotification(
            $code,
            (int) config('auth.otp.expire'),
        ));
    }

    /**
     * Verify the code and mark the user's email as verified. Idempotent.
     *
     * @throws ValidationException
     */
    public function verify(User $user, string $code): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $this->otps->verify($user->email, OtpPurpose::EmailVerification, $code);

        $user->markEmailAsVerified();
    }
}
