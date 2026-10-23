<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Passkeys\Passkey;

#[Bind(PasskeyService::class)]
#[Scoped]
interface PasskeyServiceInterface
{
    /**
     * Generate WebAuthn creation options for registering a new passkey.
     *
     * @return array<string, mixed>
     */
    public function generateRegisterOptions(User $user): array;

    /**
     * Verify attestation response and persist the passkey.
     *
     * @param  array{name: string, credential: array<string, mixed>}  $data
     */
    public function verifyAndRegister(User $user, array $data): Passkey;

    /**
     * Generate assertion challenge options for passwordless login.
     *
     * @return array{challenge_token: string, options: array<string, mixed>}
     */
    public function generateLoginOptions(?string $email = null): array;

    /**
     * Verify assertion signature and return user with a Sanctum Bearer token.
     *
     * @param  array{challenge_token: string, credential: array<string, mixed>}  $data
     * @return array{user: User, token: string}
     */
    public function verifyAndLogin(array $data): array;

    /**
     * @return Collection<int, Passkey>
     */
    public function listUserPasskeys(User $user): Collection;

    public function renamePasskey(User $user, int $id, string $newName): Passkey;

    public function deletePasskey(User $user, int $id): void;
}
