<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Notifications\Base\OtpNotification;

class PasswordResetOtpNotification extends OtpNotification
{
    protected function subject(): string
    {
        return 'Your password reset code';
    }

    protected function view(): string
    {
        return 'emails.auth.reset-password-otp';
    }
}
