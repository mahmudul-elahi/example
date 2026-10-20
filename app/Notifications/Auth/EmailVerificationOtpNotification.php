<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Notifications\Base\OtpNotification;

class EmailVerificationOtpNotification extends OtpNotification
{
    protected function subject(): string
    {
        return 'Verify your email address';
    }

    protected function view(): string
    {
        return 'emails.auth.verify-email-otp';
    }
}
