<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purpose' => OtpPurpose::EmailVerification,
            'code' => Hash::make('12345'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
            'consumed_at' => null,
        ];
    }

    public function forPurpose(OtpPurpose $purpose): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => $purpose,
        ]);
    }

    /**
     * Store a specific plain-text code, hashed as the application does.
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => Hash::make($code),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'consumed_at' => now(),
        ]);
    }
}
