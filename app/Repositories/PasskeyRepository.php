<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Passkeys\Passkey;

class PasskeyRepository implements PasskeyRepositoryInterface
{
    /**
     * @return Collection<int, Passkey>
     */
    public function getForUser(User $user): Collection
    {
        return $user->passkeys()
            ->latest('created_at')
            ->get();
    }

    public function findForUser(User $user, int $id): ?Passkey
    {
        return $user->passkeys()
            ->where('id', $id)
            ->first();
    }

    public function findByCredentialId(string $credentialId): ?Passkey
    {
        return Passkey::query()
            ->where('credential_id', $credentialId)
            ->first();
    }

    public function rename(Passkey $passkey, string $newName): Passkey
    {
        $passkey->update(['name' => $newName]);

        return $passkey;
    }

    public function delete(Passkey $passkey): void
    {
        $passkey->delete();
    }
}
