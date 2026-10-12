<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected OtpServiceInterface $otps,
    ) {}

    /**
     * Send a reset code to the address if it belongs to a user.
     *
     * Deliberately silent for unknown addresses so the endpoint cannot be used
     * to enumerate registered emails.
     */
    public function sendOtp(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if (! $user instanceof User) {
            return;
        }

        $code = $this->otps->generate($email, OtpPurpose::PasswordReset);

        $user->notify(new PasswordResetOtpNotification(
            $code,
            (int) config('auth.otp.expire'),
        ));
    }

    /**
     * Confirm the code is valid without consuming it, letting the client advance
     * to the reset step. The reset step performs its own validation anyway.
     *
     * @throws ValidationException
     */
    public function verifyOtp(string $email, string $code): void
    {
        $this->otps->validate($email, OtpPurpose::PasswordReset, $code);
    }

    /**
     * @throws ValidationException when the code is invalid or the user does not exist.
     */
    public function reset(string $email, string $code, string $password): void
    {
        $this->otps->verify($email, OtpPurpose::PasswordReset, $code);

        $user = $this->users->findByEmail($email);

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'otp' => 'The provided code is invalid or has expired.',
            ]);
        }

        DB::transaction(function () use ($user, $password): void {
            $this->users->updatePassword($user, $password);
            $user->tokens()->delete();
        });
    }
}
