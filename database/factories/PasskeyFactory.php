<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Passkeys\Passkey;

/**
 * @extends Factory<Passkey>
 */
class PasskeyFactory extends Factory
{
    protected $model = Passkey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'MacBook Pro Touch ID',
            'credential_id' => Str::random(32),
            'credential' => [
                'publicKeyCredentialId' => Str::random(32),
                'type' => 'public-key',
                'transports' => ['internal'],
                'attestationType' => 'none',
                'trustPath' => ['type' => 'empty'],
                'aaguid' => '00000000-0000-0000-0000-000000000000',
                'credentialPublicKey' => 'test-public-key',
                'userHandle' => Str::random(32),
                'counter' => 0,
            ],
            'last_used_at' => null,
        ];
    }
}
