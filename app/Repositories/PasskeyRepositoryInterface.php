<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Passkeys\Passkey;

#[Bind(PasskeyRepository::class)]
#[Scoped]
interface PasskeyRepositoryInterface
{
    /**
     * @return Collection<int, Passkey>
     */
    public function getForUser(User $user): Collection;

    public function findForUser(User $user, int $id): ?Passkey;

    public function findByCredentialId(string $credentialId): ?Passkey;

    public function rename(Passkey $passkey, string $newName): Passkey;

    public function delete(Passkey $passkey): void;
}
