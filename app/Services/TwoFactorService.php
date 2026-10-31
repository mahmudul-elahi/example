<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService implements TwoFactorServiceInterface
{
    public function __construct(
        protected Google2FA $google2fa,
    ) {}

    /**
     * @return array{secret: string, qr_code_url: string}
     */
    public function enable(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $company = config('app.name', 'Laravel');
        $qrCodeUrl = $this->google2fa->getQRCodeUrl($company, $user->email, $secret);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    /**
     * @return array{recovery_codes: array<int, string>}
     */
    public function confirm(User $user, string $code): array
    {
        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => ['Two factor authentication has not been initiated.'],
            ]);
        }

        if (! $this->verifyCode($user, $code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodeList();

        $user->forceFill([
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * @return array{recovery_codes: array<int, string>}
     */
    public function generateNewRecoveryCodes(User $user): array
    {
        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'user' => ['Two factor authentication is not enabled.'],
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodeList();

        $user->forceFill([
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return [
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function verifyCode(User $user, string $code): bool
    {
        if ($user->two_factor_secret === null) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($user->two_factor_secret, $code, 1);
    }

    public function verifyRecoveryCode(User $user, string $recoveryCode): bool
    {
        $codes = $user->two_factor_recovery_codes;
        if (! is_array($codes) || $codes === []) {
            return false;
        }

        $trimmed = trim($recoveryCode);
        $index = array_search($trimmed, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill([
            'two_factor_recovery_codes' => array_values($codes),
        ])->save();

        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function generateRecoveryCodeList(): array
    {
        return Collection::times(8, fn () => Str::random(10).'-'.Str::random(10))->all();
    }
}
