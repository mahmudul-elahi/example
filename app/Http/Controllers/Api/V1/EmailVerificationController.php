<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Http\Resources\V1\UserResource;
use App\Services\EmailVerificationServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected EmailVerificationServiceInterface $verification,
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $this->verification->sendOtp($request->user());

        return $this->success(null, 'If your email is not yet verified, a verification code has been sent.');
    }

    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $this->verification->verify($request->user(), $request->validated('otp'));

        return $this->success(new UserResource($request->user()->fresh()), 'Email verified successfully.');
    }
}
