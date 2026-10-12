<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The reasons an OTP code may be issued for. Each purpose keeps its own
 * current code per email, so a reset code can never verify an email.
 */
enum OtpPurpose: string
{
    case EmailVerification = 'email_verification';
    case PasswordReset = 'password_reset';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
