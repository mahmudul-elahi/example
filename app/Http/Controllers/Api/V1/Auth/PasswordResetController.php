<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\VerifyResetOtpRequest;
use App\Services\PasswordResetServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Auth', weight: 1)]
class PasswordResetController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasswordResetServiceInterface $passwordReset,
    ) {}

    public function sendOtp(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordReset->sendOtp($request->validated('email'));

        return $this->success(null, 'If an account exists for that email, a password reset code has been sent.');
    }

    public function verifyOtp(VerifyResetOtpRequest $request): JsonResponse
    {
        $this->passwordReset->verifyOtp(
            $request->validated('email'),
            $request->validated('otp'),
        );

        return $this->success(null, 'The code is valid. You can now reset your password.');
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordReset->reset(
            $request->validated('email'),
            $request->validated('otp'),
            $request->validated('password'),
        );

        return $this->success(null, 'Password reset successfully.');
    }
}
