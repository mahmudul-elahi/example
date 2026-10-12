<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::post('password/forgot', [PasswordResetController::class, 'sendOtp'])->middleware('throttle:password-reset');
    Route::post('password/verify', [PasswordResetController::class, 'verifyOtp'])->middleware('throttle:password-reset');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:email-verification');
        Route::post('email/resend', [EmailVerificationController::class, 'sendOtp'])->middleware('throttle:email-verification');
        Route::get('me', [AuthController::class, 'me']);
    });
});
