<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\PasskeyRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class PasskeyService implements PasskeyServiceInterface
{
    public function __construct(
        protected PasskeyRepositoryInterface $passkeys,
        protected UserRepositoryInterface $users,
        protected GenerateRegistrationOptions $generateRegistrationOptions,
        protected StorePasskey $storePasskey,
        protected GenerateVerificationOptions $generateVerificationOptions,
        protected VerifyPasskey $verifyPasskey,
    ) {}

    /**
     * Generate WebAuthn creation options for registering a new passkey.
     *
     * @return array<string, mixed>
     */
    public function generateRegisterOptions(User $user): array
    {
        $options = ($this->generateRegistrationOptions)($user);

        Cache::put(
            'passkey:reg:'.$user->id,
            WebAuthn::toJson($options),
            now()->addMinutes(2),
        );

        return WebAuthn::toBrowserArray($options);
    }

    /**
     * Verify attestation response and persist the passkey.
     *
     * @param  array{name: string, credential: array<string, mixed>}  $data
     */
    public function verifyAndRegister(User $user, array $data): Passkey
    {
        $cachedJson = Cache::pull('passkey:reg:'.$user->id);

        if (! is_string($cachedJson)) {
            throw ValidationException::withMessages([
                'credential' => __('Passkey registration challenge expired or invalid.'),
            ]);
        }

        try {
            $options = WebAuthn::fromJson($cachedJson, PublicKeyCredentialCreationOptions::class);
            $credential = WebAuthn::fromJson(
                json_encode($data['credential'], JSON_THROW_ON_ERROR),
                PublicKeyCredential::class,
            );

            return ($this->storePasskey)($user, $data['name'], $credential, $options);
        } catch (InvalidPasskeyException $e) {
            throw ValidationException::withMessages([
                'credential' => [$e->getMessage()],
            ]);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'credential' => ['Unable to register passkey: '.$e->getMessage()],
            ]);
        }
    }

    /**
     * Generate assertion challenge options for passwordless login.
     *
     * @return array{challenge_token: string, options: array<string, mixed>}
     */
    public function generateLoginOptions(?string $email = null): array
    {
        $user = null;

        if (is_string($email) && $email !== '') {
            $user = $this->users->findByEmail($email);
        }

        $options = ($this->generateVerificationOptions)($user);
        $challengeToken = (string) Str::uuid();

        Cache::put(
            'passkey:login:'.$challengeToken,
            WebAuthn::toJson($options),
            now()->addMinutes(2),
        );

        return [
            'challenge_token' => $challengeToken,
            'options' => WebAuthn::toBrowserArray($options),
        ];
    }

    /**
     * Verify assertion signature and return user with a Sanctum Bearer token.
     *
     * @param  array{challenge_token: string, credential: array<string, mixed>}  $data
     * @return array{user: User, token: string}
     */
    public function verifyAndLogin(array $data): array
    {
        $cachedJson = Cache::pull('passkey:login:'.$data['challenge_token']);

        if (! is_string($cachedJson)) {
            throw ValidationException::withMessages([
                'credential' => __('Passkey login challenge expired or invalid.'),
            ]);
        }

        try {
            $options = WebAuthn::fromJson($cachedJson, PublicKeyCredentialRequestOptions::class);
            $credential = WebAuthn::fromJson(
                json_encode($data['credential'], JSON_THROW_ON_ERROR),
                PublicKeyCredential::class,
            );

            $passkey = ($this->verifyPasskey)($credential, $options);
        } catch (InvalidPasskeyException $e) {
            throw ValidationException::withMessages([
                'credential' => [$e->getMessage()],
            ]);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'credential' => ['Unable to verify passkey: '.$e->getMessage()],
            ]);
        }

        $user = $passkey->user;

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'credential' => __('User associated with passkey not found.'),
            ]);
        }

        return [
            'user' => $this->users->loadRoles($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ];
    }

    /**
     * @return Collection<int, Passkey>
     */
    public function listUserPasskeys(User $user): Collection
    {
        return $this->passkeys->getForUser($user);
    }

    public function renamePasskey(User $user, int $id, string $newName): Passkey
    {
        $passkey = $this->passkeys->findForUser($user, $id);

        if (! $passkey instanceof Passkey) {
            throw (new ModelNotFoundException)->setModel(Passkey::class, [$id]);
        }

        return $this->passkeys->rename($passkey, $newName);
    }

    public function deletePasskey(User $user, int $id): void
    {
        $passkey = $this->passkeys->findForUser($user, $id);

        if (! $passkey instanceof Passkey) {
            throw (new ModelNotFoundException)->setModel(Passkey::class, [$id]);
        }

        $this->passkeys->delete($passkey);
    }
}
