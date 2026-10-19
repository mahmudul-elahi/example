<?php

declare(strict_types=1);

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('scopes a code to the user it was issued to', function (): void {
    $service = resolve(OtpServiceInterface::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $code = $service->generate($user, OtpPurpose::EmailVerification);

    expect($service->validate($user, OtpPurpose::EmailVerification, $code))
        ->toBeInstanceOf(OtpCode::class);

    expect(fn () => $service->validate($otherUser, OtpPurpose::EmailVerification, $code))
        ->toThrow(ValidationException::class);
});

it("deletes a user's codes when the user is deleted", function (): void {
    $user = User::factory()->create();
    $otp = OtpCode::factory()->for($user)->create();

    $user->delete();

    $this->assertDatabaseMissing('otp_codes', ['id' => $otp->id]);
});
